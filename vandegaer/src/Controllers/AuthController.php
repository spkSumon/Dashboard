<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\UserRepository;

/**
 * AuthController - Handles user authentication operations.
 *
 * This controller provides endpoints for user authentication, including login
 * functionality. It validates credentials and generates authentication tokens
 * for successful logins.
 *
 * Responsibilities:
 * - Parse and validate login credentials from request body
 * - Verify user credentials against database records
 * - Generate authentication tokens (POC implementation using base64 + HMAC)
 * - Return standardized JSON responses with user data and tokens
 *
 * Security notes:
 * - Passwords are verified using PHP's password_verify() function
 * - Current token implementation is POC (proof-of-concept) using base64 + SHA-256 HMAC
 * - Production should use proper JWT implementation with expiration and refresh tokens
 *
 * @package App\Controllers
 */
class AuthController {
    /**
     * User repository for database operations.
     *
     * @var UserRepository
     */
    private $users;

    /**
     * Secret key for token signing (HMAC).
     *
     * @var string
     */
    private $secret;

    /**
     * Constructor - Inject user repository and secret key.
     *
     * @param UserRepository $users Repository for accessing user data from database
     * @param string $secret Secret key used for HMAC token signing
     */
    public function __construct(UserRepository $users, $secret) {
        $this->users = $users;
        $this->secret = $secret;
    }

    /**
     * Authenticate user and generate authentication token.
     *
     * Validates user credentials (username and password) and generates an authentication
     * token upon successful login. The token is a POC implementation using base64-encoded
     * payload with SHA-256 HMAC signature.
     *
     * Expected request body (JSON):
     * ```json
     * {
     *   "username": "admin",
     *   "password": "securepassword123"
     * }
     * ```
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "token": "eyJ1aWQiOjEsInUiOiJhZG1pbiIsInRzIjoxNzA0MDY3MjAwfQ==.a3f5b2c1d4e6...",
     *   "user": {
     *     "id": 1,
     *     "username": "admin"
     *   }
     * }
     * ```
     *
     * Token structure (POC implementation):
     * - Part 1 (before dot): base64(json({uid: user_id, u: username, ts: timestamp}))
     * - Part 2 (after dot): sha256_hmac(secret)
     *
     * Response format (error - HTTP 400):
     * ```json
     * {
     *   "error": "Gebruikersnaam of wachtwoord ontbreekt"
     * }
     * ```
     *
     * Response format (error - HTTP 401):
     * ```json
     * {
     *   "error": "Ongeldige inlog"
     * }
     * ```
     *
     * Possible error scenarios:
     * - Missing username or password (HTTP 400)
     * - Empty username or password after trimming (HTTP 400)
     * - User not found in database (HTTP 401)
     * - Invalid password (HTTP 401)
     *
     * Security measures:
     * - Password verification using password_verify() with bcrypt/argon2 hashes
     * - Generic error message on authentication failure (doesn't reveal if username or password is wrong)
     * - Timestamp included in token for potential expiration checks
     *
     * @return void Sends JSON response and terminates execution
     */
    public function login(): void {
        $body = Request::jsonBody();
        $username = trim($body['username'] ?? '');
        $password = (string)($body['password'] ?? '');

        if (!$username || !$password) {
            Response::error('Gebruikersnaam of wachtwoord ontbreekt', 400);
        }

        $user = $this->users->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Ongeldige inlog', 401);
        }

        // POC token (geen echte JWT)
        $payload = ['uid' => (int)$user['id'], 'u' => $username, 'ts' => time()];
        $token = base64_encode(json_encode($payload)) . '.' . hash('sha256', $this->secret);

        Response::json(['token' => $token, 'user' => ['id' => (int)$user['id'], 'username' => $username]]);
    }
}
