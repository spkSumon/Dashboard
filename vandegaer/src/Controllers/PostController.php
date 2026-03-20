<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Repositories\PostRepository;

/**
 * PostController - Handles HTTP requests for social media post operations.
 *
 * This controller provides REST API endpoints for retrieving social media posts
 * and their associated metrics. It serves as the HTTP layer that processes
 * query parameters and returns JSON responses.
 *
 * Responsibilities:
 * - Parse and validate query parameters from HTTP requests
 * - Delegate database queries to PostRepository
 * - Format and return JSON responses with post data
 *
 * @package App\Controllers
 */
class PostController {
    /**
     * Repository for database operations on posts.
     *
     * @var PostRepository
     */
    private $posts;

    /**
     * Constructor - Inject post repository dependency.
     *
     * @param PostRepository $posts Repository for accessing post data from database
     */
    public function __construct(PostRepository $posts) {
        $this->posts = $posts;
    }

    /**
     * List social media posts with optional filtering.
     *
     * Retrieves posts from the database with support for filtering by platform,
     * date range, and limiting the number of results. All filters are optional.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform name (e.g., "tiktok", "instagram", "facebook")
     * - from (string, optional): Start date filter in YYYY-MM-DD format
     *   - Posts with posted_date >= this date OR posted_date IS NULL will be included
     * - to (string, optional): End date filter in YYYY-MM-DD format
     *   - Posts with posted_date <= this date OR posted_date IS NULL will be included
     * - limit (int, optional): Maximum number of posts to return (default: 100, minimum: 1)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "id": 123,
     *     "platform": "tiktok",
     *     "post_type": "video",
     *     "topic": "Tutorial",
     *     "platform_post_id": "7234567890123456789",
     *     "post_url": "https://tiktok.com/@user/video/7234567890123456789",
     *     "caption": "Check out this amazing tutorial!",
     *     "hashtags": "#tutorial #coding #tips",
     *     "posted_date": "2024-12-15",
     *     "posted_time": "14:30:00",
     *     "views": 15230,
     *     "likes": 1245,
     *     "comments": 89,
     *     "shares": 45,
     *     "saves": 67,
     *     "reach": 12000,
     *     "impressions": 18500,
     *     "engagement_rate": 8.5,
     *     "internal_caption": "Internal planning notes",
     *     "internal_notes": "Perform well with tech audience"
     *   },
     *   ...
     * ]
     * ```
     *
     * Notes:
     * - Posts are ordered by posted_date DESC (most recent first), with NULL dates sorted last
     * - If no filters are provided, returns the most recent 100 posts across all platforms
     * - Date filters are inclusive and handle NULL posted_date values
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function list(): void {
        $platform = $_GET['platform'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 100;

        Response::json($this->posts->list($platform, $from, $to, $limit));
    }

    /**
     * Retrieve a single post by ID with latest metrics snapshot.
     *
     * Fetches detailed information about a specific post, including all metadata
     * and metrics. If available, also includes the most recent metrics snapshot
     * from the metrics_history table.
     *
     * Expected route parameter:
     * - id (int): Post ID (from database primary key)
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "id": 123,
     *   "platform": "tiktok",
     *   "post_type": "video",
     *   "topic": "Tutorial",
     *   "platform_post_id": "7234567890123456789",
     *   "post_url": "https://tiktok.com/@user/video/7234567890123456789",
     *   "caption": "Check out this amazing tutorial!",
     *   "hashtags": "#tutorial #coding #tips",
     *   "posted_date": "2024-12-15",
     *   "posted_time": "14:30:00",
     *   "views": 15230,
     *   "likes": 1245,
     *   "comments": 89,
     *   "shares": 45,
     *   "saves": 67,
     *   "reach": 12000,
     *   "impressions": 18500,
     *   "engagement_rate": 8.5,
     *   "internal_caption": "Internal planning notes",
     *   "internal_notes": "Perform well with tech audience",
     *   "latest_metrics": {
     *     "views": 15500,
     *     "likes": 1280,
     *     "comments": 92,
     *     "shares": 47,
     *     "saves": 71,
     *     "snapshot_date": "2024-12-30"
     *   }
     * }
     * ```
     *
     * Response format (error - HTTP 404):
     * ```json
     * {
     *   "error": "Niet gevonden"
     * }
     * ```
     *
     * Notes:
     * - The `latest_metrics` field is only included if a metrics snapshot exists
     * - Latest metrics shows the most recent historical snapshot from metrics_history table
     * - If no snapshot exists, the response only contains the post's current metric values
     *
     * @param int $id Post ID to retrieve
     * @return void Sends JSON response and terminates execution
     */
    public function show(int $id): void {
        $post = $this->posts->find($id);
        if (!$post) Response::error('Niet gevonden', 404);

        $snap = $this->posts->latestSnapshot($id);
        if ($snap) $post['latest_metrics'] = $snap;

        Response::json($post);
    }

    /**
     * Get the earliest post date in the database.
     *
     * Returns the oldest posted_date value across all posts, useful for setting
     * default date range filters in the frontend. Returns NULL if no posts exist.
     *
     * Expected query parameters: None
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "earliest_date": "2023-01-15"
     * }
     * ```
     *
     * Response format (no posts - HTTP 200):
     * ```json
     * {
     *   "earliest_date": null
     * }
     * ```
     *
     * Use case:
     * - Frontend calls this on page load to set default "from" date filter
     * - Ensures date range covers all available data
     * - Avoids hardcoded date ranges that might exclude older posts
     *
     * @return void Sends JSON response and terminates execution
     */
    public function earliestDate(): void {
        $earliest = $this->posts->getEarliestPostDate();
        Response::json(['earliest_date' => $earliest]);
    }
}
