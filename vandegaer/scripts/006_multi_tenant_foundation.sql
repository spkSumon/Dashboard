-- ============================================
-- MIGRATION 006: Multi-Tenant Foundation
-- ============================================
-- Adds multi-tenant support to existing schema
-- Backwards compatible - doesn't break existing data
-- ============================================

USE social_media_analytics;

-- ============================================
-- STEP 1: Enhance USERS table
-- ============================================

-- Add new fields to users
ALTER TABLE users
  ADD COLUMN email VARCHAR(255) UNIQUE AFTER username,
  ADD COLUMN plan ENUM('starter', 'professional', 'agency') DEFAULT 'starter' AFTER password_hash,
  ADD COLUMN max_clients INT DEFAULT 1 AFTER plan,
  ADD COLUMN created_at_new TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER max_clients;

-- Update existing admin user
UPDATE users SET
  email = 'admin@socialbit.local',
  plan = 'agency',
  max_clients = 999
WHERE username = 'admin';

-- Add index
ALTER TABLE users ADD INDEX idx_email (email);


-- ============================================
-- STEP 2: Create CLIENTS table
-- ============================================

CREATE TABLE IF NOT EXISTS clients (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,

  -- Client Info
  name VARCHAR(255) NOT NULL,
  company_name VARCHAR(255),
  industry VARCHAR(100),
  website VARCHAR(500),

  -- Status
  is_active BOOLEAN DEFAULT TRUE,
  notes TEXT,

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_user (user_id),
  INDEX idx_active (is_active),
  INDEX idx_created (created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- STEP 3: Create SOCIAL_ACCOUNTS table
-- ============================================

CREATE TABLE IF NOT EXISTS social_accounts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  -- Platform Info
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube') NOT NULL,
  platform_user_id VARCHAR(255) NOT NULL,
  username VARCHAR(255),
  display_name VARCHAR(255),
  profile_picture_url VARCHAR(500),

  -- OAuth Tokens (store encrypted!)
  access_token TEXT,
  refresh_token TEXT,
  token_expires_at TIMESTAMP NULL,

  -- Sync Status
  is_active BOOLEAN DEFAULT TRUE,
  last_sync_at TIMESTAMP NULL,
  sync_status ENUM('pending', 'syncing', 'success', 'error') DEFAULT 'pending',
  sync_error TEXT,

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_client (client_id),
  INDEX idx_platform (platform),
  INDEX idx_active (is_active),
  INDEX idx_last_sync (last_sync_at),

  -- Unique constraint: one platform account per client
  UNIQUE KEY unique_client_platform_user (client_id, platform, platform_user_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- STEP 4: Create CONTENT_GROUPS table
-- ============================================

CREATE TABLE IF NOT EXISTS content_groups (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_id INT NOT NULL,

  -- Group Info
  name VARCHAR(255),
  description TEXT,
  primary_post_id INT, -- The "main" post (usually first one posted)

  -- Metadata
  campaign_tag VARCHAR(100),

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_client (client_id),
  INDEX idx_campaign (campaign_tag),
  INDEX idx_created (created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- STEP 5: Update POSTS table
-- ============================================

-- Add new columns
ALTER TABLE posts
  ADD COLUMN client_id INT AFTER id,
  ADD COLUMN social_account_id INT AFTER client_id,
  ADD COLUMN content_group_id INT AFTER social_account_id;

-- Add indexes
ALTER TABLE posts ADD INDEX idx_client (client_id);
ALTER TABLE posts ADD INDEX idx_social_account (social_account_id);
ALTER TABLE posts ADD INDEX idx_content_group (content_group_id);

-- Add foreign keys (nullable for now - backwards compatible)
-- We'll populate client_id later with default client
ALTER TABLE posts
  ADD CONSTRAINT fk_posts_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE posts
  ADD CONSTRAINT fk_posts_social_account
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL;

ALTER TABLE posts
  ADD CONSTRAINT fk_posts_content_group
    FOREIGN KEY (content_group_id) REFERENCES content_groups(id) ON DELETE SET NULL;


-- ============================================
-- STEP 6: Create DEFAULT CLIENT for existing data
-- ============================================

-- Create a default client for admin user
INSERT INTO clients (user_id, name, company_name, is_active, notes)
VALUES (
  (SELECT id FROM users WHERE username = 'admin' LIMIT 1),
  'Default Client',
  'My Business',
  TRUE,
  'Auto-created during migration - contains all existing posts'
);

-- Get the default client ID
SET @default_client_id = LAST_INSERT_ID();

-- Assign all existing posts to default client
UPDATE posts
SET client_id = @default_client_id
WHERE client_id IS NULL;


-- ============================================
-- STEP 7: Update OTHER TABLES (if needed)
-- ============================================

-- content_planning (if it needs client_id)
ALTER TABLE content_planning
  ADD COLUMN client_id INT AFTER id,
  ADD INDEX idx_client (client_id);

UPDATE content_planning
SET client_id = @default_client_id
WHERE client_id IS NULL;


-- ============================================
-- STEP 8: Create HELPER VIEWS
-- ============================================

-- Client dashboard overview
CREATE OR REPLACE VIEW client_overview AS
SELECT
  c.id as client_id,
  c.name as client_name,
  c.company_name,
  u.username as owner_username,
  COUNT(DISTINCT sa.id) as connected_accounts,
  COUNT(DISTINCT p.id) as total_posts,
  SUM(p.views) as total_views,
  SUM(p.likes) as total_likes,
  AVG(p.engagement_rate) as avg_engagement_rate,
  MAX(p.posted_date) as latest_post_date
FROM clients c
INNER JOIN users u ON c.user_id = u.id
LEFT JOIN social_accounts sa ON sa.client_id = c.id AND sa.is_active = TRUE
LEFT JOIN posts p ON p.client_id = c.id
WHERE c.is_active = TRUE
GROUP BY c.id, c.name, c.company_name, u.username;


-- ============================================
-- STEP 9: Update EXISTING VIEWS (add client filter)
-- ============================================

-- Update top_posts view to be client-aware
CREATE OR REPLACE VIEW top_posts_by_client AS
SELECT
  p.client_id,
  c.name as client_name,
  p.id,
  p.platform,
  p.post_type,
  p.topic,
  p.caption,
  p.posted_date,
  p.views,
  p.likes,
  p.comments,
  p.shares,
  p.saves,
  p.engagement_rate,
  (p.likes + p.comments + p.shares) as total_engagement
FROM posts p
INNER JOIN clients c ON p.client_id = c.id
WHERE p.posted_date IS NOT NULL
  AND c.is_active = TRUE
ORDER BY p.engagement_rate DESC;


-- ============================================
-- MIGRATION COMPLETE
-- ============================================

SELECT 'Migration 006 completed successfully!' as status;
SELECT 'New tables: clients, social_accounts, content_groups' as tables_added;
SELECT 'Posts table updated with client_id, social_account_id, content_group_id' as posts_updated;
SELECT CONCAT('Default client created with ID: ', @default_client_id) as default_client;
SELECT 'All existing posts assigned to default client' as data_migration;
SELECT 'Run this migration on BOTH dev and production' as note;
