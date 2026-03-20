-- ============================================
-- DATABASE SCHEMA - POC VANILLA GPT
-- ============================================
-- Complete database setup voor Social Media Analytics POC
-- Gebaseerd op schema-analytics-v2.sql met integratie van 002/004 migrations
-- ============================================

-- Database aanmaken (indien nog niet bestaat)
CREATE DATABASE IF NOT EXISTS social_media_analytics
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE social_media_analytics;

-- ============================================
-- CORE TABEL: posts
-- ============================================
-- Gemeenschappelijke basis voor alle platforms
-- Bevat zowel basis metrics als interne velden voor planning/labeling
-- ============================================

CREATE TABLE IF NOT EXISTS posts (
  id INT PRIMARY KEY AUTO_INCREMENT,

  -- Platform & Identificatie
  platform ENUM('tiktok', 'instagram', 'facebook') NOT NULL,
  platform_post_id VARCHAR(100),                   -- TikTok video_id, Instagram post_id, of AUTO_XX_001
  post_url TEXT,

  -- Content Type & Categorisatie (geïntegreerd uit 002_add_post_type_topic.sql)
  post_type VARCHAR(50),                            -- reel, story, post, carousel, video
  topic VARCHAR(100),                               -- promo, product, educational, lifestyle, etc.

  -- Content
  caption TEXT,
  hashtags TEXT,                                    -- Comma-separated (geïntegreerd uit 004)
  posted_date DATE NOT NULL,
  posted_time TIME,                                 -- Voor timing analyse

  -- Interne Velden (geïntegreerd uit 004_add_internal_fields_posts.sql)
  -- Deze wijzigen NIET de live post, alleen interne tracking
  internal_caption TEXT,                            -- Alternative caption ideeën
  internal_notes TEXT,                              -- Interne notities, learnings

  -- Basis Metrics (ALLE PLATFORMS)
  views INT DEFAULT 0,
  likes INT DEFAULT 0,
  comments INT DEFAULT 0,
  shares INT DEFAULT 0,
  saves INT DEFAULT 0,

  -- Instagram specifieke metrics (in posts tabel voor snelle queries)
  reach INT DEFAULT 0,                              -- Bereik (unieke views)
  impressions INT DEFAULT 0,                        -- Totale weergaven (kan > reach)

  -- Engagement Rate (berekend veld)
  engagement_rate DECIMAL(5,2),                     -- (likes+comments+shares)/reach * 100

  -- Metadata
  import_source ENUM('csv', 'api', 'scraping', 'manual') DEFAULT 'csv',
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- Indexes voor snelle queries
  INDEX idx_platform (platform),
  INDEX idx_posted_date (posted_date),
  INDEX idx_platform_date (platform, posted_date),
  INDEX idx_engagement (engagement_rate),
  INDEX idx_post_type (post_type),
  INDEX idx_topic (topic)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- USERS TABEL
-- ============================================
-- Simpele auth voor POC
-- ============================================

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- METRICS HISTORY (Time-series data)
-- ============================================
-- Track metrics over tijd
-- Essentieel voor trending en groei tracking
-- ============================================

CREATE TABLE IF NOT EXISTS metrics_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  post_id INT NOT NULL,

  -- Snapshot van metrics op een specifiek moment
  views INT DEFAULT 0,
  likes INT DEFAULT 0,
  comments INT DEFAULT 0,
  shares INT DEFAULT 0,
  saves INT DEFAULT 0,
  reach INT DEFAULT 0,
  impressions INT DEFAULT 0,

  -- Wanneer werd deze snapshot genomen
  snapshot_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_post_date (post_id, snapshot_date)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- HASHTAG PERFORMANCE TRACKING
-- ============================================
-- Welke hashtags presteren goed?
-- Aparte tabel voor analyse
-- ============================================

CREATE TABLE IF NOT EXISTS hashtag_performance (
  id INT PRIMARY KEY AUTO_INCREMENT,

  hashtag VARCHAR(100) NOT NULL,                   -- zonder #
  platform ENUM('tiktok', 'instagram', 'facebook'),

  -- Aggregated stats
  total_posts INT DEFAULT 0,
  total_views INT DEFAULT 0,
  total_likes INT DEFAULT 0,
  avg_engagement_rate DECIMAL(5,2),

  last_used DATE,

  UNIQUE KEY unique_hashtag_platform (hashtag, platform),
  INDEX idx_hashtag (hashtag),
  INDEX idx_performance (avg_engagement_rate)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- POST_HASHTAGS (Junction Table)
-- ============================================
-- Many-to-many relatie tussen posts en hashtags
-- ============================================

CREATE TABLE IF NOT EXISTS post_hashtags (
  post_id INT NOT NULL,
  hashtag_id INT NOT NULL,

  PRIMARY KEY (post_id, hashtag_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (hashtag_id) REFERENCES hashtag_performance(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TIKTOK DEMOGRAPHICS
-- ============================================
-- Wie kijkt naar je content?
-- Referenced in AnalyticsRepository.php
-- ============================================

CREATE TABLE IF NOT EXISTS tiktok_demographics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  post_id INT NOT NULL,

  -- Gender (percentages)
  gender_male_pct DECIMAL(5,2),
  gender_female_pct DECIMAL(5,2),

  -- Age Groups (percentages)
  age_13_17_pct DECIMAL(5,2),
  age_18_24_pct DECIMAL(5,2),
  age_25_34_pct DECIMAL(5,2),
  age_35_44_pct DECIMAL(5,2),
  age_45_54_pct DECIMAL(5,2),
  age_55_plus_pct DECIMAL(5,2),

  -- Top Locations (JSON voor flexibiliteit)
  top_countries JSON,                              -- ["Belgium", "Netherlands", "Germany"]

  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_post (post_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- IMPORT HISTORY
-- ============================================
-- Track welke CSV's/imports zijn gedaan
-- Voorkomt duplicaten
-- ============================================

CREATE TABLE IF NOT EXISTS import_history (
  id INT PRIMARY KEY AUTO_INCREMENT,

  filename VARCHAR(255),
  platform ENUM('tiktok', 'instagram', 'facebook'),
  import_type ENUM('csv', 'api', 'scraping', 'manual'),

  rows_imported INT DEFAULT 0,
  rows_skipped INT DEFAULT 0,                      -- Duplicaten
  rows_failed INT DEFAULT 0,                       -- Validatie fouten

  imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_filename (filename),
  INDEX idx_imported_at (imported_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- DEFAULT USER (voor POC testing)
-- ============================================
-- Username: admin
-- Password: admin123
-- ============================================

-- Password hash voor 'admin123' (bcrypt)
INSERT INTO users (username, password_hash) VALUES
  ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;


-- ============================================
-- ANALYTICS VIEWS (voor snelle queries)
-- ============================================

-- Top performing posts (cross-platform)
CREATE OR REPLACE VIEW top_posts AS
SELECT
  id,
  platform,
  post_type,
  topic,
  caption,
  posted_date,
  views,
  likes,
  comments,
  shares,
  saves,
  engagement_rate,
  (likes + comments + shares) as total_engagement
FROM posts
WHERE posted_date IS NOT NULL
ORDER BY engagement_rate DESC
LIMIT 100;

-- Platform vergelijking
CREATE OR REPLACE VIEW platform_comparison AS
SELECT
  platform,
  COUNT(*) as total_posts,
  AVG(views) as avg_views,
  AVG(likes) as avg_likes,
  AVG(engagement_rate) as avg_engagement_rate,
  SUM(views) as total_views
FROM posts
WHERE posted_date IS NOT NULL
GROUP BY platform;

-- Hashtag leaderboard
CREATE OR REPLACE VIEW hashtag_leaderboard AS
SELECT
  hashtag,
  platform,
  total_posts,
  avg_engagement_rate,
  total_views
FROM hashtag_performance
ORDER BY avg_engagement_rate DESC
LIMIT 50;


-- ============================================
-- SETUP COMPLETE
-- ============================================

SELECT 'Database schema created successfully!' as status;
SELECT 'Default user created: admin / admin123' as user_info;
SELECT 'Run next: 001_create_settings_table.sql' as next_step;
