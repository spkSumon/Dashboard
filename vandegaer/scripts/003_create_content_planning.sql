-- Content planning (POC)
-- Dit is puur "jouw" interne planning, geen automatische posting naar socials.

CREATE TABLE IF NOT EXISTS content_planning (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(50) NULL,                 -- 'instagram', 'tiktok', ...
  status VARCHAR(30) NOT NULL DEFAULT 'draft', -- draft | scheduled | published | cancelled
  scheduled_at DATETIME NULL,               -- wanneer je wil posten
  content_type VARCHAR(50) NULL,            -- reel/story/post/carousel/... (of 'video','image'...)
  title VARCHAR(200) NULL,
  caption TEXT NULL,
  hashtags TEXT NULL,
  topic VARCHAR(100) NULL,
  notes TEXT NULL,

  -- Optioneel: link naar een echte post (als het later effectief gepost werd)
  post_id INT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX (platform),
  INDEX (status),
  INDEX (scheduled_at),
  INDEX (post_id),
  CONSTRAINT fk_planning_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
