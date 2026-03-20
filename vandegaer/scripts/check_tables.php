<?php
// Check which tables exist after migration
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=social_media_analytics', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== CHECKING DATABASE TABLES ===\n\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total tables: " . count($tables) . "\n\n";

    $requiredTables = [
        'posts',
        'glossary_terms',
        'hashtag_tracking',
        'competitor_profiles',
        'website_traffic_attribution',
        'inbox_messages'
    ];

    foreach ($requiredTables as $table) {
        $exists = in_array($table, $tables);
        echo ($exists ? "✅" : "❌") . " $table\n";
    }

    echo "\n=== ALL TABLES ===\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
