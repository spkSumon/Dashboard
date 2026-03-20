# Priority Metrics Summary (2026)

**Quick reference for development priorities**

---

## 🚨 Critical Database Gaps (Fix Immediately)

### Missing Columns in `posts` table:
```sql
-- Add to migration 007
ALTER TABLE posts ADD COLUMN duration INT DEFAULT NULL COMMENT 'Video length in seconds';
ALTER TABLE posts ADD COLUMN average_watch_time INT DEFAULT NULL COMMENT 'Avg seconds watched';
ALTER TABLE posts ADD COLUMN completion_rate DECIMAL(5,2) DEFAULT NULL COMMENT '% watched to end';
ALTER TABLE posts ADD COLUMN sends_count INT DEFAULT 0 COMMENT 'DM shares (separate from shares)';
ALTER TABLE posts ADD COLUMN profile_visits INT DEFAULT 0 COMMENT 'Profile views from this post';
ALTER TABLE posts ADD COLUMN skip_rate DECIMAL(5,2) DEFAULT NULL COMMENT 'IG Reels: % skip in 3s';
```

### Missing Tables:
1. **follower_growth** - Daily follower tracking per platform
2. **industry_benchmarks** - 2026 benchmarks for context
3. **performance_snapshots** - Historical "vs last month" comparisons
4. **recommendations** - Automated actionable insights

---

## ⭐ Top 5 Metrics by Platform (Algorithm Priority)

### TikTok
1. **Completion Rate** (75%+ for FYP) - 40-50% algorithm weight
2. **Saves Count** (4%+ excellent)
3. **Sends/Shares** (DM shares strongest signal)
4. **Profile Visits** (discovery metric)
5. **Follower Growth** (daily tracking)

### Instagram
1. **Watch Time** (#1 factor - Adam Mosseri, Jan 2025)
2. **Skip Rate** (new 2026 - <10% good, replaces view rate)
3. **Sends via DM** (highest engagement weight)
4. **Saves Count** (high-intent signal)
5. **Engagement Rate** (0.48% avg, 6%+ excellent)

### Facebook
1. **Engagement Rate** (0.15% avg)
2. **Reach vs Impressions** (organic severely limited Jan 2026)
3. **Video Views** (Reels priority)
4. **Watch Time** (missing from schema)
5. **Shares** (passive engagement declining)

### YouTube
1. **Watch Time** (primary ranking)
2. **Average View Duration** (retention)
3. **Likes & Comments** (engagement)
4. **Shares** (distribution signal)
5. **Click-through Rate** (thumbnail/title)

---

## 📊 2026 Industry Benchmarks (Seed Data)

### Engagement Rates
| Platform | Average | Good | Excellent |
|----------|---------|------|-----------|
| TikTok | 3.70% | 4.5% | 5.3%+ |
| Instagram | 0.48% | 3-6% | 6%+ |
| Facebook | 0.15% | 0.25% | 0.5%+ |

### Completion Rates
| Platform | Average | Good | Algorithm Threshold |
|----------|---------|------|---------------------|
| TikTok | 60% | 70% | **75%** (FYP) |
| Instagram Reels | N/A | 65% | Watch time priority |
| YouTube | Varies | N/A | 50%+ typical |

### Content Type Performance (Instagram 2026)
- **Reels**: Highest reach
- **Carousels**: Most saves/shares
- **Collaborative Posts**: 2.7-3.4× engagement boost

---

## 🔌 Data Collection Strategy

### Primary: Metricool API (User has account)
- **Frequency**: Daily 4 AM per tenant
- **Metrics**: Views, likes, comments, shares, saves, reach, follower growth
- **Platforms**: TikTok, Instagram, Facebook, YouTube
- **Benefit**: Single API, standardized format, reduced quota usage

### Secondary: Direct Platform APIs
- **TikTok**: Completion rate (if unavailable via Metricool)
- **Instagram**: DM sends, skip rate (when available)
- **YouTube**: Retention curves, traffic sources
- **Frequency**: Weekly (low-priority metrics)

### Fallback: CSV Import
- **TikTok**: 60-day historical exports
- **Use case**: API access issues, migration, manual preference

---

## ⚡ Rate Limits (Critical)

| Platform | Limit | Safe Usage | Notes |
|----------|-------|------------|-------|
| TikTok API | 1K requests/day | 30/hour | 100 items/request max |
| Instagram Graph | 200 calls/hour/account | 150/hour | Rolling 1-hour window |
| Facebook Graph | Similar to IG | 150/hour | App-level enforcement |
| YouTube Analytics | 1.6M queries/min | Generous | 10K/day dev quota |
| Metricool | Not documented | Monitor | Advanced plan required |

---

## 🎯 Implementation Priority

### Week 1: Critical Schema Updates
- [ ] Migration 007: Add watch time, completion rate, sends columns
- [ ] Create `follower_growth` table
- [ ] Seed `industry_benchmarks` with 2026 data

### Week 2: Metricool Integration
- [ ] Obtain API token (verify Advanced plan)
- [ ] Build `MetricoolApiService.php`
- [ ] Map Metricool response → SocialBit schema
- [ ] Daily batch job (4 AM per tenant)

### Week 3: Direct API Gap Filling
- [ ] TikTok: Completion rate collection
- [ ] Instagram: DM sends tracking
- [ ] Error handling & rate limit management

### Week 4: Insights & Context
- [ ] Create `performance_snapshots` for historical comparison
- [ ] Build benchmark comparison logic ("2× above average")
- [ ] Plain language metric interpretation

---

## 💡 Key Insights for Business Users

### What Changed in 2026
1. **Completion rate threshold raised** from 60% → 75% (TikTok)
2. **Instagram deprecated** profile views, organic metrics (Jan 2025)
3. **DM shares > saves > likes** in algorithm weight
4. **Skip rate introduced** for Instagram Reels (first 3 seconds critical)
5. **Watch time confirmed** as #1 Instagram Reels factor

### What This Means for Users
- **Focus on retention**, not just views
- **First 3 seconds critical** (skip rate)
- **Shareability matters most** (DM sends)
- **Saves indicate value** (2.1% avg, 4%+ excellent)
- **Comments declining** (passive engagement shift)

### Actionable Recommendations (Examples)
- "Your completion rate is 82% (vs 60% average) - excellent hook!"
- "DM shares are 3× industry average - highly shareable content"
- "Post 3 more Reels this week to match top performers"
- "Tuesday 9 AM gets 2.4× more engagement than your current schedule"

---

## 📋 Data Source Matrix (Quick Ref)

| Metric | Current Schema | TikTok | Instagram | Facebook | Metricool |
|--------|----------------|--------|-----------|----------|-----------|
| Views | ✅ | CSV/API | Graph | Graph | ✅ |
| Completion Rate | ❌ **ADD** | CSV only? | ❌ | ❌ | ❓ |
| Watch Time | ❌ **ADD** | CSV only? | Graph ⚠️ | Graph ⚠️ | ❓ |
| Saves | ✅ | CSV/API | Graph | ❌ | ✅ |
| DM Sends | ❌ **ADD** | ❌ | Graph | ❌ | ❓ |
| Skip Rate | ❌ **ADD** | ❌ | New 2026 | ❌ | ❓ |
| Profile Visits | ❌ **ADD** | API | Deprecated | Graph ⚠️ | ✅ TikTok |
| Follower Growth | ❌ **TABLE** | API | Graph | Graph | ✅ |

---

**For full details, see:** `docs/2026-social-media-data-strategy.md`
