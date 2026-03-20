<?php
namespace App\Services;

use App\Repositories\PostRepository;
use App\Repositories\MetricsHistoryRepository;
use Exception;

/**
 * MetricoolApiService - Integration with Metricool API for multi-platform analytics.
 *
 * This service handles all interactions with the Metricool API to fetch social media
 * posts and metrics from connected platforms (TikTok, Instagram, Facebook, YouTube).
 *
 * Metricool API Documentation:
 * - Base URL: https://app.metricool.com/api
 * - Authentication: Header-based (X-Mc-Auth) + Query params (userId, blogId)
 * - Swagger docs: https://app.metricool.com/api/swagger.json
 *
 * Key Features:
 * - Fetch connected social media profiles (brands)
 * - Retrieve posts with metrics from multiple platforms
 * - Sync post data to SocialBit database
 * - Handle rate limits and API errors gracefully
 *
 * Authentication Requirements:
 * - userToken: API key from Metricool account settings (passed in X-Mc-Auth header)
 * - userId: User identifier
 * - blogId: Brand/profile identifier
 *
 * @package App\Services
 */
class MetricoolApiService {
    /**
     * Metricool API base URL.
     */
    private const BASE_URL = 'https://app.metricool.com/api';

    /**
     * API authentication token (userToken).
     *
     * @var string
     */
    private string $apiKey;

    /**
     * User ID for API requests.
     *
     * @var string
     */
    private string $userId;

    /**
     * Post repository for database operations.
     *
     * @var PostRepository
     */
    private PostRepository $postRepository;

    /**
     * Metrics history repository for time-series snapshots.
     *
     * @var MetricsHistoryRepository
     */
    private MetricsHistoryRepository $metricsHistoryRepository;

    /**
     * Constructor - Initialize service with API credentials.
     *
     * @param string $apiKey Metricool API key (userToken)
     * @param string $userId Metricool user ID
     * @param PostRepository $postRepository Repository for post database operations
     * @param MetricsHistoryRepository $metricsHistoryRepository Repository for metrics snapshots
     */
    public function __construct(
        string $apiKey,
        string $userId,
        PostRepository $postRepository,
        MetricsHistoryRepository $metricsHistoryRepository
    ) {
        $this->apiKey = $apiKey;
        $this->userId = $userId;
        $this->postRepository = $postRepository;
        $this->metricsHistoryRepository = $metricsHistoryRepository;
    }

    /**
     * Get all connected social media profiles (brands) from Metricool.
     *
     * Returns a list of all brands/profiles connected to the user's Metricool account.
     * Each profile represents a social media account (e.g., TikTok @username, Instagram page).
     *
     * API Endpoint: GET /admin/simpleProfiles
     *
     * @return array Array of profile objects, each containing:
     *               - id (int): Blog/brand ID
     *               - name (string): Profile name
     *               - network (string): Platform (tiktok, instagram, facebook, youtube)
     *               - username (string): Social media handle
     *
     * @throws Exception If API request fails
     *
     * @example
     * ```php
     * $profiles = $service->getConnectedProfiles();
     * foreach ($profiles as $profile) {
     *     echo "Connected: {$profile['network']} - {$profile['username']}\n";
     * }
     * ```
     */
    public function getConnectedProfiles(): array {
        $endpoint = '/admin/simpleProfiles';
        $response = $this->makeRequest($endpoint);

        // Metricool /admin/simpleProfiles returns data directly as array
        // NOT wrapped in a 'data' key like other endpoints
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        } elseif (is_array($response)) {
            // Direct array response (typical for simpleProfiles endpoint)
            return $response;
        }

        throw new Exception('Invalid response from Metricool API: expected array of profiles');
    }

    /**
     * Get posts from a specific profile with optional date filtering.
     *
     * Retrieves posts from different platforms with their associated metrics.
     * Platform-specific endpoints are used based on the network type.
     *
     * Supported Platforms:
     * - Instagram: /stats/instagram/posts (feed posts)
     * - Instagram Reels: /stats/instagram/reels
     * - Facebook: /stats/facebook/posts
     * - TikTok: Currently not explicitly documented in Swagger (may need testing)
     *
     * @param string $blogId Profile/brand ID
     * @param string $platform Platform name (instagram, facebook, tiktok, youtube)
     * @param array $filters Optional filters:
     *                       - start (string): Start date YYYYMMDD format
     *                       - end (string): End date YYYYMMDD format
     *                       - sortcolumn (string): Sort field (varies by platform)
     *
     * @return array Array of post objects with metrics
     *
     * @throws Exception If API request fails or platform not supported
     *
     * @example
     * ```php
     * $posts = $service->getPosts('12345', 'instagram', [
     *     'start' => '20260101',
     *     'end' => '20260131'
     * ]);
     * ```
     */
    public function getPosts(string $blogId, string $platform, array $filters = []): array {
        // Map platform to API endpoint
        $endpointMap = [
            'instagram' => '/stats/instagram/posts',
            'instagram_reels' => '/stats/instagram/reels',
            'instagram_stories' => '/stats/instagram/stories',
            'facebook' => '/stats/facebook/posts',
            'linkedin' => '/stats/linkedin/posts',
            'fbgroup' => '/stats/fbgroup/posts',
        ];

        // Note: TikTok endpoint not explicitly documented in Swagger
        // May need testing or contact Metricool support for TikTok endpoint

        if (!isset($endpointMap[$platform])) {
            throw new Exception("Platform '{$platform}' is not supported or endpoint not found");
        }

        $endpoint = $endpointMap[$platform];

        // Add blogId to filters
        $filters['blogId'] = $blogId;

        $response = $this->makeRequest($endpoint, $filters);

        if (!isset($response['data']) || !is_array($response['data'])) {
            return []; // No posts found
        }

        return $response['data'];
    }

    /**
     * Get aggregated metrics for a specific platform and time period.
     *
     * Retrieves aggregate statistics (sum, average) for metrics in a given period.
     * Useful for dashboard summary cards and trend analysis.
     *
     * API Endpoint: GET /stats/aggregations/{category}
     *
     * @param string $blogId Profile/brand ID
     * @param string $category Metrics category (Instagram, Facebook, etc.)
     * @param array $filters Filters:
     *                       - start (string): Start date YYYYMMDD
     *                       - end (string): End date YYYYMMDD
     *
     * @return array Aggregated metrics (key-value pairs with metric names and values)
     *
     * @throws Exception If API request fails
     */
    public function getAggregatedMetrics(string $blogId, string $category, array $filters = []): array {
        $endpoint = "/stats/aggregations/{$category}";
        $filters['blogId'] = $blogId;

        $response = $this->makeRequest($endpoint, $filters);

        return $response['data'] ?? [];
    }

    /**
     * Get demographic data (age, gender, location) for a platform.
     *
     * Retrieves audience demographic information from Metricool.
     *
     * Available endpoints:
     * - /stats/gender/{provider}
     * - /stats/age/{provider}
     * - /stats/country/{provider}
     * - /stats/city/{provider}
     *
     * @param string $blogId Profile/brand ID
     * @param string $provider Platform (facebook, instagram, tiktok, youtube)
     * @param string $type Demographic type (gender, age, country, city)
     *
     * @return array Demographic distribution (key-value pairs)
     *
     * @throws Exception If API request fails
     */
    public function getDemographics(string $blogId, string $provider, string $type = 'country'): array {
        $endpoint = "/stats/{$type}/{$provider}";
        $filters = ['blogId' => $blogId];

        $response = $this->makeRequest($endpoint, $filters);

        return $response['data'] ?? [];
    }

    /**
     * Sync posts from Metricool to SocialBit database.
     *
     * Fetches posts from Metricool API and imports them into the SocialBit database.
     * Implements intelligent conflict resolution to handle CSV vs API data conflicts.
     *
     * Conflict Resolution Strategy (Option 3 - Metrics History):
     * - New posts: Create in posts table with source='api'
     * - Existing CSV posts: Keep CSV data in posts table, add API snapshot to metrics_history
     * - Existing API posts: Update posts table with latest API data
     *
     * This preserves historical CSV baselines while tracking metric growth over time.
     *
     * Process:
     * 1. Fetch posts from Metricool API
     * 2. Map Metricool data to SocialBit schema
     * 3. For each post:
     *    - Check if post exists (by platform + platform_post_id)
     *    - If new: Create in posts table
     *    - If exists from CSV: Keep CSV, add metrics snapshot
     *    - If exists from API: Update with latest metrics
     *
     * @param int $clientId SocialBit client ID (tenant)
     * @param string $blogId Metricool blog/brand ID
     * @param string $platform Platform to sync (instagram, facebook, tiktok, etc.)
     * @param array $filters Optional date filters (start, end)
     *
     * @return array Sync results:
     *               - created (int): Number of new posts created
     *               - updated (int): Number of posts updated
     *               - snapshots_created (int): Number of metrics snapshots created
     *               - errors (array): List of errors encountered
     *
     * @throws Exception If API request fails
     */
    public function syncPosts(int $clientId, string $blogId, string $platform, array $filters = []): array {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'snapshots_created' => 0,
            'errors' => [],
        ];

        try {
            // Fetch posts from Metricool
            $metricoolPosts = $this->getPosts($blogId, $platform, $filters);

            foreach ($metricoolPosts as $metricoolPost) {
                try {
                    // Map Metricool data to SocialBit schema
                    $postData = $this->mapMetricoolPost($metricoolPost, $platform, $clientId);

                    // Check if post already exists
                    $existingPost = $this->postRepository->findByPlatformPostId(
                        $postData['platform'],
                        $postData['platform_post_id']
                    );

                    if ($existingPost) {
                        // Post exists - apply conflict resolution strategy
                        $existingSource = $existingPost['import_source'] ?? 'unknown';

                        if ($existingSource === 'csv') {
                            // CONFLICT: CSV data exists
                            // Strategy: Keep CSV data in posts table, add API snapshot to metrics_history
                            // This preserves the baseline CSV import while tracking current metrics

                            $snapshotResult = $this->metricsHistoryRepository->upsert([
                                'client_id' => $clientId,
                                'post_id' => $existingPost['id'],
                                'snapshot_date' => date('Y-m-d'),
                                'views' => $postData['views'],
                                'likes' => $postData['likes'],
                                'comments' => $postData['comments'],
                                'shares' => $postData['shares'],
                                'saves' => $postData['saves'],
                                'reach' => $postData['reach'],
                                'impressions' => $postData['impressions'],
                                'source' => 'api',
                            ]);

                            if ($snapshotResult['action'] === 'created') {
                                $stats['snapshots_created']++;
                            }

                            // Note: posts table NOT updated - CSV data preserved
                        } else {
                            // No conflict: existing data is also from API
                            // Strategy: Update with latest metrics
                            $this->postRepository->update($existingPost['id'], $postData);
                            $stats['updated']++;

                            // Also create snapshot for trend tracking
                            $this->metricsHistoryRepository->upsert([
                                'client_id' => $clientId,
                                'post_id' => $existingPost['id'],
                                'snapshot_date' => date('Y-m-d'),
                                'views' => $postData['views'],
                                'likes' => $postData['likes'],
                                'comments' => $postData['comments'],
                                'shares' => $postData['shares'],
                                'saves' => $postData['saves'],
                                'reach' => $postData['reach'],
                                'impressions' => $postData['impressions'],
                                'source' => 'api',
                            ]);
                        }
                    } else {
                        // Post doesn't exist - create new
                        $postId = $this->postRepository->create($postData);
                        $stats['created']++;

                        // Create initial snapshot
                        $this->metricsHistoryRepository->create([
                            'client_id' => $clientId,
                            'post_id' => $postId,
                            'snapshot_date' => date('Y-m-d'),
                            'views' => $postData['views'],
                            'likes' => $postData['likes'],
                            'comments' => $postData['comments'],
                            'shares' => $postData['shares'],
                            'saves' => $postData['saves'],
                            'reach' => $postData['reach'],
                            'impressions' => $postData['impressions'],
                            'source' => 'api',
                        ]);
                        $stats['snapshots_created']++;
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = [
                        'post_id' => $metricoolPost['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } catch (Exception $e) {
            throw new Exception("Metricool sync failed: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Map Metricool post data to SocialBit database schema.
     *
     * Transforms Metricool API response format to match SocialBit's posts table structure.
     * Handles platform-specific field differences.
     *
     * Metricool field mappings:
     * - id → platform_post_id
     * - text/caption → caption
     * - published_at/date → posted_date
     * - impressions/views → views
     * - likes → likes
     * - comments → comments
     * - shares → shares
     * - saves → saves
     * - reach → reach (Instagram/Facebook)
     *
     * @param array $metricoolData Raw post data from Metricool API
     * @param string $platform Platform name (for normalization)
     * @param int $clientId Client ID for multi-tenant isolation
     *
     * @return array Mapped post data ready for database insertion
     *
     * @note Field availability varies by platform - uses null coalescing for safety
     */
    private function mapMetricoolPost(array $metricoolData, string $platform, int $clientId): array {
        // Normalize platform name (remove _reels, _stories suffix)
        $basePlatform = strtok($platform, '_');

        // Map Metricool fields to SocialBit schema
        return [
            'client_id' => $clientId,
            'platform' => $basePlatform,
            'platform_post_id' => (string)($metricoolData['id'] ?? ''),
            'post_url' => $metricoolData['url'] ?? $metricoolData['permalink'] ?? null,
            'caption' => $metricoolData['text'] ?? $metricoolData['caption'] ?? $metricoolData['message'] ?? null,
            'hashtags' => $this->extractHashtags($metricoolData['text'] ?? ''),
            'posted_date' => $this->parseDate($metricoolData['published_at'] ?? $metricoolData['date'] ?? null),
            'posted_time' => $this->parseTime($metricoolData['published_at'] ?? $metricoolData['date'] ?? null),
            'views' => (int)($metricoolData['impressions'] ?? $metricoolData['views'] ?? 0),
            'likes' => (int)($metricoolData['likes'] ?? 0),
            'comments' => (int)($metricoolData['comments'] ?? 0),
            'shares' => (int)($metricoolData['shares'] ?? 0),
            'saves' => (int)($metricoolData['saves'] ?? 0),
            'reach' => (int)($metricoolData['reach'] ?? 0),
            'impressions' => (int)($metricoolData['impressions'] ?? 0),
            'engagement_rate' => (float)($metricoolData['engagement'] ?? $metricoolData['engagement_rate'] ?? 0.0),
            'import_source' => 'api',
        ];
    }

    /**
     * Extract hashtags from post text.
     *
     * Parses caption text to extract hashtags and returns them as comma-separated string.
     *
     * @param string $text Post caption/text
     *
     * @return string|null Comma-separated hashtags without # symbol, or null if none found
     *
     * @example
     * Input: "Check this out! #marketing #socialmedia #tips"
     * Output: "marketing,socialmedia,tips"
     */
    private function extractHashtags(string $text): ?string {
        preg_match_all('/#(\w+)/u', $text, $matches);

        if (empty($matches[1])) {
            return null;
        }

        return implode(',', $matches[1]);
    }

    /**
     * Parse date from Metricool timestamp.
     *
     * Converts various date formats to YYYY-MM-DD format for database storage.
     *
     * @param string|null $datetime Metricool date/datetime string
     *
     * @return string|null Date in YYYY-MM-DD format, or null if invalid
     */
    private function parseDate(?string $datetime): ?string {
        if (!$datetime) {
            return null;
        }

        // Handle YYYYMMDD format (Metricool date parameters)
        if (preg_match('/^\d{8}$/', $datetime)) {
            return substr($datetime, 0, 4) . '-' . substr($datetime, 4, 2) . '-' . substr($datetime, 6, 2);
        }

        // Try to parse as standard datetime
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Parse time from Metricool timestamp.
     *
     * Extracts time component from datetime string.
     *
     * @param string|null $datetime Metricool datetime string
     *
     * @return string|null Time in HH:MM:SS format, or null if invalid
     */
    private function parseTime(?string $datetime): ?string {
        if (!$datetime) {
            return null;
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return null;
        }

        return date('H:i:s', $timestamp);
    }

    /**
     * Make HTTP request to Metricool API.
     *
     * Handles authentication, request building, and response parsing.
     * All requests include:
     * - X-Mc-Auth header (userToken)
     * - userId query parameter
     * - blogId query parameter (if provided in params)
     *
     * @param string $endpoint API endpoint path (e.g., '/admin/simpleProfiles')
     * @param array $params Query parameters
     *
     * @return array Decoded JSON response
     *
     * @throws Exception If request fails or response is invalid
     */
    private function makeRequest(string $endpoint, array $params = []): array {
        // Build URL with query parameters
        $params['userId'] = $this->userId;
        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Mc-Auth: ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($response === false) {
            throw new Exception("Metricool API request failed: " . $error);
        }

        // Handle HTTP errors
        if ($httpCode >= 400) {
            throw new Exception("Metricool API error: HTTP {$httpCode} - {$response}");
        }

        // Decode JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from Metricool API: " . json_last_error_msg());
        }

        // Check for API-level errors
        if (isset($data['error'])) {
            throw new Exception("Metricool API error: " . ($data['error']['message'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Test API connection and credentials.
     *
     * Validates that API credentials are working by fetching connected profiles.
     * Useful for setup/configuration testing.
     *
     * @return bool True if credentials are valid and API is accessible
     */
    public function testConnection(): bool {
        try {
            $profiles = $this->getConnectedProfiles();
            return is_array($profiles);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get last sync timestamps for all connected profiles.
     *
     * Retrieves the last synchronization time for each connected platform.
     * Useful for tracking when data was last updated.
     *
     * API Endpoint: GET /profile/lastsyncs
     *
     * @param string $blogId Blog/brand ID
     *
     * @return array Last sync timestamps (key: platform, value: Unix timestamp)
     *
     * @throws Exception If API request fails
     */
    public function getLastSyncs(string $blogId): array {
        $endpoint = '/profile/lastsyncs';
        $response = $this->makeRequest($endpoint, ['blogId' => $blogId]);

        return $response['data'] ?? [];
    }
}
