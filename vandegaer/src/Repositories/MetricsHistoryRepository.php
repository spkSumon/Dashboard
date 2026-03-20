<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * MetricsHistoryRepository - Time-series metrics snapshots for posts.
 *
 * This repository manages the metrics_history table, which stores historical
 * snapshots of post metrics at different points in time. This enables:
 * - Tracking metric growth over time (views today vs. last week)
 * - Preserving data from multiple sources (CSV baseline vs. API updates)
 * - Conflict resolution (keep CSV data, add API snapshots)
 * - Trend analysis and reporting
 *
 * Responsibilities:
 * - Create metrics snapshots for posts
 * - Retrieve snapshot history for a post
 * - Query metrics at specific dates
 * - Support multi-source data tracking
 *
 * Database schema:
 * - post_id: Foreign key to posts table
 * - client_id: Multi-tenant isolation (added in Migration 010)
 * - snapshot_date: Date of metric snapshot
 * - views, likes, comments, shares, saves: Metric values at snapshot time
 * - source: Data source (csv, api, manual)
 *
 * @package App\Repositories
 */
class MetricsHistoryRepository {
    /**
     * Database connection instance.
     *
     * @var Database
     */
    private Database $db;

    /**
     * Constructor - Inject database dependency.
     *
     * @param Database $db Database instance for executing queries
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Create a new metrics snapshot for a post.
     *
     * Stores a point-in-time snapshot of post metrics. Used for:
     * - Daily automated snapshots from API sync
     * - Preserving CSV import baseline
     * - Manual metric updates
     *
     * @param array $data Snapshot data containing:
     *                    - client_id (int, required): Tenant ID
     *                    - post_id (int, required): Post ID
     *                    - snapshot_date (string, required): Date in YYYY-MM-DD format
     *                    - views (int, optional): View count (default: 0)
     *                    - likes (int, optional): Like count (default: 0)
     *                    - comments (int, optional): Comment count (default: 0)
     *                    - shares (int, optional): Share count (default: 0)
     *                    - saves (int, optional): Save count (default: 0)
     *                    - reach (int, optional): Reach metric (default: 0)
     *                    - impressions (int, optional): Impressions (default: 0)
     *                    - source (string, optional): Data source (default: 'api')
     *
     * @return int Newly created snapshot ID
     *
     * @throws \Exception If required fields missing or database error
     */
    public function create(array $data): int {
        $sql = "INSERT INTO metrics_history (
            client_id, post_id, snapshot_date,
            views, likes, comments, shares, saves,
            reach, impressions, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->exec(
            $sql,
            'iisiiiiiiis',
            [
                $data['client_id'],
                $data['post_id'],
                $data['snapshot_date'],
                $data['views'] ?? 0,
                $data['likes'] ?? 0,
                $data['comments'] ?? 0,
                $data['shares'] ?? 0,
                $data['saves'] ?? 0,
                $data['reach'] ?? 0,
                $data['impressions'] ?? 0,
                $data['source'] ?? 'api',
            ]
        );

        return $this->db->insertId();
    }

    /**
     * Get the most recent snapshot for a post.
     *
     * Retrieves the latest metrics snapshot, useful for displaying
     * current/recent metrics without querying the posts table.
     *
     * @param int $postId Post ID
     *
     * @return array|null Latest snapshot record with all fields, or null if no snapshots exist
     */
    public function getLatestSnapshot(int $postId): ?array {
        return $this->db->fetchOne(
            "SELECT *
             FROM metrics_history
             WHERE post_id = ?
             ORDER BY snapshot_date DESC, id DESC
             LIMIT 1",
            "i",
            [$postId]
        );
    }

    /**
     * Get all snapshots for a post, ordered by date.
     *
     * Returns complete snapshot history for trend analysis and charting.
     *
     * @param int $postId Post ID
     * @param string|null $source Optional filter by source (csv, api, manual)
     * @param int $limit Maximum snapshots to return (default: 100)
     *
     * @return array Array of snapshot records ordered by snapshot_date ASC
     */
    public function getSnapshotHistory(int $postId, ?string $source = null, int $limit = 100): array {
        $sql = "SELECT *
                FROM metrics_history
                WHERE post_id = ?";
        $params = [$postId];
        $types = 'i';

        if ($source) {
            $sql .= " AND source = ?";
            $params[] = $source;
            $types .= 's';
        }

        $sql .= " ORDER BY snapshot_date ASC, id ASC LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Get snapshot for a specific date.
     *
     * Retrieves metrics as they were on a specific date. Useful for
     * historical reporting and "metrics at time of CSV import".
     *
     * @param int $postId Post ID
     * @param string $date Date in YYYY-MM-DD format
     *
     * @return array|null Snapshot for that date, or null if not found
     */
    public function getSnapshotByDate(int $postId, string $date): ?array {
        return $this->db->fetchOne(
            "SELECT *
             FROM metrics_history
             WHERE post_id = ? AND snapshot_date = ?
             ORDER BY id DESC
             LIMIT 1",
            "is",
            [$postId, $date]
        );
    }

    /**
     * Check if a snapshot already exists for a post on a specific date and source.
     *
     * Prevents duplicate snapshots when running sync multiple times per day.
     *
     * @param int $postId Post ID
     * @param string $date Snapshot date (YYYY-MM-DD)
     * @param string $source Data source (csv, api, manual)
     *
     * @return bool True if snapshot exists, false otherwise
     */
    public function snapshotExists(int $postId, string $date, string $source): bool {
        $result = $this->db->fetchOne(
            "SELECT id
             FROM metrics_history
             WHERE post_id = ? AND snapshot_date = ? AND source = ?
             LIMIT 1",
            "iss",
            [$postId, $date, $source]
        );

        return $result !== null;
    }

    /**
     * Update an existing snapshot.
     *
     * Updates metrics for a specific snapshot. Used when re-syncing data
     * for the same date/source combination.
     *
     * @param int $id Snapshot ID
     * @param array $data Updated metrics data:
     *                    - views (int, optional)
     *                    - likes (int, optional)
     *                    - comments (int, optional)
     *                    - shares (int, optional)
     *                    - saves (int, optional)
     *                    - reach (int, optional)
     *                    - impressions (int, optional)
     *
     * @return bool True if update succeeded, false if snapshot not found
     */
    public function update(int $id, array $data): bool {
        $exists = $this->db->fetchOne("SELECT id FROM metrics_history WHERE id = ? LIMIT 1", "i", [$id]);
        if (!$exists) return false;

        $sql = "UPDATE metrics_history SET
            views = ?,
            likes = ?,
            comments = ?,
            shares = ?,
            saves = ?,
            reach = ?,
            impressions = ?
        WHERE id = ?";

        $this->db->exec(
            $sql,
            'iiiiiiii',
            [
                $data['views'] ?? 0,
                $data['likes'] ?? 0,
                $data['comments'] ?? 0,
                $data['shares'] ?? 0,
                $data['saves'] ?? 0,
                $data['reach'] ?? 0,
                $data['impressions'] ?? 0,
                $id,
            ]
        );

        return true;
    }

    /**
     * Create or update snapshot (upsert operation).
     *
     * If a snapshot exists for the same post/date/source, update it.
     * Otherwise, create a new snapshot.
     *
     * @param array $data Snapshot data (same as create method)
     *
     * @return array Result with:
     *               - action (string): 'created' or 'updated'
     *               - id (int): Snapshot ID
     */
    public function upsert(array $data): array {
        // Check if snapshot exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM metrics_history
             WHERE post_id = ? AND snapshot_date = ? AND source = ?
             LIMIT 1",
            "iss",
            [$data['post_id'], $data['snapshot_date'], $data['source'] ?? 'api']
        );

        if ($existing) {
            // Update existing snapshot
            $this->update($existing['id'], $data);
            return ['action' => 'updated', 'id' => $existing['id']];
        } else {
            // Create new snapshot
            $id = $this->create($data);
            return ['action' => 'created', 'id' => $id];
        }
    }

    /**
     * Get metric growth between two dates for a post.
     *
     * Calculates the difference and percentage change between snapshots.
     * Useful for "views grew by 150 (+15%)" type insights.
     *
     * @param int $postId Post ID
     * @param string $fromDate Start date (YYYY-MM-DD)
     * @param string $toDate End date (YYYY-MM-DD)
     *
     * @return array|null Growth data with:
     *                    - views_diff (int): Absolute change in views
     *                    - views_percent (float): Percentage change
     *                    - likes_diff (int): Absolute change in likes
     *                    - likes_percent (float): Percentage change
     *                    - ... (same for comments, shares, saves)
     *                    Returns null if either snapshot not found
     */
    public function getMetricGrowth(int $postId, string $fromDate, string $toDate): ?array {
        $fromSnapshot = $this->getSnapshotByDate($postId, $fromDate);
        $toSnapshot = $this->getSnapshotByDate($postId, $toDate);

        if (!$fromSnapshot || !$toSnapshot) {
            return null;
        }

        $calculateGrowth = function($old, $new) {
            $diff = $new - $old;
            $percent = $old > 0 ? (($new - $old) / $old) * 100 : 0;
            return ['diff' => $diff, 'percent' => round($percent, 2)];
        };

        return [
            'views' => $calculateGrowth($fromSnapshot['views'], $toSnapshot['views']),
            'likes' => $calculateGrowth($fromSnapshot['likes'], $toSnapshot['likes']),
            'comments' => $calculateGrowth($fromSnapshot['comments'], $toSnapshot['comments']),
            'shares' => $calculateGrowth($fromSnapshot['shares'], $toSnapshot['shares']),
            'saves' => $calculateGrowth($fromSnapshot['saves'], $toSnapshot['saves']),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Get snapshots for multiple posts in a date range.
     *
     * Bulk query for dashboard charts and reports.
     *
     * @param int $clientId Client ID for multi-tenant filtering
     * @param array $postIds Array of post IDs
     * @param string|null $fromDate Start date (optional)
     * @param string|null $toDate End date (optional)
     *
     * @return array Array of snapshots grouped by post_id
     */
    public function getBulkSnapshots(int $clientId, array $postIds, ?string $fromDate = null, ?string $toDate = null): array {
        if (empty($postIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT *
                FROM metrics_history
                WHERE client_id = ? AND post_id IN ({$placeholders})";

        $params = [$clientId, ...$postIds];
        $types = 'i' . str_repeat('i', count($postIds));

        if ($fromDate) {
            $sql .= " AND snapshot_date >= ?";
            $params[] = $fromDate;
            $types .= 's';
        }

        if ($toDate) {
            $sql .= " AND snapshot_date <= ?";
            $params[] = $toDate;
            $types .= 's';
        }

        $sql .= " ORDER BY post_id, snapshot_date ASC";

        return $this->db->fetchAll($sql, $types, $params);
    }

    /**
     * Delete snapshots for a post.
     *
     * Used when deleting posts or cleaning up old data.
     *
     * @param int $postId Post ID
     *
     * @return void
     */
    public function deleteByPostId(int $postId): void {
        $this->db->exec(
            "DELETE FROM metrics_history WHERE post_id = ?",
            "i",
            [$postId]
        );
    }

    /**
     * Delete snapshots older than a specific date.
     *
     * Data retention cleanup - remove snapshots older than X months.
     *
     * @param string $beforeDate Date in YYYY-MM-DD format
     *
     * @return int Number of snapshots deleted
     */
    public function deleteOldSnapshots(string $beforeDate): int {
        $this->db->exec(
            "DELETE FROM metrics_history WHERE snapshot_date < ?",
            "s",
            [$beforeDate]
        );

        return $this->db->affectedRows();
    }
}
