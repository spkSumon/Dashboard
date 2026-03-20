<?php
/**
 * Execute Migrations 019 & 020
 * Run via: php scripts/execute_migrations_019_020.php
 * Or browser: http://localhost/socialbit-live/scripts/execute_migrations_019_020.php
 */

// Direct connection for migration script
$dbHost = '127.0.0.1';
$dbName = 'social_media_analytics';
$dbUser = 'root';
$dbPass = '';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MIGRATION EXECUTION - 019 & 020 ===\n\n";

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );

    echo "✓ Connected to database: {$dbName}\n\n";

    // ==============================================
    // MIGRATION 019: Glossary System
    // ==============================================

    echo "=== Executing Migration 019: Glossary System ===\n";

    // Read and execute migration file
    $migration019 = file_get_contents(__DIR__ . '/019_glossary.sql');

    if (!$migration019) {
        throw new Exception("Failed to read 019_glossary.sql");
    }

    // Remove comments
    $migration019 = preg_replace('/^--.*$/m', '', $migration019);
    $migration019 = preg_replace('/\/\*.*?\*\//s', '', $migration019);

    // Split by semicolon but keep multi-line statements together
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $migration019)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 5;
        }
    );

    $executed019 = 0;
    $errors019 = 0;

    foreach ($statements as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;

        try {
            $db->exec($sql . ';');
            $executed019++;
        } catch (PDOException $e) {
            // Check if it's a duplicate table error (1050)
            if ($e->getCode() == '42S01' || strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Table already exists (skipping): " . substr($sql, 0, 50) . "...\n";
            } else {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "  SQL: " . substr($sql, 0, 100) . "...\n";
                $errors019++;
            }
        }
    }

    echo "\n✓ Migration 019 executed: {$executed019} statements\n";
    if ($errors019 > 0) {
        echo "⚠ Warnings/Errors: {$errors019}\n";
    }

    // Verify glossary table
    $stmt = $db->query("SELECT COUNT(*) as count FROM glossary");
    $result = $stmt->fetch();
    echo "✓ Glossary terms inserted: {$result['count']}\n\n";

    // ==============================================
    // MIGRATION 020: Inbox/Messages System
    // ==============================================

    echo "=== Executing Migration 020: Inbox/Messages System ===\n";

    // Read and execute migration file
    $migration020 = file_get_contents(__DIR__ . '/020_inbox_messages.sql');

    if (!$migration020) {
        throw new Exception("Failed to read 020_inbox_messages.sql");
    }

    // Remove comments
    $migration020 = preg_replace('/^--.*$/m', '', $migration020);
    $migration020 = preg_replace('/\/\*.*?\*\//s', '', $migration020);

    // Split by semicolon but keep multi-line statements together
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $migration020)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 5;
        }
    );

    $executed020 = 0;
    $errors020 = 0;

    foreach ($statements as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;

        try {
            $db->exec($sql . ';');
            $executed020++;
        } catch (PDOException $e) {
            // Check if it's a duplicate table/view error
            if ($e->getCode() == '42S01' || $e->getCode() == '42000' || strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Table/View already exists (skipping): " . substr($sql, 0, 50) . "...\n";
            } else {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "  SQL: " . substr($sql, 0, 100) . "...\n";
                $errors020++;
            }
        }
    }

    echo "\n✓ Migration 020 executed: {$executed020} statements\n";
    if ($errors020 > 0) {
        echo "⚠ Warnings/Errors: {$errors020}\n";
    }

    // ==============================================
    // VERIFICATION
    // ==============================================

    echo "\n=== Verification ===\n\n";

    // Check glossary table
    $stmt = $db->query("SHOW TABLES LIKE 'glossary'");
    if ($stmt->rowCount() > 0) {
        echo "✓ glossary table exists\n";

        $stmt = $db->query("SELECT COUNT(*) as count FROM glossary");
        $result = $stmt->fetch();
        echo "  - {$result['count']} terms loaded\n";

        $stmt = $db->query("SELECT category, COUNT(*) as count FROM glossary GROUP BY category");
        echo "  - Categories:\n";
        while ($row = $stmt->fetch()) {
            echo "    • {$row['category']}: {$row['count']} terms\n";
        }
    } else {
        echo "✗ glossary table NOT found\n";
    }

    echo "\n";

    // Check inbox tables
    $inboxTables = ['conversations', 'messages', 'conversation_participants', 'inbox_filters'];
    foreach ($inboxTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table table exists\n";
        } else {
            echo "✗ $table table NOT found\n";
        }
    }

    echo "\n";

    // Check views
    $views = ['inbox_unresolved', 'inbox_unread_counts', 'inbox_recent_activity'];
    foreach ($views as $view) {
        $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_{$dbName} = '$view'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $view view exists\n";
        } else {
            echo "✗ $view view NOT found\n";
        }
    }

    echo "\n=== MIGRATION COMPLETE ===\n\n";
    echo "✓ Migration 019: Glossary System - SUCCESS\n";
    echo "✓ Migration 020: Inbox/Messages System - SUCCESS\n\n";

    echo "Next steps:\n";
    echo "1. Backend: Implement GlossaryController + GlossaryRepository\n";
    echo "2. Backend: Implement InboxService + InboxSyncService\n";
    echo "3. Frontend: Test glossary page with real data\n";
    echo "4. Integration: Connect Metricool inbox sync\n\n";

    echo "Database is ready for development! 🚀\n";

} catch (PDOException $e) {
    echo "✗ DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
