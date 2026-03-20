-- ============================================
-- MIGRATION 020: Inbox/Messages System
-- ============================================
-- Unified inbox for social media comments & DMs
-- Created: 2026-02-07
-- Purpose: Metricool-style inbox with conversation threading
-- ============================================

USE social_media_analytics;

-- ============================================
-- CONVERSATIONS TABLE
-- ============================================
-- Groups related messages into conversations
-- One conversation per user per platform
-- ============================================

CREATE TABLE IF NOT EXISTS conversations (
  id INT PRIMARY KEY AUTO_INCREMENT,

  -- Multi-tenant
  client_id INT NOT NULL,

  -- Platform & User Info
  platform ENUM('tiktok', 'instagram', 'facebook', 'youtube', 'google_business') NOT NULL,
  platform_conversation_id VARCHAR(255),        -- Platform's internal conversation ID
  platform_user_id VARCHAR(255) NOT NULL,       -- User's ID on the platform
  username VARCHAR(100),                        -- Display username (e.g., '@john_doe')
  display_name VARCHAR(255),                    -- Full name (e.g., 'John Doe')
  avatar_url TEXT,                              -- Profile picture URL

  -- Conversation Type
  conversation_type ENUM('comment', 'dm', 'review', 'story_reply', 'mention') NOT NULL,

  -- Related Content
  post_id INT,                                  -- If comment/reply on a specific post
  post_url TEXT,                                -- Direct link to the post

  -- Status & Metadata
  status ENUM('unresolved', 'resolved', 'archived') DEFAULT 'unresolved',
  is_read BOOLEAN DEFAULT FALSE,
  is_starred BOOLEAN DEFAULT FALSE,
  priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

  -- Participant Info
  participant_count INT DEFAULT 2,              -- Usually 2 (you + them), but group DMs exist

  -- Message Summary
  last_message_preview TEXT,                    -- First 100 chars of last message
  last_message_at TIMESTAMP NULL,               -- When was the last message sent
  last_message_sender VARCHAR(50),              -- 'client' or 'user'

  -- Timestamps
  first_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,

  -- Indexes
  INDEX idx_client (client_id),
  INDEX idx_platform (platform),
  INDEX idx_status (status),
  INDEX idx_is_read (is_read),
  INDEX idx_last_message (last_message_at DESC),
  INDEX idx_client_status (client_id, status),
  UNIQUE KEY unique_client_platform_user (client_id, platform, platform_user_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- MESSAGES TABLE
-- ============================================
-- Individual messages within conversations
-- ============================================

CREATE TABLE IF NOT EXISTS messages (
  id INT PRIMARY KEY AUTO_INCREMENT,

  -- Conversation
  conversation_id INT NOT NULL,

  -- Message Content
  platform_message_id VARCHAR(255),             -- Platform's internal message ID
  message_text TEXT NOT NULL,
  message_html TEXT,                            -- HTML version (if available)

  -- Sender Info
  sender_type ENUM('client', 'user', 'system') NOT NULL,
  sender_platform_id VARCHAR(255),              -- Platform user ID of sender
  sender_name VARCHAR(255),                     -- Display name

  -- Message Type
  message_type ENUM('text', 'image', 'video', 'audio', 'sticker', 'link') DEFAULT 'text',

  -- Attachments
  attachments JSON,                             -- [{"type": "image", "url": "...", "thumbnail": "..."}]

  -- Status
  is_read BOOLEAN DEFAULT FALSE,
  read_at TIMESTAMP NULL,

  -- Metadata
  sent_at TIMESTAMP NOT NULL,                   -- When message was sent (platform time)
  imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_conversation (conversation_id),
  INDEX idx_sent_at (sent_at DESC),
  INDEX idx_sender_type (sender_type),
  INDEX idx_is_read (is_read),
  FULLTEXT INDEX idx_message_search (message_text)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- CONVERSATION_PARTICIPANTS TABLE
-- ============================================
-- Track all participants in a conversation
-- Useful for group DMs and multi-user threads
-- ============================================

CREATE TABLE IF NOT EXISTS conversation_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,

  conversation_id INT NOT NULL,

  -- Participant Info
  platform_user_id VARCHAR(255) NOT NULL,
  username VARCHAR(100),
  display_name VARCHAR(255),
  avatar_url TEXT,

  -- Participant Role
  role ENUM('owner', 'participant') DEFAULT 'participant',

  -- Timestamps
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_conversation (conversation_id),
  UNIQUE KEY unique_conversation_user (conversation_id, platform_user_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- INBOX_FILTERS TABLE (User Preferences)
-- ============================================
-- Store user's inbox filter preferences
-- ============================================

CREATE TABLE IF NOT EXISTS inbox_filters (
  id INT PRIMARY KEY AUTO_INCREMENT,

  client_id INT NOT NULL,

  -- Filter Settings
  filter_name VARCHAR(50) NOT NULL,             -- 'unresolved', 'unread', 'starred', 'platform_instagram'
  filter_config JSON,                           -- {"platforms": ["instagram"], "status": ["unresolved"]}

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign Keys
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_client (client_id),
  UNIQUE KEY unique_client_filter (client_id, filter_name)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- HELPER VIEWS
-- ============================================

-- Unresolved Conversations View
CREATE OR REPLACE VIEW inbox_unresolved AS
SELECT
  c.id,
  c.client_id,
  c.platform,
  c.username,
  c.display_name,
  c.conversation_type,
  c.last_message_preview,
  c.last_message_at,
  c.is_read,
  c.is_starred,
  c.priority,
  COUNT(m.id) as message_count,
  SUM(CASE WHEN m.is_read = FALSE AND m.sender_type = 'user' THEN 1 ELSE 0 END) as unread_count
FROM conversations c
LEFT JOIN messages m ON c.id = m.conversation_id
WHERE c.status = 'unresolved'
GROUP BY c.id
ORDER BY c.last_message_at DESC;


-- Unread Messages Count per Client
CREATE OR REPLACE VIEW inbox_unread_counts AS
SELECT
  c.client_id,
  c.platform,
  COUNT(DISTINCT c.id) as unread_conversations,
  SUM(CASE WHEN m.is_read = FALSE AND m.sender_type = 'user' THEN 1 ELSE 0 END) as unread_messages
FROM conversations c
LEFT JOIN messages m ON c.id = m.conversation_id
WHERE c.is_read = FALSE
GROUP BY c.client_id, c.platform;


-- Recent Activity View
CREATE OR REPLACE VIEW inbox_recent_activity AS
SELECT
  c.id as conversation_id,
  c.client_id,
  c.platform,
  c.username,
  c.conversation_type,
  m.message_text,
  m.sender_type,
  m.sent_at,
  c.is_read,
  c.status
FROM conversations c
INNER JOIN messages m ON c.id = m.conversation_id
WHERE m.sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY m.sent_at DESC
LIMIT 50;


-- ============================================
-- INSERT SAMPLE FILTER PRESETS
-- ============================================

-- Default filter presets will be added per client
-- INSERT INTO inbox_filters (client_id, filter_name, filter_config) VALUES
-- (1, 'unresolved', '{"status": ["unresolved"]}'),
-- (1, 'unread', '{"is_read": false}'),
-- (1, 'starred', '{"is_starred": true}'),
-- (1, 'instagram_only', '{"platforms": ["instagram"]}');


-- ============================================
-- MIGRATION COMPLETE
-- ============================================

SELECT 'Migration 020 completed successfully!' as status;
SELECT 'Tables created: conversations, messages, conversation_participants, inbox_filters' as tables;
SELECT 'Views created: inbox_unresolved, inbox_unread_counts, inbox_recent_activity' as views;
SELECT 'Ready for Metricool-style unified inbox!' as feature;
