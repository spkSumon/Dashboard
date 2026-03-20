# 2026 Social Media Data Strategy & Metrics Research

**Research Date:** 2026-02-07
**Researcher:** Social Media Expert (Claude Agent)
**Project:** SocialBit Multi-Tenant Analytics Platform

---

## Executive Summary

This research provides comprehensive analysis of 2026 social media metrics, API capabilities, competitor strategies, and actionable recommendations for SocialBit's data collection architecture. Key finding: **Watch time and completion rate** are now the #1 algorithm ranking factors across platforms, requiring significant database schema updates.

---

## 1. Priority Metrics List (2026)

### Critical Algorithm-Impacting Metrics (MUST-HAVE)

#### TikTok Priority Metrics
1. **Watch Time & Completion Rate** ⭐ #1 Priority
   - Algorithm weight: ~40-50% of ranking
   - Benchmark: 75%+ completion for FYP distribution
   - Need: `duration`, `average_watch_time`, `completion_rate`

2. **Saves Count** (High-intent signal)
   - Benchmark: 2.1% avg, 4%+ excellent
   - Current schema: ✅ Has `saves` column

3. **Sends/Shares** (DM shares - strongest signal)
   - Current schema: ✅ Has `shares` column
   - Note: API distinguishes DM shares vs general shares

4. **Profile Visits** (Discovery metric)
   - **MISSING** - Need new column

5. **Follower Growth** (Daily tracking)
   - **MISSING** - Need new table/column

#### Instagram Priority Metrics
1. **Watch Time** (Reels algorithm #1 factor)
   - Confirmed by Adam Mosseri, Jan 2025
   - **MISSING** - Need `watch_time`, `duration`

2. **Skip Rate** (New 2026 metric replacing view rate)
   - Measures % who skip in first 3 seconds
   - **MISSING** - Not available via API yet

3. **Sends via DM** (Highest engagement weight)
   - More valuable than saves/likes
   - Current: `shares` column (may need split)

4. **Saves Count**
   - Current schema: ✅ Has `saves` column

5. **Profile Visits**
   - **DEPRECATED** as of Jan 8, 2025 (API v21+)
   - Historical data still available

#### Facebook Priority Metrics
1. **Engagement Rate** (0.15% avg)
   - Current schema: ✅ Has `engagement_rate`

2. **Reach vs Impressions**
   - Current schema: ✅ Has both columns

3. **Video Views & Watch Time**
   - Watch time: **MISSING**
   - Views: ✅ Has `views` column

#### Cross-Platform Metrics
- **Engagement Rate**: TikTok 3.7%, Instagram 0.48%, Facebook 0.15%
- **Posting Frequency**: 5 posts/week optimal (2026)
- **Content Type Performance**: Reels > Carousels > Static posts

### Nice-to-Have Metrics
- Demographics (age, gender, location) - existing `tiktok_demographics` table
- Traffic sources (FYP vs profile vs hashtag)
- Best posting times (algorithmic, not chronological)
- Crosspost performance (Instagram Reels 2026 feature)

---

## 2. Platform API Capabilities & Limitations

### TikTok API (2026)

**Access Requirements:**
- Business account required for full analytics
- API approval process notoriously difficult
- Minimum 100 followers for demographics

**Rate Limits:**
- 1,000 requests/day per app
- 100 items per request (max 100,000 records/day)
- Video posting: 2/minute, 20/day max
- HTTP 429 error when exceeded

**Available Metrics:**
- ✅ Views, likes, comments, shares, saves
- ✅ Profile views
- ✅ Daily follower growth (since Sept 29, 2025)
- ✅ Demographics (100+ follower accounts only)
- ⚠️ Completion rate/watch time - unclear API availability
- ❌ Historical data limited to 60 days via CSV

**Data Collection Method:**
- Primary: CSV export (most reliable, 60-day limit)
- Secondary: Content Posting API (publish + basic metrics)
- Tertiary: Research API (1K requests/day, academic use)

### Instagram Graph API (2026)

**Access Requirements:**
- Facebook Business account + Instagram Business/Creator account
- Page access token required
- Basic Display API deprecated (Jan 8, 2025)

**Rate Limits:**
- 200 API calls/hour per Instagram account
- Resets on rolling 1-hour window
- Messaging: 200 DMs/hour, 1 automated msg/user/24h
- Hashtag search: 30 unique hashtags/week

**Available Metrics (v21+):**
- ✅ Reach, impressions, like_count, comments_count
- ✅ Saves (via `/media/insights`)
- ✅ Shares (via insights)
- ✅ Video views (Reels only after v21)
- ⚠️ Watch time - available but not documented clearly
- ❌ **DEPRECATED (v21+)**: Profile views, website clicks, email contacts

**Recent Changes (2025-2026):**
- Jan 8, 2025: Multiple metrics deprecated in Graph API v21
- Skip rate introduced (replaces view rate)
- DM shares now prioritized in algorithm

**Data Collection Method:**
- Primary: Graph API `/media/insights` endpoint
- Rate limit friendly: Batch requests (multiple metrics/call)
- Real-time: WebSocket for immediate updates (advanced)

### Facebook Graph API (2026)

**Access Requirements:**
- Facebook Page admin access
- Meta Business Suite integration

**Rate Limits:**
- Not explicitly documented (similar to Instagram)
- Enforced at app level, not per-page

**Available Metrics (Graph API v23):**
- ✅ 120+ insights metrics available
- ✅ Reach, impressions, engagement
- ✅ Paid vs organic data (organic heavily limited)
- ❌ **REMOVED (Jan 2026)**: Organic impressions, post clicks, engaged users, demographics

**Critical Limitation:**
- Organic data severely limited as of Jan 2026
- Paid ads data unaffected
- Must query metrics explicitly (no "get all" endpoint)

**Data Collection Method:**
- Primary: Graph API `/page/insights` endpoint
- Must specify exact metric names
- Historical data: 93 days max (typical)

### YouTube Analytics API (2026)

**Access Requirements:**
- YouTube channel ownership
- Google Cloud project with API enabled

**Rate Limits:**
- 1.6 million queries/minute (extremely generous)
- 10,000 units/day for dev/testing
- Production: Request quota increase (typically granted)

**Available Metrics:**
- ✅ Views, watch time, likes, comments, shares
- ✅ Average view duration, retention
- ✅ Revenue (if monetized)
- ✅ Demographics, traffic sources
- ✅ Playlist interactions, card/end screen clicks

**Data Collection Method:**
- Primary: `reports.query` method
- Supports filtering, sorting, grouping
- Historical data: Extensive (years)

---

## 3. Competitor Data Collection Strategies

### Hootsuite (2026)
- **Multi-platform approach**: 30+ social network integrations
- **Data strategy**: Direct API connections to each platform
- **Analytics**: Comprehensive KPI tracking across all accounts
- **Strengths**: Monitoring, collaboration, scheduled publishing
- **Limitations**: Analytics less deep than Sprout Social

### Buffer (2026)
- **Multi-platform approach**: Facebook, Instagram, Twitter, LinkedIn, YouTube, Pinterest
- **Data strategy**: API-first with real-time syncing
- **Analytics**: Basic engagement tracking, optimal send times
- **Strengths**: User-friendly, affordable, excellent scheduling
- **Limitations**: Lighter analytics compared to enterprise tools

### Sprout Social (2026)
- **Multi-platform approach**: All major platforms + social listening
- **Data strategy**: API + proprietary data enrichment
- **Analytics**: Most comprehensive - trend analysis, conversation tracking
- **AI Features**: OpenAI GPT integration for:
  - Automated tagging and sentiment analysis
  - Insight generation from millions of data points
  - Optimal send time predictions
- **Strengths**: Enterprise-grade analytics, social listening
- **Limitations**: Expensive (premium pricing)

### Metricool (2026) - User Has Account ⭐
- **Multi-platform approach**: TikTok, Instagram, Facebook, LinkedIn, Twitter, YouTube
- **Data strategy**: API aggregation + Looker Studio connector
- **API Access**: Advanced plan required
- **Analytics Features**:
  - 14 social platforms via API
  - 250+ data sources via Looker Studio connector
  - Cross-platform dashboard
  - Ad spend tracking (Google Ads, Facebook Ads)

**Available via Metricool API:**
- TikTok: Profile views, post metrics, daily follower growth (since Sept 2025)
- Instagram: Reach per post, engagement, followers/following
- Facebook: Page insights (subject to Meta's limitations)
- Historical data: Available with limitations

**Integration Strategy for SocialBit:**
- ✅ Leverage user's existing Metricool account
- ✅ Use Metricool API as aggregation layer (reduces direct API calls)
- ✅ Fill gaps with direct API access (completion rate, watch time)
- ✅ Use Metricool for standardized metrics across platforms
- ⚠️ Validate Metricool data freshness (batch collection frequency)

---

## 4. Data Source Matrix

| Metric | TikTok | Instagram | Facebook | YouTube | Metricool API | Notes |
|--------|--------|-----------|----------|---------|---------------|-------|
| **Views** | CSV/API ✅ | Graph API ✅ | Graph API ✅ | Analytics API ✅ | ✅ | Universal |
| **Likes** | CSV/API ✅ | Graph API ✅ | Graph API ✅ | Analytics API ✅ | ✅ | Universal |
| **Comments** | CSV/API ✅ | Graph API ✅ | Graph API ✅ | Analytics API ✅ | ✅ | Universal |
| **Shares** | CSV/API ✅ | Graph API ✅ | Graph API ✅ | Analytics API ✅ | ✅ | Universal |
| **Saves** | CSV/API ✅ | Graph API ✅ | ❌ | ❌ | ✅ | IG/TikTok only |
| **Reach** | ❌ | Graph API ✅ | Graph API ⚠️ | ❌ | ✅ | IG best source |
| **Impressions** | ❌ | Graph API ✅ | Graph API ⚠️ | ❌ | ✅ | IG best source |
| **Watch Time** | CSV only? | Graph API ⚠️ | Graph API ⚠️ | Analytics API ✅ | ❓ | Critical gap |
| **Completion Rate** | CSV only? | ❌ | ❌ | Analytics API ✅ | ❓ | **CRITICAL GAP** |
| **Skip Rate** | ❌ | ❌ New 2026 | ❌ | ❌ | ❓ | Not in APIs yet |
| **Profile Visits** | API ✅ | Deprecated ❌ | Graph API ⚠️ | ❌ | ✅ TikTok only | IG removed Jan 2025 |
| **Follower Growth** | API ✅ | Graph API ✅ | Graph API ✅ | Analytics API ✅ | ✅ | Daily tracking |
| **Demographics** | API ✅ 100+ | Graph API ⚠️ | Deprecated ❌ | Analytics API ✅ | ✅ Limited | TikTok: business only |
| **DM Sends** | ❌ | Graph API ✅ | ❌ | ❌ | ❓ | IG prioritizes 2026 |

**Legend:**
- ✅ Available and reliable
- ⚠️ Limited/deprecated/organic only
- ❌ Not available
- ❓ Unknown/needs verification

---

## 5. Recommended Data Collection Strategy

### Hybrid Approach: Metricool + Direct API

**Tier 1: Metricool API (Primary Aggregator)**
- Frequency: Daily batch (4 AM local time per tenant)
- Metrics: Standard engagement (views, likes, comments, shares, saves)
- Platforms: All supported (TikTok, Instagram, Facebook, YouTube)
- Benefits:
  - Single API for multiple platforms
  - Standardized data format
  - Reduced direct API quota consumption
  - User already has account

**Tier 2: Direct Platform APIs (Gap Filling)**
- Frequency: Weekly batch (low-priority metrics)
- Use cases:
  - TikTok: Completion rate (if unavailable via Metricool)
  - Instagram: DM sends, skip rate (when available)
  - YouTube: Watch time, retention curves
- Benefits:
  - Access to platform-specific metrics
  - Real-time data for critical events
  - Backup if Metricool unavailable

**Tier 3: CSV Import (Fallback)**
- Frequency: Manual/on-demand
- Use cases:
  - Historical data (TikTok 60-day exports)
  - Migration from other tools
  - Platform API access issues
- Benefits:
  - Always available
  - No API approval needed
  - Large historical imports

### Collection Frequency Recommendations

| Metric Type | Frequency | Method | Rationale |
|-------------|-----------|--------|-----------|
| Core engagement (likes, comments) | Daily 4 AM | Metricool API | Business hours reporting |
| Watch time, completion rate | Daily 4 AM | Direct API | Algorithm priority |
| Follower growth | Daily 4 AM | Metricool API | Trend tracking |
| Demographics | Weekly | Direct API | Low volatility |
| Historical backfill | On-demand | CSV import | One-time migration |

**Rate Limit Management:**
- TikTok: 1,000 requests/day → 41 requests/hour → Safe: 30/hour with buffer
- Instagram: 200 requests/hour → Safe: 150/hour per account
- Queue-based processing with exponential backoff on 429 errors
- Multi-tenant: Stagger collection times (avoid thundering herd)

---

## 6. Database Schema Gap Analysis

### Current Schema Strengths ✅
- Multi-tenant ready (`client_id` in migration 006)
- Core metrics: `views`, `likes`, `comments`, `shares`, `saves`
- Platform differentiation: `platform` ENUM
- Time-series tracking: `metrics_history` table
- Hashtag performance: Separate tracking tables
- Engagement rate calculation field

### Critical Missing Columns 🚨

#### posts table additions needed:
```sql
-- Algorithm Priority Metrics (2026)
duration INT DEFAULT NULL,                        -- Video length in seconds
average_watch_time INT DEFAULT NULL,              -- Average seconds watched
completion_rate DECIMAL(5,2) DEFAULT NULL,        -- % who watched to end (0-100)
sends_count INT DEFAULT 0,                        -- DM shares (separate from general shares)
profile_visits INT DEFAULT 0,                     -- Post-driven profile views

-- Platform-Specific Metrics
skip_rate DECIMAL(5,2) DEFAULT NULL,              -- Instagram Reels 2026 (% skip in 3s)
crosspost_views INT DEFAULT 0,                    -- Instagram Reels crossposts

-- Metadata for calculation
video_duration INT DEFAULT NULL,                  -- For completion rate calc
```

#### New table needed: follower_growth
```sql
CREATE TABLE follower_growth (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube') NOT NULL,

  -- Daily snapshot
  snapshot_date DATE NOT NULL,
  total_followers INT DEFAULT 0,
  new_followers INT DEFAULT 0,
  lost_followers INT DEFAULT 0,
  net_growth INT DEFAULT 0,

  -- Metadata
  data_source ENUM('metricool', 'api', 'manual') DEFAULT 'metricool',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY unique_client_platform_date (client_id, platform, snapshot_date),
  INDEX idx_platform_date (platform, snapshot_date)
);
```

#### New table needed: industry_benchmarks
```sql
CREATE TABLE industry_benchmarks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube') NOT NULL,
  metric_name VARCHAR(100) NOT NULL,              -- engagement_rate, completion_rate, etc.

  -- Benchmark ranges
  industry_average DECIMAL(10,2),
  good_threshold DECIMAL(10,2),
  excellent_threshold DECIMAL(10,2),

  -- Context
  year INT NOT NULL DEFAULT 2026,
  notes TEXT,                                      -- Source, methodology

  UNIQUE KEY unique_platform_metric_year (platform, metric_name, year),
  INDEX idx_platform (platform)
);
```

#### New table needed: performance_snapshots (for "vs last month")
```sql
CREATE TABLE performance_snapshots (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube') NOT NULL,

  -- Aggregation period
  period_type ENUM('week', 'month', 'quarter') NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,

  -- Aggregated metrics
  total_posts INT DEFAULT 0,
  avg_engagement_rate DECIMAL(5,2),
  avg_completion_rate DECIMAL(5,2),
  total_reach INT DEFAULT 0,
  follower_growth INT DEFAULT 0,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY unique_snapshot (client_id, platform, period_type, period_start),
  INDEX idx_client_platform (client_id, platform)
);
```

#### New table needed: recommendations
```sql
CREATE TABLE recommendations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  -- Recommendation details
  recommendation_type ENUM('posting_frequency', 'content_type', 'timing', 'hashtag', 'general') NOT NULL,
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube', 'all') DEFAULT 'all',
  priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',

  -- Content
  title VARCHAR(255) NOT NULL,                    -- "Post 3 more Reels this week"
  description TEXT,                               -- Detailed explanation
  action_items JSON,                              -- ["Create Reel", "Schedule for Tuesday 9 AM"]

  -- Tracking
  status ENUM('active', 'completed', 'dismissed', 'expired') DEFAULT 'active',
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,                      -- Time-sensitive recommendations

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_status (client_id, status),
  INDEX idx_priority (priority)
);
```

### Migration Priority
1. **Critical (Week 1)**: Add watch time/completion rate columns to `posts`
2. **High (Week 2)**: Create `follower_growth` table, add `sends_count`
3. **Medium (Week 3)**: Create `industry_benchmarks` table, seed 2026 data
4. **Medium (Week 4)**: Create `performance_snapshots` for historical comparison
5. **Low (Month 2)**: Create `recommendations` table for automated insights

---

## 7. 2026 Industry Benchmarks (Seed Data)

### Engagement Rates
- **TikTok**: 3.70% avg (+49% YoY) | 5.3%+ excellent
- **Instagram**: 0.48% avg (flat vs 2025) | 3-6% good, 6%+ excellent
- **Facebook**: 0.15% avg (declining) | 0.25%+ good
- **Cross-platform**: 1.8% avg

### Watch Time & Completion
- **TikTok**: 60% avg completion | 75%+ needed for FYP
- **Instagram Reels**: Watch time = #1 factor (Mosseri, Jan 2025)
- **YouTube**: Average view duration critical (platform-specific)

### Content Type Performance (2026)
- **Reels**: Highest reach and engagement across Instagram/Facebook
- **Carousels**: Most saves and shares (Instagram)
- **Collaborative Posts**: 2.7-3.4× engagement boost

### Posting Frequency
- **Optimal**: 5 posts/week (TikTok, Instagram)
- **Trend**: Brands increasing video content (Reels priority)

### Engagement Trends
- **Shares**: +45% YoY on TikTok
- **Comments**: -24% TikTok, -16% Instagram (passive engagement shift)
- **Saves**: 2.1% avg (TikTok), 4%+ excellent

---

## 8. Key Findings & Recommendations

### Critical Insights
1. **Watch time is king**: 40-50% of algorithm weight (TikTok), confirmed #1 for Instagram Reels
2. **Completion rate threshold raised**: Now 75% needed (up from 60%)
3. **DM shares > saves > likes**: Engagement quality beats quantity
4. **Passive engagement declining**: Comments down, shares/saves up
5. **Metricool advantage**: User already has account - leverage as aggregation layer

### Immediate Action Items
1. **Schema Migration**: Add watch time, completion rate, sends columns
2. **Metricool Integration**: Build API connector (reduce direct API calls)
3. **Benchmark Seeding**: Populate `industry_benchmarks` with 2026 data
4. **Data Pipeline**: Implement daily batch jobs (4 AM per tenant)
5. **Gap Filling**: Direct TikTok API for completion rate (if Metricool lacks)

### Long-Term Strategy
1. **Hybrid Collection**: Metricool (primary) + Direct APIs (gaps) + CSV (fallback)
2. **Real-time vs Batch**: Batch for most metrics, real-time for crisis monitoring (future)
3. **Rate Limit Management**: Queue-based with exponential backoff
4. **Multi-tenant Scaling**: Stagger collection to avoid thundering herd
5. **Data Enrichment**: Calculate insights (plain language) from raw metrics

### Risks & Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| TikTok API access denied | High | CSV import as primary, Metricool backup |
| Rate limits exceeded | Medium | Queue system, staggered collection, caching |
| Metricool API limitations | Medium | Direct API fallback, monitor freshness |
| Completion rate unavailable | High | CSV parsing, third-party scraping (ToS risk) |
| Algorithm changes | Medium | Quarterly research updates, flexible schema |

---

## 9. Metricool Integration Strategy

### Why Metricool as Primary Aggregator?
1. **User already has account** → No additional subscription cost
2. **Multi-platform coverage** → 14 social platforms via single API
3. **Standardized metrics** → Consistent data format across platforms
4. **Rate limit efficiency** → Reduces direct API quota consumption
5. **Looker Studio connector** → 250+ data sources (future expansion)

### Integration Architecture
```
┌─────────────────────────────────────────────────┐
│           SocialBit Data Collection             │
└─────────────────────────────────────────────────┘
                      │
         ┌────────────┴────────────┐
         │                         │
    ┌────▼──────┐          ┌──────▼─────┐
    │ Metricool │          │ Direct API │
    │    API    │          │  (Gaps)    │
    └────┬──────┘          └──────┬─────┘
         │                         │
    ┌────▼─────────────────────────▼─────┐
    │     Standardization Layer           │
    │  (Map to SocialBit schema)          │
    └────┬────────────────────────────────┘
         │
    ┌────▼────────────────────────────────┐
    │   Database (MySQL)                  │
    │   - posts                           │
    │   - metrics_history                 │
    │   - follower_growth                 │
    └─────────────────────────────────────┘
```

### Data Flow
1. **Daily Batch (4 AM per tenant timezone)**:
   - Query Metricool API for all platforms
   - Standard metrics: views, likes, comments, shares, saves, reach

2. **Weekly Supplement (Direct APIs)**:
   - TikTok: Completion rate, watch time (if unavailable via Metricool)
   - Instagram: DM sends, skip rate (when API available)
   - YouTube: Retention curves, traffic sources

3. **On-Demand CSV Import**:
   - Historical data backfill
   - Platform API outages
   - User preference (some prefer manual control)

### Metricool API Endpoints (Documented)
- **Authentication**: userId + blogId parameters required
- **Metrics Available**:
  - TikTok: Profile views, post metrics, daily follower growth
  - Instagram: Reach, engagement, followers/following
  - Facebook: Page insights (limited by Meta's organic restrictions)
- **Historical Data**: Available with platform-specific limitations
- **Rate Limits**: Not explicitly documented (likely similar to Advanced plan quotas)

### Implementation Checklist
- [ ] Obtain Metricool API token (Advanced plan required)
- [ ] Build PHP service: `MetricoolApiService.php`
- [ ] Create repository: `MetricoolRepository.php`
- [ ] Map Metricool response → SocialBit schema
- [ ] Error handling: API downtime, rate limits
- [ ] Validation: Compare Metricool vs direct API (sample posts)
- [ ] Fallback logic: Direct API if Metricool fails
- [ ] Monitoring: Data freshness alerts

---

## 10. Next Steps

### Immediate (This Week)
1. Review this research with team lead
2. Prioritize schema migrations (watch time, completion rate)
3. Verify Metricool API access level (confirm Advanced plan)
4. Design database migration 007 (add new columns/tables)

### Short-Term (Month 1)
1. Build Metricool API integration
2. Implement daily batch collection
3. Seed industry benchmarks table
4. Create follower growth tracking
5. Update dashboard to show completion rate

### Medium-Term (Month 2-3)
1. Direct API integrations (TikTok, Instagram, YouTube)
2. Queue-based collection with rate limiting
3. Performance snapshots (historical comparison)
4. Automated recommendations engine
5. Plain language insights generation

### Long-Term (Month 4+)
1. Real-time data streaming (WebSocket for critical events)
2. AI-powered insight generation (competitor analysis)
3. Predictive analytics (forecast engagement)
4. Multi-language support (international benchmarks)
5. White-label reporting (client-facing PDFs)

---

## Sources

### TikTok Research
- [Understanding TikTok API Rate Limits](https://developers.tiktok.com/doc/tiktok-api-v2-rate-limit?enter_method=left_navigation)
- [TikTok Analytics & KPIs Complete Guide 2026](https://akselera.tech/en/insights/guides/tiktok-analytics-kpi-complete-guide)
- [TikTok Analytics in 2026: Best Tools, Metrics & More](https://agencyanalytics.com/blog/tiktok-analytics)
- [2026 TikTok Marketing Benchmarks](https://www.webfx.com/blog/social-media/tiktok-benchmarks/)
- [How the TikTok Algorithm Works in 2026](https://posteverywhere.ai/blog/how-the-tiktok-algorithm-works)
- [12 TikTok Metrics You Should Track in 2026](https://planable.io/blog/tiktok-metrics/)

### Instagram Research
- [Instagram Graph API: Complete Developer Guide for 2026](https://elfsight.com/blog/instagram-graph-api-complete-developer-guide-for-2026/)
- [Instagram API Rate Limits: 200 DMs/Hour Explained (2026)](https://creatorflow.so/blog/instagram-api-rate-limits-explained/)
- [Instagram Reel Analytics: Retention & Skip Rate Explained](https://metricool.com/instagram-reel-analytics/)
- [Instagram Algorithm 2026: How It Works & Boosts Engagement](https://www.clixie.ai/blog/how-the-instagram-algorithm-works)
- [How the Instagram Algorithm Works: Your 2026 Guide](https://buffer.com/resources/instagram-algorithms/)

### Facebook Research
- [Facebook Insights updates](https://docs.supermetrics.com/docs/facebook-insights-updates)
- [Facebook Insight API: Developer guide](https://www.getphyllo.com/post/facebook-insight-api)

### YouTube Research
- [YouTube Analytics API - Data Model](https://developers.google.com/youtube/analytics/data_model)
- [Youtube API limits: How to calculate API usage cost](https://www.getphyllo.com/post/youtube-api-limits-how-to-calculate-api-usage-cost-and-fix-exceeded-api-quota)
- [YouTube Analytics API Guide](https://www.getphyllo.com/post/youtube-analytics-api-guide-how-to-pull-video-views-likes-iv)

### Benchmarks Research
- [Social Media Benchmarks For 2026](https://www.socialinsider.io/social-media-benchmarks)
- [60+ social media statistics marketers need to know in 2026](https://blog.hootsuite.com/social-media-statistics/)
- [2026 Social Media Benchmarks [Infographic]](https://www.socialmediatoday.com/news/2026-social-media-benchmarks-infographic/811179/)
- [Social media engagement rate formulas & 2026 benchmarks](https://planable.io/blog/social-media-engagement-rate/)
- [2026 Social Media Benchmarks to Guide Your Strategy](https://buffer.com/resources/social-media-benchmarks/)

### Competitor Analysis
- [Sprout Social vs. Hootsuite: Compare Features, Pricing, & More](https://www.hootsuite.com/hootsuite-vs-sprout-social)
- [Buffer vs Hootsuite vs Sprout Social](https://www.saasworthy.com/compare/buffer-vs-hootsuite-vs-sprout-social-vs-later)
- [Sprout Social vs Hootsuite: The Best Tool for Your Brand in 2026](https://sproutsocial.com/insights/sprout-social-vs-hootsuite/)

### Metricool Research
- [Metricool Review 2026: Pricing, Features, Pros & Cons](https://research.com/software/reviews/metricool)
- [API & Integrations | Metricool](https://help.metricool.com/en/category/api-integrations-dc0snw/)
- [Basic Guide for API Integration | Metricool](https://help.metricool.com/en/article/basic-guide-for-api-integration-abukgf/)
- [TikTok Metrics | Metricool](https://help.metricool.com/en/article/tiktok-metrics-1hnbc9r/)
- [Instagram Metrics | Metricool](https://help.metricool.com/en/article/instagram-metrics-12vpkyb/)
- [Metricool Connector | Airbyte Documentation](https://docs.airbyte.com/integrations/sources/metricool)

### Best Practices
- [Top 5 Social Media API for Data Collection and Analytics in 2026](https://www.cm-alliance.com/cybersecurity-blog/top-5-social-media-api-for-data-collection-and-analytics-in-2026)
- [10 Social Media Data Collection Challenges And Solutions](https://www.socialinsider.io/blog/social-media-data-collection/)
- [10 Best Unified Social Media APIs for Developers in 2026](https://www.outstand.so/blog/best-unified-social-media-apis-for-devs)

---

**End of Research Report**
