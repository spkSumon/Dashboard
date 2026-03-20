<?php
/**
 * Detailed Post-by-Post Comparison: CSV vs Metricool API
 *
 * This script performs a comprehensive, granular comparison of each post
 * to identify metric differences, data quality issues, and temporal changes.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==========================================\n";
echo "  DETAILED POST-BY-POST COMPARISON\n";
echo "  CSV vs Metricool API Data\n";
echo "==========================================\n\n";

// Load API data
$apiDataFile = __DIR__ . '/metricool_tiktok_posts_full.json';
$apiData = json_decode(file_get_contents($apiDataFile), true);
$apiPosts = $apiData['posts'];

echo "✓ Loaded API data: " . count($apiPosts) . " posts\n";

// Load and parse CSV data
$csvFile = __DIR__ . '/../tiktok-posts_2025-02-06_2026-02-06.csv';
$csvPosts = [];
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle);

while (($row = fgetcsv($handle)) !== false) {
    preg_match('/\/video\/(\d+)/', $row[1], $matches);
    $videoId = $matches[1] ?? null;
    if (!$videoId) continue;

    $csvPosts[$videoId] = [
        'videoId' => $videoId,
        'url' => $row[1],
        'title' => $row[2],
        'date' => $row[3],
        'views' => (int)$row[6],
        'likes' => (int)$row[7],
        'comments' => (int)$row[8],
        'shares' => (int)$row[9],
        'reach' => $row[10] === '' || $row[10] === '0' ? null : (int)$row[10],
        'duration' => (int)$row[11],
        'engagement' => $row[12],
        'fullVideoWatchedRate' => (float)str_replace('"', '', $row[13]),
        'totalTimeWatched' => (int)$row[14],
        'avgTimeWatched' => (float)$row[15],
    ];
}
fclose($handle);

echo "✓ Loaded CSV data: " . count($csvPosts) . " posts\n\n";

// Index API posts by videoId
$apiPostsById = [];
foreach ($apiPosts as $post) {
    $apiPostsById[$post['videoId']] = $post;
}

// ==========================================
// STEP 1: IDENTIFY MATCHING VS MISSING POSTS
// ==========================================
echo "==========================================\n";
echo "STEP 1: POST MATCHING ANALYSIS\n";
echo "==========================================\n\n";

$matchingPosts = [];
$csvOnlyPosts = [];
$apiOnlyPosts = [];

foreach ($csvPosts as $videoId => $csvPost) {
    if (isset($apiPostsById[$videoId])) {
        $matchingPosts[$videoId] = [
            'csv' => $csvPost,
            'api' => $apiPostsById[$videoId],
        ];
    } else {
        $csvOnlyPosts[$videoId] = $csvPost;
    }
}

foreach ($apiPostsById as $videoId => $apiPost) {
    if (!isset($csvPosts[$videoId])) {
        $apiOnlyPosts[$videoId] = $apiPost;
    }
}

echo "Dataset Overview:\n";
echo "  Total CSV posts: " . count($csvPosts) . "\n";
echo "  Total API posts: " . count($apiPosts) . "\n";
echo "  Matching posts: " . count($matchingPosts) . "\n";
echo "  CSV-only posts: " . count($csvOnlyPosts) . "\n";
echo "  API-only posts: " . count($apiOnlyPosts) . "\n\n";

// ==========================================
// STEP 2: DETAILED POST-BY-POST COMPARISON
// ==========================================
echo "==========================================\n";
echo "STEP 2: POST-BY-POST METRIC COMPARISON\n";
echo "==========================================\n\n";

$comparisonResults = [];
$perfectMatches = 0;
$metricsGrowth = 0;
$metricsDecline = 0;

foreach ($matchingPosts as $videoId => $data) {
    $csv = $data['csv'];
    $api = $data['api'];

    // Calculate differences
    $viewsDiff = $api['viewCount'] - $csv['views'];
    $likesDiff = $api['likeCount'] - $csv['likes'];
    $commentsDiff = $api['commentCount'] - $csv['comments'];
    $sharesDiff = $api['shareCount'] - $csv['shares'];

    // Calculate engagement rates
    $csvEngagementRate = $csv['views'] > 0
        ? (($csv['likes'] + $csv['comments'] + $csv['shares']) / $csv['views'] * 100)
        : 0;

    $apiEngagementRate = isset($api['engagement'])
        ? $api['engagement']
        : ($api['viewCount'] > 0
            ? (($api['likeCount'] + $api['commentCount'] + $api['shareCount']) / $api['viewCount'] * 100)
            : 0);

    $engagementDiff = $apiEngagementRate - $csvEngagementRate;

    // Reach comparison
    $reachDiff = null;
    if ($csv['reach'] !== null && $api['reach'] > 0) {
        $reachDiff = $api['reach'] - $csv['reach'];
    }

    // Completion rate comparison
    $completionDiff = ($api['fullVideoWatchedRate'] ?? 0) - $csv['fullVideoWatchedRate'];

    // Watch time comparison
    $watchTimeDiff = ($api['totalTimeWatched'] ?? 0) - $csv['totalTimeWatched'];
    $avgWatchTimeDiff = ($api['averageTimeWatched'] ?? 0) - $csv['avgTimeWatched'];

    // Determine if metrics changed
    $hasDifference = ($viewsDiff != 0 || $likesDiff != 0 || $commentsDiff != 0 || $sharesDiff != 0);

    if (!$hasDifference) {
        $perfectMatches++;
    } else {
        if ($viewsDiff > 0 || $likesDiff > 0) {
            $metricsGrowth++;
        } else {
            $metricsDecline++;
        }
    }

    $comparisonResults[$videoId] = [
        'videoId' => $videoId,
        'date' => substr($api['createTime'], 0, 10),
        'title' => substr($api['title'], 0, 50),
        'csv' => [
            'views' => $csv['views'],
            'likes' => $csv['likes'],
            'comments' => $csv['comments'],
            'shares' => $csv['shares'],
            'reach' => $csv['reach'],
            'engagement_rate' => $csvEngagementRate,
            'completion_rate' => $csv['fullVideoWatchedRate'],
            'watch_time' => $csv['totalTimeWatched'],
            'avg_watch_time' => $csv['avgTimeWatched'],
        ],
        'api' => [
            'views' => $api['viewCount'],
            'likes' => $api['likeCount'],
            'comments' => $api['commentCount'],
            'shares' => $api['shareCount'],
            'reach' => $api['reach'],
            'engagement_rate' => $apiEngagementRate,
            'completion_rate' => $api['fullVideoWatchedRate'] ?? 0,
            'watch_time' => $api['totalTimeWatched'] ?? 0,
            'avg_watch_time' => $api['averageTimeWatched'] ?? 0,
        ],
        'differences' => [
            'views' => $viewsDiff,
            'likes' => $likesDiff,
            'comments' => $commentsDiff,
            'shares' => $sharesDiff,
            'reach' => $reachDiff,
            'engagement_rate' => $engagementDiff,
            'completion_rate' => $completionDiff,
            'watch_time' => $watchTimeDiff,
            'avg_watch_time' => $avgWatchTimeDiff,
        ],
        'has_difference' => $hasDifference,
    ];
}

echo "Comparison Summary:\n";
echo "  Perfect matches (all metrics identical): {$perfectMatches}\n";
echo "  Posts with metric growth: {$metricsGrowth}\n";
echo "  Posts with metric decline: {$metricsDecline}\n\n";

// Display all 16 posts in detail
echo "=== DETAILED COMPARISON TABLE ===\n\n";

foreach ($comparisonResults as $result) {
    echo "Post: {$result['videoId']}\n";
    echo "Date: {$result['date']}\n";
    echo "Title: {$result['title']}...\n";
    echo "\n";

    // Views
    $viewsPct = $result['csv']['views'] > 0
        ? ($result['differences']['views'] / $result['csv']['views'] * 100)
        : 0;
    echo sprintf("  Views:      CSV=%7d | API=%7d | Diff=%+6d (%+.2f%%)\n",
        $result['csv']['views'],
        $result['api']['views'],
        $result['differences']['views'],
        $viewsPct
    );

    // Likes
    $likesPct = $result['csv']['likes'] > 0
        ? ($result['differences']['likes'] / $result['csv']['likes'] * 100)
        : 0;
    echo sprintf("  Likes:      CSV=%7d | API=%7d | Diff=%+6d (%+.2f%%)\n",
        $result['csv']['likes'],
        $result['api']['likes'],
        $result['differences']['likes'],
        $likesPct
    );

    // Comments
    $commentsPct = $result['csv']['comments'] > 0
        ? ($result['differences']['comments'] / $result['csv']['comments'] * 100)
        : 0;
    echo sprintf("  Comments:   CSV=%7d | API=%7d | Diff=%+6d (%+.2f%%)\n",
        $result['csv']['comments'],
        $result['api']['comments'],
        $result['differences']['comments'],
        $commentsPct
    );

    // Shares
    $sharesPct = $result['csv']['shares'] > 0
        ? ($result['differences']['shares'] / $result['csv']['shares'] * 100)
        : 0;
    echo sprintf("  Shares:     CSV=%7d | API=%7d | Diff=%+6d (%+.2f%%)\n",
        $result['csv']['shares'],
        $result['api']['shares'],
        $result['differences']['shares'],
        $sharesPct
    );

    // Reach
    if ($result['csv']['reach'] !== null && $result['api']['reach'] > 0) {
        $reachPct = $result['csv']['reach'] > 0
            ? ($result['differences']['reach'] / $result['csv']['reach'] * 100)
            : 0;
        echo sprintf("  Reach:      CSV=%7d | API=%7d | Diff=%+6d (%+.2f%%)\n",
            $result['csv']['reach'],
            $result['api']['reach'],
            $result['differences']['reach'],
            $reachPct
        );
    } else {
        echo sprintf("  Reach:      CSV=%7s | API=%7d | (CSV missing)\n",
            'N/A',
            $result['api']['reach']
        );
    }

    // Engagement Rate
    echo sprintf("  Engagement: CSV=%6.2f%% | API=%6.2f%% | Diff=%+.2f%%\n",
        $result['csv']['engagement_rate'],
        $result['api']['engagement_rate'],
        $result['differences']['engagement_rate']
    );

    // Completion Rate
    echo sprintf("  Completion: CSV=%6.2f%% | API=%6.2f%% | Diff=%+.2f%%\n",
        $result['csv']['completion_rate'] * 100,
        $result['api']['completion_rate'] * 100,
        $result['differences']['completion_rate'] * 100
    );

    // Watch Time
    echo sprintf("  Watch Time: CSV=%7ds | API=%7ds | Diff=%+6ds\n",
        $result['csv']['watch_time'],
        $result['api']['watch_time'],
        $result['differences']['watch_time']
    );

    echo "\n";
    echo str_repeat("-", 70) . "\n\n";
}

// ==========================================
// STEP 3: AGGREGATE STATISTICS
// ==========================================
echo "\n==========================================\n";
echo "STEP 3: AGGREGATE STATISTICS\n";
echo "==========================================\n\n";

$csvTotals = [
    'views' => 0, 'likes' => 0, 'comments' => 0, 'shares' => 0,
    'reach' => 0, 'watch_time' => 0
];

$apiTotals = [
    'views' => 0, 'likes' => 0, 'comments' => 0, 'shares' => 0,
    'reach' => 0, 'watch_time' => 0
];

foreach ($comparisonResults as $result) {
    $csvTotals['views'] += $result['csv']['views'];
    $csvTotals['likes'] += $result['csv']['likes'];
    $csvTotals['comments'] += $result['csv']['comments'];
    $csvTotals['shares'] += $result['csv']['shares'];
    $csvTotals['reach'] += $result['csv']['reach'] ?? 0;
    $csvTotals['watch_time'] += $result['csv']['watch_time'];

    $apiTotals['views'] += $result['api']['views'];
    $apiTotals['likes'] += $result['api']['likes'];
    $apiTotals['comments'] += $result['api']['comments'];
    $apiTotals['shares'] += $result['api']['shares'];
    $apiTotals['reach'] += $result['api']['reach'];
    $apiTotals['watch_time'] += $result['api']['watch_time'];
}

echo "Aggregate Totals (16 Matching Posts):\n\n";

foreach (['views', 'likes', 'comments', 'shares', 'reach', 'watch_time'] as $metric) {
    $diff = $apiTotals[$metric] - $csvTotals[$metric];
    $pct = $csvTotals[$metric] > 0 ? ($diff / $csvTotals[$metric] * 100) : 0;

    $metricLabel = ucfirst(str_replace('_', ' ', $metric));
    echo sprintf("  %-12s CSV=%8d | API=%8d | Diff=%+7d (%+.2f%%)\n",
        $metricLabel . ':',
        $csvTotals[$metric],
        $apiTotals[$metric],
        $diff,
        $pct
    );
}

// ==========================================
// STEP 4: CSV-ONLY POSTS (MISSING FROM API)
// ==========================================
echo "\n\n==========================================\n";
echo "STEP 4: CSV-ONLY POSTS (Missing from API)\n";
echo "==========================================\n\n";

echo "These " . count($csvOnlyPosts) . " posts exist in CSV but NOT in Metricool API:\n";
echo "(Reason: Older than ~90 days API retention limit)\n\n";

// Sort by date
uasort($csvOnlyPosts, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

$csvOnlyTotals = [
    'views' => 0, 'likes' => 0, 'comments' => 0, 'shares' => 0
];

foreach ($csvOnlyPosts as $post) {
    $csvOnlyTotals['views'] += $post['views'];
    $csvOnlyTotals['likes'] += $post['likes'];
    $csvOnlyTotals['comments'] += $post['comments'];
    $csvOnlyTotals['shares'] += $post['shares'];

    $engagement = $post['views'] > 0
        ? (($post['likes'] + $post['comments'] + $post['shares']) / $post['views'] * 100)
        : 0;

    echo "Video ID: {$post['videoId']}\n";
    echo "  Date: {$post['date']}\n";
    echo "  Title: " . substr($post['title'], 0, 60) . "...\n";
    echo "  Views: " . number_format($post['views']) . "\n";
    echo "  Likes: " . number_format($post['likes']) . "\n";
    echo "  Comments: " . number_format($post['comments']) . "\n";
    echo "  Shares: " . number_format($post['shares']) . "\n";
    echo "  Engagement Rate: " . number_format($engagement, 2) . "%\n";
    echo "  Completion Rate: " . number_format($post['fullVideoWatchedRate'] * 100, 2) . "%\n";
    echo "\n";
}

echo "CSV-Only Posts Impact:\n";
echo "  Total Views: " . number_format($csvOnlyTotals['views']) . "\n";
echo "  Total Likes: " . number_format($csvOnlyTotals['likes']) . "\n";
echo "  Total Comments: " . number_format($csvOnlyTotals['comments']) . "\n";
echo "  Total Shares: " . number_format($csvOnlyTotals['shares']) . "\n";
echo "  % of Total Views: " . number_format($csvOnlyTotals['views'] / ($csvTotals['views'] + $csvOnlyTotals['views']) * 100, 1) . "%\n";

// ==========================================
// STEP 5: DATA QUALITY ASSESSMENT
// ==========================================
echo "\n\n==========================================\n";
echo "STEP 5: DATA QUALITY ASSESSMENT\n";
echo "==========================================\n\n";

echo "Data Freshness:\n";
echo "  CSV export date: 2025-02-06 to 2026-02-06 (based on filename)\n";
echo "  API fetch date: " . $apiData['metadata']['fetched_at'] . "\n\n";

echo "Data Accuracy:\n";
echo "  Perfect matches: {$perfectMatches} / " . count($matchingPosts) . " (" .
    number_format($perfectMatches / count($matchingPosts) * 100, 1) . "%)\n";
echo "  Posts with differences: " . (count($matchingPosts) - $perfectMatches) . "\n\n";

echo "Conclusion:\n";
if ($perfectMatches == count($matchingPosts)) {
    echo "  ✓ EXCELLENT: 100% perfect match - CSV and API are in perfect sync!\n";
    echo "  ✓ The CSV export was recent and reflects the same data as the API.\n";
} else {
    echo "  ⚠ METRICS CHANGED: API shows different values than CSV (expected for growing posts)\n";
    echo "  → Recommendation: Use API as primary source for current data\n";
}

echo "\n==========================================\n";
echo "DETAILED COMPARISON COMPLETE\n";
echo "==========================================\n";
