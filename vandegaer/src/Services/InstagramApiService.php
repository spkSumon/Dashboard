<?php
namespace App\Services;

use App\Repositories\SettingsRepository;
use App\Repositories\PostRepository;
use Exception;

/**
 * InstagramApiService - Instagram Graph API integration.
 *
 * Handles authentication, token management, and data fetching from Instagram Graph API.
 * Uses the Instagram Basic Display API and Instagram Graph API for business accounts.
 *
 * API Documentation: https://developers.facebook.com/docs/instagram-api
 *
 * @package App\Services
 */
class InstagramApiService {
    private const GRAPH_API_BASE = 'https://graph.instagram.com';
    private const GRAPH_API_VERSION = 'v18.0';

    private SettingsRepository $settings;
    private PostRepository $postRepo;
    private ?string $accessToken = null;

    public function __construct(SettingsRepository $settings, PostRepository $postRepo) {
        $this->settings = $settings;
        $this->postRepo = $postRepo;
        $this->loadAccessToken();
    }

    /**
     * Load access token from settings.
     */
    private function loadAccessToken(): void {
        $tokenSetting = $this->settings->get('instagram_access_token');
        $this->accessToken = $tokenSetting['value'] ?? null;
    }

    /**
     * Save Instagram API credentials to settings.
     *
     * @param array $credentials Associative array with keys:
     *                          - app_id: Meta App ID
     *                          - app_secret: Meta App Secret
     *                          - access_token: Long-lived access token
     * @return void
     */
    public function saveCredentials(array $credentials): void {
        if (!empty($credentials['app_id'])) {
            $this->settings->upsert('instagram_app_id', $credentials['app_id']);
        }

        if (!empty($credentials['app_secret'])) {
            $this->settings->upsert('instagram_app_secret', $credentials['app_secret']);
        }

        if (!empty($credentials['access_token'])) {
            $this->settings->upsert('instagram_access_token', $credentials['access_token']);
            $this->accessToken = $credentials['access_token'];
        }
    }

    /**
     * Test API connection by fetching user profile.
     *
     * @return array User profile data
     * @throws Exception If connection fails
     */
    public function testConnection(): array {
        if (!$this->accessToken) {
            throw new Exception('No access token configured. Please save credentials first.');
        }

        $url = self::GRAPH_API_BASE . "/me?fields=id,username,account_type,media_count&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Instagram API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response;
    }

    /**
     * Fetch user's Instagram posts/media.
     *
     * @param int $limit Number of posts to fetch (default: 25, max: 100)
     * @return array Array of media items
     * @throws Exception If API request fails
     */
    public function fetchPosts(int $limit = 25): array {
        if (!$this->accessToken) {
            throw new Exception('No access token configured');
        }

        $fields = 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count';
        $url = self::GRAPH_API_BASE . "/me/media?fields={$fields}&limit={$limit}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Instagram API error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data'] ?? [];
    }

    /**
     * Fetch detailed insights for a specific media item.
     *
     * Note: Insights are only available for media owned by Instagram Business/Creator accounts.
     *
     * @param string $mediaId Instagram media ID
     * @return array Insights data
     * @throws Exception If API request fails
     */
    public function fetchMediaInsights(string $mediaId): array {
        if (!$this->accessToken) {
            throw new Exception('No access token configured');
        }

        // Metrics available: engagement, impressions, reach, saved
        $metrics = 'engagement,impressions,reach,saved';
        $url = "https://graph.facebook.com/" . self::GRAPH_API_VERSION . "/{$mediaId}/insights?metric={$metrics}&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Instagram insights error: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data'] ?? [];
    }

    /**
     * Save fetched posts to database.
     *
     * @param array $posts Array of Instagram media items from API
     * @param int|null $clientId Client ID for multi-tenant support (null for single-tenant)
     * @return int Number of posts saved
     */
    public function savePosts(array $posts, ?int $clientId = null): int {
        $savedCount = 0;

        foreach ($posts as $post) {
            try {
                $postData = $this->transformPostData($post, $clientId);

                // Check if post already exists by platform_post_id
                $existing = $this->postRepo->findByPlatformPostId('instagram', $post['id']);

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
                error_log("Failed to save Instagram post {$post['id']}: " . $e->getMessage());
            }
        }

        return $savedCount;
    }

    /**
     * Transform Instagram API response to database format.
     *
     * @param array $apiPost Instagram media item from API
     * @param int|null $clientId Client ID for multi-tenant support
     * @return array Post data for database
     */
    private function transformPostData(array $apiPost, ?int $clientId = null): array {
        $data = [
            'platform' => 'instagram',
            'platform_post_id' => $apiPost['id'],
            'post_url' => $apiPost['permalink'] ?? null,
            'caption' => $apiPost['caption'] ?? null,
            'post_type' => strtolower($apiPost['media_type'] ?? 'post'), // IMAGE, VIDEO, CAROUSEL_ALBUM
            'posted_date' => isset($apiPost['timestamp']) ? date('Y-m-d', strtotime($apiPost['timestamp'])) : date('Y-m-d'),
            'posted_time' => isset($apiPost['timestamp']) ? date('H:i:s', strtotime($apiPost['timestamp'])) : null,
            'likes' => $apiPost['like_count'] ?? 0,
            'comments' => $apiPost['comments_count'] ?? 0,
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
     * Extract hashtags from caption text.
     *
     * @param string $caption Post caption
     * @return array Array of hashtags (without # symbol)
     */
    private function extractHashtags(string $caption): array {
        preg_match_all('/#(\w+)/', $caption, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Refresh a long-lived access token.
     *
     * Long-lived tokens expire after 60 days. This method exchanges the current
     * token for a new one with a fresh 60-day expiration.
     *
     * @return array New token data
     * @throws Exception If refresh fails
     */
    public function refreshAccessToken(): array {
        $appId = $this->settings->get('instagram_app_id')['value'] ?? null;
        $appSecret = $this->settings->get('instagram_app_secret')['value'] ?? null;

        if (!$appId || !$appSecret || !$this->accessToken) {
            throw new Exception('Missing credentials for token refresh');
        }

        $url = self::GRAPH_API_BASE . "/refresh_access_token?grant_type=ig_refresh_token&access_token=" . urlencode($this->accessToken);

        $response = $this->makeRequest($url);

        if (isset($response['error'])) {
            throw new Exception('Token refresh failed: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        if (isset($response['access_token'])) {
            // Save new token
            $this->settings->upsert('instagram_access_token', $response['access_token']);
            $this->accessToken = $response['access_token'];
        }

        return $response;
    }

    /**
     * Make HTTP request to Instagram API.
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
