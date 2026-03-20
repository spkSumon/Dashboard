-- Migration 016: Google Business Integration
-- Adds Google Business locations and metrics tracking
-- Created: 2026-02-07
-- Purpose: Track Google Business Profile performance (search, maps, actions)

CREATE TABLE google_business_locations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  location_id VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  address TEXT,
  phone VARCHAR(50),
  website VARCHAR(255),
  enabled BOOLEAN DEFAULT TRUE,
  last_synced_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY (client_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE google_business_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  location_id INT NOT NULL,
  date DATE NOT NULL,
  search_views INT DEFAULT 0,
  maps_views INT DEFAULT 0,
  calls INT DEFAULT 0,
  direction_requests INT DEFAULT 0,
  website_clicks INT DEFAULT 0,
  photo_views INT DEFAULT 0,
  reviews_count INT DEFAULT 0,
  avg_rating DECIMAL(2,1) DEFAULT 0.0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (location_id) REFERENCES google_business_locations(id) ON DELETE CASCADE,
  UNIQUE KEY (location_id, date),
  INDEX idx_location_date (location_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
