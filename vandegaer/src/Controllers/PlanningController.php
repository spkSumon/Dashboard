<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\PlanningRepository;

/**
 * PlanningController - Handles content calendar and planning operations.
 *
 * This controller provides CRUD endpoints for content planning and calendar management.
 * It allows users to create drafts, schedule future posts, and organize content ideas
 * before they're published to social media platforms.
 *
 * Responsibilities:
 * - Parse and validate planning data from requests
 * - Delegate CRUD operations to PlanningRepository
 * - Filter planned content by platform, status, and date
 * - Return standardized JSON responses
 *
 * Available endpoints (all require authentication):
 * - GET  /api/planning - List planned content with filters
 * - GET  /api/planning/{id} - Get single planning entry
 * - POST /api/planning - Create new planning entry
 * - POST /api/planning/{id} - Update existing planning entry
 * - POST /api/planning/{id}/delete - Delete planning entry
 *
 * @package App\Controllers
 */
class PlanningController {
    /**
     * Planning repository for database operations.
     *
     * @var PlanningRepository
     */
    private $planning;

    /**
     * Constructor - Inject planning repository dependency.
     *
     * @param PlanningRepository $planning Repository for content planning data
     */
    public function __construct(PlanningRepository $planning) {
        $this->planning = $planning;
    }

    /**
     * List planned content with optional filtering.
     *
     * Retrieves content planning entries with support for filtering by platform,
     * status, and schedule date range.
     *
     * Expected query parameters ($_GET):
     * - platform (string, optional): Filter by platform (e.g., "tiktok", "instagram", "facebook")
     * - status (string, optional): Filter by status (e.g., "draft", "scheduled", "published")
     * - from (string, optional): Start date filter (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * - to (string, optional): End date filter (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * - limit (int, optional): Maximum entries to return (default: 200, minimum: 1)
     *
     * Response format (success - HTTP 200):
     * ```json
     * [
     *   {
     *     "id": 45,
     *     "platform": "instagram",
     *     "status": "scheduled",
     *     "scheduled_at": "2025-01-15 14:00:00",
     *     "content_type": "reel",
     *     "title": "Product Demo Video",
     *     "caption": "Check out our new feature!",
     *     "hashtags": "#product #demo #newfeature",
     *     "topic": "product",
     *     "notes": "Use trending audio from library",
     *     "post_id": null,
     *     "created_at": "2025-01-01 10:00:00",
     *     "updated_at": "2025-01-10 15:30:00"
     *   },
     *   ...
     * ]
     * ```
     *
     * Notes:
     * - Results ordered: drafts without scheduled_at first, then by scheduled_at ASC
     * - post_id is NULL until content is published and linked
     * - Empty array returned if no entries match filters
     *
     * @return void Sends JSON array response and terminates execution
     */
    public function list(): void {
        $platform = $_GET['platform'] ?? null;
        $status = $_GET['status'] ?? null;
        $from = $_GET['from'] ?? null; // yyyy-mm-dd of datetime
        $to = $_GET['to'] ?? null;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 200;

        Response::json($this->planning->list($platform, $status, $from, $to, $limit));
    }

    /**
     * Get a single planning entry by ID.
     *
     * Retrieves complete details for a specific content planning entry.
     *
     * Expected route parameter:
     * - id (int): Planning entry ID
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "id": 45,
     *   "platform": "instagram",
     *   "status": "scheduled",
     *   "scheduled_at": "2025-01-15 14:00:00",
     *   "content_type": "reel",
     *   "title": "Product Demo Video",
     *   "caption": "Check out our new feature!",
     *   "hashtags": "#product #demo #newfeature",
     *   "topic": "product",
     *   "notes": "Use trending audio from library",
     *   "post_id": null,
     *   "created_at": "2025-01-01 10:00:00",
     *   "updated_at": "2025-01-10 15:30:00"
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
     * @param int $id Planning entry ID (from route parameter)
     *
     * @return void Sends JSON response and terminates execution
     */
    public function show(int $id): void {
        $row = $this->planning->find($id);
        if (!$row) Response::error('Niet gevonden', 404);
        Response::json($row);
    }

    /**
     * Create a new content planning entry.
     *
     * Creates a new entry in the content calendar. All fields are optional;
     * minimum requirement is that status defaults to 'draft'.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "platform": "instagram",
     *   "status": "draft",
     *   "scheduled_at": "2025-01-15 14:00:00",
     *   "content_type": "reel",
     *   "title": "Product Demo Video",
     *   "caption": "Check out our new feature!",
     *   "hashtags": "#product #demo #newfeature",
     *   "topic": "product",
     *   "notes": "Use trending audio from library",
     *   "post_id": null
     * }
     * ```
     *
     * Minimal example (draft without schedule):
     * ```json
     * {
     *   "title": "Content idea",
     *   "notes": "Explore this topic later"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "id": 46
     * }
     * ```
     *
     * Field descriptions:
     * - platform: Target platform (tiktok, instagram, facebook)
     * - status: Entry status (draft, scheduled, published) - defaults to 'draft'
     * - scheduled_at: When to publish (YYYY-MM-DD HH:MM:SS format)
     * - content_type: Format (reel, story, post, carousel, video, image)
     * - title: Internal reference/title
     * - caption: Planned post caption/text
     * - hashtags: Planned hashtags to use
     * - topic: Content category/theme
     * - notes: Planning notes and reminders
     * - post_id: Link to published post (set when status changes to 'published')
     *
     * @return void Sends JSON response with new entry ID and terminates execution
     */
    public function create(): void {
        $d = Request::jsonBody();

        // minimum: status default draft
        $id = $this->planning->create($d);
        Response::json(['id' => $id]);
    }

    /**
     * Update an existing planning entry.
     *
     * Updates all fields of a content planning entry. Can be used to change
     * status, update content, reschedule, or link to published post.
     *
     * Expected route parameter:
     * - id (int): Planning entry ID to update
     *
     * Expected request body (JSON):
     * Same structure as create() method - all fields are optional but will be updated.
     *
     * Example (change status to scheduled and set date):
     * ```json
     * {
     *   "status": "scheduled",
     *   "scheduled_at": "2025-01-20 10:00:00"
     * }
     * ```
     *
     * Example (mark as published and link to post):
     * ```json
     * {
     *   "status": "published",
     *   "post_id": 123
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "ok": true
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
     * @param int $id Planning entry ID to update (from route parameter)
     *
     * @return void Sends JSON response and terminates execution
     */
    public function update(int $id): void {
        $d = Request::jsonBody();
        $ok = $this->planning->update($id, $d);
        if (!$ok) Response::error('Niet gevonden', 404);
        Response::json(['ok' => true]);
    }

    /**
     * Delete a content planning entry.
     *
     * Permanently removes a planning entry from the database. Use with caution
     * as this operation cannot be undone.
     *
     * Expected route parameter:
     * - id (int): Planning entry ID to delete
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "ok": true
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
     * @param int $id Planning entry ID to delete (from route parameter)
     *
     * @return void Sends JSON response and terminates execution
     */
    public function delete(int $id): void {
        $ok = $this->planning->delete($id);
        if (!$ok) Response::error('Niet gevonden', 404);
        Response::json(['ok' => true]);
    }
}
