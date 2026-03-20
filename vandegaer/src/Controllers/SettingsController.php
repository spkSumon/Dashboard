<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\SettingsRepository;

/**
 * SettingsController - Handles application settings management.
 *
 * This controller provides endpoints for managing application configuration settings
 * such as API keys, feature flags, and other key-value configuration data.
 *
 * Responsibilities:
 * - Parse and validate settings data from requests
 * - Delegate storage operations to SettingsRepository
 * - Return all settings or upsert individual settings
 * - Handle settings retrieval and updates
 *
 * Security notes:
 * - Settings may contain sensitive data (API keys, secrets)
 * - Consider authentication/authorization before exposing list() endpoint
 * - For production: implement encryption for sensitive settings
 *
 * Available endpoints:
 * - GET  /api/settings - List all settings
 * - POST /api/settings - Insert or update a setting
 *
 * @package App\Controllers
 */
class SettingsController {
    /**
     * Settings repository for database operations.
     *
     * @var SettingsRepository
     */
    private $settings;

    /**
     * Constructor - Inject settings repository dependency.
     *
     * @param SettingsRepository $settings Repository for settings data
     */
    public function __construct(SettingsRepository $settings) {
        $this->settings = $settings;
    }

    /**
     * List all application settings.
     *
     * Retrieves all settings from the database, including potentially sensitive
     * values like API keys and secrets. Results are ordered alphabetically by key.
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "key": "feature_flags",
     *     "value": "{\"new_ui\":true,\"beta_features\":false}",
     *     "updated_at": "2025-01-10 14:30:00"
     *   },
     *   {
     *     "key": "instagram_access_token",
     *     "value": "EAABs...",
     *     "updated_at": "2025-01-05 09:15:00"
     *   },
     *   {
     *     "key": "tiktok_client_id",
     *     "value": "aw123abc...",
     *     "updated_at": "2025-01-01 10:00:00"
     *   },
     *   {
     *     "key": "tiktok_client_secret",
     *     "value": "secret_abc123...",
     *     "updated_at": "2025-01-01 10:00:00"
     *   }
     * ]
     * ```
     *
     * Notes:
     * - Returns ALL settings including sensitive API keys and secrets
     * - Results ordered alphabetically by key
     * - Empty array if no settings exist
     *
     * Security considerations:
     * - This endpoint exposes all settings including secrets
     * - Should be protected with authentication/authorization
     * - Consider filtering sensitive keys before displaying in UI
     * - For production: implement role-based access control
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function list(): void {
        Response::json($this->settings->list());
    }

    /**
     * Insert or update a setting (upsert operation).
     *
     * Creates a new setting if the key doesn't exist, or updates the existing
     * value if it does. Useful for managing configuration values and API keys.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "key": "tiktok_client_id",
     *   "value": "aw123abc..."
     * }
     * ```
     *
     * Example for feature flags (JSON string value):
     * ```json
     * {
     *   "key": "feature_flags",
     *   "value": "{\"new_ui\":true,\"beta_features\":false}"
     * }
     * ```
     *
     * Example for simple text value:
     * ```json
     * {
     *   "key": "app_name",
     *   "value": "SocialBit Analytics"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "ok": true
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "Key ontbreekt"
     * }
     * ```
     *
     * Validation rules:
     * - key: Required, cannot be empty after trimming
     * - value: Optional, defaults to empty string if not provided
     *
     * Common setting keys:
     * - tiktok_client_id: TikTok OAuth client ID
     * - tiktok_client_secret: TikTok OAuth client secret
     * - instagram_access_token: Instagram Graph API token
     * - feature_flags: JSON-encoded feature toggles
     * - app_name: Application display name
     * - timezone: Default timezone
     *
     * Security notes:
     * - Values are stored as plain text in POC implementation
     * - For production: encrypt sensitive values before storage
     * - Audit trail recommended for settings changes
     *
     * @return void Sends JSON response and terminates execution
     */
    public function upsert(): void {
        $body = Request::jsonBody();
        $key = trim($body['key'] ?? '');
        $value = (string)($body['value'] ?? '');

        if (!$key) Response::error('Key ontbreekt', 400);
        $this->settings->upsert($key, $value);
        Response::json(['ok' => true]);
    }

    /**
     * Get brand colors for theming.
     *
     * Retrieves the configured brand colors (primary, secondary, accent) used
     * throughout the application UI. Returns default colors if not configured.
     *
     * Response format (HTTP 200):
     * ```json
     * {
     *   "brand_primary": "#6d28d9",
     *   "brand_secondary": "#22d3ee",
     *   "brand_accent": "#10b981"
     * }
     * ```
     *
     * Default colors (Metricool-inspired palette):
     * - brand_primary: #6d28d9 (Purple - primary brand color)
     * - brand_secondary: #22d3ee (Cyan - secondary/accent color)
     * - brand_accent: #10b981 (Emerald - success/positive indicator)
     *
     * Usage:
     * - design-lead uses this for Task #6 (brand color integration)
     * - Frontend fetches on app initialization to apply theme colors
     * - CSS variables can be set based on these values
     *
     * @return void Sends JSON response with brand colors
     */
    public function getBrandColors(): void {
        // Default Metricool-inspired colors
        $defaults = [
            'brand_primary' => '#6d28d9',    // Purple
            'brand_secondary' => '#22d3ee',  // Cyan
            'brand_accent' => '#10b981'      // Emerald
        ];

        // Fetch from database
        $primary = $this->settings->get('brand_primary');
        $secondary = $this->settings->get('brand_secondary');
        $accent = $this->settings->get('brand_accent');

        Response::json([
            'brand_primary' => $primary['value'] ?? $defaults['brand_primary'],
            'brand_secondary' => $secondary['value'] ?? $defaults['brand_secondary'],
            'brand_accent' => $accent['value'] ?? $defaults['brand_accent']
        ]);
    }

    /**
     * Update brand colors.
     *
     * Allows updating one or more brand colors at once. Only provided colors
     * are updated - others remain unchanged. Validates hex color format.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "brand_primary": "#6d28d9",
     *   "brand_secondary": "#22d3ee",
     *   "brand_accent": "#10b981"
     * }
     * ```
     *
     * Partial update (only primary color):
     * ```json
     * {
     *   "brand_primary": "#8b5cf6"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "ok": true,
     *   "updated": ["brand_primary", "brand_secondary", "brand_accent"]
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "Invalid hex color format for brand_primary"
     * }
     * ```
     *
     * Validation:
     * - Hex color format: #RRGGBB (6 hex digits)
     * - Case insensitive
     * - Leading # required
     *
     * @return void Sends JSON response and terminates execution
     */
    public function updateBrandColors(): void {
        $body = Request::jsonBody();
        $validKeys = ['brand_primary', 'brand_secondary', 'brand_accent'];
        $updated = [];

        foreach ($validKeys as $key) {
            if (isset($body[$key])) {
                $color = trim($body[$key]);

                // Validate hex color format
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    Response::error("Invalid hex color format for $key", 400);
                }

                $this->settings->upsert($key, $color);
                $updated[] = $key;
            }
        }

        if (empty($updated)) {
            Response::error('No valid brand colors provided', 400);
        }

        Response::json([
            'ok' => true,
            'updated' => $updated
        ]);
    }
}
