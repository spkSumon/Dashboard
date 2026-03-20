-- Migration 012: Hashtag Tracking System
-- Adds hashtag tracking and recommendations tables
-- Created: 2026-02-07
-- Purpose: Track hashtag performance and generate recommendations

CREATE TABLE hashtag_tracking (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  hashtag VARCHAR(100) NOT NULL,
  total_uses INT DEFAULT 0,
  avg_views DECIMAL(12,2) DEFAULT 0.00,
  avg_engagement DECIMAL(5,2) DEFAULT 0.00,
  avg_completion_rate DECIMAL(5,2) DEFAULT 0.00,
  best_performing_post_id INT,
  worst_performing_post_id INT,
  last_used_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (best_performing_post_id) REFERENCES posts(id) ON DELETE SET NULL,
  UNIQUE KEY (client_id, hashtag),
  INDEX idx_client_performance (client_id, avg_engagement DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hashtag_recommendations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  hashtag VARCHAR(100) NOT NULL,
  score DECIMAL(5,2) DEFAULT 0.00,
  reason TEXT,
  competitors_using INT DEFAULT 0,
  trending BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_score (client_id, score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
