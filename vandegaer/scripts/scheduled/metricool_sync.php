<?php
/**
 * Metricool Scheduled Sync Script
 *
 * Daily scheduled task to sync posts and metrics from Metricool API to SocialBit database.
 * This script should run via cron job at 4 AM local time daily.
 *
 * Cron Configuration:
 *   0 4 * * * /usr/bin/php /path/to/socialbit-live/scripts/scheduled/metricool_sync.php >> /var/log/metricool_sync.log 2>&1
 *
 * Process:
 * 1. Load API credentials from settings table
 * 2. Fetch all active clients
 * 3. For each client:
 *    - Get connected Metricool profiles (blogId)
 *    - Sync posts from last 30 days (to catch updates)
 *    - Log sync results
 *
 * Features:
 * - Multi-tenant support (syncs all clients)
 * - Error handling per client (one failure doesn't stop others)
 * - Detailed logging for debugging
 * - Rate limit awareness
 *
 * Requirements:
 * - Metricool API key stored in settings table (key: 'metricool_api_key')
 * - Metricool user ID stored in settings table (key: 'metricool_user_id')
 * - Each client needs blogId stored in clients table or settings
 *
 * Usage:
 *   php scripts/scheduled/metricool_sync.php
 */

// Enable error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader (adjust path if using Composer)
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Repositories/PostRepository.php';
require_once __DIR__ . '/../../src/Repositories/MetricsHistoryRepository.php';
require_once __DIR__ . '/../../src/Repositories/SettingsRepository.php';
require_once __DIR__ . '/../../src/Repositories/ClientRepository.php';
require_once __DIR__ . '/../../src/Services/MetricoolApiService.php';

use App\Core\Database;
use App\Repositories\PostRepository;
use App\Repositories\MetricsHistoryRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ClientRepository;
use App\Services\MetricoolApiService;

// Script start
$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] ===== Metricool Sync Started =====\n";

// Load configuration
$config = require __DIR__ . '/../../config/app.php';

// Initialize database
try {
    $db = new Database(
        $config['db']['host'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['name'],
        $config['db']['port']
    );
    echo "✓ Database connection established\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Initialize repositories
$settingsRepo = new SettingsRepository($db);
$clientRepo = new ClientRepository($db);
$postRepo = new PostRepository($db);
$metricsHistoryRepo = new MetricsHistoryRepository($db);

// Load Metricool API credentials from settings
try {
    $apiKeySetting = $settingsRepo->get('metricool_api_key');
    $userIdSetting = $settingsRepo->get('metricool_user_id');

    if (!$apiKeySetting || !$userIdSetting) {
        die("✗ Metricool credentials not found in settings table.\n" .
            "Please configure:\n" .
            "  - metricool_api_key\n" .
            "  - metricool_user_id\n");
    }

    $apiKey = $apiKeySetting['value'];
    $userId = $userIdSetting['value'];

    echo "✓ Metricool credentials loaded\n";
} catch (Exception $e) {
    die("✗ Failed to load credentials: " . $e->getMessage() . "\n");
}

// Initialize Metricool service
$metricoolService = new MetricoolApiService($apiKey, $userId, $postRepo, $metricsHistoryRepo);

// Test API connection
echo "→ Testing Metricool API connection...\n";
if (!$metricoolService->testConnection()) {
    die("✗ Metricool API connection test failed. Check credentials.\n");
}
echo "✓ Metricool API connection successful\n\n";

// Fetch all active clients
try {
    $clients = $clientRepo->findAll();
    echo "→ Found " . count($clients) . " client(s) to sync\n\n";
} catch (Exception $e) {
    die("✗ Failed to fetch clients: " . $e->getMessage() . "\n");
}

// Global stats
$totalStats = [
    'clients_synced' => 0,
    'clients_failed' => 0,
    'posts_created' => 0,
    'posts_updated' => 0,
    'snapshots_created' => 0,
    'total_errors' => 0,
];

// Date range for sync (last 30 days to catch updates)
$endDate = date('Ymd');
$startDate = date('Ymd', strtotime('-30 days'));

// Sync each client
foreach ($clients as $client) {
    $clientId = $client['id'];
    $clientName = $client['client_name'] ?? "Client #{$clientId}";

    echo "-------------------------------------------\n";
    echo "Syncing: {$clientName} (ID: {$clientId})\n";
    echo "-------------------------------------------\n";

    try {
        // Get client's Metricool blogId from settings or client table
        // For MVP: Store blogId in settings as "metricool_blog_id_{$clientId}"
        $blogIdSetting = $settingsRepo->get("metricool_blog_id_{$clientId}");

        if (!$blogIdSetting) {
            echo "⚠ No Metricool blogId configured for {$clientName}. Skipping.\n\n";
            continue;
        }

        $blogId = $blogIdSetting['value'];
        echo "→ Using blogId: {$blogId}\n";

        // Platforms to sync (adjust based on client's connected platforms)
        $platforms = ['instagram', 'facebook', 'instagram_reels'];

        $clientStats = [
            'created' => 0,
            'updated' => 0,
            'snapshots_created' => 0,
            'errors' => 0,
        ];

        foreach ($platforms as $platform) {
            echo "  → Syncing {$platform}...\n";

            try {
                $syncResult = $metricoolService->syncPosts($clientId, $blogId, $platform, [
                    'start' => $startDate,
                    'end' => $endDate,
                ]);

                $clientStats['created'] += $syncResult['created'];
                $clientStats['updated'] += $syncResult['updated'];
                $clientStats['snapshots_created'] += $syncResult['snapshots_created'];
                $clientStats['errors'] += count($syncResult['errors']);

                echo "    ✓ Created: {$syncResult['created']}, Updated: {$syncResult['updated']}, Snapshots: {$syncResult['snapshots_created']}\n";

                if (!empty($syncResult['errors'])) {
                    echo "    ⚠ Errors: " . count($syncResult['errors']) . "\n";
                    foreach ($syncResult['errors'] as $error) {
                        echo "      - Post {$error['post_id']}: {$error['error']}\n";
                    }
                }

                // Rate limiting: sleep 1 second between platform requests
                sleep(1);
            } catch (Exception $e) {
                echo "    ✗ Failed to sync {$platform}: " . $e->getMessage() . "\n";
                $clientStats['errors']++;
            }
        }

        // Update global stats
        $totalStats['clients_synced']++;
        $totalStats['posts_created'] += $clientStats['created'];
        $totalStats['posts_updated'] += $clientStats['updated'];
        $totalStats['snapshots_created'] += $clientStats['snapshots_created'];
        $totalStats['total_errors'] += $clientStats['errors'];

        echo "  Summary: +{$clientStats['created']} new, ~{$clientStats['updated']} updated, {$clientStats['snapshots_created']} snapshots";
        if ($clientStats['errors'] > 0) {
            echo ", {$clientStats['errors']} errors";
        }
        echo "\n\n";

    } catch (Exception $e) {
        echo "✗ Client sync failed: " . $e->getMessage() . "\n\n";
        $totalStats['clients_failed']++;
    }
}

// Final summary
$duration = round(microtime(true) - $startTime, 2);

echo "=============================================\n";
echo "SYNC COMPLETED\n";
echo "=============================================\n";
echo "Duration: {$duration}s\n";
echo "Clients synced: {$totalStats['clients_synced']}\n";
echo "Clients failed: {$totalStats['clients_failed']}\n";
echo "Posts created: {$totalStats['posts_created']}\n";
echo "Posts updated: {$totalStats['posts_updated']}\n";
echo "Snapshots created: {$totalStats['snapshots_created']}\n";
echo "Total errors: {$totalStats['total_errors']}\n";
echo "=============================================\n";
echo "[" . date('Y-m-d H:i:s') . "] ===== Metricool Sync Finished =====\n";

// Exit with error code if there were failures
exit($totalStats['clients_failed'] > 0 ? 1 : 0);
