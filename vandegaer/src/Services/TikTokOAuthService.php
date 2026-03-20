<?php
namespace App\Services;

use App\Repositories\TikTokRepository;
use Exception;

/**
 * Service voor TikTok OAuth 2.0 flow
 */
class TikTokOAuthService {
    private $clientKey;
    private $clientSecret;
    private $redirectUri;
    private $authUrl = 'https://www.tiktok.com/v2/auth/authorize/';
    private $tokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
    private $repository;

    public function __construct(array $config, TikTokRepository $repository) {
        $this->clientKey = $config['client_key'];
        $this->clientSecret = $config['client_secret'];
        $this->redirectUri = $config['redirect_uri'] ?: $this->detectRedirectUri();
        $this->repository = $repository;
    }

    /**
     * Auto-detect redirect URI based on current environment
     * @return string Redirect URI
     */
    private function detectRedirectUri(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        // Normalize base path
        if ($basePath === '\\' || $basePath === '/') {
            $basePath = '';
        }

        return $protocol . '://' . $host . $basePath . '/api/tiktok/callback';
    }

    /**
     * Genereer de authorization URL waar de gebruiker naartoe moet
     */
    public function getAuthUrl(): string {
        $params = [
            'client_key' => $this->clientKey,
            'scope' => 'user.info.basic,user.info.profile,user.info.stats,video.list,video.upload',
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'state' => bin2hex(random_bytes(16)),  // CSRF protection
        ];

        return $this->authUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code voor access token
     * @param string $code Authorization code van TikTok callback
     * @return array Token data
     * @throws Exception Als token exchange faalt
     */
    public function exchangeCodeForToken(string $code): array {
        $data = [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        $response = $this->makeRequest($this->tokenUrl, $data);

        if (isset($response['error'])) {
            throw new Exception('TikTok token exchange failed: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        if (!isset($response['access_token'])) {
            throw new Exception('No access token in response');
        }

        // Transform response naar database format
        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'token_type' => $response['token_type'] ?? 'Bearer',
            'expires_at' => date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 86400)),
            'scope' => $response['scope'] ?? null,
            'open_id' => $response['open_id'] ?? null,
        ];
    }

    /**
     * Refresh een verlopen access token
     * @param string $refreshToken Refresh token
     * @return array Nieuwe token data
     * @throws Exception Als refresh faalt
     */
    public function refreshAccessToken(string $refreshToken): array {
        $data = [
            'client_key' => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $response = $this->makeRequest($this->tokenUrl, $data);

        if (isset($response['error'])) {
            throw new Exception('TikTok token refresh failed: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $refreshToken,
            'expires_at' => date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 86400)),
        ];
    }

    /**
     * Check of token verlopen is
     */
    public function isTokenExpired(string $expiresAt): bool {
        return strtotime($expiresAt) < time();
    }

    /**
     * Haal een geldige access token op (auto-refresh als nodig)
     * @return string|null Access token of null als niet gevonden
     * @throws Exception Als token refresh faalt
     */
    public function getValidToken(): ?string {
        $tokenData = $this->repository->getLatestToken();

        if (!$tokenData) {
            return null;
        }

        // Check if expired
        if ($this->isTokenExpired($tokenData['expires_at'])) {
            // Try to refresh
            if ($tokenData['refresh_token']) {
                try {
                    $newToken = $this->refreshAccessToken($tokenData['refresh_token']);
                    $this->repository->updateToken($tokenData['id'], $newToken);
                    return $newToken['access_token'];
                } catch (Exception $e) {
                    // Refresh failed, token is invalid
                    return null;
                }
            }

            // No refresh token, token is expired
            return null;
        }

        return $tokenData['access_token'];
    }

    /**
     * Maak HTTP request naar TikTok API
     * @param string $url API endpoint
     * @param array $data POST data
     * @return array Response data
     * @throws Exception Als request faalt
     */
    private function makeRequest(string $url, array $data): array {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
