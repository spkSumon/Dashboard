<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\InstagramApiService;
use Exception;

/**
 * InstagramController - Handle Instagram API operations.
 *
 * Provides endpoints for Instagram API integration including:
 * - Saving API credentials
 * - Testing connection
 * - Fetching and saving posts
 *
 * @package App\Controllers
 */
class InstagramController {
    private InstagramApiService $service;

    public function __construct(InstagramApiService $service) {
        $this->service = $service;
    }

    /**
     * Save Instagram API credentials.
     *
     * Expected POST body:
     * {
     *   "app_id": "your_meta_app_id",
     *   "app_secret": "your_meta_app_secret",
     *   "access_token": "your_long_lived_access_token"
     * }
     *
     * @param Request $request
     * @return void
     */
    public function saveCredentials(Request $request): void {
        try {
            $data = $request->json();

            if (empty($data['access_token'])) {
                Response::json([
                    'success' => false,
                    'error' => 'Access token is required'
                ], 400);
                return;
            }

            $this->service->saveCredentials($data);

            Response::json([
                'success' => true,
                'message' => 'Instagram credentials saved successfully'
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Instagram API connection.
     *
     * @return void
     */
    public function testConnection(): void {
        try {
            $profile = $this->service->testConnection();

            Response::json([
                'success' => true,
                'message' => 'Instagram connection successful',
                'data' => $profile
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Fetch Instagram posts from API.
     *
     * Query parameters:
     * - limit: Number of posts to fetch (default: 25, max: 100)
     *
     * @param Request $request
     * @return void
     */
    public function fetchPosts(Request $request): void {
        try {
            $limit = (int)($request->query('limit') ?? 25);
            $limit = min(max($limit, 1), 100); // Clamp between 1 and 100

            $posts = $this->service->fetchPosts($limit);

            Response::json([
                'success' => true,
                'message' => 'Posts fetched successfully',
                'count' => count($posts),
                'data' => $posts
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch and save Instagram posts to database.
     *
     * Query parameters:
     * - limit: Number of posts to fetch (default: 25, max: 100)
     * - client_id: Client ID for multi-tenant support (optional)
     *
     * @param Request $request
     * @return void
     */
    public function fetchAndSave(Request $request): void {
        try {
            $limit = (int)($request->query('limit') ?? 25);
            $limit = min(max($limit, 1), 100);

            $clientId = $request->query('client_id') ? (int)$request->query('client_id') : null;

            // Fetch posts from Instagram API
            $posts = $this->service->fetchPosts($limit);

            // Save to database
            $savedCount = $this->service->savePosts($posts, $clientId);

            Response::json([
                'success' => true,
                'message' => "Successfully saved {$savedCount} Instagram posts",
                'fetched' => count($posts),
                'saved' => $savedCount
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh Instagram access token.
     *
     * Long-lived tokens expire after 60 days. This endpoint refreshes the token.
     *
     * @return void
     */
    public function refreshToken(): void {
        try {
            $result = $this->service->refreshAccessToken();

            Response::json([
                'success' => true,
                'message' => 'Access token refreshed successfully',
                'expires_in' => $result['expires_in'] ?? null
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
