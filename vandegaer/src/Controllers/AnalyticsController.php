<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Repositories\AnalyticsRepository;
use App\Services\EngagementService;

/**
 * AnalyticsController - Provides analytics and reporting endpoints.
 *
 * This controller offers comprehensive analytics endpoints for social media data,
 * including hashtag performance, engagement metrics, content type analysis, topic
 * statistics, and TikTok demographic insights.
 *
 * Responsibilities:
 * - Parse and validate query parameters for analytics filters
 * - Delegate analytics calculations to AnalyticsRepository and EngagementService
 * - Return formatted JSON responses with analytics data
 *
 * Available endpoints:
 * - GET /api/hashtags/top - Top performing hashtags
 * - GET /api/hashtags/trend?hashtag=... - Hashtag performance over time
 * - GET /api/engagement/overview - Engagement statistics overview
 * - GET /api/analytics/post-types - Content type performance analysis
 * - GET /api/analytics/topics - Topic performance analysis
 * - GET /api/tiktok/demographics/summary - TikTok audience demographics
 *
 * @package App\Controllers
 */
class AnalyticsController {
    /**
     * Repository for analytics database queries.
     *
     * @var AnalyticsRepository
     */
    private $repo;

    /**
     * Service for engagement calculations and formulas.
     *
     * @var EngagementService
     */
    private $engagement;

    /**
     * Constructor - Inject analytics dependencies.
     *
     * @param AnalyticsRepository $repo Repository for analytics SQL queries
     * @param EngagementService $engagement Service for engagement rate calculations
     */
    public function __construct(AnalyticsRepository $repo, EngagementService $engagement) {
        $this->repo = $repo;
        $this->engagement = $engagement;
    }

    /**
     * Get top performing hashtags across all posts.
     *
     * Returns hashtags ranked by total usage (post count) and performance metrics.
     * Calculates aggregate statistics including total views, likes, and engagement rates.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     * - limit (int, optional): Maximum number of hashtags to return (default: 25, minimum: 1)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "hashtag": "marketing",
     *     "total_posts": 45,
     *     "total_views": 125300,
     *     "total_likes": 8420,
     *     "avg_views": 2784.44,
     *     "avg_likes": 187.11,
     *     "avg_engagement_rate": 6.72
     *   },
     *   {
     *     "hashtag": "socialmedia",
     *     "total_posts": 38,
     *     "total_views": 98500,
     *     "total_likes": 6200,
     *     "avg_views": 2592.11,
     *     "avg_likes": 163.16,
     *     "avg_engagement_rate": 6.29
     *   },
     *   ...
     * ]
     * ```
     *
     * Notes:
     * - Results ordered by total_posts DESC, then total_views DESC
     * - Engagement rate calculated as: ((likes + comments + shares) / views) * 100
     * - Only includes hashtags from posts with posted_date in the specified range
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function topHashtags(): void {
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 25;

        Response::json($this->repo->topHashtags($platform, $from, $to, $limit));
    }

    /**
     * Get performance trend for a specific hashtag over time.
     *
     * Returns daily statistics for a given hashtag, showing how its performance
     * evolved across the specified date range.
     *
     * Expected query parameters ($_GET):
     * - hashtag (string, REQUIRED): Hashtag to analyze (without # symbol)
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "day": "2024-12-15",
     *     "posts": 3,
     *     "views": 12500,
     *     "likes": 890,
     *     "avg_engagement_rate": 7.12
     *   },
     *   {
     *     "day": "2024-12-16",
     *     "posts": 5,
     *     "views": 18300,
     *     "likes": 1250,
     *     "avg_engagement_rate": 6.83
     *   },
     *   ...
     * ]
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "hashtag ontbreekt"
     * }
     * ```
     *
     * Notes:
     * - Results ordered by day ASC (chronological order)
     * - Returns empty array if hashtag not found
     * - Engagement rate calculated as average across all posts on that day
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function hashtagTrend(): void {
        $hashtag = trim($_GET['hashtag'] ?? '');
        if ($hashtag === '') Response::error('hashtag ontbreekt', 400);

        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        Response::json($this->repo->hashtagTrend($hashtag, $platform, $from, $to));
    }

    /**
     * Get engagement overview with top performing posts.
     *
     * Calculates aggregate engagement statistics and returns the top 10 posts
     * ranked by engagement rate. Useful for understanding overall content performance.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     * - limit (int, optional): Maximum posts to analyze (default: 200, minimum: 1)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "count": 156,
     *   "avg_views": 2847.33,
     *   "avg_engagement_rate": 6.45,
     *   "top": [
     *     {
     *       "id": 245,
     *       "platform": "tiktok",
     *       "post_type": "video",
     *       "topic": "Tutorial",
     *       "caption": "How to create amazing content!",
     *       "posted_date": "2024-12-20",
     *       "views": 15600,
     *       "likes": 1340,
     *       "comments": 89,
     *       "shares": 45,
     *       "saves": 67,
     *       "engagement_rate": 9.68
     *     },
     *     ...
     *   ]
     * }
     * ```
     *
     * Notes:
     * - Engagement rate formula: ((likes + comments + shares) / views) * 100
     * - Top posts limited to 10 items, sorted by engagement_rate DESC
     * - Returns count: 0 if no posts found
     * - The 'limit' parameter affects which posts are analyzed, not the output size
     *
     * @return void Sends JSON response and terminates execution
     */
    public function engagementOverview(): void {
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 200;

        $rows = $this->repo->postsForEngagement($platform, $from, $to, $limit);
        Response::json($this->engagement->overview($rows));
    }

    /**
     * Get performance statistics grouped by post type.
     *
     * Analyzes content performance by post type (e.g., video, image, carousel, reel).
     * Calculates aggregate metrics for each content type to identify what performs best.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "post_type": "video",
     *     "posts": 123,
     *     "avg_views": 3245.67,
     *     "avg_likes": 234.12,
     *     "avg_engagement_rate": 7.21
     *   },
     *   {
     *     "post_type": "carousel",
     *     "posts": 45,
     *     "avg_views": 2890.33,
     *     "avg_likes": 198.45,
     *     "avg_engagement_rate": 6.86
     *   },
     *   {
     *     "post_type": "unknown",
     *     "posts": 12,
     *     "avg_views": 1234.50,
     *     "avg_likes": 89.25,
     *     "avg_engagement_rate": 7.24
     *   },
     *   ...
     * ]
     * ```
     *
     * Notes:
     * - Posts with NULL post_type are grouped as "unknown"
     * - Results ordered by posts DESC (most common types first)
     * - Engagement rate: ((likes + comments + shares) / views) * 100
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function postTypes(): void {
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        Response::json($this->repo->postTypeStats($platform, $from, $to));
    }

    /**
     * Get performance statistics grouped by topic.
     *
     * Analyzes content performance by topic/category (e.g., Tutorial, Product Review,
     * Behind the Scenes). Helps identify which content topics resonate with the audience.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "topic": "Tutorial",
     *     "posts": 67,
     *     "avg_views": 4123.45,
     *     "avg_likes": 289.67,
     *     "avg_engagement_rate": 7.03
     *   },
     *   {
     *     "topic": "Product Review",
     *     "posts": 54,
     *     "avg_views": 3567.89,
     *     "avg_likes": 245.33,
     *     "avg_engagement_rate": 6.88
     *   },
     *   {
     *     "topic": "unknown",
     *     "posts": 23,
     *     "avg_views": 2145.67,
     *     "avg_likes": 156.78,
     *     "avg_engagement_rate": 7.31
     *   },
     *   ...
     * ]
     * ```
     *
     * Notes:
     * - Posts with NULL topic are grouped as "unknown"
     * - Results ordered by posts DESC (most common topics first)
     * - Engagement rate: ((likes + comments + shares) / views) * 100
     * - Topics are user-defined during post creation or labeling
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function topics(): void {
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        Response::json($this->repo->topicStats($platform, $from, $to));
    }

    /**
     * Get TikTok audience demographics summary.
     *
     * Returns aggregated demographic data for TikTok posts, including gender distribution
     * and age group breakdowns. Calculates averages across all posts in the specified
     * date range.
     *
     * Expected query parameters ($_GET):
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "gender_male_pct": 42.5,
     *   "gender_female_pct": 57.5,
     *   "age_13_17_pct": 8.3,
     *   "age_18_24_pct": 35.7,
     *   "age_25_34_pct": 28.4,
     *   "age_35_44_pct": 15.2,
     *   "age_45_54_pct": 8.9,
     *   "age_55_plus_pct": 3.5
     * }
     * ```
     *
     * Notes:
     * - Only includes TikTok posts (platform='tiktok')
     * - Returns empty object {} if no demographic data available
     * - Percentages are averages across all posts with demographic data
     * - Demographic data must be stored in tiktok_demographics table (linked to posts)
     * - Gender and age percentages should sum to approximately 100%
     *
     * @return void Sends JSON response and terminates execution
     */
    public function tiktokDemographicsSummary(): void {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        Response::json($this->repo->tiktokDemographicsSummary($from, $to));
    }

    /**
     * Get hashtag recommendations for next post.
     *
     * Returns top performing hashtags users should use and poorly performing hashtags
     * to avoid. Helps users create more engaging posts by choosing effective hashtags.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - client_id (int, optional): Filter by client ID (for multi-tenant)
     * - top (int, optional): Number of recommended hashtags to return (default: 5, max: 10)
     * - avoid (int, optional): Number of hashtags to avoid (default: 3, max: 5)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "recommended": [
     *     {
     *       "hashtag": "napolipizza",
     *       "total_views": 829712,
     *       "total_posts": 11,
     *       "avg_views_per_post": 75428.36
     *     },
     *     {
     *       "hashtag": "napoletana",
     *       "total_views": 749214,
     *       "total_posts": 9,
     *       "avg_views_per_post": 83245.78
     *     }
     *   ],
     *   "avoid": [
     *     {
     *       "hashtag": "lowperformer",
     *       "total_views": 1250,
     *       "total_posts": 5,
     *       "avg_views_per_post": 250.00
     *     }
     *   ]
     * }
     * ```
     *
     * Notes:
     * - Recommended hashtags ordered by total_views DESC (best performers)
     * - Avoid hashtags ordered by total_views ASC (worst performers)
     * - Only includes hashtags that have been used at least once
     * - Average views per post calculated as total_views / total_posts
     *
     * @return void Sends JSON response and terminates execution
     */
    public function hashtagRecommendations(): void {
        $platform = $_GET['platform'] ?? null;
        $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
        $topLimit = isset($_GET['top']) ? min(10, max(1, (int)$_GET['top'])) : 5;
        $avoidLimit = isset($_GET['avoid']) ? min(5, max(1, (int)$_GET['avoid'])) : 3;

        Response::json($this->repo->hashtagRecommendations($clientId, $platform, $topLimit, $avoidLimit));
    }
}
