<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Repositories\AnalyticsRepository;
use App\Repositories\PostRepository;
use App\Services\InsightsService;

/**
 * InsightsController - Provides actionable insights and recommendations
 *
 * This controller generates plain language insights from analytics data,
 * designed for non-technical business users who need actionable recommendations.
 *
 * Key features:
 * - Plain language (no jargon)
 * - Context with benchmarks
 * - Actionable recommendations
 * - Visual indicators (emoji, status)
 *
 * Available endpoints:
 * - GET /api/insights - Get all insights for dashboard
 *
 * @package App\Controllers
 */
class InsightsController {
    /**
     * @var InsightsService
     */
    private $insightsService;

    /**
     * @var AnalyticsRepository
     */
    private $analyticsRepo;

    /**
     * @var PostRepository
     */
    private $postRepo;

    /**
     * Constructor - Inject dependencies
     *
     * @param InsightsService $insightsService Service for generating insights
     * @param AnalyticsRepository $analyticsRepo Repository for analytics queries
     * @param PostRepository $postRepo Repository for post queries
     */
    public function __construct(
        InsightsService $insightsService,
        AnalyticsRepository $analyticsRepo,
        PostRepository $postRepo
    ) {
        $this->insightsService = $insightsService;
        $this->analyticsRepo = $analyticsRepo;
        $this->postRepo = $postRepo;
    }

    /**
     * Get insights dashboard with actionable recommendations
     *
     * Returns array of insights with plain language messages,
     * recommendations, and visual indicators.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *
     * NOTE: All data in database belongs to Giuditta (no multi-tenant filtering needed yet)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "insights": [
     *     {
     *       "type": "performance",
     *       "title": "Algemene Prestaties",
     *       "status": "good",
     *       "icon": "✅",
     *       "message": "Goed bezig! Je scoort 2.1× beter dan gemiddeld",
     *       "recommendation": "Analyseer je best presterende posts en herhaal die strategie.",
     *       "metrics": {
     *         "your_engagement": 7.77,
     *         "benchmark": 3.7,
     *         "difference": 4.07
     *       }
     *     },
     *     {
     *       "type": "content_type",
     *       "title": "Beste Content Type",
     *       "status": "tip",
     *       "icon": "💡",
     *       "message": "Reels krijgen 3.2× meer engagement",
     *       "recommendation": "Post minimaal 3 Reels per week voor maximaal bereik.",
     *       "metrics": {
     *         "best_type": "reel",
     *         "engagement": 9.45,
     *         "multiplier": 3.2,
     *         "posts_count": 15
     *       }
     *     },
     *     {
     *       "type": "hashtag",
     *       "title": "Top Hashtag",
     *       "status": "success",
     *       "icon": "#️⃣",
     *       "message": "#napolipizza haalt gemiddeld 54.230 views",
     *       "recommendation": "Gebruik #napolipizza in je volgende posts voor maximaal bereik.",
     *       "metrics": {
     *         "hashtag": "napolipizza",
     *         "avg_views": 54230,
     *         "total_posts": 8,
     *         "engagement_rate": 8.5
     *       }
     *     },
     *     {
     *       "type": "timing",
     *       "title": "Beste Post Tijd",
     *       "status": "tip",
     *       "icon": "⏰",
     *       "message": "Posts op dinsdag om 11:00 presteren het best",
     *       "recommendation": "Plan je belangrijkste content voor dinsdag tussen 11:00-12:00.",
     *       "metrics": {
     *         "day": "dinsdag",
     *         "hour": 11,
     *         "avg_engagement": 9.2,
     *         "sample_size": 12
     *       }
     *     },
     *     {
     *       "type": "quality",
     *       "title": "Engagement Kwaliteit",
     *       "status": "excellent",
     *       "icon": "🎯",
     *       "message": "68% kijkt tot het einde - uitstekend!",
     *       "recommendation": "Je content is super engaging. Blijf deze stijl gebruiken.",
     *       "metrics": {
     *         "completion_rate": 68,
     *         "benchmark": 60,
     *         "sample_size": 25
     *       }
     *     }
     *   ],
     *   "generated_at": "2026-02-07T14:30:00Z"
     * }
     * ```
     *
     * Notes:
     * - Insights are ordered by importance/relevance
     * - All insights include plain language message + actionable recommendation
     * - Status can be: excellent, good, average, below_average, warning, tip, success
     * - Icons are emoji for visual feedback
     * - Metrics provide supporting data for transparency
     *
     * @return void Sends JSON response and terminates execution
     */
    public function getInsights(): void {
        // Get filters from query params
        // NOTE: All data in database is Giuditta data (no multi-tenant filtering needed yet)
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        // Fetch data for insights generation
        // 1. Get all posts (all = Giuditta posts)
        $posts = $this->postRepo->list($platform, $from, $to, 500);

        // 2. Get top hashtags (all = Giuditta hashtags)
        $hashtags = $this->analyticsRepo->topHashtags($platform, $from, $to, 10);

        // 3. Get post type statistics (all = Giuditta stats)
        $postTypes = $this->analyticsRepo->postTypeStats($platform, $from, $to);

        // Generate insights (comparing Giuditta performance vs industry benchmarks)
        $insights = $this->insightsService->generateInsights($posts, $hashtags, $postTypes);

        // Return response
        Response::json([
            'insights' => $insights,
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'client' => 'Giuditta' // All data belongs to Giuditta
        ]);
    }
}
