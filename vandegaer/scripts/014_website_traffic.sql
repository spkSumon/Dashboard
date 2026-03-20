-- Migration 014: Website Traffic Correlation
-- Adds website analytics and post traffic correlation tables
-- Created: 2026-02-07
-- Purpose: Track website traffic from social media and correlate to posts

CREATE TABLE website_analytics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  source VARCHAR(50) NOT NULL,  -- 'google_business', 'fathom'
  date DATE NOT NULL,
  page_views INT DEFAULT 0,
  unique_visitors INT DEFAULT 0,
  referral_visits INT DEFAULT 0,
  referral_source VARCHAR(100),  -- 'tiktok', 'instagram', etc.
  bounce_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_session_duration INT DEFAULT 0,  -- seconds
  conversions INT DEFAULT 0,
  data_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY (client_id, source, date),
  INDEX idx_client_date (client_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_website_traffic (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  post_id INT NOT NULL,
  date DATE NOT NULL,
  referral_visits INT DEFAULT 0,
  referral_source VARCHAR(50),
  bounce_rate DECIMAL(5,2) DEFAULT 0.00,
  avg_session_duration INT DEFAULT 0,
  conversions INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_client_post (client_id, post_id),
  INDEX idx_client_date (client_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
