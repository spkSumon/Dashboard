<?php
/**
 * Metricool API CLI Test Script
 * Can be run via CLI or browser
 *
 * CLI: php scripts/test_metricool_cli.php
 * Browser: http://localhost/socialbit-live/scripts/test_metricool_cli.php
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type based on execution context
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// Load dependencies
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/SettingsRepository.php';

use App\Core\Database;
use App\Repositories\SettingsRepository;

echo "==========================================\n";
echo "   METRICOOL API QUICK TEST\n";
echo "==========================================\n\n";

// Load configuration
$config = require __DIR__ . '/../config/app.php';

try {
    // Initialize database
    $db = new Database($config['db']);
    echo "✓ Database connected\n\n";

    // Initialize repository
    $settingsRepo = new SettingsRepository($db);

    // Load credentials
    echo "→ Loading Metricool credentials...\n";

    $apiKey = $settingsRepo->get('metricool_api_key');
    $userId = $settingsRepo->get('metricool_user_id');
    $blogId = $settingsRepo->get('metricool_blog_id_giuditta');

    if (!$apiKey || !$userId) {
        echo "❌ CREDENTIALS NOT FOUND\n\n";
        echo "Please run setup first:\n";
        echo "  http://localhost/socialbit-live/scripts/insert_metricool_credentials.php\n";
        exit(1);
    }

    echo "✓ API Key: " . substr($apiKey['value'], 0, 15) . "..." . substr($apiKey['value'], -10) . "\n";
    echo "✓ User ID: " . $userId['value'] . "\n";
    echo "✓ Blog ID (Giuditta): " . ($blogId['value'] ?? 'NOT SET') . "\n\n";

    // Test 1: API Connection
    echo "==========================================\n";
    echo "TEST 1: API Connection\n";
    echo "==========================================\n";

    $apiUrl = "https://app.metricool.com/api/admin/simpleProfiles?userId=" . $userId['value'];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Mc-Auth: ' . $apiKey['value'],
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    echo "→ Calling: GET /admin/simpleProfiles\n";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo "❌ cURL Error: " . $error . "\n";
        exit(1);
    }

    echo "→ HTTP Status: " . $httpCode . "\n";

    if ($httpCode !== 200) {
        echo "❌ API Error: HTTP " . $httpCode . "\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
        exit(1);
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Invalid JSON: " . json_last_error_msg() . "\n";
        exit(1);
    }

    echo "✓ API connection successful!\n\n";

    // Test 2: List Connected Profiles
    echo "==========================================\n";
    echo "TEST 2: Connected Profiles\n";
    echo "==========================================\n";

    // Metricool API returns data directly as array, not wrapped in 'data' key
    if (isset($data['data']) && is_array($data['data'])) {
        $profiles = $data['data'];
    } elseif (is_array($data)) {
        // Direct array response (simpleProfiles endpoint)
        $profiles = $data;
    } else {
        echo "❌ Invalid profile data returned\n";
        echo "Response: " . print_r($data, true) . "\n";
        exit(1);
    }
    echo "✓ Found " . count($profiles) . " connected profile(s):\n\n";

    $giudittaProfile = null;
    foreach ($profiles as $i => $profile) {
        // Detect platform from available fields
        $platforms = [];
        if (!empty($profile['instagram'])) $platforms[] = 'Instagram: ' . $profile['instagram'];
        if (!empty($profile['facebook'])) $platforms[] = 'Facebook: ' . $profile['facebook'];
        if (!empty($profile['tiktok'])) $platforms[] = 'TikTok: ' . $profile['tiktok'];
        if (!empty($profile['gmb'])) $platforms[] = 'Google My Business';

        $label = $profile['label'] ?? 'N/A';
        $profileId = $profile['id'] ?? 'N/A';

        echo ($i + 1) . ". {$label} (Blog ID: {$profileId})\n";
        foreach ($platforms as $platform) {
            echo "   - {$platform}\n";
        }

        if ($profileId == ($blogId['value'] ?? null)) {
            echo "   👉 THIS IS GIUDITTA!\n";
            $giudittaProfile = $profile;
        }
        echo "\n";
    }

    // Test 3: Fetch Giuditta Posts (if found)
    if ($giudittaProfile) {
        echo "==========================================\n";
        echo "TEST 3: Fetch Giuditta Posts\n";
        echo "==========================================\n";

        // Determine which platform to test (prefer Instagram if available)
        $platform = 'instagram'; // Default to Instagram
        if (!empty($giudittaProfile['instagram'])) {
            $platform = 'instagram';
        } elseif (!empty($giudittaProfile['facebook'])) {
            $platform = 'facebook';
        } elseif (!empty($giudittaProfile['tiktok'])) {
            $platform = 'tiktok';
        }

        $testBlogId = $giudittaProfile['id'];

        echo "→ Platform: {$platform}\n";
        echo "→ Blog ID: {$testBlogId}\n";

        // Map platform to endpoint
        $endpointMap = [
            'instagram' => '/stats/instagram/posts',
            'facebook' => '/stats/facebook/posts',
            'linkedin' => '/stats/linkedin/posts',
        ];

        if (!isset($endpointMap[$platform])) {
            echo "⚠️  Platform '{$platform}' endpoint not mapped yet\n";
            echo "Known platforms: " . implode(', ', array_keys($endpointMap)) . "\n";
        } else {
            $endpoint = $endpointMap[$platform];
            $startDate = date('Ymd', strtotime('-30 days'));
            $endDate = date('Ymd');

            $postsUrl = "https://app.metricool.com/api" . $endpoint
                      . "?userId=" . $userId['value']
                      . "&blogId=" . $testBlogId
                      . "&start=" . $startDate
                      . "&end=" . $endDate;

            echo "→ Calling: GET {$endpoint}\n";
            echo "→ Date range: {$startDate} - {$endDate} (last 30 days)\n";

            $ch = curl_init($postsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-Mc-Auth: ' . $apiKey['value'],
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $postsResponse = curl_exec($ch);
            $postsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($postsHttpCode === 200) {
                $postsData = json_decode($postsResponse, true);

                if (isset($postsData['data']) && is_array($postsData['data'])) {
                    $posts = $postsData['data'];
                    echo "✓ Found " . count($posts) . " post(s)\n\n";

                    if (count($posts) > 0) {
                        echo "--- First Post Sample ---\n";
                        $post = $posts[0];
                        echo "ID: " . ($post['id'] ?? 'N/A') . "\n";
                        echo "Date: " . ($post['published_at'] ?? $post['date'] ?? 'N/A') . "\n";
                        echo "Text: " . substr($post['text'] ?? $post['caption'] ?? 'N/A', 0, 80) . "...\n";
                        echo "Likes: " . ($post['likes'] ?? 0) . "\n";
                        echo "Comments: " . ($post['comments'] ?? 0) . "\n";
                        echo "Impressions: " . ($post['impressions'] ?? 0) . "\n";
                        echo "Reach: " . ($post['reach'] ?? 0) . "\n\n";

                        echo "Available fields: " . implode(', ', array_keys($post)) . "\n";
                    }
                } else {
                    echo "⚠️  No posts found in last 30 days\n";
                }
            } else {
                echo "❌ Posts API Error: HTTP " . $postsHttpCode . "\n";
                echo "Response: " . substr($postsResponse, 0, 500) . "\n";
            }
        }
    }

    // Summary
    echo "\n==========================================\n";
    echo "SUMMARY\n";
    echo "==========================================\n";
    echo "✓ Credentials: OK\n";
    echo "✓ API Connection: OK\n";
    echo "✓ Profiles Found: " . count($profiles) . "\n";
    echo "✓ Giuditta Profile: " . ($giudittaProfile ? "FOUND (Blog ID: {$giudittaProfile['id']})" : "NOT FOUND") . "\n";
    echo "\n✅ METRICOOL INTEGRATION READY!\n\n";

    echo "==========================================\n";
    echo "DATABASE CONTEXT (IMPORTANT)\n";
    echo "==========================================\n";
    echo "ℹ️  ALL database data = Giuditta data\n";
    echo "ℹ️  NO multi-tenant filtering needed\n";
    echo "ℹ️  client_id = 1 (or NULL) for all records\n\n";

    if ($giudittaProfile) {
        echo "Next steps:\n";
        echo "1. Run full sync test with MetricoolApiService\n";
        echo "2. Check which metrics are available vs needed\n";
        echo "3. Document any data gaps\n";
        echo "4. Compare Metricool data with existing CSV data\n";
        echo "   (All posts in DB are Giuditta - direct comparison)\n";
    }

} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
