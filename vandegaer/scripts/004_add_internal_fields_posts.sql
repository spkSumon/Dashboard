-- ============================================
-- DEPRECATED - NIET MEER NODIG
-- ============================================
-- Deze migratie is geïntegreerd in 000_create_database_schema.sql
--
-- Als je 000_create_database_schema.sql al hebt gerund,
-- kan je dit script OVERSLAAN.
--
-- Dit script is alleen nodig als je een oude database hebt
-- waar de posts tabel al bestaat zonder internal fields.
-- ============================================

-- Interne velden op posts (POC)
-- Hiermee kan je metadata aanpassen zonder de live post op het platform te wijzigen.

ALTER TABLE posts
  ADD COLUMN IF NOT EXISTS internal_caption TEXT NULL AFTER caption,
  ADD COLUMN IF NOT EXISTS internal_notes TEXT NULL AFTER internal_caption;
