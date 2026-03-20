-- Migration 017: Website Analytics Integration
-- Purpose: Store website traffic data from Google Analytics 4 and Fathom Analytics
-- Date: 2026-02-07
-- Dependencies: Migration 015 (data_lineage table)

-- Drop existing tables if re-running migration (reverse order for foreign keys)
DROP TABLE IF EXISTS post_traffic_correlation;
DROP TABLE IF EXISTS website_pages;
DROP TABLE IF EXISTS website_referrers;
DROP TABLE IF EXISTS website_traffic;

-- Website traffic metrics table
-- Stores daily aggregated website analytics data from GA4 and Fathom
CREATE TABLE website_traffic (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    date DATE NOT NULL,
    source VARCHAR(100) NOT NULL COMMENT 'Analytics source: ga4, fathom',

    -- Traffic metrics
    page_views INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total page views',
    unique_visitors INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unique visitors/users',
    sessions INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total sessions (GA4 only)',

    -- Engagement metrics
    avg_session_duration DECIMAL(10,2) DEFAULT NULL COMMENT 'Average session duration in seconds',
    bounce_rate DECIMAL(5,2) DEFAULT NULL COMMENT 'Bounce rate percentage',

    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for efficient querying
    INDEX idx_client_date (client_id, date),
    INDEX idx_source (source),
    INDEX idx_date (date),

    -- Constraints
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_source (client_id, date, source) COMMENT 'One record per day per source'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Daily website traffic metrics from GA4 and Fathom Analytics';

-- Website referrers table
-- Tracks traffic sources (social media, search, direct, etc.)
CREATE TABLE website_referrers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    date DATE NOT NULL,
    source VARCHAR(100) NOT NULL COMMENT 'Analytics source: ga4, fathom',

    -- Referrer identification
    referrer_hostname VARCHAR(255) NOT NULL COMMENT 'Referrer domain (e.g., instagram.com, t.co)',
    referrer_medium VARCHAR(50) DEFAULT NULL COMMENT 'GA4 medium (e.g., referral, social, organic)',

    -- Traffic from this referrer
    sessions INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sessions from this referrer',
    page_views INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Page views from this referrer',
    unique_visitors INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unique visitors from this referrer',

    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_client_date (client_id, date),
    INDEX idx_referrer (referrer_hostname),
    INDEX idx_date (date),

    -- Constraints
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_referrer (client_id, date, source, referrer_hostname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Daily referrer traffic data for social media correlation';

-- Website pages table
-- Tracks page-level traffic metrics
CREATE TABLE website_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    date DATE NOT NULL,
    source VARCHAR(100) NOT NULL COMMENT 'Analytics source: ga4, fathom',

    -- Page identification
    page_path VARCHAR(500) NOT NULL COMMENT 'URL path (e.g., /blog/my-article)',
    page_title VARCHAR(255) DEFAULT NULL COMMENT 'Page title (GA4 only)',

    -- Page metrics
    page_views INT UNSIGNED NOT NULL DEFAULT 0,
    unique_visitors INT UNSIGNED NOT NULL DEFAULT 0,
    sessions INT UNSIGNED DEFAULT NULL COMMENT 'Sessions starting on this page (landing page)',
    avg_time_on_page DECIMAL(10,2) DEFAULT NULL COMMENT 'Average time spent on page in seconds',

    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_client_date (client_id, date),
    INDEX idx_page_path (page_path(255)),
    INDEX idx_date (date),

    -- Constraints
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_page (client_id, date, source, page_path(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Daily page-level traffic metrics';

-- Post traffic correlation table
-- Links social media posts to website traffic spikes
CREATE TABLE post_traffic_correlation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    post_id INT NOT NULL COMMENT 'Social media post that drove traffic',

    -- Traffic window (typically 24-48h after post)
    traffic_date DATE NOT NULL COMMENT 'Date of traffic spike',

    -- Traffic attribution
    referrer_hostname VARCHAR(255) DEFAULT NULL COMMENT 'Referrer (if identifiable)',
    estimated_visits INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Estimated visits from this post',

    -- Attribution method
    attribution_method ENUM('referrer', 'timing', 'utm', 'manual') NOT NULL DEFAULT 'timing'
        COMMENT 'How this correlation was determined',
    confidence_score DECIMAL(3,2) DEFAULT NULL COMMENT 'Confidence score 0.00-1.00',

    -- Metadata
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_client_post (client_id, post_id),
    INDEX idx_traffic_date (traffic_date),

    -- Constraints
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_date (post_id, traffic_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Correlates social posts with website traffic increases';

-- Insert data lineage tracking for new tables (SKIPPED - data_lineage schema different)
-- INSERT INTO data_lineage (client_id, entity_type, entity_id, source_system, source_timestamp, created_at)
-- SELECT
--     1 as client_id,
--     'migration' as entity_type,
--     '017' as entity_id,
--     'manual_migration' as source_system,
--     NOW() as source_timestamp,
--     NOW() as created_at
-- ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Migration complete
SELECT 'Migration 017 completed: Website analytics tables created' as status;
