<?php
namespace App\Controllers;

use App\Services\TikTokAnalyticsService;
use Exception;

/**
 * TikTokAnalyticsController - Handles TikTok analytics synchronization.
 *
 * This controller provides endpoints for triggering manual synchronization of
 * TikTok video data and analytics via the TikTok Analytics API.
 *
 * Responsibilities:
 * - Trigger manual sync operations for TikTok videos
 * - Handle sync errors and return standardized responses
 * - Delegate sync logic to TikTokAnalyticsService
 *
 * Available endpoints:
 * - POST /api/tiktok/sync - Manually trigger TikTok video sync
 *
 * @package App\Controllers
 */
class TikTokAnalyticsController {
    /**
     * TikTok analytics service for API operations.
     *
     * @var TikTokAnalyticsService
     */
    private $analyticsService;

    /**
     * Constructor - Inject TikTok analytics service dependency.
     *
     * @param TikTokAnalyticsService $analyticsService Service for TikTok API operations
     */
    public function __construct(TikTokAnalyticsService $analyticsService) {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Trigger manual synchronization of TikTok videos and analytics.
     *
     * Initiates a full sync of the authenticated user's TikTok videos, fetching
     * video metadata and analytics data from the TikTok API. Imports new videos
     * and updates existing ones with latest metrics.
     *
     * Process:
     * 1. Retrieve valid OAuth access token from database
     * 2. Fetch user's videos from TikTok API
     * 3. Fetch analytics for each video (views, likes, comments, shares)
     * 4. Import/update videos in posts table
     * 5. Store metrics in metrics_history table
     *
     * Expected request:
     * - Method: POST
     * - No request body required
     * - Requires valid TikTok OAuth token in database
     *
     * Response format (success - HTTP 200):
     * ```json
     * {
     *   "data": {
     *     "videos_synced": 25,
     *     "new_videos": 3,
     *     "updated_videos": 22,
     *     "total_views": 125300,
     *     "total_likes": 8420,
     *     "sync_timestamp": "2025-01-15 14:30:00"
     *   }
     * }
     * ```
     *
     * Response format (error - HTTP 500):
     * ```json
     * {
     *   "error": {
     *     "code": "SYNC_ERROR",
     *     "message": "No valid TikTok token found. Please re-authorize."
     *   }
     * }
     * ```
     *
     * Common error scenarios:
     * - No valid OAuth token (user needs to re-authorize)
     * - TikTok API rate limit exceeded
     * - Network connectivity issues
     * - Invalid/expired access token (needs refresh)
     * - TikTok API service unavailable
     *
     * Notes:
     * - Sync can take several seconds for accounts with many videos
     * - Consider implementing background job processing for large accounts
     * - Rate limits: TikTok API has daily/hourly rate limits
     * - Auto-refresh tokens if expired during sync
     *
     * @return void Sends JSON response and terminates execution
     *
     * @throws Exception If sync fails due to API errors or missing tokens
     */
    public function sync(): void {
        try {
            $result = $this->analyticsService->syncAllVideos();

            echo json_encode([
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => [
                    'code' => 'SYNC_ERROR',
                    'message' => $e->getMessage()
                ]
            ]);
        }
    }
}
