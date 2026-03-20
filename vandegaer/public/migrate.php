<?php
/**
 * Web-based Migration Executor
 * Navigate to: http://localhost/socialbit-live/public/migrate.php
 */

// Security: Only allow on localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Access denied - localhost only');
}

$config = require __DIR__ . '/../config/app.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SocialBit - Database Migrations</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 40px; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        h1 { color: #38bdf8; margin-bottom: 10px; }
        .subtitle { color: #94a3b8; margin-bottom: 30px; }
        .migration { background: #334155; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #475569; }
        .migration.pending { border-left-color: #fbbf24; }
        .migration.running { border-left-color: #3b82f6; animation: pulse 1s infinite; }
        .migration.success { border-left-color: #22c55e; }
        .migration.error { border-left-color: #ef4444; }
        .migration.skipped { border-left-color: #64748b; }
        .btn { background: #3b82f6; color: white; padding: 12px 24px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: 600; }
        .btn:hover { background: #2563eb; }
        .btn:disabled { background: #475569; cursor: not-allowed; }
        pre { background: #0f172a; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; margin-top: 10px; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #fbbf24; }
        .info { color: #3b82f6; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .db-info { background: #334155; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .db-info strong { color: #38bdf8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ SocialBit Database Migrations</h1>
        <p class="subtitle">Execute missing database migrations to fix 500 errors</p>

        <div class="db-info">
            <strong>Database:</strong> <?= htmlspecialchars($config['db']['database']) ?><br>
            <strong>Host:</strong> <?= htmlspecialchars($config['db']['host']) ?>
        </div>

        <button class="btn" id="executeBtn" onclick="executeMigrations()">
            ▶️ Execute All Migrations
        </button>

        <div id="results"></div>
    </div>

    <script>
        async function executeMigrations() {
            const btn = document.getElementById('executeBtn');
            const results = document.getElementById('results');

            btn.disabled = true;
            btn.textContent = '⏳ Running migrations...';
            results.innerHTML = '';

            const migrations = [
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

            for (const migration of migrations) {
                const div = document.createElement('div');
                div.className = 'migration running';
                div.innerHTML = `<strong>📦 ${migration}</strong><br><span class="info">Running...</span>`;
                results.appendChild(div);

                try {
                    const response = await fetch('migrate.php?action=execute&file=' + encodeURIComponent(migration));
                    const data = await response.json();

                    if (data.success) {
                        div.className = 'migration success';
                        div.innerHTML = `<strong>✅ ${migration}</strong><br><span class="success">${data.message}</span>`;
                        if (data.details) {
                            div.innerHTML += `<pre>${data.details}</pre>`;
                        }
                    } else if (data.skipped) {
                        div.className = 'migration skipped';
                        div.innerHTML = `<strong>⏭️ ${migration}</strong><br><span class="warning">${data.message}</span>`;
                    } else {
                        div.className = 'migration error';
                        div.innerHTML = `<strong>❌ ${migration}</strong><br><span class="error">${data.message}</span>`;
                        if (data.details) {
                            div.innerHTML += `<pre>${data.details}</pre>`;
                        }
                    }
                } catch (error) {
                    div.className = 'migration error';
                    div.innerHTML = `<strong>❌ ${migration}</strong><br><span class="error">Network error: ${error.message}</span>`;
                }
            }

            btn.disabled = false;
            btn.textContent = '✅ Migrations Complete!';

            setTimeout(() => {
                alert('✅ Migrations complete! Refresh the posts page to see your data.');
            }, 500);
        }
    </script>
</body>
</html>

<?php

// Handle AJAX migration execution
if (isset($_GET['action']) && $_GET['action'] === 'execute') {
    header('Content-Type: application/json');

    $file = $_GET['file'] ?? '';
    $filePath = __DIR__ . '/../scripts/' . basename($file);

    if (!file_exists($filePath)) {
        echo json_encode([
            'success' => false,
            'skipped' => true,
            'message' => 'File not found'
        ]);
        exit;
    }

    try {
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sql = file_get_contents($filePath);

        // Remove comments and split by semicolons
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !preg_match('/^USE\s/i', $s)
        );

        $executed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;

            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    $skipped++;
                } else {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (count($errors) > 0) {
            echo json_encode([
                'success' => false,
                'message' => count($errors) . ' errors occurred',
                'details' => implode("\n", array_slice($errors, 0, 5))
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => "Executed: $executed statements" . ($skipped > 0 ? ", Skipped: $skipped (already exists)" : ""),
                'details' => "✓ Migration completed successfully"
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

    exit;
}
