# Metricool Conflict Resolution Strategy

**How SocialBit handles CSV vs API data conflicts**

---

## 🎯 The Problem

When importing data from multiple sources, conflicts can occur:

**Scenario:**
1. **Week 1:** User uploads TikTok CSV export
   - Post X: 1000 views (source: CSV)
2. **Week 2:** Metricool API sync runs automatically
   - Same Post X: 1050 views (source: API)

**Question:** Which value should the system use?

---

## ✅ Implemented Solution: Option 3 (Metrics History)

### Strategy

**Keep CSV baseline in `posts` table, track API updates in `metrics_history` table.**

This approach:
- ✅ Preserves historical CSV data (important for "before Metricool" baseline)
- ✅ Tracks metric growth over time (1000 → 1050 views)
- ✅ No data loss from any source
- ✅ Enables trend analysis and reporting

### How It Works

**For New Posts (Not in Database):**
```
1. Create post in posts table (source='api')
2. Create initial snapshot in metrics_history
```

**For Existing CSV Posts:**
```
1. KEEP CSV data in posts table (unchanged)
2. CREATE snapshot in metrics_history with API data
3. Result: Baseline preserved, current metrics tracked
```

**For Existing API Posts:**
```
1. UPDATE posts table with latest API data
2. CREATE snapshot in metrics_history for trend tracking
3. Result: Always fresh data, growth history maintained
```

---

## 📊 Database Tables

### `posts` Table
Contains the **baseline** or **current** data:
- CSV imports: Stores original CSV values (frozen in time)
- API imports: Stores latest API values (updated daily)

**Key field:** `import_source` (csv, api, manual)

### `metrics_history` Table
Contains **time-series snapshots**:
- Daily API sync creates new snapshot
- Tracks metric changes over time
- Enables growth calculations

**Key fields:**
- `post_id`: Link to posts table
- `snapshot_date`: Date of metric snapshot
- `source`: Data source (csv, api, manual)
- `views`, `likes`, `comments`, etc.: Metric values at snapshot time

---

## 🔄 Sync Behavior Examples

### Example 1: CSV Post Gets API Update

**Initial State (CSV Import):**
```
posts table:
  id=1, platform_post_id='ABC123', views=1000, import_source='csv'
```

**After Metricool Sync:**
```
posts table (UNCHANGED):
  id=1, platform_post_id='ABC123', views=1000, import_source='csv'

metrics_history table (NEW):
  post_id=1, snapshot_date='2026-02-07', views=1050, source='api'
```

**Dashboard Display:**
- Baseline: 1000 views (CSV import)
- Current: 1050 views (latest snapshot)
- Growth: +50 views (+5%)

---

### Example 2: API Post Gets Daily Update

**Initial State (Metricool Sync Day 1):**
```
posts table:
  id=2, platform_post_id='XYZ789', views=500, import_source='api'

metrics_history table:
  post_id=2, snapshot_date='2026-02-06', views=500, source='api'
```

**After Day 2 Sync:**
```
posts table (UPDATED):
  id=2, platform_post_id='XYZ789', views=550, import_source='api'

metrics_history table (NEW):
  post_id=2, snapshot_date='2026-02-06', views=500, source='api'
  post_id=2, snapshot_date='2026-02-07', views=550, source='api'
```

**Dashboard Display:**
- Current: 550 views
- Growth (24h): +50 views (+10%)

---

## 📈 Dashboard Implementation

### Displaying Metrics

**Option A: Show Latest (Recommended for MVP)**
```sql
SELECT
    p.id,
    p.caption,
    COALESCE(mh.views, p.views) as current_views,
    COALESCE(mh.likes, p.likes) as current_likes
FROM posts p
LEFT JOIN (
    SELECT post_id, views, likes
    FROM metrics_history
    WHERE snapshot_date = (SELECT MAX(snapshot_date) FROM metrics_history WHERE post_id = p.id)
) mh ON p.id = mh.post_id;
```

**Option B: Show Baseline + Current**
```sql
SELECT
    p.id,
    p.caption,
    p.views as baseline_views,
    p.import_source as baseline_source,
    mh.views as current_views,
    mh.snapshot_date as last_updated
FROM posts p
LEFT JOIN metrics_history mh ON p.id = mh.post_id
    AND mh.snapshot_date = (SELECT MAX(snapshot_date) FROM metrics_history WHERE post_id = p.id);
```

**Display:**
```
Post #1: "Check out our new product!"
Views: 1,050 (baseline: 1,000 from CSV)
Growth: +50 (+5%) since import
```

---

## 🛠️ Code Implementation

### In MetricoolApiService.php

```php
public function syncPosts(...) {
    // ... fetch posts from API

    foreach ($metricoolPosts as $metricoolPost) {
        $postData = $this->mapMetricoolPost(...);
        $existingPost = $this->postRepository->findByPlatformPostId(...);

        if ($existingPost) {
            $existingSource = $existingPost['import_source'];

            if ($existingSource === 'csv') {
                // CONFLICT: Keep CSV, create snapshot
                $this->metricsHistoryRepository->upsert([
                    'post_id' => $existingPost['id'],
                    'snapshot_date' => date('Y-m-d'),
                    'views' => $postData['views'],
                    // ... other metrics
                    'source' => 'api',
                ]);
                // posts table NOT updated
            } else {
                // No conflict: Update with latest
                $this->postRepository->update($existingPost['id'], $postData);

                // Also create snapshot for trend tracking
                $this->metricsHistoryRepository->upsert([...]);
            }
        } else {
            // New post: create in both tables
            $postId = $this->postRepository->create($postData);
            $this->metricsHistoryRepository->create([...]);
        }
    }
}
```

---

## 🔍 Querying Metric Growth

### Get Views Growth Over Time

```sql
SELECT
    snapshot_date,
    views,
    views - LAG(views) OVER (ORDER BY snapshot_date) as daily_growth
FROM metrics_history
WHERE post_id = 1
ORDER BY snapshot_date;
```

**Output:**
```
2026-02-06 | 1000 | NULL
2026-02-07 | 1050 | +50
2026-02-08 | 1120 | +70
```

### Get Engagement Rate Trend

```sql
SELECT
    snapshot_date,
    ROUND((likes + comments + shares) / views * 100, 2) as engagement_rate
FROM metrics_history
WHERE post_id = 1
ORDER BY snapshot_date;
```

---

## ⚙️ Alternative Strategies (Not Implemented)

### Option 1: Always Update (API Priority)
**Rule:** API always overwrites CSV

**Pros:** Simple, always fresh data
**Cons:** Loses CSV baseline, can't track growth from Day 1

**When to use:** If you don't care about historical data, only current metrics

---

### Option 2: Threshold-Based Update
**Rule:** Only update if difference > 5%

**Pros:** Avoids minor fluctuations
**Cons:** Complex logic, might ignore real growth

**When to use:** If API data is unstable or has counting errors

---

### Option 4: Data Lineage (Future Enhancement)
**Rule:** Full audit trail with priority system

**Requires:** Migration 015 (data_lineage table)

**Benefits:**
- Track every data change
- Know why metrics changed
- Rollback capability

**Priority System:**
1. API (real-time, most accurate)
2. Metricool (aggregated)
3. CSV (historical)
4. Manual (user input)

**When to implement:** After MVP, when data quality becomes critical

---

## 🧪 Testing Conflict Resolution

### Test Scenario 1: CSV → API Update

```bash
# 1. Import CSV with Post X (1000 views)
php scripts/migrate_csv_to_db.php

# 2. Verify CSV data
mysql> SELECT id, platform_post_id, views, import_source FROM posts WHERE platform_post_id='ABC123';
# Expected: views=1000, import_source='csv'

# 3. Run Metricool sync (simulating API returning 1050 views)
php scripts/scheduled/metricool_sync.php

# 4. Verify posts table unchanged
mysql> SELECT id, platform_post_id, views, import_source FROM posts WHERE platform_post_id='ABC123';
# Expected: STILL views=1000, import_source='csv' (unchanged!)

# 5. Verify snapshot created
mysql> SELECT * FROM metrics_history WHERE post_id=1 ORDER BY snapshot_date DESC LIMIT 1;
# Expected: views=1050, source='api', snapshot_date=today
```

### Test Scenario 2: API → API Update

```bash
# 1. Initial API sync (Day 1)
php scripts/scheduled/metricool_sync.php

# 2. Check posts table
mysql> SELECT views, import_source FROM posts WHERE id=2;
# Expected: views=500, import_source='api'

# 3. Wait 24 hours (or simulate by modifying API response)
# Run sync again (Day 2)
php scripts/scheduled/metricool_sync.php

# 4. Check posts table updated
mysql> SELECT views, import_source FROM posts WHERE id=2;
# Expected: views=550, import_source='api' (UPDATED)

# 5. Check metrics history has 2 snapshots
mysql> SELECT snapshot_date, views FROM metrics_history WHERE post_id=2 ORDER BY snapshot_date;
# Expected: 2 rows (Day 1: 500, Day 2: 550)
```

---

## 📚 FAQ

**Q: Why not just update posts table always?**
A: We want to preserve the CSV baseline so users can see "how the post performed when first imported" vs "how it's performing now."

**Q: What if I want to see ONLY the latest data?**
A: Use the SQL query in "Dashboard Implementation > Option A" - it shows latest snapshot data, falling back to posts table if no snapshot exists.

**Q: Will this double my storage?**
A: Slightly, yes. But metrics_history only stores numeric values (not text/captions), so overhead is minimal. For 10K posts with daily snapshots = ~300KB/day.

**Q: Can I change strategy later?**
A: Yes! You can implement Option 1 (always update) by modifying the `syncPosts()` method logic. Existing snapshots will remain for historical analysis.

**Q: What about manual edits by users?**
A: If user manually edits metrics, set `import_source='manual'`. Then API won't overwrite (same conflict logic as CSV).

---

**Last Updated:** 2026-02-07
**Implementation Status:** ✅ Complete
**Next Step:** Test with real CSV + Metricool sync
