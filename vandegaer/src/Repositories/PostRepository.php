<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * PostRepository - Database operations for social media posts.
 *
 * This repository handles all database queries related to posts (social media content).
 * It provides CRUD operations and specialized queries for retrieving, filtering, and
 * updating post data across multiple platforms.
 *
 * Responsibilities:
 * - Execute SQL queries for posts table
 * - Handle post filtering (platform, date range, limits)
 * - Manage post creation and updates (upsert operations)
 * - Retrieve metrics snapshots from metrics_history table
 * - Update post labels and internal fields
 *
 * Database schema notes:
 * - Schema v2 uses `posted_date` column (not `post_date`)
 * - Posts are identified by composite key: platform + platform_post_id
 * - Metrics snapshots stored separately in metrics_history table
 *
 * @package App\Repositories
 */
class PostRepository {
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
     * List posts with optional filtering by platform and date range.
     *
     * Retrieves posts with support for platform filtering, date range filtering,
     * and result limiting. All filters are optional and can be combined.
     *
     * @param string|null $platform Filter by platform name (e.g., 'tiktok', 'instagram', 'facebook')
     *                              NULL returns posts from all platforms
     * @param string|null $from Start date filter in YYYY-MM-DD format
     *                          Includes posts with posted_date >= this date OR posted_date IS NULL
     * @param string|null $to End date filter in YYYY-MM-DD format
     *                        Includes posts with posted_date <= this date OR posted_date IS NULL
     * @param int $limit Maximum number of posts to return (must be positive integer)
     *
     * @return array Array of post records, each containing:
     *               - id (int): Post primary key
     *               - platform (string): Platform name
     *               - post_type (string|null): Content type (video, image, carousel, etc.)
     *               - topic (string|null): Content topic/category
     *               - platform_post_id (string): Platform-specific post identifier
     *               - post_url (string|null): URL to the post
     *               - caption (string|null): Post caption/text
     *               - hashtags (string|null): Hashtags used in post
     *               - posted_date (string|null): Publication date (YYYY-MM-DD)
     *               - posted_time (string|null): Publication time (HH:MM:SS)
     *               - views (int): View count
     *               - likes (int): Like count
     *               - comments (int): Comment count
     *               - shares (int): Share count
     *               - saves (int): Save/bookmark count
     *               - reach (int): Reach metric
     *               - impressions (int): Impressions count
     *               - engagement_rate (float): Engagement rate percentage
     *               - internal_caption (string|null): Internal caption for planning
     *               - internal_notes (string|null): Internal notes
     *               Ordered by posted_date DESC (most recent first), NULL dates sorted last
     */
    public function list(?string $platform, ?string $from, ?string $to, int $limit): array {
        $sql = "SELECT id, platform, post_type, topic, platform_post_id, post_url, caption, hashtags, posted_date, posted_time, views, likes, comments, shares, saves, reach, impressions, engagement_rate, internal_caption, internal_notes
                FROM posts WHERE 1=1";
        $params = [];
        $types = '';

        if ($platform) { $sql .= " AND platform = ?"; $params[] = $platform; $types .= 's'; }
        // Datum filter is optioneel - als beide leeg zijn, toon alle posts
        if ($from) { $sql .= " AND (posted_date >= ? OR posted_date IS NULL)"; $params[] = $from; $types .= 's'; }
        if ($to) { $sql .= " AND (posted_date <= ? OR posted_date IS NULL)"; $params[] = $to; $types .= 's'; }

        $sql .= " ORDER BY COALESCE(posted_date, '1900-01-01') DESC, id DESC LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, $types ?: null, $params);
    }

    /**
     * Find a single post by ID.
     *
     * Retrieves complete post data for a specific post ID.
     *
     * @param int $id Post ID (primary key)
     *
     * @return array|null Post record with all fields (same structure as list() method)
     *                    NULL if post not found
     */
    public function find(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT id, platform, post_type, topic, platform_post_id, post_url, caption, hashtags, posted_date, posted_time, views, likes, comments, shares, saves, reach, impressions, engagement_rate, internal_caption, internal_notes
             FROM posts WHERE id = ?",
            "i",
            [$id]
        );
    }

    /**
     * Get the most recent metrics snapshot for a post.
     *
     * Retrieves the latest metrics snapshot from the metrics_history table.
     * This shows historical metric values at a specific point in time.
     *
     * @param int $postId Post ID to get snapshot for
     *
     * @return array|null Metrics snapshot containing:
     *                    - views (int): View count at snapshot time
     *                    - likes (int): Like count at snapshot time
     *                    - comments (int): Comment count at snapshot time
     *                    - shares (int): Share count at snapshot time
     *                    - saves (int): Save count at snapshot time
     *                    - snapshot_date (string): Date of snapshot (YYYY-MM-DD)
     *                    NULL if no snapshots exist for this post
     *                    Ordered by snapshot_date DESC, id DESC (most recent first)
     */
    public function latestSnapshot(int $postId): ?array {
        return $this->db->fetchOne(
            "SELECT views, likes, comments, shares, saves, snapshot_date
             FROM metrics_history
             WHERE post_id = ?
             ORDER BY snapshot_date DESC, id DESC
             LIMIT 1",
            "i",
            [$postId]
        );
    }

    /**
     * Update post labels (post_type and topic).
     *
     * Updates the post_type and topic fields for categorizing and organizing content.
     * These fields are used for analytics and reporting.
     *
     * @param int $id Post ID to update
     * @param string|null $postType Content type (e.g., 'video', 'image', 'carousel', 'reel')
     * @param string|null $topic Content topic/category (e.g., 'Tutorial', 'Product Review')
     *
     * @return bool TRUE if update succeeded, FALSE if post not found
     */
    public function updateLabels(int $id, ?string $postType, ?string $topic): bool {
        // Eerst checken of post bestaat
        $exists = $this->db->fetchOne("SELECT id FROM posts WHERE id = ? LIMIT 1", "i", [$id]);
        if (!$exists) return false;

        $this->db->exec(
            "UPDATE posts SET post_type = ?, topic = ? WHERE id = ?",
            "ssi",
            [$postType, $topic, $id]
        );
        return true;
    
    }

    /**
     * Update internal fields for content planning.
     *
     * Updates internal-only fields that are used for content planning and notes.
     * These fields do NOT affect the published post on social media platforms.
     *
     * @param int $id Post ID to update
     * @param string|null $internalCaption Internal caption for content planning
     * @param string|null $internalNotes Internal notes and observations
     * @param string|null $hashtags Hashtags (comma or space separated, with or without #)
     *
     * @return bool TRUE if update succeeded, FALSE if post not found
     */
    public function updateInternalFields(int $id, ?string $internalCaption, ?string $internalNotes, ?string $hashtags): bool {
        $exists = $this->db->fetchOne("SELECT id FROM posts WHERE id = ? LIMIT 1", "i", [$id]);
        if (!$exists) return false;

        $this->db->exec(
            "UPDATE posts SET internal_caption = ?, internal_notes = ?, hashtags = ? WHERE id = ?",
            "sssi",
            [$internalCaption, $internalNotes, $hashtags, $id]
        );
        return true;
    }

    /**
     * Find post by platform and platform-specific post ID.
     *
     * Searches for a post using the composite key of platform + platform_post_id.
     * This is used to check if a post already exists before importing.
     *
     * @param string $platform Platform name (e.g., 'tiktok', 'instagram', 'facebook')
     * @param string $platformPostId Platform-specific post identifier
     *                               (e.g., TikTok video ID, Instagram media ID)
     *
     * @return array|null Complete post record with all fields
     *                    NULL if no matching post found
     */
    public function findByPlatformPostId(string $platform, string $platformPostId): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM posts WHERE platform = ? AND platform_post_id = ? LIMIT 1",
            "ss",
            [$platform, $platformPostId]
        );
    }

    /**
     * Create a new post record.
     *
     * Inserts a new post into the database with initial metrics and metadata.
     * Used during CSV imports and API syncs.
     *
     * @param array $data Post data containing:
     *                    - platform (string, optional): Platform name
     *                    - platform_post_id (string, optional): Platform-specific ID
     *                    - post_url (string, optional): URL to post
     *                    - caption (string, optional): Post caption/text
     *                    - hashtags (string, optional): Hashtags used
     *                    - posted_date (string, optional): Publication date (YYYY-MM-DD)
     *                    - posted_time (string, optional): Publication time (HH:MM:SS)
     *                    - views (int, optional): View count (default: 0)
     *                    - likes (int, optional): Like count (default: 0)
     *                    - comments (int, optional): Comment count (default: 0)
     *                    - shares (int, optional): Share count (default: 0)
     *                    - engagement_rate (float, optional): Engagement rate (default: 0.0)
     *                    - post_type (string, optional): Content type
     *                    - import_source (string, optional): Import source (default: 'manual')
     *
     * @return int Newly created post ID (auto-increment primary key)
     */
    public function create(array $data): int {
        $sql = "INSERT INTO posts (
            platform, platform_post_id, post_url, caption, hashtags,
            posted_date, posted_time, views, likes, comments, shares,
            engagement_rate, post_type, import_source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->exec(
            $sql,
            'sssssssiiiidss',
            [
                $data['platform'] ?? null,
                $data['platform_post_id'] ?? null,
                $data['post_url'] ?? null,
                $data['caption'] ?? null,
                $data['hashtags'] ?? null,
                $data['posted_date'] ?? null,
                $data['posted_time'] ?? null,
                $data['views'] ?? 0,
                $data['likes'] ?? 0,
                $data['comments'] ?? 0,
                $data['shares'] ?? 0,
                $data['engagement_rate'] ?? 0.0,
                $data['post_type'] ?? null,
                $data['import_source'] ?? 'manual'
            ]
        );

        return $this->db->insertId();
    }

    /**
     * Update existing post metrics and metadata.
     *
     * Updates an existing post's metrics and content. Sets last_updated timestamp.
     * Used for refreshing post data during API syncs.
     *
     * @param int $id Post ID to update
     * @param array $data Updated post data containing:
     *                    - caption (string, optional): Updated caption
     *                    - hashtags (string, optional): Updated hashtags
     *                    - views (int, optional): Updated view count (default: 0)
     *                    - likes (int, optional): Updated like count (default: 0)
     *                    - comments (int, optional): Updated comment count (default: 0)
     *                    - shares (int, optional): Updated share count (default: 0)
     *                    - engagement_rate (float, optional): Updated engagement rate (default: 0.0)
     *
     * @return bool TRUE if update succeeded, FALSE if post not found
     */
    public function update(int $id, array $data): bool {
        $exists = $this->db->fetchOne("SELECT id FROM posts WHERE id = ? LIMIT 1", "i", [$id]);
        if (!$exists) return false;

        $sql = "UPDATE posts SET
            caption = ?,
            hashtags = ?,
            views = ?,
            likes = ?,
            comments = ?,
            shares = ?,
            engagement_rate = ?,
            last_updated = CURRENT_TIMESTAMP
        WHERE id = ?";

        $this->db->exec(
            $sql,
            'ssiiidi',
            [
                $data['caption'] ?? null,
                $data['hashtags'] ?? null,
                $data['views'] ?? 0,
                $data['likes'] ?? 0,
                $data['comments'] ?? 0,
                $data['shares'] ?? 0,
                $data['engagement_rate'] ?? 0.0,
                $id
            ]
        );

        return true;
    }

    /**
     * Get the earliest post date in the database.
     *
     * Returns the oldest posted_date value across all posts, useful for setting
     * default date range filters. Returns NULL if no posts exist or all posts
     * have NULL posted_date.
     *
     * @return string|null Earliest posted_date in YYYY-MM-DD format, or NULL if no posts
     *
     * @example
     * ```php
     * $earliest = $repo->getEarliestPostDate();
     * // Returns "2023-01-15" if that's the oldest post date
     * ```
     */
    public function getEarliestPostDate(): ?string {
        $sql = "SELECT MIN(posted_date) as earliest_date
                FROM posts
                WHERE posted_date IS NOT NULL";

        $result = $this->db->fetchAll($sql);

        return $result[0]['earliest_date'] ?? null;
    }
}
