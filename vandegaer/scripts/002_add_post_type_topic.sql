-- ============================================
-- DEPRECATED - NIET MEER NODIG
-- ============================================
-- Deze migratie is geïntegreerd in 000_create_database_schema.sql
--
-- Als je 000_create_database_schema.sql al hebt gerund,
-- kan je dit script OVERSLAAN.
--
-- Dit script is alleen nodig als je een oude database hebt
-- waar de posts tabel al bestaat zonder post_type en topic kolommen.
-- ============================================

-- Add post_type and topic to posts (POC labeling)
-- This enables analytics per content type (reel/story/...) and per topic (promo/product/...)

ALTER TABLE posts
  ADD COLUMN IF NOT EXISTS post_type VARCHAR(50) NULL AFTER platform,
  ADD COLUMN IF NOT EXISTS topic VARCHAR(100) NULL AFTER post_type;

-- Optional: normalize topics (later), for now we keep a simple VARCHAR for speed.
