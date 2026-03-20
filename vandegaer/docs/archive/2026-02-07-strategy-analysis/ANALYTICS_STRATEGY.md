# SocialBit Analytics Strategy
## Actionable Business Insights for Non-Technical Users

**Document Version:** 1.0
**Date:** 2026-02-07
**Author:** Data Science Team (Claude Sonnet 4.5)

---

## Executive Summary

This document defines the analytics strategy for SocialBit, focusing on translating raw social media data into actionable business recommendations. The core principle: **business users need CONTEXT and RECOMMENDATIONS, not just metrics**.

**Key Insight:** A metric without context is just a number. "4.2% engagement rate" means nothing unless we say "You're performing 2× better than average for your industry."

---

## 1. Core KPI Definitions

### 1.1 Primary Business KPIs

#### Engagement Quality Score (EQS)
**Purpose:** Single metric that captures content effectiveness
**Formula:**
```
EQS = (weighted_engagement / views) × 100
weighted_engagement = (likes × 1) + (comments × 3) + (shares × 5) + (saves × 4)
```

**Why weighted?** Not all engagement is equal:
- **Shares (5×):** Highest intent signal - user vouches for content publicly
- **Saves (4×):** High-intent bookmark for future reference
- **Comments (3×):** Active conversation, more valuable than passive like
- **Likes (1×):** Baseline engagement, lowest barrier

**Business Interpretation:**
- EQS < 2%: Content not resonating, needs improvement
- EQS 2-5%: Average performance, room for optimization
- EQS 5-10%: Good performance, identify what works
- EQS > 10%: Excellent, replicate this content strategy

**Database Implementation:**
```sql
-- Add to posts table or calculate in view
ALTER TABLE posts ADD COLUMN engagement_quality_score DECIMAL(5,2);

-- Calculation stored procedure
CREATE FUNCTION calculate_eqs(post_id INT) RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
  DECLARE views_val, likes_val, comments_val, shares_val, saves_val INT;
  DECLARE weighted_eng DECIMAL(10,2);

  SELECT views, likes, comments, shares, saves
  INTO views_val, likes_val, comments_val, shares_val, saves_val
  FROM posts WHERE id = post_id;

  SET weighted_eng = (likes_val * 1) + (comments_val * 3) + (shares_val * 5) + (saves_val * 4);

  IF views_val > 0 THEN
    RETURN (weighted_eng / views_val) * 100;
  END IF;

  RETURN 0;
END;
```

---

#### Watch Time & Completion Rate (Algorithm Priority #1)
**Purpose:** Predict algorithmic reach - platforms prioritize content people watch completely

**Formulas:**
```
Completion Rate = (average_watch_time / video_duration) × 100
Watch Time Quality = completion_rate × views
```

**2026 Benchmarks (from MEMORY.md):**
- **TikTok:** 60% avg completion, 75%+ excellent
- **Instagram Reels:** Skip rate <10% is good (inverse metric)

**Business Interpretation:**
- Completion Rate > 75%: Algorithm will boost this heavily
- Completion Rate 50-75%: Good retention, optimize opening hook
- Completion Rate < 50%: Content too long or weak hook

**Required Schema Changes:**
```sql
-- Migration 007: Add watch time metrics
ALTER TABLE posts ADD COLUMN duration INT COMMENT 'Video duration in seconds';
ALTER TABLE posts ADD COLUMN average_watch_time INT COMMENT 'Avg watch time in seconds';
ALTER TABLE posts ADD COLUMN completion_rate DECIMAL(5,2) COMMENT 'Calculated: (avg_watch_time/duration)*100';
ALTER TABLE posts ADD COLUMN watch_time_total BIGINT COMMENT 'Total watch time across all views';

-- Instagram-specific
ALTER TABLE posts ADD COLUMN reels_skip_rate DECIMAL(5,2) COMMENT 'Instagram Reels skip percentage';

-- Index for performance queries
CREATE INDEX idx_completion_rate ON posts(completion_rate);
CREATE INDEX idx_watch_time ON posts(watch_time_total);
```

---

#### Discovery Metrics (Growth Signals)
**Purpose:** Measure content's ability to reach new audiences

**Key Metrics:**
- **Profile Visits:** How many viewers clicked through to profile
- **Follower Growth:** Net new followers attributed to specific content
- **Crossposted Views:** Instagram Reels shown on Facebook/Explore (2026 feature)

**Business Interpretation:**
- High engagement + low profile visits = entertaining but not brand-building
- High profile visits + low follows = mismatch between content and profile
- High crosspost views = algorithm considers this high-quality content

**Required Schema Changes:**
```sql
-- Migration 007 continued
ALTER TABLE posts ADD COLUMN profile_visits INT DEFAULT 0 COMMENT 'Clicks to profile from this post';
ALTER TABLE posts ADD COLUMN follower_growth INT DEFAULT 0 COMMENT 'New followers attributed to this post';
ALTER TABLE posts ADD COLUMN crossposted_views INT DEFAULT 0 COMMENT 'Instagram: views on Facebook/Explore';

CREATE INDEX idx_profile_visits ON posts(profile_visits);
CREATE INDEX idx_follower_growth ON posts(follower_growth);
```

---

### 1.2 Vanity vs. Actionable Metrics

**Vanity Metrics** (informative but not actionable alone):
- Total followers
- Total views
- Total likes

**Actionable Metrics** (can guide decisions):
- Engagement Quality Score trend
- Completion rate by content type
- Best posting times (day/hour with highest engagement)
- Hashtag performance (which drive discovery)
- Topic performance (which topics convert)

**Business Rule:** Always pair vanity metrics with context:
- ❌ "You have 10,000 followers"
- ✅ "You gained 500 followers this month (+5% growth, above 2% industry average)"

---

## 2. Statistical Methods for Comparison

### 2.1 Historical Comparison (Time-Series Analysis)

#### Period-over-Period Growth
**Purpose:** Show trajectory and momentum

**Calculation:**
```sql
-- Month-over-month comparison view
CREATE OR REPLACE VIEW monthly_performance_comparison AS
SELECT
  DATE_FORMAT(posted_date, '%Y-%m') AS month,
  COUNT(*) AS posts_count,
  AVG(views) AS avg_views,
  AVG(engagement_rate) AS avg_engagement,
  AVG(completion_rate) AS avg_completion,

  -- Calculate vs previous month
  LAG(AVG(views)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m')) AS prev_month_views,
  LAG(AVG(engagement_rate)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m')) AS prev_month_engagement,

  -- Growth percentage
  ROUND(((AVG(views) - LAG(AVG(views)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m')))
    / LAG(AVG(views)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m'))) * 100, 2) AS views_growth_pct,

  ROUND(((AVG(engagement_rate) - LAG(AVG(engagement_rate)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m')))
    / LAG(AVG(engagement_rate)) OVER (ORDER BY DATE_FORMAT(posted_date, '%Y-%m'))) * 100, 2) AS engagement_growth_pct

FROM posts
WHERE posted_date IS NOT NULL
  AND client_id = ? -- Multi-tenant filter
GROUP BY DATE_FORMAT(posted_date, '%Y-%m')
ORDER BY month DESC;
```

**Business Presentation:**
```
Your Performance This Month:
━━━━━━━━━━━━━━━━━━━━━━━━━━━
📈 Average Views: 15,420 (+22% vs last month)
💬 Engagement Rate: 5.8% (+0.7% vs last month)
⏱️  Completion Rate: 72% (+5% vs last month)

✅ You're trending up! Keep posting similar content.
```

---

#### Seasonal Patterns Detection
**Purpose:** Identify cyclical trends (day of week, time of day, seasonal)

**Implementation:**
```sql
-- Best posting day/time analysis
CREATE OR REPLACE VIEW optimal_posting_schedule AS
SELECT
  DAYNAME(posted_date) AS day_of_week,
  HOUR(posted_time) AS hour_of_day,
  COUNT(*) AS posts_count,
  AVG(engagement_rate) AS avg_engagement,
  AVG(views) AS avg_views,
  RANK() OVER (ORDER BY AVG(engagement_rate) DESC) AS engagement_rank
FROM posts
WHERE posted_date IS NOT NULL
  AND posted_time IS NOT NULL
  AND client_id = ?
  AND posted_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAYS) -- Last 90 days
GROUP BY DAYNAME(posted_date), HOUR(posted_time)
ORDER BY avg_engagement DESC
LIMIT 10;
```

**Business Presentation:**
```
🎯 Your Best Posting Times:

1️⃣ Tuesday, 9 AM → 6.8% engagement (23 posts)
2️⃣ Thursday, 2 PM → 6.2% engagement (18 posts)
3️⃣ Saturday, 11 AM → 5.9% engagement (15 posts)

💡 Recommendation: Schedule your next 3 posts for these windows.
```

---

### 2.2 Benchmark Comparison (Contextual Performance)

#### Industry Benchmarks Table
**Purpose:** Store 2026 industry averages for comparison

```sql
-- Migration 007 continued
CREATE TABLE IF NOT EXISTS industry_benchmarks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  platform ENUM('tiktok', 'instagram', 'facebook') NOT NULL,
  industry VARCHAR(100) DEFAULT 'general' COMMENT 'e.g., retail, tech, hospitality, general',
  metric_name VARCHAR(100) NOT NULL COMMENT 'engagement_rate, completion_rate, etc.',

  -- Benchmark values
  percentile_25 DECIMAL(10,2) COMMENT 'Bottom 25% - below average',
  percentile_50 DECIMAL(10,2) COMMENT 'Median - average',
  percentile_75 DECIMAL(10,2) COMMENT 'Top 25% - above average',
  percentile_90 DECIMAL(10,2) COMMENT 'Top 10% - excellent',

  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY unique_benchmark (platform, industry, metric_name),
  INDEX idx_platform_metric (platform, metric_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed with 2026 benchmarks from MEMORY.md
INSERT INTO industry_benchmarks (platform, industry, metric_name, percentile_25, percentile_50, percentile_75, percentile_90) VALUES
('tiktok', 'general', 'engagement_rate', 2.0, 3.7, 5.3, 8.0),
('instagram', 'general', 'engagement_rate', 0.30, 0.48, 3.0, 6.0),
('facebook', 'general', 'engagement_rate', 0.10, 0.15, 0.50, 1.0),
('tiktok', 'general', 'completion_rate', 50.0, 60.0, 75.0, 85.0),
('instagram', 'general', 'reels_skip_rate', 20.0, 15.0, 10.0, 5.0);
```

**Usage in Analytics:**
```sql
-- Compare user performance to benchmarks
SELECT
  p.client_id,
  p.platform,
  AVG(p.engagement_rate) AS user_avg_engagement,
  b.percentile_50 AS industry_median,
  ROUND((AVG(p.engagement_rate) / b.percentile_50) * 100, 0) AS performance_vs_median_pct,

  CASE
    WHEN AVG(p.engagement_rate) >= b.percentile_90 THEN 'Excellent (Top 10%)'
    WHEN AVG(p.engagement_rate) >= b.percentile_75 THEN 'Above Average (Top 25%)'
    WHEN AVG(p.engagement_rate) >= b.percentile_50 THEN 'Average'
    ELSE 'Below Average'
  END AS performance_category

FROM posts p
JOIN industry_benchmarks b
  ON b.platform = p.platform
  AND b.metric_name = 'engagement_rate'
  AND b.industry = 'general'
WHERE p.client_id = ?
  AND p.posted_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
GROUP BY p.client_id, p.platform;
```

**Business Presentation:**
```
📊 Your Performance vs Industry:

TikTok:
  Your Avg: 5.2% engagement
  Industry: 3.7% average
  Result: 140% of average → Above Average (Top 25%)

Instagram:
  Your Avg: 0.8% engagement
  Industry: 0.48% average
  Result: 167% of average → Excellent (Top 10%)

✨ You're outperforming the market! Keep it up.
```

---

### 2.3 Statistical Significance & Confidence

#### Sample Size Considerations
**Problem:** Small sample sizes lead to unreliable conclusions

**Business Rule:**
- Need **minimum 30 posts** for reliable platform averages
- Need **minimum 10 posts** per hashtag/topic for comparison
- Flag small samples with warning icon in UI

**Implementation:**
```sql
-- Add confidence indicators to views
CREATE OR REPLACE VIEW analytics_with_confidence AS
SELECT
  *,
  CASE
    WHEN post_count < 10 THEN 'low'
    WHEN post_count < 30 THEN 'medium'
    ELSE 'high'
  END AS confidence_level
FROM (
  SELECT
    platform,
    COUNT(*) AS post_count,
    AVG(engagement_rate) AS avg_engagement,
    STDDEV(engagement_rate) AS engagement_stddev
  FROM posts
  WHERE client_id = ?
  GROUP BY platform
) stats;
```

**Business Presentation:**
```
Instagram Performance: 0.8% engagement
⚠️  Low Confidence (only 8 posts) - post more for reliable data

TikTok Performance: 5.2% engagement
✅ High Confidence (47 posts) - this is a reliable average
```

---

#### Variance & Consistency Analysis
**Purpose:** Identify if performance is consistent or erratic

**Formula:**
- **Coefficient of Variation (CV)** = (standard deviation / mean) × 100
- CV < 30%: Consistent performance
- CV 30-60%: Moderate variance
- CV > 60%: Highly inconsistent (need strategy refinement)

**Implementation:**
```sql
-- Performance consistency analysis
SELECT
  platform,
  post_type,
  COUNT(*) AS posts,
  AVG(engagement_rate) AS avg_engagement,
  STDDEV(engagement_rate) AS stddev_engagement,
  ROUND((STDDEV(engagement_rate) / AVG(engagement_rate)) * 100, 2) AS coefficient_variation,

  CASE
    WHEN (STDDEV(engagement_rate) / AVG(engagement_rate)) * 100 < 30 THEN 'Consistent'
    WHEN (STDDEV(engagement_rate) / AVG(engagement_rate)) * 100 < 60 THEN 'Moderate Variance'
    ELSE 'Highly Variable'
  END AS consistency_rating

FROM posts
WHERE client_id = ? AND engagement_rate > 0
GROUP BY platform, post_type
HAVING COUNT(*) >= 10;
```

**Business Presentation:**
```
Content Consistency Report:
━━━━━━━━━━━━━━━━━━━━━━━━━
TikTok Videos: ✅ Consistent (CV: 22%)
  → Your video strategy is working reliably

Instagram Reels: ⚠️  Highly Variable (CV: 78%)
  → Some reels hit, others flop - refine your approach

💡 Recommendation: Review your top 3 Instagram Reels and identify common patterns.
```

---

## 3. Predictive Opportunities (Keep Simple)

### 3.1 Content Performance Prediction (Simple Scoring)

**Purpose:** Predict if content will perform well BEFORE posting

**Approach:** Rule-based scoring system (NOT machine learning initially)

**Predictive Factors:**
1. **Optimal posting time** (+2 points if posted during top 3 time slots)
2. **Proven hashtags** (+1 point per high-performing hashtag used)
3. **Content type match** (+2 points if using best-performing content type)
4. **Topic alignment** (+2 points if using top-performing topic)
5. **Historical pattern** (+1 point if similar past content performed well)

**Implementation:**
```sql
-- Content prediction scoring table
CREATE TABLE IF NOT EXISTS content_predictions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT COMMENT 'NULL if prediction before posting',

  -- Prediction factors
  optimal_time_score INT DEFAULT 0 COMMENT '0-2 points',
  hashtag_score INT DEFAULT 0 COMMENT '0-5 points',
  content_type_score INT DEFAULT 0 COMMENT '0-2 points',
  topic_score INT DEFAULT 0 COMMENT '0-2 points',
  historical_score INT DEFAULT 0 COMMENT '0-1 points',

  total_score INT AS (optimal_time_score + hashtag_score + content_type_score + topic_score + historical_score),

  predicted_category ENUM('low', 'medium', 'high') AS (
    CASE
      WHEN total_score >= 8 THEN 'high'
      WHEN total_score >= 5 THEN 'medium'
      ELSE 'low'
    END
  ),

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,
  INDEX idx_client_prediction (client_id, predicted_category)
) ENGINE=InnoDB;
```

**Business Presentation:**
```
📝 Content Prediction for Your Draft Post:

Predicted Performance: 🔥 HIGH (9/12 points)

✅ Posting time: Tuesday 9 AM (optimal) → +2
✅ Hashtags: #marketing #socialmedia (proven) → +2
✅ Content type: Reel (your best format) → +2
✅ Topic: Tutorial (high engagement) → +2
✅ Similar past content performed well → +1

💡 Confidence: This post is likely to perform in your top 25%.
```

---

### 3.2 Trend Forecasting (Moving Averages)

**Purpose:** Predict next month's performance based on trends

**Approach:** Simple Moving Average (SMA) + Linear extrapolation

**Implementation:**
```sql
-- 3-month moving average with forecast
CREATE OR REPLACE VIEW performance_forecast AS
SELECT
  month_date,
  avg_engagement,

  -- 3-month moving average
  AVG(avg_engagement) OVER (
    ORDER BY month_date
    ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
  ) AS sma_3month,

  -- Simple linear forecast (next month = SMA + trend)
  AVG(avg_engagement) OVER (
    ORDER BY month_date
    ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
  ) +
  (AVG(avg_engagement) OVER (
    ORDER BY month_date
    ROWS BETWEEN 1 PRECEDING AND CURRENT ROW
  ) -
  AVG(avg_engagement) OVER (
    ORDER BY month_date
    ROWS BETWEEN 2 PRECEDING AND 1 PRECEDING
  )) AS forecast_next_month

FROM (
  SELECT
    DATE_FORMAT(posted_date, '%Y-%m-01') AS month_date,
    AVG(engagement_rate) AS avg_engagement
  FROM posts
  WHERE client_id = ?
  GROUP BY DATE_FORMAT(posted_date, '%Y-%m-01')
) monthly_stats
ORDER BY month_date DESC;
```

**Business Presentation:**
```
📈 Forecast for Next Month:

Based on your 3-month trend:
  Dec 2025: 4.2% engagement
  Jan 2026: 4.8% engagement (+14%)
  Feb 2026: 5.1% engagement (current)
  Mar 2026: 5.4% engagement (forecast) 📊

✨ You're on an upward trajectory! Stay consistent with your current strategy.
```

---

## 4. Correlation Analysis Opportunities

### 4.1 Feature Correlation Matrix

**Purpose:** Discover non-obvious relationships between variables

**Key Questions:**
1. Do longer captions drive more engagement?
2. Do more hashtags = better reach?
3. Does posting frequency affect average engagement?
4. Do posts with questions in caption get more comments?

**Implementation:**
```sql
-- Correlation analysis: hashtag count vs engagement
SELECT
  hashtag_count_bucket,
  COUNT(*) AS posts,
  AVG(engagement_rate) AS avg_engagement,
  AVG(views) AS avg_views
FROM (
  SELECT
    p.*,
    COUNT(ph.hashtag_id) AS hashtag_count,
    CASE
      WHEN COUNT(ph.hashtag_id) = 0 THEN '0 hashtags'
      WHEN COUNT(ph.hashtag_id) <= 3 THEN '1-3 hashtags'
      WHEN COUNT(ph.hashtag_id) <= 7 THEN '4-7 hashtags'
      ELSE '8+ hashtags'
    END AS hashtag_count_bucket
  FROM posts p
  LEFT JOIN post_hashtags ph ON ph.post_id = p.id
  WHERE p.client_id = ?
  GROUP BY p.id
) hashtag_analysis
GROUP BY hashtag_count_bucket
ORDER BY
  CASE hashtag_count_bucket
    WHEN '0 hashtags' THEN 1
    WHEN '1-3 hashtags' THEN 2
    WHEN '4-7 hashtags' THEN 3
    WHEN '8+ hashtags' THEN 4
  END;
```

**Business Presentation:**
```
🔍 Hashtag Strategy Insights:

Hashtag Count → Avg Engagement:
  0 hashtags:   3.2% (15 posts)
  1-3 hashtags: 5.8% (42 posts) ⭐ BEST
  4-7 hashtags: 4.1% (28 posts)
  8+ hashtags:  2.9% (8 posts)

💡 Recommendation: Use 2-3 targeted hashtags per post. More isn't better.
```

---

### 4.2 Content Element Analysis

**Purpose:** Identify specific content patterns that drive performance

**Advanced SQL Pattern Matching:**
```sql
-- Question posts vs statement posts
SELECT
  CASE
    WHEN caption LIKE '%?' THEN 'Question-based'
    WHEN caption LIKE '%!' THEN 'Exclamation-based'
    ELSE 'Statement-based'
  END AS caption_style,

  COUNT(*) AS posts,
  AVG(engagement_rate) AS avg_engagement,
  AVG(comments) AS avg_comments

FROM posts
WHERE client_id = ? AND caption IS NOT NULL
GROUP BY
  CASE
    WHEN caption LIKE '%?' THEN 'Question-based'
    WHEN caption LIKE '%!' THEN 'Exclamation-based'
    ELSE 'Statement-based'
  END
HAVING COUNT(*) >= 10;
```

**Business Presentation:**
```
✍️  Caption Style Performance:

Question-based: 6.2% engagement, 18 avg comments
Exclamation-based: 4.8% engagement, 12 avg comments
Statement-based: 3.9% engagement, 8 avg comments

💡 Recommendation: Ask questions in your captions to boost comments 2×.
```

---

## 5. Anomaly Detection

### 5.1 Viral Content Detection

**Purpose:** Automatically flag outlier posts (good and bad)

**Approach:** Statistical outlier detection using IQR (Interquartile Range)

**Implementation:**
```sql
-- Detect viral posts (outliers above Q3 + 1.5*IQR)
WITH engagement_quartiles AS (
  SELECT
    platform,
    -- Calculate quartiles
    PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY engagement_rate) AS q1,
    PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY engagement_rate) AS median,
    PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY engagement_rate) AS q3
  FROM posts
  WHERE client_id = ?
  GROUP BY platform
),
outlier_bounds AS (
  SELECT
    platform,
    q1,
    q3,
    (q3 - q1) AS iqr,
    q3 + (1.5 * (q3 - q1)) AS upper_bound_outlier,
    q1 - (1.5 * (q3 - q1)) AS lower_bound_outlier
  FROM engagement_quartiles
)
SELECT
  p.id,
  p.platform,
  p.caption,
  p.posted_date,
  p.engagement_rate,
  ob.upper_bound_outlier,

  CASE
    WHEN p.engagement_rate > ob.upper_bound_outlier THEN 'Viral Hit'
    WHEN p.engagement_rate < ob.lower_bound_outlier THEN 'Underperformer'
    ELSE 'Normal'
  END AS anomaly_type

FROM posts p
JOIN outlier_bounds ob ON ob.platform = p.platform
WHERE p.client_id = ?
  AND (p.engagement_rate > ob.upper_bound_outlier
       OR p.engagement_rate < ob.lower_bound_outlier)
ORDER BY p.engagement_rate DESC;
```

**Business Presentation:**
```
🚀 Viral Content Alert!

Post #1247 (Jan 15): 18.3% engagement
  → 4× your average! This went viral.

✅ What made it work:
  - Posted Tuesday 9 AM (optimal time)
  - Used #tutorial #howto (proven hashtags)
  - 68% completion rate (high retention)
  - Asked question in caption

💡 Action: Replicate this format in your next 3 posts.
```

---

### 5.2 Performance Drop Alerts

**Purpose:** Notify when performance suddenly declines

**Approach:** Z-score anomaly detection (standard deviations from mean)

**Implementation:**
```sql
-- Detect sudden performance drops
WITH recent_stats AS (
  SELECT
    AVG(engagement_rate) AS avg_engagement,
    STDDEV(engagement_rate) AS stddev_engagement
  FROM posts
  WHERE client_id = ?
    AND posted_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
)
SELECT
  p.id,
  p.caption,
  p.posted_date,
  p.engagement_rate,
  rs.avg_engagement,

  -- Z-score: how many std deviations from mean
  (p.engagement_rate - rs.avg_engagement) / rs.stddev_engagement AS z_score,

  CASE
    WHEN (p.engagement_rate - rs.avg_engagement) / rs.stddev_engagement < -2 THEN 'Significant Drop'
    WHEN (p.engagement_rate - rs.avg_engagement) / rs.stddev_engagement < -1 THEN 'Below Normal'
    ELSE 'Normal'
  END AS alert_level

FROM posts p
CROSS JOIN recent_stats rs
WHERE p.client_id = ?
  AND p.posted_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
  AND (p.engagement_rate - rs.avg_engagement) / rs.stddev_engagement < -1
ORDER BY z_score ASC;
```

**Business Presentation:**
```
⚠️  Performance Alert:

Your last 3 posts underperformed:
  Jan 5: 2.1% engagement (-2.3σ below average)
  Jan 7: 2.4% engagement (-1.8σ below average)
  Jan 9: 2.6% engagement (-1.5σ below average)

Possible causes:
  ❌ Posted outside optimal windows (late night)
  ❌ No proven hashtags used
  ❌ Low completion rate (42% vs your 68% average)

💡 Action: Return to your proven formula - post Tue/Thu mornings with tutorial content.
```

---

## 6. Recommended Analytical Views

### 6.1 Performance Dashboard View
```sql
CREATE OR REPLACE VIEW client_performance_dashboard AS
SELECT
  c.id AS client_id,
  c.business_name,

  -- Overall stats (last 30 days)
  COUNT(p.id) AS total_posts_30d,
  SUM(p.views) AS total_views_30d,
  AVG(p.engagement_rate) AS avg_engagement_30d,
  AVG(p.completion_rate) AS avg_completion_30d,

  -- vs previous 30 days
  (SELECT AVG(engagement_rate)
   FROM posts
   WHERE client_id = c.id
     AND posted_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAYS)
                         AND DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
  ) AS avg_engagement_prev_30d,

  -- Growth calculation
  ROUND(((AVG(p.engagement_rate) -
    (SELECT AVG(engagement_rate)
     FROM posts
     WHERE client_id = c.id
       AND posted_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAYS)
                           AND DATE_SUB(CURDATE(), INTERVAL 30 DAYS))
  ) /
  (SELECT AVG(engagement_rate)
   FROM posts
   WHERE client_id = c.id
     AND posted_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAYS)
                         AND DATE_SUB(CURDATE(), INTERVAL 30 DAYS))
  ) * 100, 2) AS engagement_growth_pct,

  -- Benchmark comparison
  (SELECT percentile_50 FROM industry_benchmarks WHERE platform = 'tiktok' AND metric_name = 'engagement_rate' LIMIT 1) AS tiktok_industry_avg,

  CASE
    WHEN AVG(p.engagement_rate) >= (SELECT percentile_90 FROM industry_benchmarks WHERE platform = p.platform AND metric_name = 'engagement_rate' LIMIT 1) THEN 'Excellent'
    WHEN AVG(p.engagement_rate) >= (SELECT percentile_75 FROM industry_benchmarks WHERE platform = p.platform AND metric_name = 'engagement_rate' LIMIT 1) THEN 'Above Average'
    WHEN AVG(p.engagement_rate) >= (SELECT percentile_50 FROM industry_benchmarks WHERE platform = p.platform AND metric_name = 'engagement_rate' LIMIT 1) THEN 'Average'
    ELSE 'Below Average'
  END AS performance_category

FROM clients c
LEFT JOIN posts p ON p.client_id = c.id
  AND p.posted_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
GROUP BY c.id, c.business_name;
```

---

### 6.2 Content Recommendation Engine View
```sql
CREATE OR REPLACE VIEW content_recommendations AS
WITH best_performers AS (
  SELECT
    client_id,
    post_type,
    topic,
    DAYNAME(posted_date) AS best_day,
    HOUR(posted_time) AS best_hour,
    AVG(engagement_rate) AS avg_engagement,
    COUNT(*) AS sample_size,
    RANK() OVER (PARTITION BY client_id ORDER BY AVG(engagement_rate) DESC) AS performance_rank
  FROM posts
  WHERE posted_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAYS)
    AND engagement_rate IS NOT NULL
  GROUP BY client_id, post_type, topic, DAYNAME(posted_date), HOUR(posted_time)
  HAVING COUNT(*) >= 3
)
SELECT
  client_id,

  -- Top content type
  (SELECT post_type FROM best_performers WHERE performance_rank = 1 LIMIT 1) AS recommended_content_type,

  -- Top topic
  (SELECT topic FROM best_performers WHERE performance_rank = 1 LIMIT 1) AS recommended_topic,

  -- Best posting day
  (SELECT best_day FROM best_performers WHERE performance_rank = 1 LIMIT 1) AS recommended_day,

  -- Best posting hour
  (SELECT best_hour FROM best_performers WHERE performance_rank = 1 LIMIT 1) AS recommended_hour,

  -- Expected engagement if following recommendations
  (SELECT avg_engagement FROM best_performers WHERE performance_rank = 1 LIMIT 1) AS expected_engagement,

  -- Confidence level
  CASE
    WHEN (SELECT sample_size FROM best_performers WHERE performance_rank = 1 LIMIT 1) >= 10 THEN 'High'
    WHEN (SELECT sample_size FROM best_performers WHERE performance_rank = 1 LIMIT 1) >= 5 THEN 'Medium'
    ELSE 'Low'
  END AS confidence_level

FROM (SELECT DISTINCT client_id FROM posts) clients;
```

---

### 6.3 Weekly Insights Summary View
```sql
CREATE OR REPLACE VIEW weekly_insights_summary AS
SELECT
  client_id,
  YEARWEEK(posted_date) AS year_week,

  -- Performance metrics
  COUNT(*) AS posts_this_week,
  AVG(engagement_rate) AS avg_engagement,
  AVG(completion_rate) AS avg_completion,
  SUM(views) AS total_views,
  SUM(profile_visits) AS total_profile_visits,
  SUM(follower_growth) AS net_follower_growth,

  -- Best post
  (SELECT caption FROM posts
   WHERE client_id = p.client_id
     AND YEARWEEK(posted_date) = YEARWEEK(p.posted_date)
   ORDER BY engagement_rate DESC LIMIT 1) AS best_post_caption,

  MAX(engagement_rate) AS best_post_engagement,

  -- Worst post
  MIN(engagement_rate) AS worst_post_engagement,

  -- Growth vs previous week
  LAG(AVG(engagement_rate)) OVER (PARTITION BY client_id ORDER BY YEARWEEK(posted_date)) AS prev_week_engagement,

  ROUND(((AVG(engagement_rate) - LAG(AVG(engagement_rate)) OVER (PARTITION BY client_id ORDER BY YEARWEEK(posted_date)))
    / LAG(AVG(engagement_rate)) OVER (PARTITION BY client_id ORDER BY YEARWEEK(posted_date))) * 100, 2) AS week_over_week_growth_pct

FROM posts p
WHERE posted_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
GROUP BY client_id, YEARWEEK(posted_date);
```

---

## 7. Machine Learning Opportunities (Future)

### 7.1 When to Consider ML

**NOT YET recommended because:**
- Small sample sizes per client (< 1000 posts)
- High variance in content types
- Complexity overhead for single developer
- Simple rule-based systems work well initially

**Consider ML when:**
1. Client has 1000+ posts with consistent metrics
2. Rule-based predictions plateau in accuracy
3. Need to analyze caption text/sentiment (NLP)
4. Want to predict viral content with >70% accuracy

---

### 7.2 Potential ML Applications (Month 6+)

#### A. Caption NLP Analysis
**Goal:** Extract themes, sentiment, topics from captions automatically

**Approach:**
- Pre-trained BERT/GPT-4 embeddings
- Classify sentiment (positive/neutral/negative)
- Extract key phrases
- Correlate with engagement

**Business Value:**
- Auto-tag posts by theme
- Identify which phrases drive engagement
- Generate caption suggestions

---

#### B. Engagement Prediction Model
**Goal:** Predict engagement rate before posting (ML-based, not rule-based)

**Approach:**
- Features: posting time, hashtags, caption length, historical performance, platform
- Model: Random Forest or XGBoost regression
- Train on 1000+ labeled examples
- Output: predicted engagement rate ± confidence interval

**Business Value:**
- "This post will likely get 5.2% ± 1.1% engagement"
- Schedule optimizer (reorder queue by prediction)

---

#### C. Anomaly Detection (Advanced)
**Goal:** Auto-detect bot traffic, spam, data quality issues

**Approach:**
- Isolation Forest or LSTM autoencoder
- Learn normal patterns, flag deviations
- Detect sudden follower spikes (possible fake followers)

**Business Value:**
- Data quality monitoring
- Alert on suspicious activity

---

## 8. Presenting Confidence & Uncertainty

### 8.1 UI Indicators

**Always include:**

1. **Sample Size Badges**
   ```
   Average Engagement: 5.2%
   [n=47 posts] ✅ Reliable

   vs

   Average Engagement: 5.2%
   [n=3 posts] ⚠️  Too few data points
   ```

2. **Confidence Intervals (when possible)**
   ```
   Predicted Engagement: 5.2% (±1.1%)

   Meaning: 68% confident the actual result will be 4.1% - 6.3%
   ```

3. **Date Ranges**
   ```
   Performance: 5.2% engagement
   Period: Last 30 days (Jan 1 - Jan 30, 2026)
   vs Previous: 4.8% engagement (Dec 1-30, 2025)
   ```

4. **Visual Indicators**
   - Green ✅: High confidence (n≥30, low variance)
   - Yellow ⚠️: Medium confidence (n=10-29, moderate variance)
   - Red 🔴: Low confidence (n<10, high variance)

---

### 8.2 Plain Language Uncertainty

**Bad:**
> "Your engagement rate is 5.2% with a coefficient of variation of 0.42 and a 95% CI of [4.1, 6.3]"

**Good:**
> "Your average engagement is 5.2%, but your posts vary quite a bit - some hit 10%, others get 2%. We're 95% confident your true average is between 4-6%."

**Even Better:**
> "You typically get 4-6% engagement per post. Your best posts reach 10%, but inconsistent ones drop to 2%. Stick to your proven formula (tutorial reels on Tuesday mornings) for more consistent results."

---

## 9. Implementation Priority

### Phase 1: Foundation (Month 1-2) ✅ CURRENT
- [x] Basic engagement rate calculation
- [x] Top posts view
- [x] Platform comparison
- [x] Hashtag performance

### Phase 2: Context (Month 2-3) ← WE ARE HERE
- [ ] Add watch time metrics (migration 007)
- [ ] Industry benchmarks table
- [ ] Month-over-month comparison
- [ ] Confidence indicators

### Phase 3: Recommendations (Month 3-4)
- [ ] Best posting times analysis
- [ ] Content recommendation engine
- [ ] Predictive scoring (rule-based)
- [ ] Anomaly detection (viral posts)

### Phase 4: Advanced Analytics (Month 4-6)
- [ ] Correlation analysis
- [ ] Weekly insights summaries
- [ ] Performance alerts
- [ ] Trend forecasting

### Phase 5: ML Exploration (Month 6+)
- [ ] Caption NLP
- [ ] Engagement prediction model
- [ ] Advanced anomaly detection

---

## 10. Summary: Analytics Principles

**ALWAYS:**
1. ✅ Provide context (benchmarks, historical comparison)
2. ✅ Use plain language ("2× better than average" not "212% of median")
3. ✅ Give actionable recommendations ("Post reels on Tuesday 9 AM")
4. ✅ Show confidence/sample size
5. ✅ Focus on quality metrics (completion rate, saves) over vanity metrics

**NEVER:**
1. ❌ Show raw metrics without interpretation
2. ❌ Use technical jargon (IQR, z-score, coefficient of variation)
3. ❌ Make claims with insufficient data
4. ❌ Ignore statistical significance
5. ❌ Present data without actionable next steps

**Remember:** Business users don't want to become data scientists. They want to know:
- "Is this good or bad?"
- "What should I do differently?"
- "Will it improve my results?"

That's the entire job of analytics.

---

## Sources

This strategy is based on 2026 industry research and best practices:

**Social Media KPIs & Benchmarks:**
- [Social media KPIs you should be tracking in 2026](https://planable.io/blog/social-media-kpis/)
- [4 Social Media KPIs To Track in 2026](https://www.designrush.com/agency/social-media-marketing/trends/social-media-kpis)
- [The Ultimate Guide to Social Media Data, Statistics & Analytics (2026)](https://improvado.io/blog/social-media-data)
- [21 social media metrics you must track for success in 2026](https://blog.hootsuite.com/social-media-metrics/)
- [Social Media KPIs That Drive Business Growth](https://sproutsocial.com/insights/social-media-kpis/)

**Time Series & Anomaly Detection:**
- [Detecting Anomalies in Social Media Volume Time Series](https://towardsdatascience.com/detecting-anomalies-in-social-media-volume-time-series-9cae614a11d0/)
- [Deep Learning for Time Series Anomaly Detection: A Survey](https://arxiv.org/html/2211.05244v3)
- [Introducing practical and robust anomaly detection in a time series](https://blog.x.com/engineering/en_us/a/2015/introducing-practical-and-robust-anomaly-detection-in-a-time-series)

**Statistical Methods:**
- [Statistical literacy guide: Confidence intervals and statistical significance](https://researchbriefings.files.parliament.uk/documents/SN04448/SN04448.pdf)
- [Maximizing Social Media User Engagement Through Predictive Analytics](https://www.mdpi.com/2076-3417/15/21/11720)

---

**Document Status:** ✅ Complete - Ready for database team implementation
**Next Steps:** Implement Migration 007 (watch time metrics + industry benchmarks)
**Contact:** Data Science Team
