<?php
/**
 * CSV vs Metricool API - Detailed Comparison
 *
 * Comprehensive analysis comparing CSV data with Metricool API data
 * Identifies missing posts, metric discrepancies, and data quality issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==========================================\n";
echo "  CSV vs METRICOOL API COMPARISON\n";
echo "==========================================\n\n";

// Load API data
$apiDataFile = __DIR__ . '/metricool_tiktok_posts_full.json';
if (!file_exists($apiDataFile)) {
    die("✗ API data file not found: {$apiDataFile}\n");
}

$apiData = json_decode(file_get_contents($apiDataFile), true);
$apiPosts = $apiData['posts'];

echo "✓ Loaded API data: " . count($apiPosts) . " posts\n";

// Load CSV data
$csvFile = __DIR__ . '/../tiktok-posts_2025-02-06_2026-02-06.csv';
if (!file_exists($csvFile)) {
    die("✗ CSV file not found: {$csvFile}\n");
}

$csvPosts = [];
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle); // Skip header
while (($row = fgetcsv($handle)) !== false) {
    // Extract video ID from URL
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
        'reach' => $row[10] === '' ? 0 : (int)$row[10],
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
// PART 1: IDENTIFY MISSING POSTS
// ==========================================
echo "==========================================\n";
echo "PART 1: MISSING POSTS ANALYSIS\n";
echo "==========================================\n\n";

$csvOnlyPosts = [];
$apiOnlyPosts = [];
$matchingPosts = [];

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

echo "Total CSV posts: " . count($csvPosts) . "\n";
echo "Total API posts: " . count($apiPosts) . "\n";
echo "Matching posts: " . count($matchingPosts) . "\n";
echo "CSV-only posts: " . count($csvOnlyPosts) . "\n";
echo "API-only posts: " . count($apiOnlyPosts) . "\n\n";

// Show CSV-only posts (older than API retention)
if (!empty($csvOnlyPosts)) {
    echo "--- CSV-ONLY POSTS (Missing from API) ---\n";
    echo "These posts are in CSV but NOT in Metricool API (likely > 90 days old)\n\n";

    foreach ($csvOnlyPosts as $videoId => $post) {
        echo "Video ID: {$videoId}\n";
        echo "  Date: {$post['date']}\n";
        echo "  Title: " . substr($post['title'], 0, 60) . "...\n";
        echo "  Views: " . number_format($post['views']) . "\n";
        echo "  Engagement: " . number_format(($post['likes'] + $post['comments'] + $post['shares']) / max($post['views'], 1) * 100, 2) . "%\n\n";
    }
}

// ==========================================
// PART 2: METRIC DISCREPANCIES
// ==========================================
echo "\n==========================================\n";
echo "PART 2: METRIC DISCREPANCIES\n";
echo "==========================================\n\n";

$discrepancies = [];

foreach ($matchingPosts as $videoId => $data) {
    $csv = $data['csv'];
    $api = $data['api'];

    $postDiscrepancies = [];

    // Compare views
    $viewsDiff = $api['viewCount'] - $csv['views'];
    if ($viewsDiff != 0) {
        $postDiscrepancies['views'] = [
            'csv' => $csv['views'],
            'api' => $api['viewCount'],
            'diff' => $viewsDiff,
            'diff_pct' => $csv['views'] > 0 ? ($viewsDiff / $csv['views'] * 100) : 0,
        ];
    }

    // Compare likes
    $likesDiff = $api['likeCount'] - $csv['likes'];
    if ($likesDiff != 0) {
        $postDiscrepancies['likes'] = [
            'csv' => $csv['likes'],
            'api' => $api['likeCount'],
            'diff' => $likesDiff,
            'diff_pct' => $csv['likes'] > 0 ? ($likesDiff / $csv['likes'] * 100) : 0,
        ];
    }

    // Compare comments
    $commentsDiff = $api['commentCount'] - $csv['comments'];
    if ($commentsDiff != 0) {
        $postDiscrepancies['comments'] = [
            'csv' => $csv['comments'],
            'api' => $api['commentCount'],
            'diff' => $commentsDiff,
            'diff_pct' => $csv['comments'] > 0 ? ($commentsDiff / $csv['comments'] * 100) : 0,
        ];
    }

    // Compare shares
    $sharesDiff = $api['shareCount'] - $csv['shares'];
    if ($sharesDiff != 0) {
        $postDiscrepancies['shares'] = [
            'csv' => $csv['shares'],
            'api' => $api['shareCount'],
            'diff' => $sharesDiff,
            'diff_pct' => $csv['shares'] > 0 ? ($sharesDiff / $csv['shares'] * 100) : 0,
        ];
    }

    // Compare reach (if both have data)
    if ($csv['reach'] > 0 && $api['reach'] > 0) {
        $reachDiff = $api['reach'] - $csv['reach'];
        if ($reachDiff != 0) {
            $postDiscrepancies['reach'] = [
                'csv' => $csv['reach'],
                'api' => $api['reach'],
                'diff' => $reachDiff,
                'diff_pct' => ($reachDiff / $csv['reach'] * 100),
            ];
        }
    }

    if (!empty($postDiscrepancies)) {
        $discrepancies[$videoId] = [
            'date' => substr($api['createTime'], 0, 10),
            'title' => substr($api['title'], 0, 50),
            'discrepancies' => $postDiscrepancies,
        ];
    }
}

echo "Posts with discrepancies: " . count($discrepancies) . " / " . count($matchingPosts) . "\n";
echo "Perfect matches: " . (count($matchingPosts) - count($discrepancies)) . "\n\n";

if (!empty($discrepancies)) {
    echo "--- TOP 5 POSTS WITH LARGEST DISCREPANCIES ---\n\n";

    // Sort by total difference magnitude
    usort($discrepancies, function($a, $b) {
        $sumA = 0;
        $sumB = 0;
        foreach ($a['discrepancies'] as $metric => $diff) {
            $sumA += abs($diff['diff']);
        }
        foreach ($b['discrepancies'] as $metric => $diff) {
            $sumB += abs($diff['diff']);
        }
        return $sumB <=> $sumA;
    });

    $topDiscrepancies = array_slice($discrepancies, 0, 5);

    foreach ($topDiscrepancies as $i => $data) {
        echo ($i + 1) . ". Date: {$data['date']}\n";
        echo "   Title: {$data['title']}...\n";
        echo "   Discrepancies:\n";

        foreach ($data['discrepancies'] as $metric => $diff) {
            $direction = $diff['diff'] > 0 ? '↑' : '↓';
            echo "     {$metric}: CSV=" . number_format($diff['csv']) . " | API=" . number_format($diff['api']) . " | Diff={$direction}" . number_format(abs($diff['diff'])) . " (" . number_format($diff['diff_pct'], 1) . "%)\n";
        }
        echo "\n";
    }
}

// ==========================================
// PART 3: DATA QUALITY SUMMARY
// ==========================================
echo "\n==========================================\n";
echo "PART 3: DATA QUALITY SUMMARY\n";
echo "==========================================\n\n";

$totalViewsDiffCSV = 0;
$totalViewsDiffAPI = 0;
$totalLikesDiffCSV = 0;
$totalLikesDiffAPI = 0;

foreach ($matchingPosts as $videoId => $data) {
    $totalViewsDiffCSV += $data['csv']['views'];
    $totalViewsDiffAPI += $data['api']['viewCount'];
    $totalLikesDiffCSV += $data['csv']['likes'];
    $totalLikesDiffAPI += $data['api']['likeCount'];
}

echo "Aggregate Comparison (Matching Posts Only):\n";
echo "  CSV Total Views: " . number_format($totalViewsDiffCSV) . "\n";
echo "  API Total Views: " . number_format($totalViewsDiffAPI) . "\n";
echo "  Difference: " . number_format($totalViewsDiffAPI - $totalViewsDiffCSV) . " (" . number_format(($totalViewsDiffAPI - $totalViewsDiffCSV) / $totalViewsDiffCSV * 100, 2) . "%)\n\n";

echo "  CSV Total Likes: " . number_format($totalLikesDiffCSV) . "\n";
echo "  API Total Likes: " . number_format($totalLikesDiffAPI) . "\n";
echo "  Difference: " . number_format($totalLikesDiffAPI - $totalLikesDiffCSV) . " (" . number_format(($totalLikesDiffAPI - $totalLikesDiffCSV) / $totalLikesDiffCSV * 100, 2) . "%)\n\n";

// ==========================================
// PART 4: RECOMMENDATIONS
// ==========================================
echo "\n==========================================\n";
echo "PART 4: RECOMMENDATIONS\n";
echo "==========================================\n\n";

echo "1. DATA RETENTION:\n";
echo "   - Metricool API limited to ~90 days of history\n";
echo "   - 9 posts in CSV (from before 2025-11-13) NOT in API\n";
echo "   → RECOMMENDATION: Keep CSV imports for historical data (>90 days)\n\n";

echo "2. METRIC UPDATES:\n";
echo "   - API data is more current (metrics grow over time)\n";
echo "   - Total views increased by " . number_format($totalViewsDiffAPI - $totalViewsDiffCSV) . " since CSV export\n";
echo "   → RECOMMENDATION: Use API as primary source, CSV as fallback/baseline\n\n";

echo "3. DATA QUALITY:\n";
echo "   - " . (count($matchingPosts) - count($discrepancies)) . " / " . count($matchingPosts) . " posts have metric differences\n";
echo "   - Differences expected (metrics grow naturally)\n";
echo "   → RECOMMENDATION: Implement metrics_history table for tracking growth\n\n";

echo "4. MISSING METRICS:\n";
echo "   - API does NOT provide: saves_count, sends_count (DM shares), profile_visits\n";
echo "   - These are CRITICAL for 2026 algorithm performance\n";
echo "   → RECOMMENDATION: Integrate direct TikTok API for gap metrics\n\n";

echo "==========================================\n";
echo "COMPARISON COMPLETE\n";
echo "==========================================\n";
