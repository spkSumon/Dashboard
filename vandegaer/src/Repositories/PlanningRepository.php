<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * PlanningRepository - Database operations for content planning.
 *
 * This repository handles CRUD operations for planned social media content
 * that may not yet exist on platforms (Instagram, TikTok, etc.). It manages
 * content drafts, scheduled posts, and content ideas.
 *
 * Responsibilities:
 * - Store and retrieve planned content entries
 * - Filter planned content by platform, status, and schedule dates
 * - Manage content lifecycle (draft → scheduled → published)
 * - Link planned content to actual published posts
 *
 * Content states:
 * - draft: Content idea or draft, may not have scheduled date
 * - scheduled: Content scheduled for future publication
 * - published: Content that has been posted (linked to posts.id via post_id)
 *
 * @package App\Repositories
 */
class PlanningRepository {
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
     * List planned content with optional filtering.
     *
     * Retrieves content planning entries with support for filtering by platform,
     * status, and schedule date range. Results are ordered to show drafts without
     * scheduled dates first, followed by scheduled content in chronological order.
     *
     * @param string|null $platform Filter by platform (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL includes all platforms
     * @param string|null $status Filter by status (e.g., 'draft', 'scheduled', 'published')
     *                            NULL includes all statuses
     * @param string|null $from Start date filter in YYYY-MM-DD HH:MM:SS format
     *                          Includes entries with scheduled_at >= this date OR NULL scheduled_at
     * @param string|null $to End date filter in YYYY-MM-DD HH:MM:SS format
     *                        Includes entries with scheduled_at <= this date OR NULL scheduled_at
     * @param int $limit Maximum number of entries to return (must be positive)
     *
     * @return array Array of planning records, each containing:
     *               - id (int): Planning entry ID
     *               - platform (string|null): Target platform
     *               - status (string): Entry status (draft, scheduled, published)
     *               - scheduled_at (string|null): Scheduled publication date/time
     *               - content_type (string|null): Content format (video, image, carousel)
     *               - title (string|null): Internal title/reference
     *               - caption (string|null): Planned post caption
     *               - hashtags (string|null): Planned hashtags
     *               - topic (string|null): Content topic/category
     *               - notes (string|null): Planning notes
     *               - post_id (int|null): Linked published post ID (if published)
     *               - created_at (string): Creation timestamp
     *               - updated_at (string): Last update timestamp
     *               Ordered by: drafts without date first, then scheduled_at ASC
     */
    public function list(?string $platform, ?string $status, ?string $from, ?string $to, int $limit): array {
        $sql = "SELECT id, platform, status, scheduled_at, content_type, title, caption, hashtags, topic, notes, post_id, created_at, updated_at
                FROM content_planning WHERE 1=1";
        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND platform = ?"; $params[] = $platform; $types .= 's'; }
        if ($status) { $sql .= " AND status = ?"; $params[] = $status; $types .= 's'; }
        if ($from) { $sql .= " AND (scheduled_at IS NULL OR scheduled_at >= ?)"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND (scheduled_at IS NULL OR scheduled_at <= ?)"; $params[] = $to; $types .= 's'; }

        // Drafts zonder date: eerst; daarna scheduled
        $sql .= " ORDER BY (scheduled_at IS NULL) DESC, scheduled_at ASC, id ASC LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Find a single planned content entry by ID.
     *
     * Retrieves complete planning data for a specific entry.
     *
     * @param int $id Planning entry ID (primary key)
     *
     * @return array|null Planning record with all fields (same structure as list() method)
     *                    NULL if entry not found
     */
    public function find(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT id, platform, status, scheduled_at, content_type, title, caption, hashtags, topic, notes, post_id, created_at, updated_at
             FROM content_planning WHERE id = ?",
            "i",
            [$id]
        );
    }

    /**
     * Create a new content planning entry.
     *
     * Inserts a new planned content entry into the database. Used for creating
     * content drafts, scheduled posts, and content ideas.
     *
     * @param array $d Planning data containing:
     *                 - platform (string, optional): Target platform
     *                 - status (string, optional): Entry status (default: 'draft')
     *                 - scheduled_at (string, optional): Scheduled date/time (YYYY-MM-DD HH:MM:SS)
     *                 - content_type (string, optional): Content format (video, image, carousel)
     *                 - title (string, optional): Internal title/reference
     *                 - caption (string, optional): Planned post caption
     *                 - hashtags (string, optional): Planned hashtags
     *                 - topic (string, optional): Content topic/category
     *                 - notes (string, optional): Planning notes
     *                 - post_id (int, optional): Linked published post ID
     *
     * @return int Newly created planning entry ID (auto-increment primary key)
     */
    public function create(array $d): int {
        $this->db->exec(
            "INSERT INTO content_planning (platform, status, scheduled_at, content_type, title, caption, hashtags, topic, notes, post_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sssssssssi",
            [
                $d['platform'] ?? null,
                $d['status'] ?? 'draft',
                $d['scheduled_at'] ?? null,
                $d['content_type'] ?? null,
                $d['title'] ?? null,
                $d['caption'] ?? null,
                $d['hashtags'] ?? null,
                $d['topic'] ?? null,
                $d['notes'] ?? null,
                (int)($d['post_id'] ?? 0) ?: null
            ]
        );
        return $this->db->insertId();
    }

    /**
     * Update an existing content planning entry.
     *
     * Updates all fields of a planning entry. Can be used to change status from
     * draft to scheduled, update content details, or link to published post.
     *
     * @param int $id Planning entry ID to update
     * @param array $d Updated planning data containing same fields as create() method
     *
     * @return bool TRUE if entry exists (even if no changes made), FALSE if entry not found
     *
     * @note Verifies entry existence after update to confirm success
     */
    public function update(int $id, array $d): bool {
        $this->db->exec(
            "UPDATE content_planning
             SET platform = ?, status = ?, scheduled_at = ?, content_type = ?, title = ?, caption = ?, hashtags = ?, topic = ?, notes = ?, post_id = ?
             WHERE id = ?",
            "sssssssssii",
            [
                $d['platform'] ?? null,
                $d['status'] ?? 'draft',
                $d['scheduled_at'] ?? null,
                $d['content_type'] ?? null,
                $d['title'] ?? null,
                $d['caption'] ?? null,
                $d['hashtags'] ?? null,
                $d['topic'] ?? null,
                $d['notes'] ?? null,
                (int)($d['post_id'] ?? 0) ?: null,
                $id
            ]
        );

        $exists = $this->db->fetchOne("SELECT id FROM content_planning WHERE id = ? LIMIT 1", "i", [$id]);
        return $exists !== null;
    }

    /**
     * Delete a content planning entry.
     *
     * Permanently removes a planning entry from the database. Use with caution
     * as this operation cannot be undone.
     *
     * @param int $id Planning entry ID to delete
     *
     * @return bool TRUE if entry was deleted, FALSE if entry not found
     *
     * @note Returns FALSE if entry doesn't exist (nothing to delete)
     */
    public function delete(int $id): bool {
        return $this->db->exec("DELETE FROM content_planning WHERE id = ?", "i", [$id]) > 0;
    }
}
