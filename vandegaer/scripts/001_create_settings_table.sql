-- Settings table (POC)
-- Bewaar hier bv API keys.
-- Voor productie: encrypt of secret store gebruiken.

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(190) NOT NULL,
  `value` TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
