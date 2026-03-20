<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\PostRepository;

/**
 * LabelController - Handles manual labeling of social media posts.
 *
 * This controller provides endpoints for manually assigning labels to posts
 * for categorization and analytics purposes. Labels include post_type (content format)
 * and topic (content category/theme).
 *
 * Responsibilities:
 * - Parse and validate label data from request body
 * - Update post labels in database via PostRepository
 * - Validate label length constraints
 * - Return standardized JSON responses
 *
 * Use cases:
 * - Categorize posts by content type (reel, story, post, carousel, etc.)
 * - Tag posts by topic/theme (promo, product, tutorial, behind-the-scenes, etc.)
 * - Organize content for analytics and reporting
 *
 * Available endpoints:
 * - POST /api/posts/{id}/label - Set labels for a post (auth required)
 *
 * @package App\Controllers
 */
class LabelController {
    /**
     * Post repository for database operations.
     *
     * @var PostRepository
     */
    private $posts;

    /**
     * Constructor - Inject post repository dependency.
     *
     * @param PostRepository $posts Repository for accessing post data
     */
    public function __construct(PostRepository $posts) {
        $this->posts = $posts;
    }

    /**
     * Set labels (post_type and topic) for a post.
     *
     * Updates the post_type and topic fields for manual categorization.
     * Empty strings are converted to NULL. Validates label lengths.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "post_type": "reel",
     *   "topic": "product demo"
     * }
     * ```
     *
     * Both fields are optional - you can update one or both:
     * ```json
     * {
     *   "post_type": "story"
     * }
     * ```
     *
     * To clear a label, pass empty string or null:
     * ```json
     * {
     *   "post_type": "",
     *   "topic": null
     * }
     * ```
     *
     * Common post_type values:
     * - reel: Short video content (Instagram Reels, TikTok)
     * - story: Temporary/24h content (Instagram Stories)
     * - post: Standard feed post
     * - carousel: Multi-image/video post
     * - video: Standard video post
     * - image: Single image post
     *
     * Common topic values:
     * - promo: Promotional content
     * - product: Product showcase
     * - tutorial: How-to content
     * - behind-the-scenes: BTS content
     * - announcement: News/announcements
     * - user-generated: UGC content
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "ok": true
     * }
     * ```
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "post_type te lang"
     * }
     * ```
     * or
     * ```json
     * {
     *   "error": "topic te lang"
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
     * Validation rules:
     * - post_type: Maximum 50 characters
     * - topic: Maximum 100 characters
     * - Empty strings converted to NULL
     * - Post must exist in database
     *
     * @param int $id Post ID to update (from route parameter)
     *
     * @return void Sends JSON response and terminates execution
     */
    public function setLabel(int $id): void {
        $body = Request::jsonBody();
        $postType = isset($body['post_type']) ? trim((string)$body['post_type']) : null;
        $topic = isset($body['topic']) ? trim((string)$body['topic']) : null;

        if ($postType === '') $postType = null;
        if ($topic === '') $topic = null;

        // basic validation: keep short
        if ($postType !== null && strlen($postType) > 50) Response::error('post_type te lang', 400);
        if ($topic !== null && strlen($topic) > 100) Response::error('topic te lang', 400);

        $ok = $this->posts->updateLabels($id, $postType, $topic);
        if (!$ok) Response::error('Niet gevonden', 404);

        Response::json(['ok' => true]);
    }
}
