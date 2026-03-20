#!/usr/bin/env php
<?php
/**
 * Metricool Daily Sync - Scheduled job for automated data collection.
 *
 * This script runs daily (recommended: 4 AM local time per tenant) to fetch
 * posts and metrics from all connected platforms via Metricool API.
 *
 * Execution:
 * - Manual: php scripts/scheduled/metricool_daily_sync.php
 * - Cron: 0 4 * * * /usr/bin/php /path/to/scripts/scheduled/metricool_daily_sync.php
 *
 * Features:
 * - Fetches posts from all connected Metricool profiles
 * - Multi-tenant support (loops through all clients)
 * - Conflict resolution (CSV baseline vs API updates)
 * - Error handling and logging
 * - Summary report at the end
 *
 * Database requirements:
 * - settings table with metricool_api_key and metricool_user_id
 * - clients table with active tenants
 * - posts and metrics_history tables
 *
 * @package SocialBit\Scripts
 */

// Change to project root directory
chdir(__DIR__ . '/../..');

// Load configuration
$config = require __DIR__ . '/../../config/app.php';

// Autoload classes (manual autoloader since no Composer)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Repositories\SettingsRepository;
use App\Repositories\PostRepository;
use App\Repositories\MetricsHistoryRepository;
use App\Repositories\ClientRepository;
use App\Services\MetricoolApiService;

// Script start time
$startTime = microtime(true);
$scriptDate = date('Y-m-d H:i:s');

echo "=== Metricool Daily Sync ===\n";
echo "Started at: {$scriptDate}\n";
echo "Environment: {$config['environment']}\n\n";

try {
    // Initialize database connection
    $db = new Database($config['db']);
    echo "✓ Database connected\n";

    // Initialize repositories
    $settingsRepo = new SettingsRepository($db);
    $postRepo = new PostRepository($db);
    $metricsRepo = new MetricsHistoryRepository($db);
    $clientRepo = new ClientRepository($db);

    // Fetch Metricool credentials from settings
    $apiKeySetting = $settingsRepo->get('metricool_api_key');
    $userIdSetting = $settingsRepo->get('metricool_user_id');

    if (!$apiKeySetting || !$userIdSetting) {
        throw new Exception('Metricool credentials not configured in settings table');
    }

    $apiKey = $apiKeySetting['value'];
    $userId = $userIdSetting['value'];

    echo "✓ Metricool credentials loaded\n";

    // Initialize Metricool service
    $metricoolService = new MetricoolApiService($apiKey, $userId, $postRepo, $metricsRepo);

    // Test connection
    if (!$metricoolService->testConnection()) {
        throw new Exception('Metricool API connection test failed');
    }

    echo "✓ Metricool API connected\n\n";

    // Fetch connected profiles
    $profiles = $metricoolService->getConnectedProfiles();
    echo "Found " . count($profiles) . " connected profiles:\n";
    foreach ($profiles as $profile) {
        $network = $profile['network'] ?? 'unknown';
        $username = $profile['username'] ?? $profile['name'] ?? 'unknown';
        $blogId = $profile['id'] ?? 'unknown';
        echo "  - {$network}: {$username} (Blog ID: {$blogId})\n";
    }
    echo "\n";

    // Fetch all active clients
    // For POC: Use a hardcoded client_id = 1 (first client)
    // TODO: Implement multi-tenant loop when client management is ready
    $clients = [['id' => 1, 'name' => 'Default Client']];
    echo "Found " . count($clients) . " active clients\n\n";

    // Global statistics
    $totalStats = [
        'created' => 0,
        'updated' => 0,
        'snapshots_created' => 0,
        'errors' => 0,
        'clients_processed' => 0,
        'profiles_synced' => 0
    ];

    // Date range: last 30 days to today
    $endDate = date('Ymd'); // Today
    $startDate = date('Ymd', strtotime('-30 days')); // 30 days ago

    // Loop through each client (multi-tenant support)
    foreach ($clients as $client) {
        $clientId = $client['id'];
        $clientName = $client['name'] ?? "Client #{$clientId}";

        echo "--- Processing {$clientName} (ID: {$clientId}) ---\n";

        // Get client-specific Metricool blog IDs from settings or use all profiles
        // For POC: sync all profiles for all clients
        // TODO: Add client-specific profile mapping in future

        foreach ($profiles as $profile) {
            $blogId = (string)$profile['id'];
            $platform = $profile['network'];
            $username = $profile['username'];

            echo "  Syncing {$platform} ({$username})... ";

            try {
                $stats = $metricoolService->syncPosts($clientId, $blogId, $platform, [
                    'start' => $startDate,
                    'end' => $endDate
                ]);

                echo "✓ Created: {$stats['created']}, Updated: {$stats['updated']}, Snapshots: {$stats['snapshots_created']}\n";

                // Aggregate stats
                $totalStats['created'] += $stats['created'];
                $totalStats['updated'] += $stats['updated'];
                $totalStats['snapshots_created'] += $stats['snapshots_created'];
                $totalStats['errors'] += count($stats['errors']);
                $totalStats['profiles_synced']++;

                // Log individual errors
                if (!empty($stats['errors'])) {
                    foreach ($stats['errors'] as $error) {
                        echo "    ⚠ Error on post {$error['post_id']}: {$error['error']}\n";
                    }
                }
            } catch (Exception $e) {
                echo "✗ Failed: {$e->getMessage()}\n";
                $totalStats['errors']++;
            }
        }

        $totalStats['clients_processed']++;
        echo "\n";
    }

    // Summary
    $duration = round(microtime(true) - $startTime, 2);
    echo "=== Sync Complete ===\n";
    echo "Clients processed: {$totalStats['clients_processed']}\n";
    echo "Profiles synced: {$totalStats['profiles_synced']}\n";
    echo "Posts created: {$totalStats['created']}\n";
    echo "Posts updated: {$totalStats['updated']}\n";
    echo "Snapshots created: {$totalStats['snapshots_created']}\n";
    echo "Errors: {$totalStats['errors']}\n";
    echo "Duration: {$duration} seconds\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";

    // Exit with appropriate code
    exit($totalStats['errors'] > 0 ? 1 : 0);

} catch (Exception $e) {
    $duration = round(microtime(true) - $startTime, 2);
    echo "\n✗ FATAL ERROR: {$e->getMessage()}\n";
    echo "Duration: {$duration} seconds\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
    exit(1);
}
