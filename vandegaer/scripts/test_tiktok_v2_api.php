<?php
/**
 * Metricool API - TikTok v2 Endpoint Test
 *
 * Test the v2 TikTok posts endpoint with proper parameters
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/SettingsRepository.php';

use App\Core\Database;
use App\Repositories\SettingsRepository;

$config = require __DIR__ . '/../config/app.php';
$db = new Database($config['db']);
$settingsRepo = new SettingsRepository($db);

$apiKey = $settingsRepo->get('metricool_api_key')['value'];
$userId = $settingsRepo->get('metricool_user_id')['value'];
$blogId = '5668624';

echo "==========================================\n";
echo "  TIKTOK V2 API TEST\n";
echo "==========================================\n\n";

// Use ISO datetime format (YYYY-MM-DD'T'HH:mm:ss) for v2 API
$endDate = date('Y-m-d\TH:i:s');
$startDate = date('Y-m-d\TH:i:s', strtotime('-90 days'));
$timezone = 'Europe/Brussels'; // From profile data

echo "Date range: {$startDate} to {$endDate}\n";
echo "Timezone: {$timezone}\n";
echo "Blog ID: {$blogId}\n\n";

// Test v2 TikTok posts endpoint
$url = "https://app.metricool.com/api/v2/analytics/posts/tiktok?userId={$userId}&blogId={$blogId}&from={$startDate}&to={$endDate}&timezone=" . urlencode($timezone);

echo "Testing: /v2/analytics/posts/tiktok\n";
echo "URL: {$url}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Mc-Auth: ' . $apiKey,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
}

if ($httpCode === 200) {
    $data = json_decode($response, true);

    if ($data) {
        echo "✓ SUCCESS!\n\n";

        // Check response structure
        if (isset($data['data']) && is_array($data['data'])) {
            $posts = $data['data'];
            echo "Posts found: " . count($posts) . "\n\n";

            if (!empty($posts)) {
                // Show summary statistics
                echo "=== SUMMARY ===\n";
                $totalViews = 0;
                $totalLikes = 0;
                $totalComments = 0;
                $totalShares = 0;

                foreach ($posts as $post) {
                    $totalViews += ($post['views'] ?? $post['impressions'] ?? 0);
                    $totalLikes += ($post['likes'] ?? 0);
                    $totalComments += ($post['comments'] ?? 0);
                    $totalShares += ($post['shares'] ?? 0);
                }

                echo "Total Views: " . number_format($totalViews) . "\n";
                echo "Total Likes: " . number_format($totalLikes) . "\n";
                echo "Total Comments: " . number_format($totalComments) . "\n";
                echo "Total Shares: " . number_format($totalShares) . "\n\n";

                // Show first 3 posts in detail
                echo "=== SAMPLE POSTS ===\n\n";
                $samples = array_slice($posts, 0, 3);

                foreach ($samples as $i => $post) {
                    echo "--- Post " . ($i + 1) . " ---\n";
                    echo json_encode($post, JSON_PRETTY_PRINT) . "\n\n";
                }

                if (count($posts) > 3) {
                    echo "... and " . (count($posts) - 3) . " more posts\n\n";
                }

                // Show all available fields
                echo "=== AVAILABLE FIELDS ===\n";
                $allFields = [];
                foreach ($posts as $post) {
                    $allFields = array_merge($allFields, array_keys($post));
                }
                $allFields = array_unique($allFields);
                sort($allFields);
                echo implode(', ', $allFields) . "\n\n";

            } else {
                echo "No posts found in the date range.\n\n";
            }

        } else {
            echo "Unexpected response structure:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

    } else {
        echo "Failed to parse JSON response\n";
        echo "Raw response:\n";
        echo $response . "\n";
    }

} else {
    echo "✗ Request failed\n";
    echo "Response:\n";
    echo $response . "\n";
}

echo "\n==========================================\n";
