-- Metricool API Credentials voor Giuditta
-- Run: mysql -u root social_media_analytics < scripts/insert_metricool_credentials.sql

-- Delete existing credentials (if any)
DELETE FROM settings WHERE `key` IN ('metricool_api_key', 'metricool_user_id', 'metricool_blog_id_giuditta');

-- Insert new credentials
INSERT INTO settings (`key`, `value`, updated_at) VALUES
  ('metricool_api_key', 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB', NOW()),
  ('metricool_user_id', '4394337', NOW()),
  ('metricool_blog_id_giuditta', '5668624', NOW());

-- Verify
SELECT `key`, LEFT(`value`, 20) as value_preview, updated_at
FROM settings
WHERE `key` LIKE 'metricool%';
