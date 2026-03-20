<?php
/**
 * Metricool API - Fetch All TikTok Posts
 *
 * Comprehensive fetch of all TikTok posts from last 90 days with detailed metrics
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
echo "  FETCH ALL TIKTOK POSTS FROM METRICOOL\n";
echo "==========================================\n\n";

// Date range: last 90 days (matching CSV data: 2025-08-19 to 2026-02-05)
$endDate = date('Y-m-d\TH:i:s');
$startDate = date('Y-m-d\TH:i:s', strtotime('-90 days'));
$timezone = 'Europe/Brussels';

$url = "https://app.metricool.com/api/v2/analytics/posts/tiktok?userId={$userId}&blogId={$blogId}&from={$startDate}&to={$endDate}&timezone=" . urlencode($timezone);

echo "Fetching TikTok posts...\n";
echo "Date range: {$startDate} to {$endDate}\n";
echo "Timezone: {$timezone}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Mc-Auth: ' . $apiKey,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("✗ API request failed with HTTP {$httpCode}\nResponse: {$response}\n");
}

$data = json_decode($response, true);
if (!$data || !isset($data['data'])) {
    die("✗ Invalid response structure\n");
}

$posts = $data['data'];
echo "✓ Successfully fetched " . count($posts) . " posts\n\n";

// Calculate aggregate statistics
$stats = [
    'total_posts' => count($posts),
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'total_shares' => 0,
    'total_reach' => 0,
    'total_watch_time' => 0,
    'avg_engagement_rate' => 0,
    'avg_completion_rate' => 0,
    'avg_watch_time' => 0,
];

$engagementRates = [];
$completionRates = [];

foreach ($posts as $post) {
    $stats['total_views'] += $post['viewCount'] ?? 0;
    $stats['total_likes'] += $post['likeCount'] ?? 0;
    $stats['total_comments'] += $post['commentCount'] ?? 0;
    $stats['total_shares'] += $post['shareCount'] ?? 0;
    $stats['total_reach'] += $post['reach'] ?? 0;
    $stats['total_watch_time'] += $post['totalTimeWatched'] ?? 0;

    if (isset($post['engagement'])) {
        $engagementRates[] = $post['engagement'];
    }

    if (isset($post['fullVideoWatchedRate'])) {
        $completionRates[] = $post['fullVideoWatchedRate'];
    }
}

if (!empty($engagementRates)) {
    $stats['avg_engagement_rate'] = array_sum($engagementRates) / count($engagementRates);
}

if (!empty($completionRates)) {
    $stats['avg_completion_rate'] = array_sum($completionRates) / count($completionRates);
}

if ($stats['total_views'] > 0) {
    $stats['avg_watch_time'] = $stats['total_watch_time'] / $stats['total_views'];
}

// Display summary
echo "==========================================\n";
echo "AGGREGATE STATISTICS\n";
echo "==========================================\n";
echo "Total Posts: " . $stats['total_posts'] . "\n";
echo "Total Views: " . number_format($stats['total_views']) . "\n";
echo "Total Likes: " . number_format($stats['total_likes']) . "\n";
echo "Total Comments: " . number_format($stats['total_comments']) . "\n";
echo "Total Shares: " . number_format($stats['total_shares']) . "\n";
echo "Total Reach: " . number_format($stats['total_reach']) . "\n";
echo "Total Watch Time: " . number_format($stats['total_watch_time']) . " seconds\n";
echo "Avg Engagement Rate: " . number_format($stats['avg_engagement_rate'], 2) . "%\n";
echo "Avg Completion Rate: " . number_format($stats['avg_completion_rate'] * 100, 2) . "%\n";
echo "Avg Watch Time: " . number_format($stats['avg_watch_time'], 2) . " seconds\n\n";

// Date range analysis
$dates = array_map(function($post) {
    return substr($post['createTime'], 0, 10);
}, $posts);

$earliestDate = min($dates);
$latestDate = max($dates);

echo "Date Range: {$earliestDate} to {$latestDate}\n";
echo "Posts per week: " . number_format(count($posts) / 13, 1) . " (over ~13 weeks)\n\n";

// Top 5 performing posts
echo "==========================================\n";
echo "TOP 5 PERFORMING POSTS (By Views)\n";
echo "==========================================\n\n";

usort($posts, function($a, $b) {
    return ($b['viewCount'] ?? 0) <=> ($a['viewCount'] ?? 0);
});

$topPosts = array_slice($posts, 0, 5);

foreach ($topPosts as $i => $post) {
    $date = substr($post['createTime'], 0, 10);
    $caption = substr($post['videoDescription'], 0, 60);
    echo ($i + 1) . ". Video ID: {$post['videoId']}\n";
    echo "   Date: {$date}\n";
    echo "   Caption: {$caption}...\n";
    echo "   Views: " . number_format($post['viewCount']) . "\n";
    echo "   Likes: " . number_format($post['likeCount']) . "\n";
    echo "   Comments: " . number_format($post['commentCount']) . "\n";
    echo "   Shares: " . number_format($post['shareCount']) . "\n";
    echo "   Engagement: " . number_format($post['engagement'] ?? 0, 2) . "%\n";
    echo "   Completion Rate: " . number_format(($post['fullVideoWatchedRate'] ?? 0) * 100, 2) . "%\n";
    echo "   Avg Watch Time: " . number_format($post['averageTimeWatched'] ?? 0, 2) . "s\n\n";
}

// Compare with CSV data
echo "==========================================\n";
echo "CSV COMPARISON CHECK\n";
echo "==========================================\n\n";

$csvVideoIds = [
    '7603411713987284256', // Most recent from CSV
    '7601927011422063905',
    '7600815079918947616',
];

$apiVideoIds = array_column($posts, 'videoId');

echo "CSV Video IDs sample: " . implode(', ', $csvVideoIds) . "\n";
echo "Found in API: ";
$foundCount = 0;
foreach ($csvVideoIds as $csvId) {
    if (in_array($csvId, $apiVideoIds)) {
        echo "✓ {$csvId} ";
        $foundCount++;
    } else {
        echo "✗ {$csvId} ";
    }
}
echo "\n";
echo "Match rate: " . ($foundCount / count($csvVideoIds) * 100) . "%\n\n";

// Available metrics
echo "==========================================\n";
echo "AVAILABLE METRICS IN API\n";
echo "==========================================\n";
$samplePost = $posts[0];
echo "Standard metrics:\n";
echo "  - videoId (string)\n";
echo "  - createTime (datetime)\n";
echo "  - videoDescription (string)\n";
echo "  - duration (int) - video length in seconds\n";
echo "  - coverImageUrl (string)\n";
echo "  - shareUrl (string)\n\n";

echo "Engagement metrics:\n";
echo "  - viewCount (int)\n";
echo "  - likeCount (int)\n";
echo "  - commentCount (int)\n";
echo "  - shareCount (int)\n";
echo "  - reach (int) - unique viewers\n";
echo "  - engagement (float) - engagement rate %\n\n";

echo "2026 Algorithm Metrics (CRITICAL):\n";
echo "  - fullVideoWatchedRate (float) - completion rate\n";
echo "  - totalTimeWatched (int) - total watch time in seconds\n";
echo "  - averageTimeWatched (float) - avg watch time per view\n";
echo "  - impressionSources (object) - traffic sources breakdown\n";
echo "    * forYou, follow, hashtag, sound, personalProfile, search\n\n";

echo "Missing metrics (not in API):\n";
echo "  - saves_count (DM shares)\n";
echo "  - sends_count (DM shares)\n";
echo "  - profile_visits\n";
echo "  - skip_rate (Instagram Reels only)\n\n";

// Save full data to JSON file
$outputFile = __DIR__ . '/metricool_tiktok_posts_full.json';
file_put_contents($outputFile, json_encode([
    'metadata' => [
        'fetched_at' => date('Y-m-d H:i:s'),
        'date_range' => [
            'from' => $startDate,
            'to' => $endDate,
        ],
        'total_posts' => count($posts),
        'blog_id' => $blogId,
    ],
    'statistics' => $stats,
    'posts' => $posts,
], JSON_PRETTY_PRINT));

echo "✓ Full data saved to: {$outputFile}\n\n";

echo "==========================================\n";
echo "TASK #3 COMPLETE\n";
echo "==========================================\n";
