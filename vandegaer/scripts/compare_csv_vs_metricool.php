<?php
/**
 * Compare Existing CSV Data vs Metricool API Data
 *
 * Purpose: Verify conflict resolution strategy for Giuditta posts
 * Context: ALL database posts are Giuditta (no client filtering needed)
 *
 * This script:
 * 1. Shows existing posts in database (from CSV import)
 * 2. Fetches same posts from Metricool API
 * 3. Compares metrics (CSV baseline vs current API values)
 * 4. Tests conflict resolution strategy
 *
 * Run via browser: http://localhost/socialbit-live/scripts/compare_csv_vs_metricool.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// Load dependencies
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/SettingsRepository.php';
require_once __DIR__ . '/../src/Repositories/PostRepository.php';

use App\Core\Database;
use App\Repositories\SettingsRepository;
use App\Repositories\PostRepository;

echo "==========================================\n";
echo "  CSV vs METRICOOL DATA COMPARISON\n";
echo "==========================================\n\n";

// Load configuration
$config = require __DIR__ . '/../config/app.php';

try {
    // Initialize
    $db = new Database($config['db']);
    $settingsRepo = new SettingsRepository($db);
    $postRepo = new PostRepository($db);

    echo "✓ Database connected\n\n";

    // Get existing posts from database (ALL are Giuditta)
    echo "==========================================\n";
    echo "EXISTING DATABASE POSTS (CSV)\n";
    echo "==========================================\n";

    $existingPosts = $db->fetchAll("
        SELECT
            id,
            platform,
            platform_post_id,
            posted_date,
            caption,
            views,
            likes,
            comments,
            shares,
            saves,
            engagement_rate,
            import_source,
            hashtags
        FROM posts
        WHERE import_source = 'csv'
        ORDER BY posted_date DESC
        LIMIT 10
    ");

    if (empty($existingPosts)) {
        echo "⚠️  No CSV posts found in database\n";
        echo "This is expected if no CSV import has been done yet.\n\n";
    } else {
        echo "Found " . count($existingPosts) . " CSV posts (showing latest 10):\n\n";

        foreach ($existingPosts as $i => $post) {
            echo "--- Post " . ($i + 1) . " ---\n";
            echo "ID: {$post['id']}\n";
            echo "Platform: {$post['platform']}\n";
            echo "Platform Post ID: {$post['platform_post_id']}\n";
            echo "Date: {$post['posted_date']}\n";
            echo "Caption: " . substr($post['caption'] ?? 'N/A', 0, 50) . "...\n";
            echo "Views: {$post['views']} | Likes: {$post['likes']} | Comments: {$post['comments']}\n";
            echo "Engagement Rate: {$post['engagement_rate']}%\n";
            echo "Hashtags: " . ($post['hashtags'] ?? 'N/A') . "\n";
            echo "\n";
        }
    }

    // Count posts by platform
    echo "==========================================\n";
    echo "POSTS BY PLATFORM\n";
    echo "==========================================\n";

    $platformStats = $db->fetchAll("
        SELECT
            platform,
            COUNT(*) as total_posts,
            import_source,
            MAX(posted_date) as latest_post,
            MIN(posted_date) as earliest_post
        FROM posts
        GROUP BY platform, import_source
        ORDER BY platform, import_source
    ");

    foreach ($platformStats as $stat) {
        echo "{$stat['platform']} ({$stat['import_source']}): ";
        echo "{$stat['total_posts']} posts ";
        echo "({$stat['earliest_post']} to {$stat['latest_post']})\n";
    }

    // Load Metricool credentials
    echo "\n==========================================\n";
    echo "METRICOOL DATA FETCH\n";
    echo "==========================================\n";

    $apiKey = $settingsRepo->get('metricool_api_key');
    $userId = $settingsRepo->get('metricool_user_id');
    $blogId = $settingsRepo->get('metricool_blog_id_giuditta');

    if (!$apiKey || !$userId || !$blogId) {
        echo "❌ Metricool credentials not found\n";
        echo "Run setup script first: insert_metricool_credentials.php\n";
        exit(1);
    }

    echo "→ Fetching posts from Metricool...\n";
    echo "   Blog ID: {$blogId['value']}\n";
    echo "   Date range: Last 90 days\n\n";

    // Fetch Instagram posts from Metricool
    $startDate = date('Ymd', strtotime('-90 days'));
    $endDate = date('Ymd');

    $apiUrl = "https://app.metricool.com/api/stats/instagram/posts"
            . "?userId=" . $userId['value']
            . "&blogId=" . $blogId['value']
            . "&start=" . $startDate
            . "&end=" . $endDate;

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Mc-Auth: ' . $apiKey['value'],
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "❌ Metricool API Error: HTTP {$httpCode}\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
        exit(1);
    }

    $metricoolData = json_decode($response, true);
    $metricoolPosts = $metricoolData['data'] ?? [];

    echo "✓ Found " . count($metricoolPosts) . " posts from Metricool\n\n";

    if (empty($metricoolPosts)) {
        echo "⚠️  No Metricool posts in last 90 days\n";
        echo "This might be normal if account hasn't posted recently.\n";
        exit(0);
    }

    // Compare: Find matching posts (CSV vs API)
    echo "==========================================\n";
    echo "DATA COMPARISON (CSV vs API)\n";
    echo "==========================================\n\n";

    $matches = 0;
    $csvOnly = 0;
    $apiOnly = 0;
    $metricDifferences = [];

    // Build lookup map of Metricool posts by ID
    $metricoolMap = [];
    foreach ($metricoolPosts as $post) {
        $postId = (string)($post['id'] ?? '');
        if ($postId) {
            $metricoolMap[$postId] = $post;
        }
    }

    // Compare each CSV post with Metricool data
    foreach ($existingPosts as $csvPost) {
        $platformPostId = $csvPost['platform_post_id'];

        if (isset($metricoolMap[$platformPostId])) {
            // MATCH FOUND
            $matches++;
            $apiPost = $metricoolMap[$platformPostId];

            echo "✓ MATCH: Post ID {$platformPostId}\n";
            echo "  Caption: " . substr($csvPost['caption'] ?? 'N/A', 0, 40) . "...\n";

            // Compare metrics
            $csvViews = (int)$csvPost['views'];
            $apiViews = (int)($apiPost['impressions'] ?? $apiPost['views'] ?? 0);
            $csvLikes = (int)$csvPost['likes'];
            $apiLikes = (int)($apiPost['likes'] ?? 0);
            $csvComments = (int)$csvPost['comments'];
            $apiComments = (int)($apiPost['comments'] ?? 0);

            echo "  Views:    CSV={$csvViews} | API={$apiViews}";
            if ($apiViews != $csvViews) {
                $diff = $apiViews - $csvViews;
                echo " (Δ " . ($diff >= 0 ? "+{$diff}" : $diff) . ")";
            }
            echo "\n";

            echo "  Likes:    CSV={$csvLikes} | API={$apiLikes}";
            if ($apiLikes != $csvLikes) {
                $diff = $apiLikes - $csvLikes;
                echo " (Δ " . ($diff >= 0 ? "+{$diff}" : $diff) . ")";
            }
            echo "\n";

            echo "  Comments: CSV={$csvComments} | API={$apiComments}";
            if ($apiComments != $csvComments) {
                $diff = $apiComments - $csvComments;
                echo " (Δ " . ($diff >= 0 ? "+{$diff}" : $diff) . ")";
            }
            echo "\n\n";

            // Track significant differences
            if (abs($apiViews - $csvViews) > ($csvViews * 0.1)) { // >10% difference
                $metricDifferences[] = [
                    'post_id' => $platformPostId,
                    'metric' => 'views',
                    'csv' => $csvViews,
                    'api' => $apiViews,
                    'percent_change' => ($csvViews > 0 ? (($apiViews - $csvViews) / $csvViews * 100) : 0)
                ];
            }
        } else {
            // CSV only (not in Metricool data)
            $csvOnly++;
        }
    }

    // Count API-only posts (not in CSV)
    foreach ($metricoolMap as $postId => $post) {
        $found = false;
        foreach ($existingPosts as $csvPost) {
            if ($csvPost['platform_post_id'] == $postId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $apiOnly++;
        }
    }

    // Summary
    echo "==========================================\n";
    echo "COMPARISON SUMMARY\n";
    echo "==========================================\n";
    echo "Matched posts (CSV + API): {$matches}\n";
    echo "CSV only: {$csvOnly}\n";
    echo "API only (new posts): {$apiOnly}\n";
    echo "Significant differences (>10%): " . count($metricDifferences) . "\n\n";

    if (!empty($metricDifferences)) {
        echo "Posts with significant metric changes:\n";
        foreach ($metricDifferences as $diff) {
            echo "  - Post {$diff['post_id']}: {$diff['metric']} ";
            echo "changed " . number_format($diff['percent_change'], 1) . "%\n";
        }
    }

    echo "\n==========================================\n";
    echo "CONFLICT RESOLUTION STRATEGY\n";
    echo "==========================================\n";
    echo "For matched posts (CSV baseline exists):\n";
    echo "  → Keep CSV data in 'posts' table (baseline)\n";
    echo "  → Add API data to 'metrics_history' (growth tracking)\n";
    echo "  → Track metric changes over time\n\n";
    echo "For new posts (API only):\n";
    echo "  → Create in 'posts' table with source='api'\n";
    echo "  → Initial snapshot to 'metrics_history'\n\n";

    echo "✅ COMPARISON COMPLETE!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
