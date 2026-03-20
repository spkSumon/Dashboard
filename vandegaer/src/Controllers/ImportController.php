<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Services\TikTokCsvImporter;
use App\Services\GenericCsvImporter;

/**
 * ImportController - Handles CSV import operations for social media analytics data.
 *
 * This controller provides endpoints for importing social media data from CSV files.
 * It supports both TikTok-specific imports and generic multi-platform imports,
 * delegating the actual import logic to dedicated service classes.
 *
 * Responsibilities:
 * - Validate $_FILES input from HTTP requests
 * - Delegate import processing to appropriate service (TikTokCsvImporter or GenericCsvImporter)
 * - Return standardized JSON responses with import results
 *
 * @package App\Controllers
 */
class ImportController {
    /**
     * TikTok CSV import service.
     *
     * @var TikTokCsvImporter
     */
    private $tiktok;

    /**
     * Generic/multi-platform CSV import service.
     *
     * @var GenericCsvImporter
     */
    private $generic;

    /**
     * Constructor - Inject CSV import services.
     *
     * @param TikTokCsvImporter $tiktok Service for importing TikTok-specific CSV files
     * @param GenericCsvImporter $generic Service for importing generic/multi-platform CSV files
     */
    public function __construct(TikTokCsvImporter $tiktok, GenericCsvImporter $generic) {
        $this->tiktok = $tiktok;
        $this->generic = $generic;
    }

    /**
     * Import TikTok analytics data from CSV file.
     *
     * Processes a TikTok Studio export CSV file and imports posts and metrics into the database.
     * Creates or updates posts in the `posts` table and stores metric snapshots in `metrics_history`.
     *
     * Expected $_FILES structure:
     * - $_FILES['file']['tmp_name']: Path to uploaded CSV file
     * - $_FILES['file']['error']: Upload error code (should be UPLOAD_ERR_OK)
     * - $_FILES['file']['name']: Original filename (used for logging)
     *
     * Expected CSV columns (flexible mapping):
     * - post_id / video_id / id (required): TikTok post identifier
     * - caption / title (optional): Post caption/title
     * - url / link (optional): Post URL
     * - posted_date / date / published_date (optional): Publication date (YYYY-MM-DD)
     * - posted_time / time (optional): Publication time (HH:MM:SS)
     * - views / play_count (optional): View count
     * - likes / like_count (optional): Like count
     * - comments / comment_count (optional): Comment count
     * - shares / share_count (optional): Share count
     * - saves / save_count (optional): Save/bookmark count
     * - snapshot_date (optional): Date of metrics snapshot (defaults to today)
     *
     * Response format (success):
     * ```json
     * {
     *   "processed": 15,
     *   "skipped": 2
     * }
     * ```
     *
     * Response format (error - HTTP 500):
     * ```json
     * {
     *   "error": "Importfout: [detailed error message]"
     * }
     * ```
     *
     * Query parameters ($_GET):
     * - client_id (int, optional): Client ID to associate imported posts with (defaults to 1)
     *
     * Possible error scenarios:
     * - File upload failed (UPLOAD_ERR_*)
     * - CSV file is empty or malformed
     * - Database transaction failure
     * - Missing required columns (post_id)
     *
     * @return void Sends JSON response and terminates execution
     */
    public function tiktok(): void {
        // Get client_id from query params (default to 1 for backwards compatibility)
        $clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 1;

        $result = $this->tiktok->import($_FILES['file'] ?? [], $clientId);
        if (!empty($result['error'])) Response::error('Importfout: ' . $result['error'], 500);
        Response::json($result);
    }

    /**
     * Import generic/multi-platform social media data from CSV file.
     *
     * Auto-detects CSV format and processes data from multiple social media platforms.
     * Supports both TikTok exports and custom content tracker formats (content_tracker_enhanced.csv).
     * Creates or updates posts with comprehensive metrics and processes hashtags into separate tables.
     *
     * Expected $_FILES structure:
     * - $_FILES['file']['tmp_name']: Path to uploaded CSV file
     * - $_FILES['file']['error']: Upload error code (should be UPLOAD_ERR_OK)
     * - $_FILES['file']['name']: Original filename (used for logging)
     *
     * Supported CSV formats:
     *
     * Format 1: TikTok Export
     * - Columns: post_id, caption, url, posted_date, views, likes, comments, shares, saves
     *
     * Format 2: Content Tracker Enhanced
     * - Columns: Content_ID, Platform, Status, Datum, Post_Tijd, Content_Type, Post_Onderwerp,
     *   Caption_Kort, Hashtags, Bereik, Video_Views, Likes, Comments, Shares, Saves,
     *   Impressies, Engagement, Notities
     * - Only imports posts with Status != "Gepland"
     * - Supported platforms: instagram, tiktok, facebook
     *
     * Response format (success):
     * ```json
     * {
     *   "processed": 42,
     *   "skipped": 5,
     *   "format": "content_tracker"
     * }
     * ```
     *
     * Response format (error - HTTP 500):
     * ```json
     * {
     *   "error": "Importfout: [detailed error message]"
     * }
     * ```
     *
     * Possible error scenarios:
     * - File upload failed (UPLOAD_ERR_*)
     * - CSV file is empty or has mismatched columns
     * - Unknown/unsupported CSV format
     * - Database transaction failure
     * - Invalid platform name
     *
     * Side effects:
     * - Inserts/updates records in `posts` table (upsert by platform + platform_post_id)
     * - Inserts metric snapshots in `metrics_history` table
     * - Processes hashtags into `hashtag_performance` and `post_hashtags` tables
     * - Logs import operation in `import_history` table
     *
     * @return void Sends JSON response and terminates execution
     */
    public function generic(): void {
        $result = $this->generic->import($_FILES['file'] ?? []);
        if (!empty($result['error'])) {
            Response::error('Importfout: ' . $result['error'], 500);
        }
        Response::json($result);
    }
}
