<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use Core\Database;

$db = Database::getInstance();

echo "=== GIUDITTA DATA CHECK ===\n\n";

// Check clients
echo "1. CLIENTS:\n";
$clients = $db->query("SELECT * FROM clients")->fetchAll();
echo "Total clients: " . count($clients) . "\n";
foreach ($clients as $client) {
    echo "  - ID: {$client['id']}, Name: {$client['name']}, Email: {$client['email']}\n";
}

// Check posts
echo "\n2. POSTS:\n";
$postCount = $db->query("SELECT COUNT(*) as count FROM posts")->fetch();
echo "Total posts: " . $postCount['count'] . "\n";

// Check latest posts
echo "\n3. LATEST 5 POSTS:\n";
$latestPosts = $db->query("
    SELECT id, platform, caption, views, likes, posted_date, hashtags
    FROM posts
    ORDER BY posted_date DESC
    LIMIT 5
")->fetchAll();

foreach ($latestPosts as $post) {
    echo "  Post #{$post['id']} ({$post['platform']}):\n";
    echo "    Caption: " . substr($post['caption'], 0, 50) . "...\n";
    echo "    Views: {$post['views']}, Likes: {$post['likes']}\n";
    echo "    Date: {$post['posted_date']}\n";
    echo "    Hashtags: {$post['hashtags']}\n\n";
}

// Check hashtags
echo "\n4. HASHTAGS:\n";
$hashtags = $db->query("
    SELECT hashtag, COUNT(*) as uses, AVG(views) as avg_views
    FROM post_hashtags
    LEFT JOIN posts ON post_hashtags.post_id = posts.id
    GROUP BY hashtag
    ORDER BY uses DESC
    LIMIT 10
")->fetchAll();

if (count($hashtags) > 0) {
    echo "Top hashtags:\n";
    foreach ($hashtags as $tag) {
        echo "  #{$tag['hashtag']}: {$tag['uses']} uses, avg " . number_format($tag['avg_views']) . " views\n";
    }
} else {
    echo "No hashtags tracked yet.\n";
}

// Check Metricool service file
echo "\n5. METRICOOL SERVICE:\n";
$metricoolFile = __DIR__ . '/../src/Services/MetricoolApiService.php';
if (file_exists($metricoolFile)) {
    echo "✓ MetricoolApiService.php EXISTS\n";

    // Check if credentials are configured
    $content = file_get_contents($metricoolFile);
    if (strpos($content, 'METRICOOL_API_KEY') !== false) {
        echo "  - Uses METRICOOL_API_KEY from config\n";
    }

    // Check for Giuditta/client_id support
    if (strpos($content, 'client_id') !== false) {
        echo "  - Multi-tenant support: YES\n";
    }
} else {
    echo "✗ MetricoolApiService.php NOT FOUND\n";
}

// Check if Metricool credentials exist in config
echo "\n6. METRICOOL CONFIG:\n";
if (defined('METRICOOL_API_KEY')) {
    echo "✓ METRICOOL_API_KEY configured\n";
} else {
    echo "✗ METRICOOL_API_KEY not configured in config/app.php\n";
}

echo "\n=== END CHECK ===\n";
