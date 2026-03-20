<?php
/**
 * Metricool API Test Script
 *
 * Interactive test script to verify Metricool API integration.
 * Tests API connectivity, fetches connected profiles, and demonstrates post retrieval.
 *
 * Usage:
 *   php scripts/test_metricool_api.php
 *
 * What it does:
 * 1. Loads Metricool credentials from settings table
 * 2. Tests API connection
 * 3. Lists connected social media profiles
 * 4. Fetches sample posts from first available profile
 * 5. Shows data mapping to SocialBit schema
 *
 * Prerequisites:
 * - Metricool API key stored in settings table (key: 'metricool_api_key')
 * - Metricool user ID stored in settings table (key: 'metricool_user_id')
 * - Active Metricool Advanced plan
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/PostRepository.php';
require_once __DIR__ . '/../src/Repositories/MetricsHistoryRepository.php';
require_once __DIR__ . '/../src/Repositories/SettingsRepository.php';
require_once __DIR__ . '/../src/Services/MetricoolApiService.php';

use App\Core\Database;
use App\Repositories\PostRepository;
use App\Repositories\MetricsHistoryRepository;
use App\Repositories\SettingsRepository;
use App\Services\MetricoolApiService;

echo "==========================================\n";
echo "    METRICOOL API INTEGRATION TEST\n";
echo "==========================================\n\n";

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Initialize database
try {
    $db = new Database($config['db']);
    echo "✓ Database connection established\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Initialize repositories
$settingsRepo = new SettingsRepository($db);
$postRepo = new PostRepository($db);
$metricsHistoryRepo = new MetricsHistoryRepository($db);

// Load Metricool credentials
echo "\n→ Loading Metricool credentials...\n";

try {
    $apiKeySetting = $settingsRepo->get('metricool_api_key');
    $userIdSetting = $settingsRepo->get('metricool_user_id');

    if (!$apiKeySetting || !$userIdSetting) {
        echo "\n✗ Metricool credentials not found in settings table.\n\n";
        echo "Please store your credentials using these SQL commands:\n\n";
        echo "INSERT INTO settings (`key`, `value`, updated_at) VALUES\n";
        echo "  ('metricool_api_key', 'YOUR_API_KEY_HERE', NOW()),\n";
        echo "  ('metricool_user_id', 'YOUR_USER_ID_HERE', NOW());\n\n";
        echo "Get your API key from: https://app.metricool.com/account/settings/api\n";
        exit(1);
    }

    $apiKey = $apiKeySetting['value'];
    $userId = $userIdSetting['value'];

    echo "✓ API Key: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "\n";
    echo "✓ User ID: {$userId}\n";
} catch (Exception $e) {
    die("✗ Failed to load credentials: " . $e->getMessage() . "\n");
}

// Initialize Metricool service
$metricoolService = new MetricoolApiService($apiKey, $userId, $postRepo, $metricsHistoryRepo);

// Test 1: API Connection
echo "\n==========================================\n";
echo "TEST 1: API Connection\n";
echo "==========================================\n";

try {
    if ($metricoolService->testConnection()) {
        echo "✓ API connection successful!\n";
    } else {
        echo "✗ API connection failed - invalid credentials\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Connection test error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Fetch Connected Profiles
echo "\n==========================================\n";
echo "TEST 2: Connected Profiles\n";
echo "==========================================\n";

try {
    $profiles = $metricoolService->getConnectedProfiles();

    if (empty($profiles)) {
        echo "⚠ No connected profiles found.\n";
        echo "Please connect at least one social media account in Metricool.\n";
        exit(0);
    }

    echo "✓ Found " . count($profiles) . " connected profile(s):\n\n";

    foreach ($profiles as $i => $profile) {
        echo ($i + 1) . ". ";
        echo isset($profile['network']) ? strtoupper($profile['network']) : 'Unknown Platform';
        echo " - ";
        echo $profile['username'] ?? $profile['name'] ?? 'N/A';
        echo " (Blog ID: " . ($profile['id'] ?? 'N/A') . ")\n";
    }

    // Store first profile for testing
    $testProfile = $profiles[0];
    $testBlogId = $testProfile['id'] ?? null;
    $testPlatform = strtolower($testProfile['network'] ?? 'instagram');

} catch (Exception $e) {
    echo "✗ Failed to fetch profiles: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Fetch Posts
echo "\n==========================================\n";
echo "TEST 3: Fetch Posts (Last 7 Days)\n";
echo "==========================================\n";

if (!$testBlogId) {
    echo "⚠ No blogId available for testing\n";
    exit(0);
}

echo "→ Testing with Blog ID: {$testBlogId}\n";
echo "→ Platform: {$testPlatform}\n";

// Date range: last 7 days
$endDate = date('Ymd');
$startDate = date('Ymd', strtotime('-7 days'));

try {
    $posts = $metricoolService->getPosts($testBlogId, $testPlatform, [
        'start' => $startDate,
        'end' => $endDate,
    ]);

    if (empty($posts)) {
        echo "⚠ No posts found in the last 7 days\n";
        echo "This is normal if you haven't posted recently.\n";
    } else {
        echo "✓ Found " . count($posts) . " post(s):\n\n";

        // Show first 3 posts as samples
        $samplePosts = array_slice($posts, 0, 3);

        foreach ($samplePosts as $i => $post) {
            echo "--- Post " . ($i + 1) . " ---\n";
            echo "ID: " . ($post['id'] ?? 'N/A') . "\n";
            echo "Date: " . ($post['published_at'] ?? $post['date'] ?? 'N/A') . "\n";
            echo "Text: " . substr($post['text'] ?? $post['caption'] ?? 'N/A', 0, 60) . "...\n";
            echo "Likes: " . ($post['likes'] ?? 0) . " | ";
            echo "Comments: " . ($post['comments'] ?? 0) . " | ";
            echo "Shares: " . ($post['shares'] ?? 0) . "\n";
            echo "Views/Impressions: " . ($post['impressions'] ?? $post['views'] ?? 0) . "\n\n";
        }

        if (count($posts) > 3) {
            echo "... and " . (count($posts) - 3) . " more post(s)\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Failed to fetch posts: " . $e->getMessage() . "\n";
    echo "\nNote: Some platforms may not be supported yet.\n";
    echo "Supported platforms: instagram, instagram_reels, facebook, linkedin\n";
}

// Test 4: Test Sync (Dry Run)
echo "\n==========================================\n";
echo "TEST 4: Data Mapping Test\n";
echo "==========================================\n";

if (!empty($posts) && isset($posts[0])) {
    echo "→ Testing data mapping with first post...\n\n";

    try {
        // Use reflection to access private method for testing
        $reflection = new ReflectionClass($metricoolService);
        $mapMethod = $reflection->getMethod('mapMetricoolPost');
        $mapMethod->setAccessible(true);

        $mappedPost = $mapMethod->invoke($metricoolService, $posts[0], $testPlatform, 1);

        echo "Mapped SocialBit Schema:\n";
        echo "  platform: " . ($mappedPost['platform'] ?? 'N/A') . "\n";
        echo "  platform_post_id: " . ($mappedPost['platform_post_id'] ?? 'N/A') . "\n";
        echo "  posted_date: " . ($mappedPost['posted_date'] ?? 'N/A') . "\n";
        echo "  caption: " . substr($mappedPost['caption'] ?? 'N/A', 0, 60) . "...\n";
        echo "  views: " . ($mappedPost['views'] ?? 0) . "\n";
        echo "  likes: " . ($mappedPost['likes'] ?? 0) . "\n";
        echo "  comments: " . ($mappedPost['comments'] ?? 0) . "\n";
        echo "  shares: " . ($mappedPost['shares'] ?? 0) . "\n";
        echo "  engagement_rate: " . ($mappedPost['engagement_rate'] ?? 0) . "\n";
        echo "  hashtags: " . ($mappedPost['hashtags'] ?? 'N/A') . "\n";
        echo "\n✓ Data mapping successful!\n";
    } catch (Exception $e) {
        echo "⚠ Mapping test skipped: " . $e->getMessage() . "\n";
    }
}

// Test 5: Demographics (if available)
echo "\n==========================================\n";
echo "TEST 5: Demographics (Optional)\n";
echo "==========================================\n";

try {
    $demographics = $metricoolService->getDemographics($testBlogId, $testPlatform, 'country');

    if (!empty($demographics)) {
        echo "✓ Demographics data available:\n";
        $topCountries = array_slice($demographics, 0, 5, true);
        foreach ($topCountries as $country => $count) {
            echo "  {$country}: {$count}\n";
        }
    } else {
        echo "⚠ No demographics data available\n";
        echo "(This is normal for some platforms or accounts)\n";
    }
} catch (Exception $e) {
    echo "⚠ Demographics test skipped: " . $e->getMessage() . "\n";
}

// Final Summary
echo "\n==========================================\n";
echo "TEST SUMMARY\n";
echo "==========================================\n";
echo "✓ API Connection: SUCCESS\n";
echo "✓ Profiles Fetched: " . count($profiles ?? []) . "\n";
echo "✓ Posts Retrieved: " . count($posts ?? []) . "\n";
echo "\nNext Steps:\n";
echo "1. Store blogId in settings for each client:\n";
echo "   INSERT INTO settings (`key`, `value`) VALUES ('metricool_blog_id_1', '{$testBlogId}');\n\n";
echo "2. Run scheduled sync:\n";
echo "   php scripts/scheduled/metricool_sync.php\n\n";
echo "3. Setup cron job for daily sync:\n";
echo "   0 4 * * * /usr/bin/php " . realpath(__DIR__) . "/scheduled/metricool_sync.php\n\n";
echo "==========================================\n";
