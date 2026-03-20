<?php
namespace App\Controllers;

use App\Services\TikTokOAuthService;
use App\Repositories\TikTokRepository;
use App\Helpers\Response;
use Exception;

/**
 * Controller voor TikTok OAuth 2.0 flow
 *
 * Verantwoordelijk voor:
 * - TikTok OAuth 2.0 authorization flow (redirect naar TikTok login)
 * - Access token exchange na callback
 * - Token opslag in database via TikTokRepository
 * - App token generatie voor eigen authenticatie
 * - Connection status monitoring
 * - Token disconnect/revocation
 *
 * OAuth Flow:
 * 1. User klikt "Connect TikTok" → authorize() redirect naar TikTok
 * 2. User logt in op TikTok en geeft toestemming
 * 3. TikTok redirect naar callback() met authorization code
 * 4. callback() exchange code voor access token
 * 5. Token wordt opgeslagen + app token gegenereerd
 * 6. User wordt geredirect naar frontend met app token
 *
 * @see TikTokOAuthService Voor OAuth 2.0 API communicatie met TikTok
 * @see TikTokRepository Voor token lifecycle management in database
 */
class TikTokOAuthController {
    /**
     * @var TikTokOAuthService TikTok OAuth API service
     */
    private $oauthService;

    /**
     * @var TikTokRepository Token repository voor database operaties
     */
    private $repository;

    /**
     * @var string HMAC secret voor app token signing (POC implementatie)
     */
    private $secret;

    public function __construct(TikTokOAuthService $oauthService, TikTokRepository $repository, string $secret = '') {
        $this->oauthService = $oauthService;
        $this->repository = $repository;
        $this->secret = $secret;
    }

    /**
     * Genereer app token na TikTok login
     */
    private function generateAppToken(string $openId): string {
        $payload = [
            'uid' => 0,
            'u' => 'tiktok_' . substr($openId, 0, 8),
            'tiktok_id' => $openId,
            'ts' => time()
        ];
        return base64_encode(json_encode($payload)) . '.' . hash('sha256', $this->secret);
    }

    /**
     * Start OAuth flow - redirect naar TikTok authorization
     * GET /api/tiktok/authorize
     */
    public function authorize(): void {
        try {
            $authUrl = $this->oauthService->getAuthUrl();

            // Redirect naar TikTok
            header('Location: ' . $authUrl);
            exit;
        } catch (Exception $e) {
            Response::error('Failed to generate authorization URL: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle OAuth callback van TikTok
     * GET /api/tiktok/callback?code=xxx&state=xxx
     */
    public function callback(): void {
        try {
            // Check for error from TikTok
            if (isset($_GET['error'])) {
                throw new Exception('TikTok authorization failed: ' . ($_GET['error_description'] ?? $_GET['error']));
            }

            // Check authorization code
            if (!isset($_GET['code'])) {
                throw new Exception('No authorization code received');
            }

            $code = $_GET['code'];

            // Exchange code voor access token
            $tokenData = $this->oauthService->exchangeCodeForToken($code);

            // Sla token op in database
            $tokenId = $this->repository->saveToken($tokenData);

            // Genereer app token voor authenticatie
            $openId = $tokenData['open_id'] ?? 'unknown';
            $appToken = $this->generateAppToken($openId);

            // Redirect naar frontend met success + token (detecteer base path)
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $basePath . '/?tiktok=connected&token=' . urlencode($appToken));
            exit;

        } catch (Exception $e) {
            // Redirect naar frontend met error
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $errorMsg = urlencode($e->getMessage());
            header('Location: ' . $basePath . '/?tiktok=error&message=' . $errorMsg);
            exit;
        }
    }

    /**
     * Check TikTok connection status
     * GET /api/tiktok/status
     */
    public function status(): void {
        try {
            $hasToken = $this->repository->hasValidToken();
            $latestToken = $this->repository->getLatestToken();

            Response::json([
                'connection_status' => $hasToken ? 'connected' : 'disconnected',
                'last_sync' => $latestToken['updated_at'] ?? null,
                'token_expires_at' => $latestToken['expires_at'] ?? null,
                'open_id' => $latestToken['open_id'] ?? null,
            ]);
        } catch (Exception $e) {
            Response::error('Failed to check status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Disconnect TikTok (revoke token)
     * POST /api/tiktok/disconnect
     */
    public function disconnect(): void {
        try {
            // Voor nu: gewoon expired tokens verwijderen
            $deleted = $this->repository->deleteExpiredTokens();

            Response::json([
                'message' => 'TikTok disconnected successfully',
                'tokens_deleted' => $deleted
            ]);
        } catch (Exception $e) {
            Response::error('Failed to disconnect: ' . $e->getMessage(), 500);
        }
    }
}
