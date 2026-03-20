-- ============================================
-- TIKTOK TOKENS TABLE
-- ============================================
-- Opslag voor TikTok OAuth 2.0 tokens
-- ============================================

CREATE TABLE IF NOT EXISTS tiktok_tokens (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NULL,  -- Link naar users tabel (optioneel voor POC, kan NULL zijn)

  -- OAuth tokens
  access_token TEXT NOT NULL,
  refresh_token TEXT,
  token_type VARCHAR(20) DEFAULT 'Bearer',
  expires_at DATETIME NOT NULL,

  -- Token metadata
  scope TEXT,  -- Permissions granted (user.info.basic,video.list,video.insights)
  open_id VARCHAR(100),  -- TikTok user identifier

  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Foreign key (optional, kan NULL zijn voor POC)
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

  -- Indexes
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INFO
-- ============================================
-- Deze tabel slaat TikTok OAuth tokens op
-- Access tokens verlopen na 24 uur
-- Refresh tokens worden gebruikt om nieuwe access tokens te krijgen
-- ============================================

SELECT 'TikTok tokens table created successfully!' as status;
