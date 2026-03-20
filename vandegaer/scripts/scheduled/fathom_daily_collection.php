#!/usr/bin/env php
<?php
/**
 * Fathom Analytics Daily Data Collection Script
 *
 * This script should be run daily (recommended: 6 AM local time) to:
 * 1. Fetch yesterday's analytics data from Fathom API
 * 2. Store aggregated metrics in website_analytics table
 * 3. Correlate referrer traffic with social media posts
 *
 * Schedule:
 * - Windows Task Scheduler: Daily at 6:00 AM
 *   Command: php C:\xampp3\htdocs\socialbit-live\scripts\scheduled\fathom_daily_collection.php
 *
 * - Linux Cron: Daily at 6:00 AM
 *   0 6 * * * cd /var/www/socialbit-live && php scripts/scheduled/fathom_daily_collection.php
 *
 * Prerequisites:
 * - Fathom credentials configured in settings table (fathom_api_token, fathom_site_id)
 * - Database migration 014 executed (website_analytics, post_website_traffic tables)
 *
 * Exit Codes:
 * 0 = Success
 * 1 = Configuration error
 * 2 = Database connection error
 * 3 = API error
 *
 * @package SocialBit
 * @subpackage Scheduled
 */

declare(strict_types=1);

// Change to project root directory
chdir(__DIR__ . '/../..');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Database;
use App\Repositories\SettingsRepository;
use App\Repositories\WebAnalyticsRepository;
use App\Repositories\ClientRepository;
use App\Services\FathomAnalyticsApiService;

// ==================== CONFIGURATION ====================

$logFile = __DIR__ . '/../../storage/logs/fathom_collection_' . date('Y-m-d') . '.log';

function logMessage(string $message, string $level = 'INFO'): void {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";
    echo $logLine; // Console output
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

// ==================== MAIN EXECUTION ====================

try {
    logMessage("=== Fathom Analytics Daily Collection Started ===");

    // 1. Load configuration
    $config = require __DIR__ . '/../../config/app.php';
    if (!$config) {
        throw new Exception("Failed to load configuration file");
    }

    // 2. Connect to database
    $db = new Database($config['db']);
    logMessage("Database connection established");

    // 3. Initialize repositories
    $settingsRepo = new SettingsRepository($db);
    $webAnalyticsRepo = new WebAnalyticsRepository($db);
    $clientRepo = new ClientRepository($db);

    // 4. Get Fathom credentials
    $apiTokenRecord = $settingsRepo->get('fathom_api_token');
    $siteIdRecord = $settingsRepo->get('fathom_site_id');

    if (!$apiTokenRecord || !$siteIdRecord) {
        logMessage("Fathom credentials not configured - skipping collection", 'WARNING');
        exit(1);
    }

    $apiToken = $apiTokenRecord['value'];
    $siteId = $siteIdRecord['value'];

    logMessage("Fathom credentials loaded (Site ID: {$siteId})");

    // 5. Initialize Fathom API service
    $fathomService = new FathomAnalyticsApiService($apiToken, $siteId, $settingsRepo);

    // Test connection
    if (!$fathomService->testConnection()) {
        throw new Exception("Fathom API connection test failed - check credentials");
    }
    logMessage("Fathom API connection verified");

    // 6. Determine date to collect (yesterday by default)
    $dateToCollect = isset($argv[1]) ? $argv[1] : date('Y-m-d', strtotime('-1 day'));
    logMessage("Collecting data for date: {$dateToCollect}");

    // 7. Get all active clients
    $clients = $clientRepo->getAll();
    $totalClients = count($clients);
    logMessage("Found {$totalClients} active clients");

    if ($totalClients === 0) {
        logMessage("No clients found - nothing to collect", 'WARNING');
        exit(0);
    }

    // 8. Collect data for each client
    $successCount = 0;
    $errorCount = 0;
    $totalCorrelatedPosts = 0;

    foreach ($clients as $client) {
        $clientId = (int)$client['id'];
        $clientName = $client['name'] ?? "Client #{$clientId}";

        try {
            logMessage("Processing {$clientName}...");

            // Fetch analytics data from Fathom
            $data = $fathomService->collectAnalyticsData($dateToCollect, $dateToCollect);

            // Transform and store aggregated metrics
            $dayData = $fathomService->transformToDbFormat($data['aggregations']);

            $webAnalyticsRepo->storeAnalytics($clientId, 'fathom', $dateToCollect, $dayData);

            logMessage("  ✓ Stored aggregated metrics for {$clientName}");

            // Correlate referrers with posts
            $correlatedPosts = 0;
            if (!empty($data['referrers'])) {
                // Add date to each referrer for correlation
                $referrersWithDate = array_map(function($ref) use ($dateToCollect) {
                    $ref['date'] = $dateToCollect;
                    return $ref;
                }, $data['referrers']);

                $correlatedPosts = $webAnalyticsRepo->correlateReferrersWithPosts(
                    $clientId,
                    $referrersWithDate
                );

                logMessage("  ✓ Correlated {$correlatedPosts} posts with website traffic");
                $totalCorrelatedPosts += $correlatedPosts;
            }

            $successCount++;
        } catch (Exception $e) {
            logMessage("  ✗ Error processing {$clientName}: " . $e->getMessage(), 'ERROR');
            $errorCount++;
        }
    }

    // 9. Summary report
    logMessage("=== Collection Summary ===");
    logMessage("Date: {$dateToCollect}");
    logMessage("Clients processed: {$totalClients}");
    logMessage("Successful: {$successCount}");
    logMessage("Errors: {$errorCount}");
    logMessage("Total posts correlated: {$totalCorrelatedPosts}");
    logMessage("=== Fathom Analytics Collection Completed ===");

    exit($errorCount > 0 ? 3 : 0);

} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(2);
}
