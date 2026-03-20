<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\SettingsRepository;
use App\Repositories\PostRepository;
use App\Repositories\MetricsHistoryRepository;
use App\Services\MetricoolApiService;
use Exception;

/**
 * MetricoolController - Handles Metricool API integration endpoints.
 *
 * This controller provides endpoints for configuring and testing Metricool API
 * integration, fetching connected profiles, and syncing posts from multiple platforms.
 *
 * Responsibilities:
 * - Save and validate Metricool API credentials
 * - Test API connection
 * - Fetch connected social media profiles
 * - Trigger manual data synchronization
 * - Retrieve sync statistics
 *
 * Available endpoints:
 * - POST /api/metricool/test - Test API connection
 * - POST /api/metricool/save - Save API credentials
 * - GET  /api/metricool/profiles - Get connected profiles
 * - POST /api/metricool/sync - Trigger manual sync
 * - GET  /api/metricool/status - Get connection status
 *
 * @package App\Controllers
 */
class MetricoolController {
    /**
     * Settings repository for storing API credentials.
     *
     * @var SettingsRepository
     */
    private SettingsRepository $settings;

    /**
     * Post repository for database operations.
     *
     * @var PostRepository
     */
    private PostRepository $postRepo;

    /**
     * Metrics history repository for snapshots.
     *
     * @var MetricsHistoryRepository
     */
    private MetricsHistoryRepository $metricsRepo;

    /**
     * Constructor - Inject repository dependencies.
     *
     * @param SettingsRepository $settings Settings repository
     * @param PostRepository $postRepo Post repository
     * @param MetricsHistoryRepository $metricsRepo Metrics repository
     */
    public function __construct(
        SettingsRepository $settings,
        PostRepository $postRepo,
        MetricsHistoryRepository $metricsRepo
    ) {
        $this->settings = $settings;
        $this->postRepo = $postRepo;
        $this->metricsRepo = $metricsRepo;
    }

    /**
     * Test Metricool API connection.
     *
     * Validates API credentials by attempting to fetch connected profiles.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "api_key": "your_metricool_api_key",
     *   "user_id": "your_metricool_user_id"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "message": "Connection successful",
     *   "profiles_count": 5,
     *   "profiles": [
     *     {
     *       "id": 12345,
     *       "name": "TikTok @username",
     *       "network": "tiktok",
     *       "username": "username"
     *     }
     *   ]
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": {
     *     "code": null,
     *     "message": "API connection failed: Invalid credentials"
     *   }
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function testConnection(): void {
        $body = Request::jsonBody();
        $apiKey = trim($body['api_key'] ?? '');
        $userId = trim($body['user_id'] ?? '');

        if (!$apiKey || !$userId) {
            Response::error('API key and User ID are required', 400);
        }

        try {
            // Create service instance with provided credentials
            $service = new MetricoolApiService(
                $apiKey,
                $userId,
                $this->postRepo,
                $this->metricsRepo
            );

            // Test connection by fetching profiles
            $profiles = $service->getConnectedProfiles();

            Response::json([
                'success' => true,
                'message' => 'Connection successful',
                'profiles_count' => count($profiles),
                'profiles' => $profiles
            ]);
        } catch (Exception $e) {
            Response::error('API connection failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Save Metricool API credentials to database.
     *
     * Stores API key and user ID in settings table for later use.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "api_key": "your_metricool_api_key",
     *   "user_id": "your_metricool_user_id"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "message": "Metricool credentials saved successfully"
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": {
     *     "code": null,
     *     "message": "API key and User ID are required"
     *   }
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function saveCredentials(): void {
        $body = Request::jsonBody();
        $apiKey = trim($body['api_key'] ?? '');
        $userId = trim($body['user_id'] ?? '');

        if (!$apiKey || !$userId) {
            Response::error('API key and User ID are required', 400);
        }

        try {
            // Save credentials to settings
            $this->settings->upsert('metricool_api_key', $apiKey);
            $this->settings->upsert('metricool_user_id', $userId);

            Response::json([
                'success' => true,
                'message' => 'Metricool credentials saved successfully'
            ]);
        } catch (Exception $e) {
            Response::error('Failed to save credentials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get connected social media profiles from Metricool.
     *
     * Retrieves all social media accounts connected to the Metricool account.
     * Requires saved API credentials in settings table.
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "profiles": [
     *     {
     *       "id": 12345,
     *       "name": "TikTok @myaccount",
     *       "network": "tiktok",
     *       "username": "myaccount"
     *     },
     *     {
     *       "id": 67890,
     *       "name": "Instagram @mybrand",
     *       "network": "instagram",
     *       "username": "mybrand"
     *     }
     *   ],
     *   "count": 2
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": {
     *     "code": null,
     *     "message": "Metricool credentials not configured"
     *   }
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getProfiles(): void {
        try {
            $service = $this->createServiceFromSettings();

            $profiles = $service->getConnectedProfiles();

            Response::json([
                'success' => true,
                'profiles' => $profiles,
                'count' => count($profiles)
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Trigger manual sync of posts from Metricool.
     *
     * Fetches posts from a specific platform and blog ID, then syncs them
     * to the SocialBit database using the conflict resolution strategy.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "client_id": 1,
     *   "blog_id": "12345",
     *   "platform": "instagram",
     *   "start_date": "20260101",
     *   "end_date": "20260131"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "stats": {
     *     "created": 15,
     *     "updated": 8,
     *     "snapshots_created": 23,
     *     "errors": []
     *   }
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": {
     *     "code": null,
     *     "message": "Missing required parameters: client_id, blog_id, platform"
     *   }
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function syncPosts(): void {
        $body = Request::jsonBody();
        $clientId = (int)($body['client_id'] ?? 0);
        $blogId = trim($body['blog_id'] ?? '');
        $platform = trim($body['platform'] ?? '');
        $startDate = trim($body['start_date'] ?? '');
        $endDate = trim($body['end_date'] ?? '');

        if (!$clientId || !$blogId || !$platform) {
            Response::error('Missing required parameters: client_id, blog_id, platform', 400);
        }

        try {
            $service = $this->createServiceFromSettings();

            // Build filters
            $filters = [];
            if ($startDate) $filters['start'] = $startDate;
            if ($endDate) $filters['end'] = $endDate;

            // Sync posts
            $stats = $service->syncPosts($clientId, $blogId, $platform, $filters);

            Response::json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            Response::error('Sync failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get Metricool API connection status.
     *
     * Checks if Metricool credentials are configured and tests the connection.
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "configured": true,
     *   "connected": true,
     *   "profiles_count": 5
     * }
     * ```
     *
     * Response format (not configured - HTTP 200):
     * ```json
     * {
     *   "configured": false,
     *   "connected": false,
     *   "profiles_count": 0
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getStatus(): void {
        try {
            // Check if credentials are configured
            $apiKey = $this->settings->get('metricool_api_key');
            $userId = $this->settings->get('metricool_user_id');

            if (!$apiKey || !$userId) {
                Response::json([
                    'configured' => false,
                    'connected' => false,
                    'profiles_count' => 0
                ]);
                return;
            }

            // Test connection
            $service = $this->createServiceFromSettings();
            $connected = $service->testConnection();

            if ($connected) {
                $profiles = $service->getConnectedProfiles();
                Response::json([
                    'configured' => true,
                    'connected' => true,
                    'profiles_count' => count($profiles)
                ]);
            } else {
                Response::json([
                    'configured' => true,
                    'connected' => false,
                    'profiles_count' => 0
                ]);
            }
        } catch (Exception $e) {
            Response::json([
                'configured' => true,
                'connected' => false,
                'profiles_count' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create MetricoolApiService instance from saved settings.
     *
     * Retrieves API credentials from settings table and instantiates service.
     *
     * @return MetricoolApiService Configured service instance
     *
     * @throws Exception If credentials not found in settings
     */
    private function createServiceFromSettings(): MetricoolApiService {
        $apiKey = $this->settings->get('metricool_api_key');
        $userId = $this->settings->get('metricool_user_id');

        if (!$apiKey || !$userId) {
            throw new Exception('Metricool credentials not configured. Please save API key and User ID first.');
        }

        return new MetricoolApiService(
            $apiKey['value'],
            $userId['value'],
            $this->postRepo,
            $this->metricsRepo
        );
    }
}
