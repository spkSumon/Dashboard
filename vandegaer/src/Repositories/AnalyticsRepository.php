<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * AnalyticsRepository - Database queries for analytics and reporting.
 *
 * This repository provides specialized SQL queries for analytics, aggregations,
 * and reporting purposes. It handles complex queries involving multiple tables,
 * GROUP BY operations, and statistical calculations.
 *
 * Responsibilities:
 * - Execute analytics SQL queries (hashtags, engagement, demographics)
 * - Perform aggregations and statistical calculations
 * - Join multiple tables for comprehensive reports
 * - Filter data by platform and date ranges
 *
 * Architecture notes:
 * - Controllers: HTTP layer
 * - Services: Business logic and formulas
 * - Repositories: SQL queries (this class)
 *
 * @package App\Repositories
 */
class AnalyticsRepository {
    /**
     * Database connection instance.
     *
     * @var Database
     */
    private $db;

    /**
     * Constructor - Inject database dependency.
     *
     * @param Database $db Database instance for executing queries
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Get top performing hashtags with aggregate metrics.
     *
     * Calculates live statistics by joining posts, post_hashtags, and hashtag_performance
     * tables. Returns hashtags ranked by total usage and performance metrics.
     *
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Only posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Only posts with posted_date <= this date
     * @param int $limit Maximum number of hashtags to return (must be positive)
     *
     * @return array Array of hashtag records, each containing:
     *               - hashtag (string): Hashtag text (without # symbol)
     *               - total_posts (int): Number of posts using this hashtag
     *               - total_views (int): Sum of views across all posts
     *               - total_likes (int): Sum of likes across all posts
     *               - avg_views (float): Average views per post
     *               - avg_likes (float): Average likes per post
     *               - avg_engagement_rate (float): Average engagement percentage
     *               Ordered by total_posts DESC, then total_views DESC
     *
     * @note Engagement rate formula: ((likes + comments + shares) / views) * 100
     */
    public function topHashtags(?string $platform, ?string $from, ?string $to, int $limit): array {
        // Bereken live op posts + post_hashtags + hashtag_performance
        $sql = "
            SELECT
              hp.hashtag,
              COUNT(DISTINCT ph.post_id) AS total_posts,
              SUM(p.views) AS total_views,
              SUM(p.likes) AS total_likes,
              AVG(p.views) AS avg_views,
              AVG(p.likes) AS avg_likes,
              AVG(CASE WHEN p.views > 0 THEN ((p.likes + p.comments + p.shares) / p.views) * 100 ELSE NULL END) AS avg_engagement_rate
            FROM post_hashtags ph
            JOIN hashtag_performance hp ON hp.id = ph.hashtag_id
            JOIN posts p ON p.id = ph.post_id
            WHERE 1=1
        ";

        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND p.platform = ?"; $params[] = $platform; $types .= 's'; }
        // In schema staat 'posted_date' (niet post_date) -> we filter daarop
        if ($from) { $sql .= " AND p.posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND p.posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $sql .= " GROUP BY hp.hashtag
                  ORDER BY total_posts DESC, total_views DESC
                  LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Get hashtag performance trend over time (daily aggregation).
     *
     * Returns daily statistics for a specific hashtag, showing how posts and
     * engagement evolved across the specified date range.
     *
     * @param string $hashtag Hashtag to analyze (without # symbol)
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Only posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Only posts with posted_date <= this date
     *
     * @return array Array of daily records, each containing:
     *               - day (string): Date in YYYY-MM-DD format
     *               - posts (int): Number of posts using hashtag on this day
     *               - views (int): Sum of views on this day
     *               - likes (int): Sum of likes on this day
     *               - avg_engagement_rate (float): Average engagement rate for posts on this day
     *               Ordered by day ASC (chronological order)
     *               Returns empty array if hashtag not found
     *
     * @note Engagement rate formula: ((likes + comments + shares) / views) * 100
     */
    public function hashtagTrend(string $hashtag, ?string $platform, ?string $from, ?string $to): array {
        $sql = "
            SELECT
              p.posted_date AS day,
              COUNT(DISTINCT p.id) AS posts,
              SUM(p.views) AS views,
              SUM(p.likes) AS likes,
              AVG(CASE WHEN p.views > 0 THEN ((p.likes + p.comments + p.shares) / p.views) * 100 ELSE 0 END) AS avg_engagement_rate
            FROM post_hashtags ph
            JOIN hashtag_performance hp ON hp.id = ph.hashtag_id
            JOIN posts p ON p.id = ph.post_id
            WHERE hp.hashtag = ?
        ";
        $params = [$hashtag];
        $types = 's';

        if ($platform) { $sql .= " AND p.platform = ?"; $params[] = $platform; $types .= 's'; }
        if ($from) { $sql .= " AND p.posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND p.posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $sql .= " GROUP BY p.posted_date
                  ORDER BY p.posted_date ASC";

        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Get posts data for engagement calculations.
     *
     * Retrieves basic post data that controllers/services can use for further
     * engagement analysis and calculations. Returns raw post metrics without
     * computed engagement rates.
     *
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Posts with posted_date <= this date
     * @param int $limit Maximum number of posts to return (must be positive)
     *
     * @return array Array of post records, each containing:
     *               - id (int): Post ID
     *               - platform (string): Platform name
     *               - post_type (string|null): Content type
     *               - topic (string|null): Content topic
     *               - caption (string|null): Post caption
     *               - posted_date (string|null): Publication date
     *               - views (int): View count
     *               - likes (int): Like count
     *               - comments (int): Comment count
     *               - shares (int): Share count
     *               - saves (int): Save count
     *               Ordered by posted_date DESC, id DESC (most recent first)
     */
    public function postsForEngagement(?string $platform, ?string $from, ?string $to, int $limit): array {
        $sql = "
          SELECT id, platform, post_type, topic, caption, posted_date, views, likes, comments, shares, saves
          FROM posts
          WHERE 1=1
        ";
        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND platform = ?"; $params[] = $platform; $types .= 's'; }
        if ($from) { $sql .= " AND posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $sql .= " ORDER BY posted_date DESC, id DESC LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Get performance statistics grouped by post type.
     *
     * Aggregates post metrics by content type (video, image, carousel, reel, etc.)
     * to identify which formats perform best.
     *
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Posts with posted_date <= this date
     *
     * @return array Array of post type records, each containing:
     *               - post_type (string): Content type ('video', 'image', 'carousel', 'unknown', etc.)
     *               - posts (int): Number of posts of this type
     *               - avg_views (float): Average views per post
     *               - avg_likes (float): Average likes per post
     *               - avg_engagement_rate (float): Average engagement percentage
     *               Ordered by posts DESC (most common types first)
     *
     * @note Posts with NULL post_type are grouped as 'unknown'
     * @note Engagement rate formula: ((likes + comments + shares) / views) * 100
     */
    public function postTypeStats(?string $platform, ?string $from, ?string $to): array {
        $sql = "
          SELECT
            COALESCE(post_type, 'unknown') AS post_type,
            COUNT(*) AS posts,
            AVG(views) AS avg_views,
            AVG(likes) AS avg_likes,
            AVG(CASE WHEN views > 0 THEN ((likes + comments + shares) / views) * 100 ELSE 0 END) AS avg_engagement_rate
          FROM posts
          WHERE 1=1
        ";
        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND platform = ?"; $params[] = $platform; $types .= 's'; }
        if ($from) { $sql .= " AND posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $sql .= " GROUP BY COALESCE(post_type, 'unknown')
                  ORDER BY posts DESC";

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Get performance statistics grouped by topic.
     *
     * Aggregates post metrics by content topic/category (Tutorial, Product Review,
     * Behind the Scenes, etc.) to identify which topics resonate with the audience.
     *
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Posts with posted_date <= this date
     *
     * @return array Array of topic records, each containing:
     *               - topic (string): Topic/category name ('Tutorial', 'Product Review', 'unknown', etc.)
     *               - posts (int): Number of posts with this topic
     *               - avg_views (float): Average views per post
     *               - avg_likes (float): Average likes per post
     *               - avg_engagement_rate (float): Average engagement percentage
     *               Ordered by posts DESC (most common topics first)
     *
     * @note Posts with NULL topic are grouped as 'unknown'
     * @note Topics are user-defined during post creation or labeling
     * @note Engagement rate formula: ((likes + comments + shares) / views) * 100
     */
    public function topicStats(?string $platform, ?string $from, ?string $to): array {
        $sql = "
          SELECT
            COALESCE(topic, 'unknown') AS topic,
            COUNT(*) AS posts,
            AVG(views) AS avg_views,
            AVG(likes) AS avg_likes,
            AVG(CASE WHEN views > 0 THEN ((likes + comments + shares) / views) * 100 ELSE 0 END) AS avg_engagement_rate
          FROM posts
          WHERE 1=1
        ";
        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND platform = ?"; $params[] = $platform; $types .= 's'; }
        if ($from) { $sql .= " AND posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $sql .= " GROUP BY COALESCE(topic, 'unknown')
                  ORDER BY posts DESC";

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Get TikTok audience demographics summary (aggregated averages).
     *
     * Calculates average demographic percentages across all TikTok posts in the
     * specified date range. Joins posts with tiktok_demographics table.
     *
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Posts with posted_date >= this date
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Posts with posted_date <= this date
     *
     * @return array Demographic summary containing:
     *               - gender_male_pct (float|null): Average percentage of male viewers
     *               - gender_female_pct (float|null): Average percentage of female viewers
     *               - age_13_17_pct (float|null): Average percentage of 13-17 age group
     *               - age_18_24_pct (float|null): Average percentage of 18-24 age group
     *               - age_25_34_pct (float|null): Average percentage of 25-34 age group
     *               - age_35_44_pct (float|null): Average percentage of 35-44 age group
     *               - age_45_54_pct (float|null): Average percentage of 45-54 age group
     *               - age_55_plus_pct (float|null): Average percentage of 55+ age group
     *               Empty array {} if no demographic data available
     *
     * @note Only processes TikTok posts (platform='tiktok')
     * @note Percentages are simple averages across all posts with demographic data
     * @note Gender and age percentages should sum to approximately 100%
     */
    public function tiktokDemographicsSummary(?string $from, ?string $to): array {
        $sql = "
          SELECT
            AVG(gender_male_pct) AS gender_male_pct,
            AVG(gender_female_pct) AS gender_female_pct,
            AVG(age_13_17_pct) AS age_13_17_pct,
            AVG(age_18_24_pct) AS age_18_24_pct,
            AVG(age_25_34_pct) AS age_25_34_pct,
            AVG(age_35_44_pct) AS age_35_44_pct,
            AVG(age_45_54_pct) AS age_45_54_pct,
            AVG(age_55_plus_pct) AS age_55_plus_pct
          FROM tiktok_demographics d
          JOIN posts p ON p.id = d.post_id
          WHERE p.platform = 'tiktok'
        ";
        $params = [];
        $types = '';

        if ($from) { $sql .= " AND p.posted_date >= ?"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND p.posted_date <= ?"; $params[] = $to; $types .= 's'; }

        $row = $this->db->fetchOne($sql, $types ?: null, $params);
        return $row ?: [];
    }

    /**
     * Get hashtag recommendations for next post.
     *
     * Returns top performing hashtags (by views) and poorly performing hashtags to avoid.
     * Helps users choose effective hashtags for their next post.
     *
     * @param int|null $clientId Filter by client (for multi-tenant)
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param int $topLimit Maximum number of recommended hashtags to return (default: 5)
     * @param int $avoidLimit Maximum number of hashtags to avoid (default: 3)
     *
     * @return array Recommendations containing:
     *               - recommended (array): Top performing hashtags
     *                 - hashtag (string): Hashtag text (without # symbol)
     *                 - total_views (int): Total views across all posts
     *                 - total_posts (int): Number of posts using this hashtag
     *                 - avg_views_per_post (float): Average views per post
     *               - avoid (array): Poorly performing hashtags
     *                 - hashtag (string): Hashtag text
     *                 - total_views (int): Total views
     *                 - total_posts (int): Number of posts
     *                 - avg_views_per_post (float): Average views per post
     *
     * @note Recommended hashtags ordered by total_views DESC
     * @note Avoid hashtags ordered by total_views ASC (lowest performing)
     * @note Only includes hashtags used at least once
     */
    public function hashtagRecommendations(
        ?int $clientId,
        ?string $platform,
        int $topLimit = 5,
        int $avoidLimit = 3
    ): array {
        // Base query for hashtag performance
        $baseWhere = "WHERE total_posts > 0";
        $params = [];
        $types = '';

        if ($clientId) {
            $baseWhere .= " AND client_id = ?";
            $params[] = $clientId;
            $types .= 'i';
        }

        if ($platform) {
            $baseWhere .= " AND platform = ?";
            $params[] = $platform;
            $types .= 's';
        }

        // Get top performing hashtags (by total views)
        $topSql = "
            SELECT
                hashtag,
                total_views,
                total_posts,
                ROUND(total_views / total_posts, 2) AS avg_views_per_post
            FROM hashtag_performance
            $baseWhere
            ORDER BY total_views DESC
            LIMIT ?
        ";
        $topParams = array_merge($params, [$topLimit]);
        $topTypes = $types . 'i';

        // Get poorly performing hashtags to avoid (lowest views)
        $avoidSql = "
            SELECT
                hashtag,
                total_views,
                total_posts,
                ROUND(total_views / total_posts, 2) AS avg_views_per_post
            FROM hashtag_performance
            $baseWhere
            ORDER BY total_views ASC
            LIMIT ?
        ";
        $avoidParams = array_merge($params, [$avoidLimit]);
        $avoidTypes = $types . 'i';

        return [
            'recommended' => $this->db->fetchAll($topSql, $topTypes ?: null, $topParams),
            'avoid' => $this->db->fetchAll($avoidSql, $avoidTypes ?: null, $avoidParams)
        ];
    }
}
