<?php
namespace App\Services;

use App\Repositories\SettingsRepository;
use App\Repositories\PostRepository;
use Exception;

/**
 * FacebookApiService - Facebook Graph API integration.
 *
 * Handles authentication, token management, and data fetching from Facebook Graph API.
 * Focuses on Facebook Pages and their posts/insights.
 *
 * API Documentation: https://developers.facebook.com/docs/graph-api
 *
 * @package App\Services
 */
class FacebookApiService {
    private const GRAPH_API_BASE = 'https://graph.facebook.com';
    private const GRAPH_API_VERSION = 'v18.0';

    private SettingsRepository $settings;
    private PostRepository $postRepo;
    private ?string $accessToken = null;
    private ?string $pageId = null;

    public function __construct(SettingsRepository $settings, PostRepository $postRepo) {
        $this->settings = $settings;
        $this->postRepo = $postRepo;
        $this->loadCredentials();
    }

    /**
     * Load credentials from settings.
     */
    private function loadCredentials(): void {
        $tokenSetting = $this->settings->get('facebook_access_token');
        $this->accessToken = $tokenSetting['value'] ?? null;

        $pageIdSetting = $this->settings->get('facebook_page_id');
        $this->pageId = $pageIdSetting['value'] ?? null;
    }

    /**
     * Save Facebook API credentials to settings.
     *
     * @param array $credentials Associative array with keys:
     *                          - access_token: Page access token
     *                          - page_id: Facebook Page ID
     * @return void
     */
    public function saveCredentials(array $credentials): void {
        if (!empty($credentials['access_token'])) {
            $this->settings->upsert('facebook_access_token', $credentials['access_token']);
            $this->accessToken = $credentials['access_token'];
        }

        if (!empty($credentials['page_id'])) {
            $this->settings->upsert('facebook_page_id', $credentials['page_id']);
            $this->pageId = $credentials['page_id'];
        }
    }

    /**
     * Test API connection by fetching page details.
     *
     * @return array Page data
     * @throws Exception If connection fails
     */
    public function testConnection(): array {
        if (!$this->accessToken || !$this->pageId) {
            throw new Exception('No access token or page ID configured. Please save credentials first.');
        }

        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . "/{$this->pageId}?fields=id,name,fan_count,about,category&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Facebook API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response;
    }

    /**
     * Fetch page posts.
     *
     * @param int $limit Number of posts to fetch (default: 25, max: 100)
     * @return array Array of posts
     * @throws Exception If API request fails
     */
    public function fetchPosts(int $limit = 25): array {
        if (!$this->accessToken || !$this->pageId) {
            throw new Exception('No access token or page ID configured');
        }

        $fields = 'id,message,created_time,permalink_url,attachments{media_type,type,url},story,type';
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . "/{$this->pageId}/posts?fields={$fields}&limit={$limit}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Facebook API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data'] ?? [];
    }

    /**
     * Fetch post insights (metrics).
     *
     * Available metrics:
     * - post_engaged_users: People who clicked, liked, commented or shared
     * - post_impressions: Total impressions
     * - post_impressions_unique: Unique impressions (reach)
     * - post_reactions_by_type_total: Breakdown of reactions
     *
     * @param string $postId Facebook post ID
     * @return array Insights data
     * @throws Exception If API request fails
     */
    public function fetchPostInsights(string $postId): array {
        if (!$this->accessToken) {
            throw new Exception('No access token configured');
        }

        $metrics = 'post_engaged_users,post_impressions,post_impressions_unique,post_reactions_like_total,post_reactions_by_type_total';
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . "/{$postId}/insights?metric={$metrics}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Facebook insights error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data'] ?? [];
    }

    /**
     * Fetch post engagement details (likes, comments, shares).
     *
     * @param string $postId Facebook post ID
     * @return array Engagement data
     * @throws Exception If API request fails
     */
    public function fetchPostEngagement(string $postId): array {
        if (!$this->accessToken) {
            throw new Exception('No access token configured');
        }

        $fields = 'likes.summary(true),comments.summary(true),shares';
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . "/{$postId}?fields={$fields}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Facebook engagement error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return [
            'likes' => $response['likes']['summary']['total_count'] ?? 0,
            'comments' => $response['comments']['summary']['total_count'] ?? 0,
            'shares' => $response['shares']['count'] ?? 0,
        ];
    }

    /**
     * Save fetched posts to database.
     *
     * @param array $posts Array of Facebook posts from API
     * @param int|null $clientId Client ID for multi-tenant support (null for single-tenant)
     * @return int Number of posts saved
     */
    public function savePosts(array $posts, ?int $clientId = null): int {
        $savedCount = 0;

        foreach ($posts as $post) {
            try {
                // Fetch engagement metrics for this post
                $engagement = $this->fetchPostEngagement($post['id']);

                $postData = $this->transformPostData($post, $engagement, $clientId);

                // Check if post already exists by platform_post_id
                $existing = $this->postRepo->findByPlatformPostId('facebook', $post['id']);

                if ($existing) {
                    // Update existing post
                    $this->postRepo->update($existing['id'], $postData);
                } else {
                    // Create new post
                    $this->postRepo->create($postData);
                }

                $savedCount++;
            } catch (Exception $e) {
                // Log error but continue processing other posts
                error_log("Failed to save Facebook post {$post['id']}: " . $e->getMessage());
            }
        }

        return $savedCount;
    }

    /**
     * Transform Facebook API response to database format.
     *
     * @param array $apiPost Facebook post from API
     * @param array $engagement Engagement data (likes, comments, shares)
     * @param int|null $clientId Client ID for multi-tenant support
     * @return array Post data for database
     */
    private function transformPostData(array $apiPost, array $engagement, ?int $clientId = null): array {
        $data = [
            'platform' => 'facebook',
            'platform_post_id' => $apiPost['id'],
            'post_url' => $apiPost['permalink_url'] ?? null,
            'caption' => $apiPost['message'] ?? $apiPost['story'] ?? null,
            'post_type' => $this->detectPostType($apiPost),
            'posted_date' => isset($apiPost['created_time']) ? date('Y-m-d', strtotime($apiPost['created_time'])) : date('Y-m-d'),
            'posted_time' => isset($apiPost['created_time']) ? date('H:i:s', strtotime($apiPost['created_time'])) : null,
            'likes' => $engagement['likes'] ?? 0,
            'comments' => $engagement['comments'] ?? 0,
            'shares' => $engagement['shares'] ?? 0,
            'import_source' => 'api',
        ];

        // Add client_id if provided (multi-tenant)
        if ($clientId !== null) {
            $data['client_id'] = $clientId;
        }

        // Extract hashtags from caption
        if (!empty($data['caption'])) {
            $hashtags = $this->extractHashtags($data['caption']);
            if (!empty($hashtags)) {
                $data['hashtags'] = implode(',', $hashtags);
            }
        }

        return $data;
    }

    /**
     * Detect post type from Facebook post data.
     *
     * @param array $post Facebook post data
     * @return string Post type (photo, video, link, status, etc.)
     */
    private function detectPostType(array $post): string {
        // Check API post type field
        if (isset($post['type'])) {
            $type = strtolower($post['type']);
            // Map Facebook types to our standard types
            $typeMap = [
                'photo' => 'photo',
                'video' => 'video',
                'link' => 'link',
                'status' => 'status',
                'event' => 'event',
                'offer' => 'offer',
            ];
            return $typeMap[$type] ?? $type;
        }

        // Check attachments
        if (isset($post['attachments']['data'][0])) {
            $attachment = $post['attachments']['data'][0];
            $mediaType = $attachment['media_type'] ?? $attachment['type'] ?? 'unknown';
            return strtolower($mediaType);
        }

        return 'status';
    }

    /**
     * Extract hashtags from text.
     *
     * @param string $text Post text
     * @return array Array of hashtags (without # symbol)
     */
    private function extractHashtags(string $text): array {
        preg_match_all('/#(\w+)/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Get page insights (page-level metrics).
     *
     * Useful metrics:
     * - page_impressions: Total page impressions
     * - page_engaged_users: People who engaged with the page
     * - page_fans: Total page likes
     *
     * @param array $metrics Array of metric names to fetch
     * @param string $period Time period ('day', 'week', 'days_28')
     * @return array Insights data
     * @throws Exception If API request fails
     */
    public function fetchPageInsights(array $metrics, string $period = 'day'): array {
        if (!$this->accessToken || !$this->pageId) {
            throw new Exception('No access token or page ID configured');
        }

        $metricString = implode(',', $metrics);
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . "/{$this->pageId}/insights?metric={$metricString}&period={$period}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Facebook page insights error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data'] ?? [];
    }

    /**
     * Make HTTP request to Facebook Graph API.
     *
     * @param string $url Full API endpoint URL
     * @param string $method HTTP method (GET or POST)
     * @param array $data POST data (if method is POST)
     * @return array Decoded JSON response
     * @throws Exception If request fails
     */
    private function makeRequest(string $url, string $method = 'GET', array $data = []): array {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception('HTTP error: ' . $httpCode);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
