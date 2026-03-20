<?php
namespace App\Services;

use App\Core\Database;
use App\Repositories\PostRepository;
use Exception;

/**
 * Service voor TikTok Analytics API (video list + metrics)
 */
class TikTokAnalyticsService {
    private $videoListUrl = 'https://open.tiktokapis.com/v2/video/list/';
    private $videoQueryUrl = 'https://open.tiktokapis.com/v2/research/video/query/';
    private $userInfoUrl = 'https://open.tiktokapis.com/v2/user/info/';
    private $oauth;
    private $postRepo;
    private $db;

    public function __construct(TikTokOAuthService $oauth, PostRepository $postRepo, Database $db) {
        $this->oauth = $oauth;
        $this->postRepo = $postRepo;
        $this->db = $db;
    }

    /**
     * Fetch user's videos from TikTok API
     * @param string $accessToken Access token
     * @return array Videos data
     * @throws Exception Als API call faalt
     */
    public function fetchUserVideos(string $accessToken): array {
        $allVideos = [];
        $cursor = 0;
        $hasMore = true;

        while ($hasMore) {
            $url = $this->videoListUrl . '?' . http_build_query([
                'fields' => 'id,title,create_time,duration,cover_image_url,share_url,video_description',
                'max_count' => 20,
                'cursor' => $cursor
            ]);

            $response = $this->makeAuthenticatedRequest($url, 'GET', $accessToken);

            if (isset($response['error'])) {
                throw new Exception('TikTok video list failed: ' . ($response['error']['message'] ?? 'Unknown error'));
            }

            if (isset($response['data']['videos'])) {
                $allVideos = array_merge($allVideos, $response['data']['videos']);
            }

            $hasMore = $response['data']['has_more'] ?? false;
            $cursor = $response['data']['cursor'] ?? 0;

            // Safety: max 100 videos per sync
            if (count($allVideos) >= 100) {
                break;
            }
        }

        return $allVideos;
    }

    /**
     * Fetch video analytics (views, likes, comments, shares)
     * @param array $videoIds Array of video IDs
     * @param string $accessToken Access token
     * @return array Analytics data per video
     * @throws Exception Als API call faalt
     */
    public function fetchVideoAnalytics(array $videoIds, string $accessToken): array {
        if (empty($videoIds)) {
            return [];
        }

        // TikTok Research API query
        $query = [
            'query' => [
                'and' => [
                    [
                        'field_name' => 'video_id',
                        'operation' => 'IN',
                        'field_values' => $videoIds
                    ]
                ]
            ],
            'fields' => [
                'video_id',
                'view_count',
                'like_count',
                'comment_count',
                'share_count',
                'create_time',
                'video_description',
                'hashtag_names'
            ]
        ];

        $response = $this->makeAuthenticatedRequest(
            $this->videoQueryUrl,
            'POST',
            $accessToken,
            $query
        );

        if (isset($response['error'])) {
            throw new Exception('TikTok analytics query failed: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['data']['videos'] ?? [];
    }

    /**
     * Import video + analytics naar database
     * @param array $videoData Video metadata van TikTok
     * @param array|null $analyticsData Analytics data (optional)
     * @return int Post ID
     */
    public function importVideo(array $videoData, ?array $analyticsData = null): int {
        // Merge video data met analytics data
        $data = array_merge($videoData, $analyticsData ?? []);

        // Transform TikTok data naar posts tabel format
        $postData = [
            'platform' => 'tiktok',
            'platform_post_id' => $data['id'] ?? $data['video_id'],
            'post_url' => $data['share_url'] ?? null,
            'caption' => $data['video_description'] ?? $data['title'] ?? null,
            'hashtags' => $this->extractHashtags($data),
            'posted_date' => $this->extractDate($data['create_time'] ?? null),
            'posted_time' => $this->extractTime($data['create_time'] ?? null),
            'views' => $data['view_count'] ?? 0,
            'likes' => $data['like_count'] ?? 0,
            'comments' => $data['comment_count'] ?? 0,
            'shares' => $data['share_count'] ?? 0,
            'engagement_rate' => $this->calculateEngagementRate($data),
            'post_type' => 'video', // TikTok heeft alleen videos
            'import_source' => 'api',
        ];

        // Check if post already exists
        $existingPost = $this->postRepo->findByPlatformPostId(
            $postData['platform'],
            $postData['platform_post_id']
        );

        if ($existingPost) {
            // Update existing post
            $this->postRepo->update($existingPost['id'], $postData);
            return $existingPost['id'];
        } else {
            // Create new post
            return $this->postRepo->create($postData);
        }
    }

    /**
     * Sync all videos (full sync)
     * @return array Sync results
     */
    public function syncAllVideos(): array {
        try {
            // Get valid access token
            $accessToken = $this->oauth->getValidToken();

            if (!$accessToken) {
                throw new Exception('No valid TikTok token. Please reconnect.');
            }

            // Fetch videos
            $videos = $this->fetchUserVideos($accessToken);

            if (empty($videos)) {
                return [
                    'synced' => 0,
                    'new' => 0,
                    'updated' => 0,
                    'failed' => 0
                ];
            }

            // Extract video IDs
            $videoIds = array_map(function($v) { return $v['id']; }, $videos);

            // Fetch analytics for all videos
            $analyticsData = $this->fetchVideoAnalytics($videoIds, $accessToken);

            // Create lookup array
            $analyticsMap = [];
            foreach ($analyticsData as $analytics) {
                $analyticsMap[$analytics['video_id']] = $analytics;
            }

            // Import each video
            $new = 0;
            $updated = 0;
            $failed = 0;

            foreach ($videos as $video) {
                try {
                    $videoId = $video['id'];
                    $analytics = $analyticsMap[$videoId] ?? null;

                    // Check if exists
                    $existingPost = $this->postRepo->findByPlatformPostId('tiktok', $videoId);

                    $this->importVideo($video, $analytics);

                    if ($existingPost) {
                        $updated++;
                    } else {
                        $new++;
                    }
                } catch (Exception $e) {
                    $failed++;
                    error_log("Failed to import video {$video['id']}: " . $e->getMessage());
                }
            }

            return [
                'synced' => $new + $updated,
                'new' => $new,
                'updated' => $updated,
                'failed' => $failed
            ];

        } catch (Exception $e) {
            throw new Exception('Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract hashtags from TikTok data
     */
    private function extractHashtags(array $data): ?string {
        if (isset($data['hashtag_names']) && is_array($data['hashtag_names'])) {
            return implode(', ', array_map(function($h) { return '#' . $h; }, $data['hashtag_names']));
        }

        // Fallback: extract from description
        if (isset($data['video_description'])) {
            preg_match_all('/#(\w+)/', $data['video_description'], $matches);
            if (!empty($matches[0])) {
                return implode(', ', $matches[0]);
            }
        }

        return null;
    }

    /**
     * Extract date from unix timestamp
     */
    private function extractDate(?int $timestamp): ?string {
        if (!$timestamp) {
            return null;
        }
        return date('Y-m-d', $timestamp);
    }

    /**
     * Extract time from unix timestamp
     */
    private function extractTime(?int $timestamp): ?string {
        if (!$timestamp) {
            return null;
        }
        return date('H:i:s', $timestamp);
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(array $data): float {
        $views = $data['view_count'] ?? 0;
        if ($views == 0) {
            return 0.0;
        }

        $likes = $data['like_count'] ?? 0;
        $comments = $data['comment_count'] ?? 0;
        $shares = $data['share_count'] ?? 0;

        return round((($likes + $comments + $shares) / $views) * 100, 2);
    }

    /**
     * Make authenticated request naar TikTok API
     * @param string $url API endpoint
     * @param string $method HTTP method (GET/POST)
     * @param string $accessToken Access token
     * @param array|null $data POST data (for POST requests)
     * @return array Response data
     * @throws Exception Als request faalt
     */
    private function makeAuthenticatedRequest(
        string $url,
        string $method,
        string $accessToken,
        ?array $data = null
    ): array {
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST' && $data !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode . ' - ' . $response);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
