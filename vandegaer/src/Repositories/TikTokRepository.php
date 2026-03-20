<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * TikTokRepository - Database operations for TikTok OAuth tokens and user mapping.
 *
 * This repository manages the storage and retrieval of TikTok OAuth 2.0 access tokens,
 * refresh tokens, and user mappings. It handles token lifecycle management including
 * creation, updates, expiration checks, and cleanup.
 *
 * Responsibilities:
 * - Store OAuth tokens from TikTok authorization flow
 * - Retrieve valid (non-expired) tokens for API requests
 * - Update tokens during refresh operations
 * - Clean up expired tokens from database
 * - Map TikTok open_id to internal user_id
 *
 * Token lifecycle:
 * 1. saveToken() - Store initial token after OAuth authorization
 * 2. getValidToken() - Retrieve token for API requests (checks expiration)
 * 3. updateToken() - Refresh expired token with new credentials
 * 4. deleteExpiredTokens() - Periodic cleanup of old tokens
 *
 * @package App\Repositories
 */
class TikTokRepository {
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
     * Save new OAuth token to database.
     *
     * Stores TikTok OAuth credentials after successful authorization. This is called
     * when the user first authorizes the application or after a manual re-authorization.
     *
     * @param array $tokenData Token data containing:
     *                         - user_id (int, optional): Internal user ID
     *                         - client_id (int, optional): Multi-tenant client ID
     *                         - access_token (string, required): OAuth access token
     *                         - refresh_token (string, optional): OAuth refresh token
     *                         - token_type (string, optional): Token type (default: 'Bearer')
     *                         - expires_at (string, required): Expiration timestamp (YYYY-MM-DD HH:MM:SS)
     *                         - scope (string, optional): Granted OAuth scopes
     *                         - open_id (string, optional): TikTok user identifier
     *
     * @return int Newly created token record ID (auto-increment primary key)
     *
     * @note Access tokens typically expire after 24 hours
     * @note Refresh tokens can be used to obtain new access tokens
     */
    public function saveToken(array $tokenData): int {
        // Check if client_id column exists (multi-tenant support)
        $hasClientId = $this->hasClientIdColumn();

        if ($hasClientId) {
            $sql = "INSERT INTO tiktok_tokens (
                user_id, client_id, access_token, refresh_token, token_type,
                expires_at, scope, open_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->exec(
                $sql,
                'iissssss',
                [
                    $tokenData['user_id'] ?? null,
                    $tokenData['client_id'] ?? null,
                    $tokenData['access_token'],
                    $tokenData['refresh_token'] ?? null,
                    $tokenData['token_type'] ?? 'Bearer',
                    $tokenData['expires_at'],
                    $tokenData['scope'] ?? null,
                    $tokenData['open_id'] ?? null,
                ]
            );
        } else {
            // Legacy mode (no client_id)
            $sql = "INSERT INTO tiktok_tokens (
                user_id, access_token, refresh_token, token_type,
                expires_at, scope, open_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->db->exec(
                $sql,
                'issssss',
                [
                    $tokenData['user_id'] ?? null,
                    $tokenData['access_token'],
                    $tokenData['refresh_token'] ?? null,
                    $tokenData['token_type'] ?? 'Bearer',
                    $tokenData['expires_at'],
                    $tokenData['scope'] ?? null,
                    $tokenData['open_id'] ?? null,
                ]
            );
        }

        return $this->db->insertId();
    }

    /**
     * Get the most recent token (regardless of expiration status).
     *
     * Retrieves the latest token from the database ordered by creation time.
     * Does NOT check if the token is still valid/unexpired.
     *
     * @return array|null Token record containing:
     *                    - id (int): Token record ID
     *                    - user_id (int|null): Internal user ID
     *                    - access_token (string): OAuth access token
     *                    - refresh_token (string|null): OAuth refresh token
     *                    - token_type (string): Token type (usually 'Bearer')
     *                    - expires_at (string): Expiration timestamp
     *                    - scope (string|null): Granted OAuth scopes
     *                    - open_id (string|null): TikTok user identifier
     *                    - created_at (string): Creation timestamp
     *                    - updated_at (string): Last update timestamp
     *                    NULL if no tokens exist
     *
     * @note Use getValidToken() instead if you need a token for API requests
     */
    public function getLatestToken(): ?array {
        $sql = "SELECT * FROM tiktok_tokens
                ORDER BY created_at DESC
                LIMIT 1";

        return $this->db->fetchOne($sql);
    }

    /**
     * Get a valid (non-expired) token for API requests.
     *
     * Retrieves the most recent token that has not yet expired. This is the
     * preferred method for obtaining tokens to use in TikTok API requests.
     *
     * @return array|null Token record with same structure as getLatestToken()
     *                    NULL if no valid tokens exist (all expired or none stored)
     *
     * @note Returns NULL if all tokens are expired - caller should trigger token refresh
     * @note Tokens with expires_at > NOW() are considered valid
     */
    public function getValidToken(): ?array {
        $sql = "SELECT * FROM tiktok_tokens
                WHERE expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1";

        return $this->db->fetchOne($sql);
    }

    /**
     * Update existing token with refreshed credentials.
     *
     * Updates an existing token record with new access_token, refresh_token, and
     * expiration time. Called after using refresh_token to obtain new credentials
     * from TikTok OAuth server.
     *
     * @param int $id Token record ID to update
     * @param array $tokenData Updated token data containing:
     *                         - access_token (string, required): New OAuth access token
     *                         - refresh_token (string, optional): New OAuth refresh token
     *                         - expires_at (string, required): New expiration timestamp
     *
     * @return void
     *
     * @note Sets updated_at to NOW() automatically
     * @note Does not verify if the token ID exists before updating
     */
    public function updateToken(int $id, array $tokenData): void {
        $sql = "UPDATE tiktok_tokens
                SET access_token = ?,
                    refresh_token = ?,
                    expires_at = ?,
                    updated_at = NOW()
                WHERE id = ?";

        $this->db->exec(
            $sql,
            'sssi',
            [
                $tokenData['access_token'],
                $tokenData['refresh_token'] ?? null,
                $tokenData['expires_at'],
                $id,
            ]
        );
    }

    /**
     * Delete old/expired tokens for cleanup.
     *
     * Removes tokens that expired more than 7 days ago. This prevents the
     * tiktok_tokens table from growing indefinitely with stale credentials.
     *
     * @return int Number of tokens deleted
     *
     * @note Only deletes tokens expired for 7+ days to preserve recent history
     * @note Should be run periodically (e.g., daily cron job)
     * @note Does not affect valid or recently expired tokens
     */
    public function deleteExpiredTokens(): int {
        $sql = "DELETE FROM tiktok_tokens
                WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";

        $this->db->exec($sql);

        return $this->db->affectedRows();
    }

    /**
     * Check if a valid token exists.
     *
     * Quick check to determine if there's at least one non-expired token
     * available for TikTok API requests.
     *
     * @param int|null $clientId Optional client ID for multi-tenant filtering
     * @return bool TRUE if at least one valid token exists, FALSE otherwise
     *
     * @note More efficient than getValidToken() when you only need to check existence
     * @note Use getValidToken() if you need the actual token data
     */
    public function hasValidToken(?int $clientId = null): bool {
        $hasClientId = $this->hasClientIdColumn();

        if ($hasClientId && $clientId !== null) {
            $sql = "SELECT COUNT(*) as count FROM tiktok_tokens
                    WHERE expires_at > NOW() AND client_id = ?";
            $result = $this->db->query($sql, 'i', [$clientId])->fetch_assoc();
        } else {
            $sql = "SELECT COUNT(*) as count FROM tiktok_tokens
                    WHERE expires_at > NOW()";
            $result = $this->db->fetchOne($sql);
        }

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Check if tiktok_tokens table has client_id column (multi-tenant support).
     *
     * @return bool TRUE if client_id column exists, FALSE otherwise
     */
    private function hasClientIdColumn(): bool {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $sql = "SHOW COLUMNS FROM tiktok_tokens LIKE 'client_id'";
            $result = $this->db->fetchOne($sql);
            $hasColumn = !empty($result);
        }

        return $hasColumn;
    }
}
