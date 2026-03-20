-- Migration 013: Competitor Analysis
-- Adds competitor tracking and metrics tables
-- Created: 2026-02-07
-- Purpose: Track competitor performance and enable benchmarking

CREATE TABLE competitors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  competitor_name VARCHAR(100) NOT NULL,
  platform VARCHAR(50) NOT NULL,
  profile_url TEXT,
  profile_handle VARCHAR(100),
  tracking_enabled BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_platform (client_id, platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competitor_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  competitor_id INT NOT NULL,
  date DATE NOT NULL,
  followers INT DEFAULT 0,
  following INT DEFAULT 0,
  total_posts INT DEFAULT 0,
  avg_engagement_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_views INT DEFAULT 0,
  posting_frequency DECIMAL(4,2) DEFAULT 0.00,  -- posts per day
  top_hashtags JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE,
  UNIQUE KEY (competitor_id, date),
  INDEX idx_competitor_date (competitor_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
