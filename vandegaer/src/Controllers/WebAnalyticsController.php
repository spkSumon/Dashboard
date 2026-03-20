<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Services\FathomAnalyticsApiService;
use App\Repositories\SettingsRepository;
use App\Repositories\WebAnalyticsRepository;
use Exception;

/**
 * WebAnalyticsController - Handles HTTP requests for website analytics (Fathom, Google Analytics).
 *
 * This controller provides REST API endpoints for website traffic analytics from
 * Fathom Analytics and Google Analytics 4. Enables correlation of social media
 * posts with website traffic increases.
 *
 * Responsibilities:
 * - Manage analytics API credentials (save, test, retrieve)
 * - Fetch website traffic metrics (page views, visitors, referrers)
 * - Provide time-series data for charts
 * - Handle API errors gracefully
 *
 * Available endpoints:
 * - POST /api/analytics/fathom/credentials - Save Fathom API credentials
 * - GET /api/analytics/fathom/test - Test Fathom connection
 * - GET /api/analytics/fathom/stats - Get aggregated stats
 * - GET /api/analytics/fathom/referrers - Get traffic sources
 * - GET /api/analytics/fathom/pages - Get page-level metrics
 * - GET /api/analytics/fathom/timeseries - Get daily breakdown
 *
 * @package App\Controllers
 */
class WebAnalyticsController {
    /**
     * Settings repository for credential storage.
     *
     * @var SettingsRepository
     */
    private SettingsRepository $settingsRepo;

    /**
     * Web analytics repository for data storage.
     *
     * @var WebAnalyticsRepository
     */
    private WebAnalyticsRepository $webAnalyticsRepo;

    /**
     * Constructor - Inject repository dependencies.
     *
     * @param SettingsRepository $settingsRepo Repository for settings storage
     * @param WebAnalyticsRepository $webAnalyticsRepo Repository for analytics data
     */
    public function __construct(
        SettingsRepository $settingsRepo,
        WebAnalyticsRepository $webAnalyticsRepo
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->webAnalyticsRepo = $webAnalyticsRepo;
    }

    // ==================== FATHOM ANALYTICS ====================

    /**
     * Save Fathom Analytics credentials.
     *
     * Stores API token and site ID in settings table for later use.
     *
     * Expected POST body (JSON):
     * ```json
     * {
     *   "api_token": "ABC123...",
     *   "site_id": "SITE123"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "message": "Fathom credentials saved successfully"
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "Missing required field: api_token"
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function saveFathomCredentials(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['api_token']) || !isset($input['site_id'])) {
            Response::error('Missing required fields: api_token, site_id', 400);
        }

        try {
            $this->settingsRepo->upsert('fathom_api_token', $input['api_token']);
            $this->settingsRepo->upsert('fathom_site_id', $input['site_id']);

            Response::json([
                'success' => true,
                'message' => 'Fathom credentials saved successfully'
            ]);
        } catch (Exception $e) {
            Response::error('Failed to save credentials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test Fathom Analytics connection.
     *
     * Validates stored credentials by attempting to fetch data from Fathom API.
     *
     * Expected query parameters: None (uses stored credentials)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "message": "Fathom connection successful"
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "success": false,
     *   "error": "Credentials not configured"
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function testFathomConnection(): void {
        try {
            $service = $this->getFathomService();
            $isConnected = $service->testConnection();

            if ($isConnected) {
                Response::json([
                    'success' => true,
                    'message' => 'Fathom connection successful'
                ]);
            } else {
                Response::json([
                    'success' => false,
                    'error' => 'Connection failed - check credentials'
                ], 401);
            }
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get aggregated Fathom statistics.
     *
     * Retrieves summary statistics for a date range including visits, unique visitors,
     * average duration, and bounce rate.
     *
     * Expected query parameters ($_GET):
     * - date_from (string, optional): Start date YYYY-MM-DD (default: 30 days ago)
     * - date_to (string, optional): End date YYYY-MM-DD (default: today)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "visits": 15230,
     *   "uniques": 8420,
     *   "pageviews": 18500,
     *   "avg_duration": 142.5,
     *   "bounce_rate": 45.2
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getFathomStats(): void {
        try {
            $service = $this->getFathomService();

            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $stats = $service->getAggregations($dateFrom, $dateTo);
            Response::json($stats);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Fathom referrers (traffic sources).
     *
     * Retrieves top referrers driving traffic to the website. Essential for
     * correlating social media posts with website visits.
     *
     * Expected query parameters ($_GET):
     * - date_from (string, optional): Start date YYYY-MM-DD (default: 30 days ago)
     * - date_to (string, optional): End date YYYY-MM-DD (default: today)
     * - limit (int, optional): Number of referrers to return (default: 20, max: 50)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "referrer": "t.co",
     *     "visits": 450,
     *     "uniques": 320
     *   },
     *   {
     *     "referrer": "instagram.com",
     *     "visits": 280,
     *     "uniques": 210
     *   }
     * ]
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getFathomReferrers(): void {
        try {
            $service = $this->getFathomService();

            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;

            $referrers = $service->getReferrers($dateFrom, $dateTo, $limit);
            Response::json($referrers);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Fathom page-level statistics.
     *
     * Retrieves metrics for individual pages to identify most popular content.
     *
     * Expected query parameters ($_GET):
     * - date_from (string, optional): Start date YYYY-MM-DD (default: 30 days ago)
     * - date_to (string, optional): End date YYYY-MM-DD (default: today)
     * - limit (int, optional): Number of pages to return (default: 20, max: 50)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "pathname": "/menu",
     *     "visits": 1250,
     *     "uniques": 890
     *   },
     *   {
     *     "pathname": "/reservations",
     *     "visits": 820,
     *     "uniques": 650
     *   }
     * ]
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getFathomPages(): void {
        try {
            $service = $this->getFathomService();

            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;

            $pages = $service->getPages($dateFrom, $dateTo, $limit);
            Response::json($pages);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get Fathom time-series data.
     *
     * Retrieves daily breakdown of traffic metrics for chart visualization.
     *
     * Expected query parameters ($_GET):
     * - date_from (string, optional): Start date YYYY-MM-DD (default: 30 days ago)
     * - date_to (string, optional): End date YYYY-MM-DD (default: today)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "date": "2026-01-15",
     *     "visits": 450,
     *     "uniques": 320
     *   },
     *   {
     *     "date": "2026-01-16",
     *     "visits": 520,
     *     "uniques": 380
     *   }
     * ]
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getFathomTimeSeries(): void {
        try {
            $service = $this->getFathomService();

            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $timeseries = $service->getTimeSeries($dateFrom, $dateTo);
            Response::json($timeseries);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Collect and store Fathom analytics data.
     *
     * Fetches data from Fathom API and stores it in the database.
     * This endpoint is designed to be called by scheduled jobs (cron/task scheduler).
     *
     * Expected query parameters ($_GET):
     * - client_id (int, required): Tenant ID
     * - date_from (string, optional): Start date YYYY-MM-DD (default: yesterday)
     * - date_to (string, optional): End date YYYY-MM-DD (default: yesterday)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "success": true,
     *   "message": "Collected 1 days of analytics data",
     *   "stored_records": 1,
     *   "correlated_posts": 3
     * }
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function collectFathomData(): void {
        try {
            $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
            if (!$clientId) {
                Response::error('Missing required parameter: client_id', 400);
            }

            $service = $this->getFathomService();

            // Default to yesterday's data (cron runs daily)
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 day'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('-1 day'));

            // Collect all analytics data
            $data = $service->collectAnalyticsData($dateFrom, $dateTo);

            // Store aggregated stats for each day in the range
            $storedRecords = 0;
            $currentDate = $dateFrom;
            while (strtotime($currentDate) <= strtotime($dateTo)) {
                $dayData = $service->transformToDbFormat($data['aggregations']);

                $this->webAnalyticsRepo->storeAnalytics(
                    $clientId,
                    'fathom',
                    $currentDate,
                    $dayData
                );

                $storedRecords++;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            // Correlate referrers with posts
            $correlatedPosts = 0;
            if (!empty($data['referrers'])) {
                // Add date to each referrer for correlation
                $referrersWithDate = array_map(function($ref) use ($dateTo) {
                    $ref['date'] = $dateTo;
                    return $ref;
                }, $data['referrers']);

                $correlatedPosts = $this->webAnalyticsRepo->correlateReferrersWithPosts(
                    $clientId,
                    $referrersWithDate
                );
            }

            Response::json([
                'success' => true,
                'message' => "Collected {$storedRecords} days of analytics data",
                'stored_records' => $storedRecords,
                'correlated_posts' => $correlatedPosts,
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get top posts that drove website traffic.
     *
     * Returns posts ordered by referral visits with traffic metrics.
     * Essential for understanding which social content drives website engagement.
     *
     * Expected query parameters ($_GET):
     * - client_id (int, required): Tenant ID
     * - limit (int, optional): Number of posts to return (default: 10, max: 50)
     * - date_from (string, optional): Filter by traffic date
     * - date_to (string, optional): Filter by traffic date
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "post_id": 123,
     *     "platform": "tiktok",
     *     "topic": "menu_item",
     *     "caption_preview": "Check out our new spring menu! 🌸",
     *     "posted_date": "2026-02-15",
     *     "total_visits": 450,
     *     "total_conversions": 12,
     *     "avg_bounce_rate": 35.5,
     *     "avg_session_duration": 180
     *   }
     * ]
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getTopTrafficPosts(): void {
        try {
            $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
            if (!$clientId) {
                Response::error('Missing required parameter: client_id', 400);
            }

            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            $posts = $this->webAnalyticsRepo->getTopTrafficDrivingPosts(
                $clientId,
                $limit,
                $dateFrom,
                $dateTo
            );

            Response::json($posts);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get website analytics summary for a date range.
     *
     * Returns aggregated website metrics from stored data (not live API call).
     * Faster than hitting Fathom API directly, uses cached database records.
     *
     * Expected query parameters ($_GET):
     * - client_id (int, required): Tenant ID
     * - source (string, optional): Filter by source ('fathom', 'google_business')
     * - date_from (string, optional): Start date (default: 30 days ago)
     * - date_to (string, optional): End date (default: today)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "date": "2026-02-19",
     *     "source": "fathom",
     *     "page_views": 1250,
     *     "unique_visitors": 890,
     *     "referral_visits": 120,
     *     "bounce_rate": 42.5,
     *     "avg_session_duration": 165
     *   }
     * ]
     * ```
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getStoredAnalytics(): void {
        try {
            $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
            if (!$clientId) {
                Response::error('Missing required parameter: client_id', 400);
            }

            $source = $_GET['source'] ?? null;
            $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            $analytics = $this->webAnalyticsRepo->getAnalyticsByDateRange(
                $clientId,
                $source,
                $dateFrom,
                $dateTo
            );

            Response::json($analytics);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get or create FathomAnalyticsApiService instance.
     *
     * Loads credentials from settings table and initializes service.
     *
     * @return FathomAnalyticsApiService Configured service instance
     * @throws Exception If credentials not configured
     */
    private function getFathomService(): FathomAnalyticsApiService {
        $apiToken = $this->settingsRepo->get('fathom_api_token');
        $siteId = $this->settingsRepo->get('fathom_site_id');

        if (!$apiToken || !$siteId) {
            throw new Exception('Fathom credentials not configured. Please save credentials first.');
        }

        return new FathomAnalyticsApiService(
            $apiToken['value'],
            $siteId['value'],
            $this->settingsRepo
        );
    }
}
