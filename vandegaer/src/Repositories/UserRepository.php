<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * UserRepository - Database operations for user accounts.
 *
 * This repository handles database queries related to user authentication and
 * user account management. It provides methods for retrieving user data needed
 * for authentication and authorization.
 *
 * Responsibilities:
 * - Execute SQL queries against the users table
 * - Retrieve user credentials for authentication
 * - Handle user lookup operations
 *
 * Security notes:
 * - Returns password_hash field for verification (never plain text passwords)
 * - Password verification should be done using password_verify() function
 * - User queries are parameterized to prevent SQL injection
 *
 * @package App\Repositories
 */
class UserRepository {
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
     * Find user by username for authentication.
     *
     * Retrieves user credentials needed for login authentication. Returns the
     * password hash which should be verified using password_verify() function.
     *
     * @param string $username Username to search for (case-sensitive)
     *
     * @return array|null User record containing:
     *                    - id (int): User primary key
     *                    - username (string): Username
     *                    - password_hash (string): Hashed password (bcrypt/argon2)
     *                    NULL if user not found
     *
     * @example
     * ```php
     * $user = $userRepo->findByUsername('admin');
     * if ($user && password_verify($password, $user['password_hash'])) {
     *     // Authentication successful
     * }
     * ```
     */
    public function findByUsername(string $username): ?array {
        return $this->db->fetchOne(
            "SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1",
            "s",
            [$username]
        );
    }
}
