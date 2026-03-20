<?php
/**
 * Execute all missing migrations (007-020)
 * Run this to fix 500 errors on the posts page
 */

// Direct database connection for local XAMPP
try {
    $dsn = "mysql:host=127.0.0.1;dbname=social_media_analytics;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "=== EXECUTING MISSING MIGRATIONS ===\n\n";

    // List of migrations to execute (in order)
    $migrations = [
        '007_analytics_enhancement.sql',
        '011_algorithm_metrics_2026.sql',
        '012_hashtag_tracking.sql',
        '013_competitor_analysis.sql',
        '014_website_traffic.sql',
        '015_data_lineage.sql',
        '017_database_optimization.sql',
        '019_glossary.sql',
        '020_inbox_messages.sql',
    ];

    foreach ($migrations as $migration) {
        $filePath = __DIR__ . '/' . $migration;

        if (!file_exists($filePath)) {
            echo "⚠️  SKIPPED: $migration (file not found)\n";
            continue;
        }

        echo "📦 Executing: $migration\n";

        $sql = file_get_contents($filePath);

        // Split by semicolons (simple approach - works for most migrations)
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !preg_match('/^--/', $s)
        );

        $success = 0;
        $errors = 0;

        foreach ($statements as $statement) {
            // Skip comments and USE database statements
            if (preg_match('/^(--|\/\*|USE\s)/i', trim($statement))) {
                continue;
            }

            try {
                $pdo->exec($statement);
                $success++;
            } catch (PDOException $e) {
                // Check if error is "already exists" - that's OK
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "   ℹ️  Already exists (skipped)\n";
                } else {
                    echo "   ❌ Error: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }

        if ($errors === 0) {
            echo "   ✅ Success ($success statements)\n\n";
        } else {
            echo "   ⚠️  Completed with $errors errors\n\n";
        }
    }

    echo "=== MIGRATION COMPLETE ===\n";
    echo "\n🎉 All migrations executed!\n";
    echo "🔄 Refresh your browser to see the posts.\n\n";

} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
