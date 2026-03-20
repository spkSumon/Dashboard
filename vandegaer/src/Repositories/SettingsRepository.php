<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * SettingsRepository - Database operations for application settings storage.
 *
 * This repository provides key-value storage for application settings such as
 * API keys, configuration values, and feature flags. It's designed for POC/MVP
 * use with simple storage mechanism.
 *
 * Responsibilities:
 * - Store and retrieve settings by key
 * - Handle settings upsert (insert or update)
 * - List all application settings
 *
 * Security warnings:
 * - POC implementation stores values as plain text
 * - For production: encrypt secrets or use dedicated secret management (e.g., Vault, AWS Secrets Manager)
 * - Never commit sensitive settings to version control
 *
 * Common settings keys:
 * - 'tiktok_client_id': TikTok OAuth client ID
 * - 'tiktok_client_secret': TikTok OAuth client secret
 * - 'instagram_access_token': Instagram Graph API token
 * - 'feature_flags': JSON-encoded feature toggles
 *
 * @package App\Repositories
 */
class SettingsRepository {
    /**
     * Database connection instance.
     *
     * @var Database
     */
    private $db;

    /**
     * Constructor - Inject database dependency.
     *
     * @param Database $db Database instance for executing queries
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get a setting value by key.
     *
     * Retrieves a single setting record from the database. Use this method to
     * fetch configuration values, API keys, or feature flags.
     *
     * @param string $key Setting key to retrieve (e.g., 'tiktok_client_id')
     *
     * @return array|null Setting record containing:
     *                    - key (string): Setting key
     *                    - value (string): Setting value (plain text)
     *                    - updated_at (string): Last update timestamp
     *                    NULL if setting not found
     *
     * @example
     * ```php
     * $setting = $repo->get('tiktok_client_id');
     * $clientId = $setting['value'] ?? 'default_value';
     * ```
     *
     * @note For sensitive values (API secrets), consider encryption in production
     */
    public function get(string $key): ?array {
        return $this->db->fetchOne(
            "SELECT `key`, `value`, updated_at FROM settings WHERE `key` = ? LIMIT 1",
            "s",
            [$key]
        );
    }

    /**
     * Insert or update a setting (upsert operation).
     *
     * Creates a new setting if the key doesn't exist, or updates the existing
     * value if it does. Automatically updates the updated_at timestamp.
     *
     * @param string $key Setting key (e.g., 'tiktok_client_secret')
     * @param string $value Setting value to store
     *
     * @return void
     *
     * @note Uses MySQL ON DUPLICATE KEY UPDATE for atomic upsert
     * @note updated_at is automatically set to NOW() on both insert and update
     *
     * @example
     * ```php
     * $repo->upsert('instagram_access_token', 'EAABs...');
     * ```
     *
     * @warning In production, encrypt sensitive values before storing
     */
    public function upsert(string $key, string $value): void {
        $this->db->exec(
            "INSERT INTO settings (`key`, `value`, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()",
            "ss",
            [$key, $value]
        );
    }

    /**
     * List all settings.
     *
     * Retrieves all setting records from the database, ordered alphabetically by key.
     * Useful for settings management UI or debugging.
     *
     * @return array Array of setting records, each containing:
     *               - key (string): Setting key
     *               - value (string): Setting value
     *               - updated_at (string): Last update timestamp
     *               Ordered by key ASC (alphabetically)
     *               Empty array if no settings exist
     *
     * @note Returns ALL settings including sensitive values - use with caution
     * @note Consider filtering sensitive keys before displaying in UI
     *
     * @example
     * ```php
     * $allSettings = $repo->list();
     * foreach ($allSettings as $setting) {
     *     echo $setting['key'] . ': ' . $setting['value'];
     * }
     * ```
     */
    public function list(): array {
        return $this->db->fetchAll("SELECT `key`, `value`, updated_at FROM settings ORDER BY `key` ASC");
    }
}
