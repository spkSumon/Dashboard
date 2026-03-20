<?php
/**
 * Metricool API - Fetch TikTok Posts Test
 *
 * Test script to fetch posts from Metricool with extended date range
 * and better platform detection.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
echo "  METRICOOL API - FETCH POSTS TEST\n";
echo "==========================================\n\n";

$config = require __DIR__ . '/../config/app.php';
$db = new Database($config['db']);
$settingsRepo = new SettingsRepository($db);
$postRepo = new PostRepository($db);
$metricsHistoryRepo = new MetricsHistoryRepository($db);

// Load credentials
$apiKey = $settingsRepo->get('metricool_api_key')['value'];
$userId = $settingsRepo->get('metricool_user_id')['value'];
$blogId = '5668624'; // Giuditta's blog ID

$metricoolService = new MetricoolApiService($apiKey, $userId, $postRepo, $metricsHistoryRepo);

// Step 1: Get connected profiles with full details
echo "Step 1: Fetching connected profiles...\n";
$profiles = $metricoolService->getConnectedProfiles();

echo "Found " . count($profiles) . " profile(s):\n";
foreach ($profiles as $profile) {
    echo "\nProfile Details:\n";
    echo json_encode($profile, JSON_PRETTY_PRINT) . "\n";
}

// Step 2: Try different platform endpoints for TikTok
$platforms = ['tiktok', 'instagram', 'facebook', 'linkedin'];
$endDate = date('Ymd');
$startDate = date('Ymd', strtotime('-90 days')); // 90 days as per task

echo "\n\nStep 2: Testing different platform endpoints...\n";
echo "Date range: {$startDate} to {$endDate}\n\n";

foreach ($platforms as $platform) {
    echo "--- Testing {$platform} ---\n";

    try {
        $posts = $metricoolService->getPosts($blogId, $platform, [
            'start' => $startDate,
            'end' => $endDate,
        ]);

        echo "✓ Found " . count($posts) . " posts\n";

        if (!empty($posts)) {
            echo "Sample post data:\n";
            $sample = $posts[0];
            echo "  ID: " . ($sample['id'] ?? 'N/A') . "\n";
            echo "  Date: " . ($sample['published_at'] ?? $sample['date'] ?? 'N/A') . "\n";
            echo "  Text: " . substr($sample['text'] ?? $sample['caption'] ?? 'N/A', 0, 50) . "...\n";
            echo "  Likes: " . ($sample['likes'] ?? 0) . "\n";
            echo "  Comments: " . ($sample['comments'] ?? 0) . "\n";
            echo "  Shares: " . ($sample['shares'] ?? 0) . "\n";
            echo "  Views: " . ($sample['views'] ?? $sample['impressions'] ?? 0) . "\n";

            echo "\n  Full post structure:\n";
            echo json_encode($sample, JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";

    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
}

// Step 3: Test raw API call to /admin/simpleProfiles
echo "\nStep 3: Raw API test for profile details...\n";
try {
    $url = "https://app.metricool.com/api/admin/simpleProfiles?userId={$userId}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Mc-Auth: ' . $apiKey,
        'Accept: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: {$httpCode}\n";
    echo "Raw Response:\n";
    echo $response . "\n\n";

    $data = json_decode($response, true);
    if ($data) {
        echo "Parsed Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n==========================================\n";
echo "TEST COMPLETE\n";
echo "==========================================\n";
