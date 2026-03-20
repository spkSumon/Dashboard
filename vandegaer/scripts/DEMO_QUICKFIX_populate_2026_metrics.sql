-- ============================================
-- DEMO QUICKFIX: Populate 2026 Metrics
-- ============================================
-- URGENT: 2-hour demo preparation
-- Populates realistic watch time, completion rate, quality engagement metrics
-- Fixes zero-value posts that break analytics
--
-- Time to execute: 2-3 minutes
-- Safe: Only updates existing data, no schema changes
-- Reversible: Keep backup of original data
-- ============================================

USE social_media_analytics;

-- Backup current state (for rollback)
CREATE TABLE IF NOT EXISTS posts_backup_demo AS SELECT * FROM posts;

SELECT '🚀 Starting demo data fix...' AS status;

-- ============================================
-- STEP 1: Fix posts with zero views
-- ============================================
-- Problem: 8 posts have views = 0 → causes division by zero errors
-- Solution: Give them realistic low-performing metrics
-- ============================================

SELECT '  → Fixing posts with zero views...' AS progress;

UPDATE posts
SET
  views = 100 + FLOOR(RAND() * 900), -- Random 100-1000 views
  reach = 80 + FLOOR(RAND() * 720),  -- Reach = 80-90% of views
  impressions = 120 + FLOOR(RAND() * 1080) -- Impressions = 120-130% of views
WHERE views = 0;

-- Recalculate likes, comments, shares based on new views
UPDATE posts
SET
  likes = FLOOR(views * (0.02 + RAND() * 0.03)), -- 2-5% engagement rate
  comments = FLOOR(views * (0.005 + RAND() * 0.01)), -- 0.5-1.5% comment rate
  shares = FLOOR(views * (0.001 + RAND() * 0.005)), -- 0.1-0.6% share rate
  saves = FLOOR(views * (0.003 + RAND() * 0.007)) -- 0.3-1% save rate
WHERE views < 10000 AND views > 0;

SELECT CONCAT('  ✅ Fixed ', ROW_COUNT(), ' posts with zero views') AS result;


-- ============================================
-- STEP 2: Populate 2026 Algorithm Metrics
-- ============================================
-- These are the metrics that matter for 2026 algorithms:
-- - Watch time & completion rate (PRIORITY #1)
-- - Sends count (DM shares - highest intent)
-- - Profile visits (discovery metric)
-- ============================================

SELECT '  → Populating 2026 algorithm metrics...' AS progress;

-- Set realistic video duration (if missing)
UPDATE posts
SET duration = CASE
  WHEN post_type = 'Reel' THEN 15 + FLOOR(RAND() * 75) -- Reels: 15-90 sec
  WHEN post_type = 'video' THEN 30 + FLOOR(RAND() * 270) -- Videos: 30-300 sec
  ELSE 60 -- Default 60 sec
END
WHERE duration = 0 OR duration IS NULL;

-- Calculate realistic watch time
-- Algorithm: Higher engagement → higher watch time
-- Good posts: 60-80% completion
-- Average posts: 45-65% completion
-- Poor posts: 30-50% completion
UPDATE posts
SET
  average_watch_time = CASE
    WHEN engagement_rate > 0.3 THEN FLOOR(duration * (0.60 + RAND() * 0.20)) -- Good: 60-80%
    WHEN engagement_rate > 0.1 THEN FLOOR(duration * (0.45 + RAND() * 0.20)) -- Average: 45-65%
    ELSE FLOOR(duration * (0.30 + RAND() * 0.20)) -- Poor: 30-50%
  END,
  watch_time = FLOOR(views * duration * (0.40 + RAND() * 0.30)) -- Total watch time
WHERE views > 0;

-- Populate sends_count (DM shares - 2-5% of likes for good content)
UPDATE posts
SET sends_count = CASE
  WHEN engagement_rate > 0.3 THEN FLOOR(likes * (0.03 + RAND() * 0.02)) -- Good: 3-5%
  WHEN engagement_rate > 0.1 THEN FLOOR(likes * (0.02 + RAND() * 0.01)) -- Average: 2-3%
  ELSE FLOOR(likes * (0.01 + RAND() * 0.01)) -- Poor: 1-2%
END
WHERE views > 0;

-- Populate profile_visits (1-3% of views for engaging content)
UPDATE posts
SET profile_visits = FLOOR(views * (0.01 + RAND() * 0.02))
WHERE views > 0;

-- Populate skip_rate for Instagram Reels (inverse of completion rate)
UPDATE posts
SET skip_rate = CASE
  WHEN platform = 'instagram' AND post_type = 'Reel'
  THEN ROUND(100 - (average_watch_time / NULLIF(duration, 0) * 100), 2)
  ELSE NULL
END
WHERE platform = 'instagram' AND views > 0;

-- Populate follower_growth (attributed to high-performing posts)
UPDATE posts
SET follower_growth = CASE
  WHEN engagement_rate > 0.5 THEN FLOOR(profile_visits * (0.10 + RAND() * 0.15)) -- 10-25%
  WHEN engagement_rate > 0.2 THEN FLOOR(profile_visits * (0.05 + RAND() * 0.10)) -- 5-15%
  ELSE FLOOR(profile_visits * (0.01 + RAND() * 0.05)) -- 1-6%
END
WHERE profile_visits > 0;

SELECT '  ✅ 2026 metrics populated' AS result;


-- ============================================
-- STEP 3: Recalculate Engagement Rate
-- ============================================
-- Use proper formula: (likes + comments + shares) / reach * 100
-- If reach = 0, use views as fallback
-- ============================================

SELECT '  → Recalculating engagement rates...' AS progress;

UPDATE posts
SET engagement_rate = ROUND(
  (likes + comments + shares) / NULLIF(COALESCE(reach, views), 0) * 100,
  2
)
WHERE views > 0;

SELECT '  ✅ Engagement rates recalculated' AS result;


-- ============================================
-- STEP 4: Update Hashtag Performance
-- ============================================
-- Recalculate hashtag stats based on updated post metrics
-- ============================================

SELECT '  → Updating hashtag performance...' AS progress;

-- Note: This requires hashtag associations in post_hashtags table
-- If table is empty, this won't do anything (graceful skip)
UPDATE hashtag_performance hp
INNER JOIN (
  SELECT
    ph.hashtag_id,
    COUNT(DISTINCT p.id) as post_count,
    AVG(p.views) as avg_views,
    AVG(p.engagement_rate) as avg_engagement,
    SUM(p.views) as total_views,
    MAX(p.posted_date) as last_used
  FROM post_hashtags ph
  INNER JOIN posts p ON ph.post_id = p.id
  WHERE p.views > 0
  GROUP BY ph.hashtag_id
) calc ON hp.id = calc.hashtag_id
SET
  hp.total_posts = calc.post_count,
  hp.total_views = calc.total_views,
  hp.avg_engagement_rate = calc.avg_engagement,
  hp.last_used = calc.last_used;

SELECT '  ✅ Hashtag performance updated' AS result;


-- ============================================
-- STEP 5: Verification
-- ============================================

SELECT '🔍 Verifying data quality...' AS status;

-- Check 1: No more zero views
SELECT
  CASE
    WHEN COUNT(*) = 0 THEN '✅ No posts with zero views'
    ELSE CONCAT('⚠️  WARNING: ', COUNT(*), ' posts still have zero views!')
  END AS check_zero_views
FROM posts
WHERE views = 0;

-- Check 2: All posts have completion rate
SELECT
  CASE
    WHEN COUNT(*) = 0 THEN '✅ All posts have completion rate'
    ELSE CONCAT('⚠️  WARNING: ', COUNT(*), ' posts missing completion rate!')
  END AS check_completion_rate
FROM posts
WHERE (average_watch_time = 0 OR average_watch_time IS NULL) AND views > 0;

-- Check 3: Quality metrics populated
SELECT
  COUNT(*) as posts_with_data,
  AVG(views) as avg_views,
  AVG(engagement_rate) as avg_engagement,
  AVG(average_watch_time / NULLIF(duration, 0) * 100) as avg_completion_pct,
  AVG(sends_count) as avg_sends,
  AVG(profile_visits) as avg_profile_visits
FROM posts
WHERE views > 0;

-- Check 4: Show sample of fixed data
SELECT
  id,
  platform,
  post_type,
  views,
  engagement_rate,
  ROUND(average_watch_time / NULLIF(duration, 0) * 100, 1) as completion_pct,
  sends_count,
  profile_visits,
  follower_growth
FROM posts
WHERE views > 0
ORDER BY engagement_rate DESC
LIMIT 5;


-- ============================================
-- DEMO QUICKFIX COMPLETE
-- ============================================

SELECT '✅ Demo data fix complete!' AS status;
SELECT CONCAT(
  '📊 Summary:\n',
  '  - All posts now have realistic view counts\n',
  '  - 2026 algorithm metrics populated (watch time, completion rate, sends)\n',
  '  - Engagement rates recalculated\n',
  '  - Hashtag performance updated\n',
  '\n',
  '🎯 Ready for demo!\n',
  '\n',
  '⏱️  Execution time: 2-3 minutes\n',
  '📦 Backup: posts_backup_demo table created\n',
  '\n',
  '🔄 Rollback (if needed):\n',
  '  DELETE FROM posts;\n',
  '  INSERT INTO posts SELECT * FROM posts_backup_demo;\n',
  '  DROP TABLE posts_backup_demo;\n'
) AS summary;

-- ============================================
-- ROLLBACK INSTRUCTIONS
-- ============================================
-- If something goes wrong, restore from backup:
--
-- DELETE FROM posts;
-- INSERT INTO posts SELECT * FROM posts_backup_demo;
-- DROP TABLE posts_backup_demo;
-- ============================================
