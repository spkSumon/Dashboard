<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

/**
 * WebAnalyticsRepository - Data access layer for website analytics (Fathom, Google Analytics).
 *
 * Handles CRUD operations for website_analytics and post_website_traffic tables.
 * Provides methods for storing analytics data and correlating traffic with social posts.
 *
 * Database Tables:
 * - website_analytics: Daily aggregated website metrics by source
 * - post_website_traffic: Traffic attribution to specific social posts
 *
 * @package App\Repositories
 */
class WebAnalyticsRepository {
    /**
     * Database instance.
     *
     * @var Database
     */
    private Database $db;

    /**
     * Constructor - Inject database dependency.
     *
     * @param Database $db Database connection instance
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    // ==================== WEBSITE ANALYTICS ====================

    /**
     * Store or update website analytics data.
     *
     * Upserts daily analytics record for a specific source (Fathom, Google Analytics).
     * Uses UNIQUE KEY (client_id, source, date) to prevent duplicates.
     *
     * @param int $clientId Tenant ID
     * @param string $source Data source ('fathom', 'google_business')
     * @param string $date Date in YYYY-MM-DD format
     * @param array $data Analytics data:
     *                    - page_views (int): Total page views
     *                    - unique_visitors (int): Unique visitors
     *                    - referral_visits (int): Visits from social media
     *                    - referral_source (string|null): Primary referral source
     *                    - bounce_rate (float): Bounce rate percentage
     *                    - avg_session_duration (int): Average session in seconds
     *                    - conversions (int): Goal completions
     *                    - data_json (array|null): Raw API response for debugging
     *
     * @return bool True if stored successfully
     * @throws Exception If database operation fails
     */
    public function storeAnalytics(int $clientId, string $source, string $date, array $data): bool {
        $sql = "
            INSERT INTO website_analytics (
                client_id, source, date,
                page_views, unique_visitors, referral_visits, referral_source,
                bounce_rate, avg_session_duration, conversions, data_json
            ) VALUES (
                :client_id, :source, :date,
                :page_views, :unique_visitors, :referral_visits, :referral_source,
                :bounce_rate, :avg_session_duration, :conversions, :data_json
            )
            ON DUPLICATE KEY UPDATE
                page_views = VALUES(page_views),
                unique_visitors = VALUES(unique_visitors),
                referral_visits = VALUES(referral_visits),
                referral_source = VALUES(referral_source),
                bounce_rate = VALUES(bounce_rate),
                avg_session_duration = VALUES(avg_session_duration),
                conversions = VALUES(conversions),
                data_json = VALUES(data_json)
        ";

        $params = [
            ':client_id' => $clientId,
            ':source' => $source,
            ':date' => $date,
            ':page_views' => $data['page_views'] ?? 0,
            ':unique_visitors' => $data['unique_visitors'] ?? 0,
            ':referral_visits' => $data['referral_visits'] ?? 0,
            ':referral_source' => $data['referral_source'] ?? null,
            ':bounce_rate' => $data['bounce_rate'] ?? 0.0,
            ':avg_session_duration' => $data['avg_session_duration'] ?? 0,
            ':conversions' => $data['conversions'] ?? 0,
            ':data_json' => isset($data['data_json']) ? json_encode($data['data_json']) : null,
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Get website analytics for a date range.
     *
     * Retrieves aggregated website metrics grouped by date.
     *
     * @param int $clientId Tenant ID
     * @param string $source Data source filter ('fathom', 'google_business', or null for all)
     * @param string $dateFrom Start date in YYYY-MM-DD format
     * @param string $dateTo End date in YYYY-MM-DD format
     *
     * @return array Array of analytics records ordered by date DESC
     */
    public function getAnalyticsByDateRange(
        int $clientId,
        ?string $source,
        string $dateFrom,
        string $dateTo
    ): array {
        $sql = "
            SELECT
                id, source, date,
                page_views, unique_visitors, referral_visits, referral_source,
                bounce_rate, avg_session_duration, conversions,
                created_at
            FROM website_analytics
            WHERE client_id = :client_id
                AND date BETWEEN :date_from AND :date_to
        ";

        $params = [
            ':client_id' => $clientId,
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo,
        ];

        if ($source !== null) {
            $sql .= " AND source = :source";
            $params[':source'] = $source;
        }

        $sql .= " ORDER BY date DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total visitors for a date range (for jungle dashboard).
     *
     * Returns sum of unique visitors across all sources.
     *
     * @param int $clientId Tenant ID
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     *
     * @return int Total unique visitors
     */
    public function getTotalVisitors(int $clientId, string $dateFrom, string $dateTo): int {
        $sql = "
            SELECT SUM(unique_visitors) as total
            FROM website_analytics
            WHERE client_id = :client_id
                AND date BETWEEN :date_from AND :date_to
        ";

        $result = $this->db->query($sql, [
            ':client_id' => $clientId,
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo,
        ])->fetch(PDO::FETCH_ASSOC);

        return (int)($result['total'] ?? 0);
    }

    // ==================== POST TRAFFIC CORRELATION ====================

    /**
     * Store traffic attribution for a specific post.
     *
     * Links website traffic to a social media post based on referral data.
     * Useful for identifying which posts drive the most website visits.
     *
     * @param int $clientId Tenant ID
     * @param int $postId Post ID from posts table
     * @param string $date Date of traffic
     * @param array $data Traffic data:
     *                    - referral_visits (int): Number of visits from this post
     *                    - referral_source (string): Platform/source (e.g., 'tiktok', 'instagram')
     *                    - bounce_rate (float): Bounce rate for these visits
     *                    - avg_session_duration (int): Average session duration in seconds
     *                    - conversions (int): Conversions attributed to this post
     *
     * @return bool True if stored successfully
     * @throws Exception If database operation fails
     */
    public function storePostTraffic(int $clientId, int $postId, string $date, array $data): bool {
        $sql = "
            INSERT INTO post_website_traffic (
                client_id, post_id, date,
                referral_visits, referral_source,
                bounce_rate, avg_session_duration, conversions
            ) VALUES (
                :client_id, :post_id, :date,
                :referral_visits, :referral_source,
                :bounce_rate, :avg_session_duration, :conversions
            )
        ";

        $params = [
            ':client_id' => $clientId,
            ':post_id' => $postId,
            ':date' => $date,
            ':referral_visits' => $data['referral_visits'] ?? 0,
            ':referral_source' => $data['referral_source'] ?? null,
            ':bounce_rate' => $data['bounce_rate'] ?? 0.0,
            ':avg_session_duration' => $data['avg_session_duration'] ?? 0,
            ':conversions' => $data['conversions'] ?? 0,
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Get top posts that drove website traffic.
     *
     * Retrieves posts ordered by total referral visits with post details.
     * Essential for understanding which content brings people to your website.
     *
     * @param int $clientId Tenant ID
     * @param int $limit Number of posts to return (default: 10)
     * @param string|null $dateFrom Optional start date filter
     * @param string|null $dateTo Optional end date filter
     *
     * @return array Array of posts with traffic data, each containing:
     *               - post_id, platform, topic, caption (preview)
     *               - total_visits, total_conversions
     *               - avg_bounce_rate, avg_session_duration
     *               - posted_date
     */
    public function getTopTrafficDrivingPosts(
        int $clientId,
        int $limit = 10,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $sql = "
            SELECT
                p.id as post_id,
                p.platform,
                p.topic,
                SUBSTRING(p.caption, 1, 100) as caption_preview,
                p.posted_date,
                SUM(pwt.referral_visits) as total_visits,
                SUM(pwt.conversions) as total_conversions,
                AVG(pwt.bounce_rate) as avg_bounce_rate,
                AVG(pwt.avg_session_duration) as avg_session_duration
            FROM post_website_traffic pwt
            INNER JOIN posts p ON pwt.post_id = p.id
            WHERE pwt.client_id = :client_id
        ";

        $params = [':client_id' => $clientId];

        if ($dateFrom !== null) {
            $sql .= " AND pwt.date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= " AND pwt.date <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql .= "
            GROUP BY p.id, p.platform, p.topic, p.caption, p.posted_date
            ORDER BY total_visits DESC
            LIMIT :limit
        ";

        $params[':limit'] = $limit;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get traffic data for a specific post.
     *
     * Retrieves all traffic attribution records for a single post.
     *
     * @param int $postId Post ID
     *
     * @return array Array of traffic records ordered by date DESC
     */
    public function getPostTraffic(int $postId): array {
        $sql = "
            SELECT
                date, referral_visits, referral_source,
                bounce_rate, avg_session_duration, conversions
            FROM post_website_traffic
            WHERE post_id = :post_id
            ORDER BY date DESC
        ";

        return $this->db->query($sql, [':post_id' => $postId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Correlate referrer data with posts automatically.
     *
     * Matches Fathom referrer hostnames to posts based on platform and date proximity.
     * This is a smart correlation algorithm that attributes traffic to the most likely post.
     *
     * Algorithm:
     * 1. Map referrer hostname to platform (e.g., 't.co' -> 'tiktok')
     * 2. Find posts from that platform posted within 7 days before the traffic spike
     * 3. Attribute traffic to the most recent post (assumption: recent posts drive traffic)
     *
     * @param int $clientId Tenant ID
     * @param array $referrers Referrer data from Fathom (array of ['referrer', 'visits', 'date'])
     *
     * @return int Number of posts correlated with traffic
     */
    public function correlateReferrersWithPosts(int $clientId, array $referrers): int {
        $platformMap = [
            't.co' => 'tiktok',
            'twitter.com' => 'tiktok',
            'instagram.com' => 'instagram',
            'facebook.com' => 'facebook',
            'fb.com' => 'facebook',
            'youtube.com' => 'youtube',
            'youtu.be' => 'youtube',
        ];

        $correlatedCount = 0;

        foreach ($referrers as $referrer) {
            $hostname = $referrer['referrer'] ?? '';
            $platform = $platformMap[strtolower($hostname)] ?? null;

            if ($platform === null) {
                continue; // Skip unknown referrers
            }

            // Find most recent post from this platform within 7 days before traffic date
            $sql = "
                SELECT id, posted_date
                FROM posts
                WHERE client_id = :client_id
                    AND platform = :platform
                    AND posted_date <= :traffic_date
                    AND posted_date >= DATE_SUB(:traffic_date, INTERVAL 7 DAY)
                ORDER BY posted_date DESC
                LIMIT 1
            ";

            $trafficDate = $referrer['date'] ?? date('Y-m-d');
            $post = $this->db->query($sql, [
                ':client_id' => $clientId,
                ':platform' => $platform,
                ':traffic_date' => $trafficDate,
            ])->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                // Attribute this traffic to the post
                $this->storePostTraffic($clientId, (int)$post['id'], $trafficDate, [
                    'referral_visits' => (int)($referrer['visits'] ?? 0),
                    'referral_source' => $platform,
                    'bounce_rate' => (float)($referrer['bounce_rate'] ?? 0.0),
                    'avg_session_duration' => (int)($referrer['avg_duration'] ?? 0),
                    'conversions' => 0, // Fathom doesn't provide this in referrers
                ]);

                $correlatedCount++;
            }
        }

        return $correlatedCount;
    }
}
