<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * ClientRepository - Database operations for client management.
 *
 * This repository handles all database queries related to clients (customers).
 * It provides CRUD operations for managing multiple clients under a single user account.
 *
 * Responsibilities:
 * - Execute SQL queries for clients table
 * - Handle client creation, updates, and deletion
 * - Retrieve client lists and individual client data
 * - Validate client ownership (user_id)
 *
 * @package App\Repositories
 */
class ClientRepository {
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
     * Get all clients for a specific user.
     *
     * Retrieves all clients owned by the given user ID, ordered by creation date
     * with most recent first.
     *
     * @param int $userId User ID to filter clients by
     * @param bool $activeOnly If true, only return active clients (is_active = TRUE)
     *
     * @return array Array of client records, each containing:
     *               - id (int): Client primary key
     *               - user_id (int): Owner user ID
     *               - name (string): Client name
     *               - company_name (string|null): Company name
     *               - industry (string|null): Industry sector
     *               - website (string|null): Website URL
     *               - is_active (bool): Whether client is active
     *               - notes (string|null): Internal notes
     *               - created_at (string): Creation timestamp
     *               - updated_at (string): Last update timestamp
     */
    public function getAllByUser(int $userId, bool $activeOnly = false): array {
        $sql = "SELECT * FROM clients WHERE user_id = ?";

        if ($activeOnly) {
            $sql .= " AND is_active = TRUE";
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, "i", [$userId]);
    }

    /**
     * Get a single client by ID.
     *
     * Retrieves a specific client record by its primary key.
     *
     * @param int $clientId Client ID to retrieve
     *
     * @return array|null Client record with same structure as getAllByUser(),
     *                    or null if client not found
     */
    public function getById(int $clientId): ?array {
        $result = $this->db->fetchAll(
            "SELECT * FROM clients WHERE id = ? LIMIT 1",
            "i",
            [$clientId]
        );

        return $result[0] ?? null;
    }

    /**
     * Create a new client.
     *
     * Inserts a new client record into the database.
     *
     * @param int $userId User ID who owns this client
     * @param array $data Client data containing:
     *                    - name (string, required): Client name
     *                    - company_name (string, optional): Company name
     *                    - industry (string, optional): Industry sector
     *                    - website (string, optional): Website URL
     *                    - notes (string, optional): Internal notes
     *
     * @return int Newly created client ID
     */
    public function create(int $userId, array $data): int {
        $name = $data['name'] ?? '';
        $companyName = $data['company_name'] ?? null;
        $industry = $data['industry'] ?? null;
        $website = $data['website'] ?? null;
        $notes = $data['notes'] ?? null;

        $this->db->exec(
            "INSERT INTO clients (user_id, name, company_name, industry, website, notes, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW(), NOW())",
            "isssss",
            [$userId, $name, $companyName, $industry, $website, $notes]
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update an existing client.
     *
     * Updates client fields. Only provided fields are updated.
     *
     * @param int $clientId Client ID to update
     * @param array $data Client data to update (partial update supported):
     *                    - name (string, optional): Client name
     *                    - company_name (string, optional): Company name
     *                    - industry (string, optional): Industry sector
     *                    - website (string, optional): Website URL
     *                    - notes (string, optional): Internal notes
     *                    - is_active (bool, optional): Active status
     *
     * @return bool True if update successful, false otherwise
     */
    public function update(int $clientId, array $data): bool {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $types .= "s";
            $values[] = $data['name'];
        }

        if (isset($data['company_name'])) {
            $fields[] = "company_name = ?";
            $types .= "s";
            $values[] = $data['company_name'];
        }

        if (isset($data['industry'])) {
            $fields[] = "industry = ?";
            $types .= "s";
            $values[] = $data['industry'];
        }

        if (isset($data['website'])) {
            $fields[] = "website = ?";
            $types .= "s";
            $values[] = $data['website'];
        }

        if (isset($data['notes'])) {
            $fields[] = "notes = ?";
            $types .= "s";
            $values[] = $data['notes'];
        }

        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $types .= "i";
            $values[] = $data['is_active'] ? 1 : 0;
        }

        if (empty($fields)) {
            return false; // Nothing to update
        }

        // Add updated_at
        $fields[] = "updated_at = NOW()";

        // Add client_id to params
        $types .= "i";
        $values[] = $clientId;

        $sql = "UPDATE clients SET " . implode(", ", $fields) . " WHERE id = ?";

        return $this->db->exec($sql, $types, $values);
    }

    /**
     * Delete a client (soft delete by setting is_active = FALSE).
     *
     * Sets the client's is_active flag to FALSE instead of actually deleting the record.
     * This preserves historical data while hiding the client from normal operations.
     *
     * @param int $clientId Client ID to deactivate
     *
     * @return bool True if deactivation successful, false otherwise
     */
    public function deactivate(int $clientId): bool {
        return $this->db->exec(
            "UPDATE clients SET is_active = FALSE, updated_at = NOW() WHERE id = ?",
            "i",
            [$clientId]
        );
    }

    /**
     * Hard delete a client and all associated data.
     *
     * WARNING: This permanently deletes the client and ALL associated data
     * (posts, social accounts, content groups) due to CASCADE constraints.
     * Use with extreme caution!
     *
     * @param int $clientId Client ID to permanently delete
     *
     * @return bool True if deletion successful, false otherwise
     */
    public function delete(int $clientId): bool {
        return $this->db->exec(
            "DELETE FROM clients WHERE id = ?",
            "i",
            [$clientId]
        );
    }

    /**
     * Count total clients for a user.
     *
     * @param int $userId User ID to count clients for
     * @param bool $activeOnly If true, only count active clients
     *
     * @return int Total number of clients
     */
    public function countByUser(int $userId, bool $activeOnly = false): int {
        $sql = "SELECT COUNT(*) as count FROM clients WHERE user_id = ?";

        if ($activeOnly) {
            $sql .= " AND is_active = TRUE";
        }

        $result = $this->db->fetchAll($sql, "i", [$userId]);
        return (int)($result[0]['count'] ?? 0);
    }

    /**
     * Check if a client belongs to a specific user.
     *
     * Verifies ownership before allowing operations on a client.
     *
     * @param int $clientId Client ID to check
     * @param int $userId User ID to verify ownership against
     *
     * @return bool True if client belongs to user, false otherwise
     */
    public function belongsToUser(int $clientId, int $userId): bool {
        $result = $this->db->fetchAll(
            "SELECT COUNT(*) as count FROM clients WHERE id = ? AND user_id = ?",
            "ii",
            [$clientId, $userId]
        );

        return ((int)($result[0]['count'] ?? 0)) > 0;
    }
}
