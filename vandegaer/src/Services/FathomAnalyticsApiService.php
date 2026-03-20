<?php
namespace App\Services;

use App\Repositories\SettingsRepository;
use Exception;

/**
 * FathomAnalyticsApiService - Integration with Fathom Analytics API.
 *
 * This service handles interactions with Fathom Analytics API to fetch privacy-first
 * website analytics and correlate them with social media posts.
 *
 * Fathom Analytics API Documentation:
 * - Base URL: https://api.usefathom.com/v1
 * - Authentication: Bearer token (simple!)
 * - API Docs: https://usefathom.com/docs/api-reference
 *
 * Key Features:
 * - Fetch page views, unique visitors
 * - Get referrer data (traffic sources)
 * - Track goal conversions
 * - Privacy-first analytics (no cookies, GDPR compliant)
 *
 * Authentication Requirements:
 * - API token from Fathom dashboard (Settings → API)
 * - Header: Authorization: Bearer {token}
 *
 * @package App\Services
 */
class FathomAnalyticsApiService {
    /**
     * Fathom API base URL.
     */
    private const BASE_URL = 'https://api.usefathom.com/v1';

    /**
     * API authentication token.
     *
     * @var string
     */
    private string $apiToken;

    /**
     * Fathom site ID.
     *
     * @var string
     */
    private string $siteId;

    /**
     * Settings repository for credential storage.
     *
     * @var SettingsRepository
     */
    private SettingsRepository $settingsRepo;

    /**
     * Constructor - Initialize service with API credentials.
     *
     * @param string $apiToken Fathom API token
     * @param string $siteId Fathom site ID
     * @param SettingsRepository $settingsRepo Repository for settings storage
     */
    public function __construct(
        string $apiToken,
        string $siteId,
        SettingsRepository $settingsRepo
    ) {
        $this->apiToken = $apiToken;
        $this->siteId = $siteId;
        $this->settingsRepo = $settingsRepo;
    }

    /**
     * Get aggregated site stats for a date range.
     *
     * Retrieves summary statistics including page views, unique visitors, etc.
     *
     * API Endpoint: GET /aggregations
     *
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     *
     * @return array Aggregated metrics:
     *               - visits (int): Total visits (pageviews)
     *               - uniques (int): Unique visitors
     *               - pageviews (int): Total pageviews
     *               - avg_duration (float): Average visit duration
     *               - bounce_rate (float): Bounce rate percentage
     *
     * @throws Exception If API request fails
     *
     * @example
     * ```php
     * $stats = $service->getAggregations('2024-01-01', '2024-01-31');
     * // Returns: ['visits' => 15230, 'uniques' => 8420, ...]
     * ```
     */
    public function getAggregations(string $dateFrom, string $dateTo): array {
        $endpoint = '/aggregations';
        $params = [
            'entity' => 'pageview',
            'entity_id' => $this->siteId,
            'aggregates' => 'visits,uniques,pageviews,avg_duration,bounce_rate',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $response = $this->makeRequest($endpoint, $params);

        return $response['data'] ?? [];
    }

    /**
     * Get top referrers (traffic sources).
     *
     * Retrieves referral traffic data to identify which websites/platforms are
     * driving visits. Essential for correlating social posts with website traffic.
     *
     * API Endpoint: GET /aggregations
     *
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     * @param int $limit Number of referrers to return (default: 20)
     *
     * @return array Array of referrer objects, each containing:
     *               - referrer (string): Referrer hostname (e.g., "t.co", "instagram.com")
     *               - visits (int): Number of visits from this referrer
     *               - uniques (int): Unique visitors from this referrer
     *
     * @throws Exception If API request fails
     */
    public function getReferrers(string $dateFrom, string $dateTo, int $limit = 20): array {
        $endpoint = '/aggregations';
        $params = [
            'entity' => 'pageview',
            'entity_id' => $this->siteId,
            'aggregates' => 'visits,uniques',
            'field_grouping' => 'referrer_hostname',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
            'sort_by' => 'visits:desc',
        ];

        $response = $this->makeRequest($endpoint, $params);

        return $response['data'] ?? [];
    }

    /**
     * Get page-level statistics.
     *
     * Retrieves metrics for individual pages to identify most popular content
     * and which pages are driven by social traffic.
     *
     * API Endpoint: GET /aggregations
     *
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     * @param int $limit Number of pages to return (default: 20)
     *
     * @return array Array of page objects, each containing:
     *               - pathname (string): Page URL path
     *               - visits (int): Total visits to this page
     *               - uniques (int): Unique visitors to this page
     *
     * @throws Exception If API request fails
     */
    public function getPages(string $dateFrom, string $dateTo, int $limit = 20): array {
        $endpoint = '/aggregations';
        $params = [
            'entity' => 'pageview',
            'entity_id' => $this->siteId,
            'aggregates' => 'visits,uniques',
            'field_grouping' => 'pathname',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit,
            'sort_by' => 'visits:desc',
        ];

        $response = $this->makeRequest($endpoint, $params);

        return $response['data'] ?? [];
    }

    /**
     * Get time-series data for charting traffic over time.
     *
     * Retrieves daily breakdown of traffic metrics for trend analysis.
     *
     * API Endpoint: GET /aggregations
     *
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     *
     * @return array Array of daily metrics, each containing:
     *               - date (string): Date in YYYY-MM-DD format
     *               - visits (int): Visits on this day
     *               - uniques (int): Unique visitors on this day
     *
     * @throws Exception If API request fails
     */
    public function getTimeSeries(string $dateFrom, string $dateTo): array {
        $endpoint = '/aggregations';
        $params = [
            'entity' => 'pageview',
            'entity_id' => $this->siteId,
            'aggregates' => 'visits,uniques',
            'date_grouping' => 'date',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'timezone' => 'UTC',
        ];

        $response = $this->makeRequest($endpoint, $params);

        return $response['data'] ?? [];
    }

    /**
     * Make HTTP request to Fathom API.
     *
     * Handles authentication, request building, and response parsing.
     * All requests include Bearer token authentication.
     *
     * @param string $endpoint API endpoint path (e.g., '/aggregations')
     * @param array $params Query parameters
     *
     * @return array Decoded JSON response
     *
     * @throws Exception If request fails or response is invalid
     */
    private function makeRequest(string $endpoint, array $params = []): array {
        // Build URL with query parameters
        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($response === false) {
            throw new Exception("Fathom API request failed: " . $error);
        }

        // Handle HTTP errors
        if ($httpCode >= 400) {
            throw new Exception("Fathom API error: HTTP {$httpCode} - {$response}");
        }

        // Decode JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from Fathom API: " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Test API connection and credentials.
     *
     * Validates that API token is working and site is accessible.
     *
     * @return bool True if credentials are valid and API is accessible
     */
    public function testConnection(): bool {
        try {
            // Try to fetch last 7 days of data as a simple test
            $dateTo = date('Y-m-d');
            $dateFrom = date('Y-m-d', strtotime('-7 days'));

            $this->getAggregations($dateFrom, $dateTo);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Store Fathom credentials in settings table.
     *
     * Saves API token and site ID for later retrieval.
     *
     * @param string $apiToken Fathom API token
     * @param string $siteId Fathom site ID
     *
     * @return bool True if saved successfully
     */
    public function saveCredentials(string $apiToken, string $siteId): bool {
        try {
            $this->settingsRepo->upsert('fathom_api_token', $apiToken);
            $this->settingsRepo->upsert('fathom_site_id', $siteId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Load Fathom credentials from settings table.
     *
     * Retrieves stored Fathom configuration.
     *
     * @return array|null Configuration array with 'api_token' and 'site_id', or null if not set
     */
    public function loadCredentials(): ?array {
        try {
            $apiToken = $this->settingsRepo->get('fathom_api_token');
            $siteId = $this->settingsRepo->get('fathom_site_id');

            if (!$apiToken || !$siteId) {
                return null;
            }

            return [
                'api_token' => $apiToken,
                'site_id' => $siteId,
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Collect and prepare analytics data for storage.
     *
     * Fetches Fathom data and transforms it into a format suitable for database storage.
     * This is the main method used by scheduled jobs to collect daily analytics.
     *
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     *
     * @return array Structured data ready for WebAnalyticsRepository:
     *               - aggregations: Daily summary stats
     *               - referrers: Traffic sources
     *               - pages: Page-level metrics
     *               - timeseries: Daily breakdown
     *
     * @throws Exception If API request fails
     */
    public function collectAnalyticsData(string $dateFrom, string $dateTo): array {
        return [
            'aggregations' => $this->getAggregations($dateFrom, $dateTo),
            'referrers' => $this->getReferrers($dateFrom, $dateTo),
            'pages' => $this->getPages($dateFrom, $dateTo),
            'timeseries' => $this->getTimeSeries($dateFrom, $dateTo),
        ];
    }

    /**
     * Transform Fathom aggregations into database format.
     *
     * Converts Fathom API response into the structure expected by website_analytics table.
     *
     * @param array $aggregations Raw Fathom aggregations response
     *
     * @return array Database-ready format with:
     *               - page_views (int)
     *               - unique_visitors (int)
     *               - bounce_rate (float)
     *               - avg_session_duration (int)
     */
    public function transformToDbFormat(array $aggregations): array {
        return [
            'page_views' => (int)($aggregations['pageviews'] ?? $aggregations['visits'] ?? 0),
            'unique_visitors' => (int)($aggregations['uniques'] ?? 0),
            'bounce_rate' => (float)($aggregations['bounce_rate'] ?? 0.0),
            'avg_session_duration' => (int)($aggregations['avg_duration'] ?? 0),
            'referral_visits' => 0, // Calculated separately from referrers
            'referral_source' => null,
            'conversions' => 0, // Would come from goals endpoint (future enhancement)
            'data_json' => $aggregations, // Store raw response for debugging
        ];
    }
}
