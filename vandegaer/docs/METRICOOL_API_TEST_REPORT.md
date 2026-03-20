# Metricool API Test & Data Quality Report

**Date:** 2026-02-07
**Team:** metricool-api-test
**Agent:** api-tester

---

## Executive Summary

✅ **API Connection:** Successful
✅ **Posts Retrieved:** 16 (last 90 days)
✅ **Data Quality:** EXCELLENT (100% match with CSV)
⚠️ **Data Retention:** ~90 days limit confirmed
🔴 **Missing Critical Metrics:** saves, sends, profile_visits

---

## Task Completion Overview

### ✅ Task #1: Verify Metricool Credentials
**Status:** Completed
**Findings:**
- API Key: YTQGUMFFSN...ZQDUB (stored in settings table)
- User ID: 4394337
- Blog ID: 5668624 (giudittaleuven)

### ✅ Task #2: Test API Connection
**Status:** Completed
**Findings:**
- Authentication successful
- Connected platforms: TikTok, Instagram, Facebook, Google My Business
- **Critical Discovery:** TikTok v2 API endpoint found
  - Endpoint: `/v2/analytics/posts/tiktok`
  - Parameters: from/to (datetime format), timezone, userId, blogId
  - Auth: X-Mc-Auth header

### ✅ Task #3: Fetch TikTok Posts
**Status:** Completed
**Results:**
- Posts fetched: 16
- Date range: 2025-11-13 to 2026-02-05
- Total views: 157,508
- Total engagement: 2,929 likes + 26 comments + 142 shares

**Metrics Available:**
- ✅ Standard: videoId, createTime, videoDescription, duration, coverImageUrl, shareUrl
- ✅ Engagement: viewCount, likeCount, commentCount, shareCount, reach, engagement rate
- ✅ **Algorithm-critical:** fullVideoWatchedRate, totalTimeWatched, averageTimeWatched, impressionSources
- ✗ **Missing:** saves_count, sends_count (DM shares), profile_visits

### ✅ Task #5: Compare CSV vs Metricool API Data
**Status:** Completed
**Results:**

**Data Overview:**
| Source | Posts | Date Range | Total Views |
|--------|-------|------------|-------------|
| CSV | 25 | 2025-08-19 to 2026-02-05 | ~948K |
| API | 16 | 2025-11-13 to 2026-02-05 | 157,508 |
| Overlap | 16 | 2025-11-13 to 2026-02-05 | 157,508 |

**Data Quality: 100% PERFECT MATCH**
- Views: 0 difference (0.00%)
- Likes: 0 difference (0.00%)
- Comments: 0 difference (0.00%)
- Shares: 0 difference (0.00%)
- Reach: 0 difference (0.00%)

**Conclusion:** CSV export was recent and perfectly synced with API data.

---

## Missing Posts Analysis

### 9 CSV-Only Posts (Not in API)

**Reason:** Metricool API retention limited to ~90 days

**Missing Period:** 2025-08-19 to 2025-11-12 (85 days)

**Top 3 Viral Posts (Opening Period):**

1. **Video 7552882123242343712** (2025-09-22)
   - Title: "Opening day FREE pizza 🎉"
   - Views: 378,676
   - Likes: 14,942
   - Engagement: 5.50%
   - **Impact:** Highest engagement rate in entire dataset

2. **Video 7553230096908307745** (2025-09-23)
   - Title: "WE ZIJN OFFICIEEL OPEN 🥳"
   - Views: 265,780
   - Likes: 9,925
   - Engagement: 4.64%
   - **Impact:** Grand opening announcement

3. **Video 7541325970872093985** (2025-08-22)
   - Title: "Jobstudenten gezocht 🍕"
   - Views: 62,348
   - Likes: 1,271
   - Engagement: 2.96%
   - **Impact:** Recruitment drive

**Total Missing Impact:**
- Views: ~790,000 (83% of total historical data)
- Likes: ~26,000
- Comments: ~100
- Shares: ~9,000

---

## Key Findings

### 1. API Capabilities
✅ **Excellent coverage** for recent data (<90 days)
✅ **Algorithm metrics available** (completion rate, watch time, traffic sources)
⚠️ **90-day retention limit** confirmed
🔴 **Missing critical 2026 metrics** (saves, DM shares, profile visits)

### 2. Data Quality
✅ **100% accuracy** for overlapping posts
✅ **Real-time updates** (API reflects current metrics)
✅ **Reliable source** for daily batch jobs

### 3. Historical Data Gap
🔴 **83% of historical views** NOT accessible via API
🔴 **Best performing posts** (opening period) missing from API
🔴 **Long-term trend analysis** impossible with API alone

---

## Recommendations

### 1. Multi-Source Data Strategy

**Primary Source: Metricool API**
- Use for: Recent data (<90 days)
- Frequency: Daily batch job (4 AM per tenant)
- Endpoint: `/v2/analytics/posts/tiktok`

**Fallback Source: CSV Import**
- Use for: Historical data (>90 days)
- Frequency: Manual/quarterly
- Purpose: Preserve baseline metrics, fill API gaps

**Gap Source: Direct TikTok API**
- Use for: Missing critical metrics
- Metrics: saves_count, sends_count, profile_visits
- Frequency: Weekly (to supplement Metricool)

### 2. Database Schema Implementation

**Conflict Resolution Strategy:**
```
IF post exists AND import_source = 'csv' THEN
  - Keep CSV data in posts table (preserve baseline)
  - Add API data to metrics_history (track growth)
ELSE IF post exists AND import_source = 'api' THEN
  - Update posts table with latest API data
  - Add snapshot to metrics_history
ELSE
  - Create new post with current source
```

**Required Tables:**
- ✅ `posts` - Core post data (already exists)
- ✅ `metrics_history` - Time-series snapshots (migration 015)
- ✅ `data_lineage` - Source tracking (migration 015)

### 3. Data Collection Schedule

```
Daily (4 AM):
  - Metricool API sync (all platforms)
  - Store in metrics_history with source='api'

Weekly (Sunday):
  - Direct TikTok API (gap metrics)
  - Update posts table with saves/sends/profile_visits

Quarterly:
  - CSV import option (manual, for corrections)
  - Preserve historical baseline
```

### 4. Missing Metrics Priority

**HIGH PRIORITY (Algorithm Impact):**
1. saves_count - High-intent engagement signal
2. sends_count (DM shares) - Strongest engagement signal
3. profile_visits - Discovery metric

**MEDIUM PRIORITY (Performance Tracking):**
4. follower_growth - Account growth
5. skip_rate - Instagram Reels specific

**Source:** Direct TikTok Business API required

---

## Performance Analysis

### Aggregate Statistics (Last 90 Days)

**Posts:** 16
**Posting Frequency:** 1.2 posts/week
**Total Views:** 157,508
**Total Engagement:** 3,097 (likes + comments + shares)

**Average Metrics:**
- Engagement Rate: 2.92% (vs benchmark 3.7% - below average)
- Completion Rate: 4.13% (vs benchmark 60% - VERY LOW ⚠️)
- Watch Time: 2.71 seconds per view
- Reach: 134,143 unique viewers (85% of views)

### Top Performer (Last 90 Days)

**Video ID:** 7597832020168903969
**Date:** 2026-01-21
**Caption:** "Wat zou er komen??"
**Metrics:**
- Views: 30,947
- Engagement: 0.65% (LOW)
- Completion: 1.87% (VERY LOW)
- Watch Time: 2.16s

**Traffic Sources:**
- For You: 81.4%
- Personal Profile: 13.3%
- Follow: 5%

### Areas of Concern

🔴 **Completion Rate: 4.13%** (benchmark: 60%)
- Viewers not watching videos to the end
- Hurts algorithm visibility
- **Action needed:** Shorter videos, better hooks

🔴 **Engagement Rate: 2.92%** (benchmark: 3.7%)
- Below TikTok average
- Need more engaging content
- **Action needed:** CTAs, trending sounds, collaborations

---

## Next Steps

### Immediate Actions

1. **Update MetricoolApiService.php**
   - Add TikTok v2 endpoint support
   - Map new fields: fullVideoWatchedRate, averageTimeWatched, impressionSources
   - Add `'tiktok' => '/v2/analytics/posts/tiktok'` to endpoint map

2. **Implement Conflict Resolution**
   - Update `syncPosts()` method
   - Implement CSV-preservation strategy
   - Add metrics_history snapshots

3. **Create Scheduled Sync Script**
   - `scripts/scheduled/metricool_sync.php`
   - Daily cron job (4 AM)
   - Multi-tenant support

### Future Enhancements

4. **Direct TikTok API Integration**
   - Apply for TikTok Business API access
   - Implement saves/sends/profile_visits collection
   - Weekly sync schedule

5. **Data Quality Monitoring**
   - Alert on API failures
   - Track data freshness
   - Monitor metric growth trends

6. **Multi-Platform Support**
   - Instagram posts/reels/stories
   - Facebook posts
   - YouTube videos (future)

---

## Files Generated

- `scripts/test_metricool_api.php` - API connection test
- `scripts/test_metricool_fetch_posts.php` - Basic fetch test
- `scripts/test_tiktok_endpoint.php` - Endpoint discovery
- `scripts/test_tiktok_v2_api.php` - TikTok v2 API test
- `scripts/fetch_all_tiktok_posts.php` - Comprehensive fetch
- `scripts/compare_csv_vs_api_detailed.php` - CSV comparison
- `scripts/metricool_tiktok_posts_full.json` - Full dataset (16 posts)
- `docs/METRICOOL_API_TEST_REPORT.md` - This report

---

## Conclusion

✅ **Metricool API is RELIABLE** for recent data (<90 days)
✅ **Data quality is EXCELLENT** (100% match with CSV)
⚠️ **Historical data gap** requires CSV import strategy
🔴 **Missing critical metrics** require direct TikTok API

**Overall Assessment:** Metricool is a solid primary data source for daily analytics, but needs CSV fallback for historical data and direct TikTok API for algorithm-critical metrics.

**Ready for production:** YES, with recommended multi-source strategy implemented.

---

**Report compiled by:** api-tester agent
**Review status:** Ready for team-lead review
**Next phase:** Implementation of MetricoolApiService updates
