<?php
/**
 * Quick Database Status Checker
 * Check which tables exist to determine migration status
 */

// Direct connection for diagnostic script
$dbHost = '127.0.0.1';
$dbName = 'social_media_analytics';
$dbUser = 'root';
$dbPass = '';

try {
    $db = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== DATABASE STATUS CHECK ===\n\n";
    echo "Database: {$dbName}\n";
    echo "Host: {$dbHost}\n\n";

    // Check if migrations table exists
    $stmt = $db->query("SHOW TABLES LIKE 'migrations'");
    $hasMigrationsTable = $stmt->rowCount() > 0;

    if ($hasMigrationsTable) {
        echo "✓ Migrations table exists\n\n";
        echo "=== Applied Migrations ===\n";
        $stmt = $db->query("SELECT * FROM migrations ORDER BY id DESC LIMIT 10");
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($migrations) > 0) {
            foreach ($migrations as $m) {
                echo "- Migration {$m['id']}: {$m['name']} (applied {$m['applied_at']})\n";
            }
        } else {
            echo "No migrations recorded yet\n";
        }
    } else {
        echo "✗ No migrations table found\n";
    }

    echo "\n=== Existing Tables ===\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "- $table\n";
    }

    echo "\n=== Key Tables Check ===\n";

    $keyTables = [
        'clients' => 'Multi-tenant foundation (Migration 006)',
        'social_accounts' => 'Social accounts (Migration 006)',
        'website_analytics' => 'Website analytics (Migration 014)',
        'conversations' => 'Inbox/Messages (Migration 020)',
        'glossary' => 'Glossary (Migration 019 - simple)',
        'glossary_terms' => 'Glossary (Migration 019 - complex)',
        'hashtag_tracking' => 'Hashtag tracking (Migration 012)',
        'competitors' => 'Competitor analysis (Migration 013)',
        'google_business_locations' => 'Google Business (Migration 016)'
    ];

    foreach ($keyTables as $table => $description) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? "✓" : "✗";
        echo "$status $table - $description\n";
    }

    echo "\n=== Posts Table Columns (Algorithm Metrics Check) ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $algorithmMetrics = [
        'watch_time' => 'Migration 007/011',
        'completion_rate' => 'Migration 007/011',
        'sends_count' => 'Migration 007/011',
        'profile_visits' => 'Migration 007/011',
        'skip_rate' => 'Migration 007/011',
        'crossposted_views' => 'Migration 007/011'
    ];

    foreach ($algorithmMetrics as $col => $migration) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $exists = true;
                break;
            }
        }
        $status = $exists ? "✓" : "✗";
        echo "$status $col - $migration\n";
    }

    echo "\n=== Summary ===\n";
    echo "Total tables: " . count($tables) . "\n";
    echo "Database is ready for: " . ($hasMigrationsTable ? "migration tracking" : "initial setup") . "\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
