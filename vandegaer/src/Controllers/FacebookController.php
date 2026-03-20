<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\FacebookApiService;
use Exception;

/**
 * FacebookController - Handle Facebook API operations.
 *
 * Provides endpoints for Facebook Graph API integration including:
 * - Saving API credentials
 * - Testing connection
 * - Fetching and saving posts
 * - Fetching page insights
 *
 * @package App\Controllers
 */
class FacebookController {
    private FacebookApiService $service;

    public function __construct(FacebookApiService $service) {
        $this->service = $service;
    }

    /**
     * Save Facebook API credentials.
     *
     * Expected POST body:
     * {
     *   "access_token": "your_page_access_token",
     *   "page_id": "your_facebook_page_id"
     * }
     *
     * @param Request $request
     * @return void
     */
    public function saveCredentials(Request $request): void {
        try {
            $data = $request->json();

            if (empty($data['access_token']) || empty($data['page_id'])) {
                Response::json([
                    'success' => false,
                    'error' => 'Access token and page ID are required'
                ], 400);
                return;
            }

            $this->service->saveCredentials($data);

            Response::json([
                'success' => true,
                'message' => 'Facebook credentials saved successfully'
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Facebook API connection.
     *
     * @return void
     */
    public function testConnection(): void {
        try {
            $page = $this->service->testConnection();

            Response::json([
                'success' => true,
                'message' => 'Facebook connection successful',
                'data' => $page
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Fetch Facebook posts from API.
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
     * Fetch and save Facebook posts to database.
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

            // Fetch posts from Facebook API
            $posts = $this->service->fetchPosts($limit);

            // Save to database
            $savedCount = $this->service->savePosts($posts, $clientId);

            Response::json([
                'success' => true,
                'message' => "Successfully saved {$savedCount} Facebook posts",
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
     * Fetch page insights.
     *
     * Query parameters:
     * - metrics: Comma-separated list of metrics (default: page_impressions,page_engaged_users)
     * - period: Time period (day, week, days_28) (default: day)
     *
     * @param Request $request
     * @return void
     */
    public function getPageInsights(Request $request): void {
        try {
            $metricsStr = $request->query('metrics') ?? 'page_impressions,page_engaged_users,page_fans';
            $metrics = array_map('trim', explode(',', $metricsStr));

            $period = $request->query('period') ?? 'day';

            $insights = $this->service->fetchPageInsights($metrics, $period);

            Response::json([
                'success' => true,
                'message' => 'Page insights fetched successfully',
                'data' => $insights
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
