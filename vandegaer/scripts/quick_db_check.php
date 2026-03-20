<?php
// Quick database check for demo prep
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=social_media_analytics', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== DATABASE STATUS ===\n\n";

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM posts');
    $postCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Posts: $postCount\n";

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM clients');
    $clientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Clients: $clientCount\n";

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM metrics_history');
    $metricsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Metrics snapshots: $metricsCount\n\n";

    if ($postCount > 0) {
        echo "=== SAMPLE POSTS ===\n";
        $stmt = $pdo->query('SELECT id, caption, platform, views, likes, comments, reach, posted_date FROM posts LIMIT 5');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $preview = substr($row['caption'], 0, 50) . (strlen($row['caption']) > 50 ? '...' : '');
            echo "- [{$row['platform']}] {$preview}\n";
            echo "  Views: {$row['views']}, Reach: {$row['reach']}, Likes: {$row['likes']}, Date: {$row['posted_date']}\n";
        }
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
