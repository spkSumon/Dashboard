-- ============================================
-- MIGRATION 007: ANALYTICS ENHANCEMENT
-- ============================================
-- Adds critical quality metrics, industry benchmarks, and
-- analytics infrastructure for actionable business insights
--
-- CONTEXT: Business users need CONTEXT, not just raw data.
-- This migration adds the database foundation for:
-- - Algorithm-priority metrics (watch time, completion rate)
-- - Industry benchmark comparisons
-- - Predictive content scoring
-- - Performance tracking and recommendations
--
-- Author: Data Science Team
-- Date: 2026-02-07
-- Based on: ANALYTICS_STRATEGY.md
-- ============================================

USE social_media_analytics;

-- ============================================
-- STEP 1: ADD ALGORITHM-PRIORITY METRICS TO POSTS
-- ============================================
-- Watch time and completion rate are THE most important
-- metrics for 2026 algorithms (TikTok, Instagram Reels)

ALTER TABLE posts
  -- Video duration and watch time
  ADD COLUMN duration INT COMMENT 'Video duration in seconds',
  ADD COLUMN average_watch_time INT COMMENT 'Average watch time per view in seconds',
  ADD COLUMN completion_rate DECIMAL(5,2) COMMENT 'Calculated: (avg_watch_time/duration)*100',
  ADD COLUMN watch_time_total BIGINT COMMENT 'Total watch time across all views (seconds)',

  -- High-intent engagement signals
  -- Note: saves is already in schema, but ensuring it exists
  -- ADD COLUMN saves INT DEFAULT 0, -- Already exists
  ADD COLUMN sends_count INT DEFAULT 0 COMMENT 'DM shares (highest intent signal)',

  -- Discovery metrics
  ADD COLUMN profile_visits INT DEFAULT 0 COMMENT 'Clicks to profile from this post',
  ADD COLUMN follower_growth INT DEFAULT 0 COMMENT 'New followers attributed to this post',

  -- Platform-specific metrics
  ADD COLUMN reels_skip_rate DECIMAL(5,2) COMMENT 'Instagram Reels: percentage who skipped',
  ADD COLUMN crossposted_views INT DEFAULT 0 COMMENT 'Instagram: views on Facebook/Explore',

  -- Engagement Quality Score (weighted engagement)
  ADD COLUMN engagement_quality_score DECIMAL(5,2) COMMENT 'Weighted: likes*1 + comments*3 + shares*5 + saves*4';

-- Indexes for performance analytics queries
CREATE INDEX idx_completion_rate ON posts(completion_rate);
CREATE INDEX idx_watch_time ON posts(watch_time_total);
CREATE INDEX idx_profile_visits ON posts(profile_visits);
CREATE INDEX idx_follower_growth ON posts(follower_growth);
CREATE INDEX idx_eqs ON posts(engagement_quality_score);

-- ============================================
-- STEP 2: INDUSTRY BENCHMARKS TABLE
-- ============================================
-- Store 2026 industry averages for contextual comparison
-- Source: MEMORY.md and web research (2026 benchmarks)

CREATE TABLE IF NOT EXISTS industry_benchmarks (
  id INT PRIMARY KEY AUTO_INCREMENT,

  platform ENUM('tiktok', 'instagram', 'facebook') NOT NULL,
  industry VARCHAR(100) DEFAULT 'general' COMMENT 'e.g., retail, tech, hospitality, general',
  metric_name VARCHAR(100) NOT NULL COMMENT 'engagement_rate, completion_rate, etc.',

  -- Quartile benchmarks for performance categorization
  percentile_25 DECIMAL(10,2) COMMENT 'Bottom 25% - below average',
  percentile_50 DECIMAL(10,2) COMMENT 'Median - average performance',
  percentile_75 DECIMAL(10,2) COMMENT 'Top 25% - above average',
  percentile_90 DECIMAL(10,2) COMMENT 'Top 10% - excellent performance',

  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY unique_benchmark (platform, industry, metric_name),
  INDEX idx_platform_metric (platform, metric_name)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed with 2026 industry benchmarks
INSERT INTO industry_benchmarks (platform, industry, metric_name, percentile_25, percentile_50, percentile_75, percentile_90) VALUES
-- TikTok benchmarks
('tiktok', 'general', 'engagement_rate', 2.0, 3.7, 5.3, 8.0),
('tiktok', 'general', 'completion_rate', 50.0, 60.0, 75.0, 85.0),
('tiktok', 'general', 'saves_rate', 1.0, 2.1, 4.0, 6.0),

-- Instagram benchmarks
('instagram', 'general', 'engagement_rate', 0.30, 0.48, 3.0, 6.0),
('instagram', 'general', 'reels_skip_rate', 20.0, 15.0, 10.0, 5.0),
('instagram', 'general', 'saves_rate', 0.5, 1.5, 3.0, 5.0),

-- Facebook benchmarks
('facebook', 'general', 'engagement_rate', 0.10, 0.15, 0.50, 1.0),

-- Cross-platform benchmarks
('tiktok', 'general', 'avg_views', 500, 1500, 5000, 20000),
('instagram', 'general', 'avg_views', 300, 800, 3000, 10000),
('facebook', 'general', 'avg_views', 200, 500, 2000, 8000)

ON DUPLICATE KEY UPDATE
  percentile_25 = VALUES(percentile_25),
  percentile_50 = VALUES(percentile_50),
  percentile_75 = VALUES(percentile_75),
  percentile_90 = VALUES(percentile_90);

-- ============================================
-- STEP 3: PERFORMANCE SNAPSHOTS TABLE
-- ============================================
-- Track historical performance for "vs last month" comparisons
-- Automatically populated by nightly batch job

CREATE TABLE IF NOT EXISTS performance_snapshots (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  snapshot_date DATE NOT NULL COMMENT 'Date of this snapshot',
  period_type ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',

  -- Aggregate metrics for the period
  posts_count INT DEFAULT 0,
  total_views BIGINT DEFAULT 0,
  total_engagement INT DEFAULT 0 COMMENT 'Sum of likes+comments+shares+saves',

  avg_engagement_rate DECIMAL(5,2),
  avg_completion_rate DECIMAL(5,2),
  avg_eqs DECIMAL(5,2) COMMENT 'Average Engagement Quality Score',

  follower_count INT COMMENT 'Total followers at snapshot time',
  follower_growth_period INT COMMENT 'Net growth during this period',

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY unique_snapshot (client_id, snapshot_date, period_type),
  INDEX idx_client_date (client_id, snapshot_date)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STEP 4: USER GOALS TABLE
-- ============================================
-- Track business goals and progress
-- Enables "You're 68% toward your goal" feedback

CREATE TABLE IF NOT EXISTS user_goals (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  goal_type ENUM('engagement_rate', 'followers', 'views', 'posts_per_week') NOT NULL,
  target_value DECIMAL(10,2) NOT NULL COMMENT 'Goal target',
  current_value DECIMAL(10,2) COMMENT 'Current progress',

  deadline DATE COMMENT 'Goal deadline',
  status ENUM('active', 'achieved', 'missed', 'cancelled') DEFAULT 'active',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_goals (client_id, status)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STEP 5: RECOMMENDATIONS TABLE
-- ============================================
-- Store auto-generated actionable recommendations
-- "Post more reels on Tuesday 9 AM"

CREATE TABLE IF NOT EXISTS recommendations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  recommendation_type ENUM('posting_time', 'content_type', 'hashtag', 'topic', 'general') NOT NULL,
  title VARCHAR(255) NOT NULL COMMENT 'Short recommendation title',
  description TEXT COMMENT 'Detailed explanation',

  priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
  confidence_score DECIMAL(5,2) COMMENT '0-100 confidence in this recommendation',

  -- Supporting data
  based_on_posts INT COMMENT 'Number of posts analyzed',
  expected_improvement DECIMAL(5,2) COMMENT 'Expected % improvement if followed',

  status ENUM('active', 'dismissed', 'applied', 'expired') DEFAULT 'active',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP COMMENT 'When recommendation becomes outdated',

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_active (client_id, status),
  INDEX idx_priority (priority, created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STEP 6: CONTENT PREDICTIONS TABLE
-- ============================================
-- Store predictions for content performance
-- Before posting: "This will likely perform well"
-- After posting: Compare prediction vs actual

CREATE TABLE IF NOT EXISTS content_predictions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT COMMENT 'NULL if prediction before posting',

  -- Prediction factors (rule-based scoring)
  optimal_time_score INT DEFAULT 0 COMMENT '0-2 points: posted at optimal time',
  hashtag_score INT DEFAULT 0 COMMENT '0-5 points: using proven hashtags',
  content_type_score INT DEFAULT 0 COMMENT '0-2 points: using best-performing type',
  topic_score INT DEFAULT 0 COMMENT '0-2 points: using top-performing topic',
  historical_score INT DEFAULT 0 COMMENT '0-1 points: similar past content performed well',

  total_score INT AS (optimal_time_score + hashtag_score + content_type_score + topic_score + historical_score) STORED,

  predicted_category ENUM('low', 'medium', 'high') AS (
    CASE
      WHEN (optimal_time_score + hashtag_score + content_type_score + topic_score + historical_score) >= 8 THEN 'high'
      WHEN (optimal_time_score + hashtag_score + content_type_score + topic_score + historical_score) >= 5 THEN 'medium'
      ELSE 'low'
    END
  ) STORED,

  predicted_engagement_rate DECIMAL(5,2) COMMENT 'Predicted engagement %',
  actual_engagement_rate DECIMAL(5,2) COMMENT 'Actual result after posting',

  prediction_error DECIMAL(5,2) AS (ABS(actual_engagement_rate - predicted_engagement_rate)) STORED,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,
  INDEX idx_client_prediction (client_id, predicted_category),
  INDEX idx_accuracy (prediction_error)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STEP 7: ANALYTICS VIEWS
-- ============================================

-- Monthly performance comparison with growth %
CREATE OR REPLACE VIEW monthly_performance_comparison AS
SELECT
  p.client_id,
  DATE_FORMAT(p.posted_date, '%Y-%m') AS month,
  COUNT(*) AS posts_count,
  AVG(p.views) AS avg_views,
  AVG(p.engagement_rate) AS avg_engagement,
  AVG(p.completion_rate) AS avg_completion,

  -- Previous month metrics using LAG
  LAG(AVG(p.views)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')) AS prev_month_views,
  LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')) AS prev_month_engagement,

  -- Growth percentage
  ROUND(((AVG(p.views) - LAG(AVG(p.views)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')))
    / NULLIF(LAG(AVG(p.views)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')), 0)) * 100, 2) AS views_growth_pct,

  ROUND(((AVG(p.engagement_rate) - LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')))
    / NULLIF(LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY DATE_FORMAT(p.posted_date, '%Y-%m')), 0)) * 100, 2) AS engagement_growth_pct

FROM posts p
WHERE p.posted_date IS NOT NULL
GROUP BY p.client_id, DATE_FORMAT(p.posted_date, '%Y-%m');

-- Optimal posting schedule analysis
CREATE OR REPLACE VIEW optimal_posting_schedule AS
SELECT
  client_id,
  DAYNAME(posted_date) AS day_of_week,
  HOUR(posted_time) AS hour_of_day,
  COUNT(*) AS posts_count,
  AVG(engagement_rate) AS avg_engagement,
  AVG(views) AS avg_views,
  AVG(completion_rate) AS avg_completion,
  RANK() OVER (PARTITION BY client_id ORDER BY AVG(engagement_rate) DESC) AS engagement_rank
FROM posts
WHERE posted_date IS NOT NULL
  AND posted_time IS NOT NULL
  AND posted_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAYS)
GROUP BY client_id, DAYNAME(posted_date), HOUR(posted_time);

-- Content recommendation engine
CREATE OR REPLACE VIEW content_recommendations_generated AS
WITH ranked_performance AS (
  SELECT
    client_id,
    platform,
    post_type,
    topic,
    DAYNAME(posted_date) AS best_day,
    HOUR(posted_time) AS best_hour,
    AVG(engagement_rate) AS avg_engagement,
    AVG(completion_rate) AS avg_completion,
    COUNT(*) AS sample_size,
    RANK() OVER (PARTITION BY client_id ORDER BY AVG(engagement_rate) DESC) AS performance_rank
  FROM posts
  WHERE posted_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAYS)
    AND engagement_rate IS NOT NULL
  GROUP BY client_id, platform, post_type, topic, DAYNAME(posted_date), HOUR(posted_time)
  HAVING COUNT(*) >= 3
)
SELECT
  client_id,
  platform,
  post_type AS recommended_content_type,
  topic AS recommended_topic,
  best_day AS recommended_day,
  best_hour AS recommended_hour,
  avg_engagement AS expected_engagement,
  avg_completion AS expected_completion,
  sample_size,
  CASE
    WHEN sample_size >= 10 THEN 'high'
    WHEN sample_size >= 5 THEN 'medium'
    ELSE 'low'
  END AS confidence_level
FROM ranked_performance
WHERE performance_rank <= 3
ORDER BY client_id, performance_rank;

-- Performance dashboard summary
CREATE OR REPLACE VIEW client_performance_dashboard AS
SELECT
  c.id AS client_id,
  c.business_name,

  -- Last 30 days stats
  COUNT(p.id) AS total_posts_30d,
  COALESCE(SUM(p.views), 0) AS total_views_30d,
  COALESCE(AVG(p.engagement_rate), 0) AS avg_engagement_30d,
  COALESCE(AVG(p.completion_rate), 0) AS avg_completion_30d,
  COALESCE(AVG(p.engagement_quality_score), 0) AS avg_eqs_30d,

  -- Sample size for confidence
  COUNT(p.id) AS sample_size,
  CASE
    WHEN COUNT(p.id) < 10 THEN 'low'
    WHEN COUNT(p.id) < 30 THEN 'medium'
    ELSE 'high'
  END AS confidence_level

FROM clients c
LEFT JOIN posts p ON p.client_id = c.id
  AND p.posted_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS)
GROUP BY c.id, c.business_name;

-- Weekly insights summary
CREATE OR REPLACE VIEW weekly_insights_summary AS
SELECT
  p.client_id,
  YEARWEEK(p.posted_date) AS year_week,
  DATE(DATE_SUB(p.posted_date, INTERVAL WEEKDAY(p.posted_date) DAY)) AS week_start_date,

  -- Performance metrics
  COUNT(*) AS posts_this_week,
  AVG(p.engagement_rate) AS avg_engagement,
  AVG(p.completion_rate) AS avg_completion,
  SUM(p.views) AS total_views,
  SUM(p.profile_visits) AS total_profile_visits,
  SUM(p.follower_growth) AS net_follower_growth,

  -- Best and worst posts
  MAX(p.engagement_rate) AS best_post_engagement,
  MIN(p.engagement_rate) AS worst_post_engagement,

  -- Week-over-week growth
  LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY YEARWEEK(p.posted_date)) AS prev_week_engagement,
  ROUND(((AVG(p.engagement_rate) - LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY YEARWEEK(p.posted_date)))
    / NULLIF(LAG(AVG(p.engagement_rate)) OVER (PARTITION BY p.client_id ORDER BY YEARWEEK(p.posted_date)), 0)) * 100, 2) AS week_over_week_growth_pct

FROM posts p
WHERE p.posted_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
GROUP BY p.client_id, YEARWEEK(p.posted_date), DATE(DATE_SUB(p.posted_date, INTERVAL WEEKDAY(p.posted_date) DAY));

-- ============================================
-- STEP 8: UPDATE EXISTING METRICS HISTORY
-- ============================================
-- Add new metrics to metrics_history table for time-series tracking

ALTER TABLE metrics_history
  ADD COLUMN completion_rate DECIMAL(5,2),
  ADD COLUMN average_watch_time INT,
  ADD COLUMN profile_visits INT DEFAULT 0,
  ADD COLUMN follower_growth INT DEFAULT 0,
  ADD COLUMN engagement_quality_score DECIMAL(5,2);

-- ============================================
-- STEP 9: STORED PROCEDURES
-- ============================================

-- Calculate Engagement Quality Score
DROP FUNCTION IF EXISTS calculate_eqs;

DELIMITER //
CREATE FUNCTION calculate_eqs(
  p_likes INT,
  p_comments INT,
  p_shares INT,
  p_saves INT,
  p_views INT
) RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
  DECLARE weighted_eng DECIMAL(10,2);

  -- Weighted formula: likes×1 + comments×3 + shares×5 + saves×4
  SET weighted_eng = (p_likes * 1) + (p_comments * 3) + (p_shares * 5) + (p_saves * 4);

  IF p_views > 0 THEN
    RETURN ROUND((weighted_eng / p_views) * 100, 2);
  END IF;

  RETURN 0;
END //
DELIMITER ;

-- Calculate Completion Rate
DROP FUNCTION IF EXISTS calculate_completion_rate;

DELIMITER //
CREATE FUNCTION calculate_completion_rate(
  p_avg_watch_time INT,
  p_duration INT
) RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
  IF p_duration > 0 THEN
    RETURN ROUND((p_avg_watch_time / p_duration) * 100, 2);
  END IF;

  RETURN 0;
END //
DELIMITER ;

-- ============================================
-- STEP 10: BACKFILL CALCULATED COLUMNS
-- ============================================
-- Calculate EQS for existing posts

UPDATE posts
SET engagement_quality_score = calculate_eqs(likes, comments, shares, saves, views)
WHERE views > 0;

-- Calculate completion rate for existing posts with duration data
UPDATE posts
SET completion_rate = calculate_completion_rate(average_watch_time, duration)
WHERE duration > 0 AND average_watch_time IS NOT NULL;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================

SELECT '✅ Migration 007 completed successfully!' AS status;
SELECT 'Added: Watch time metrics, industry benchmarks, recommendations, predictions' AS summary;
SELECT 'Next steps: Update PHP services to populate new metrics' AS next_step;
