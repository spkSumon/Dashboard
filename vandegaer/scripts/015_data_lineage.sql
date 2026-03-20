-- Migration 015: Data Lineage (Conflict Resolution)
-- Adds data lineage tracking for conflict resolution
-- Created: 2026-02-07
-- Purpose: Track data source and priority for conflict resolution (API vs CSV vs Manual)

CREATE TABLE data_lineage (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,
  entity_type VARCHAR(50) NOT NULL,  -- 'post', 'metric', 'hashtag'
  entity_id INT NOT NULL,
  field_name VARCHAR(100) NOT NULL,
  old_value TEXT,
  new_value TEXT,
  source VARCHAR(50) NOT NULL,  -- 'api', 'metricool', 'csv', 'manual'
  priority INT NOT NULL,  -- 1=API, 2=Metricool, 3=CSV, 4=Manual
  resolution_reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_client_created (client_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
