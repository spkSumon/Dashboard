<?php
/**
 * Direct credential insertion script for Metricool
 * Run this via browser: http://localhost/socialbit-live/scripts/insert_metricool_credentials.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

echo "=== METRICOOL CREDENTIALS SETUP ===\n\n";

// Load configuration
$config = require __DIR__ . '/../config/app.php';

try {
    $db = new Database($config['db']);

    // Delete existing Metricool credentials
    echo "→ Verwijderen oude Metricool credentials...\n";
    $affected = $db->exec("DELETE FROM settings WHERE `key` IN ('metricool_api_key', 'metricool_user_id', 'metricool_blog_id_giuditta')");
    echo "  Verwijderd: " . $affected . " rijen\n\n";

    // Insert new credentials
    echo "→ Toevoegen nieuwe credentials...\n";
    $db->exec("
        INSERT INTO settings (`key`, `value`, updated_at) VALUES
            ('metricool_api_key', 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB', NOW()),
            ('metricool_user_id', '4394337', NOW()),
            ('metricool_blog_id_giuditta', '5668624', NOW())
    ");

    echo "  ✅ 3 credentials toegevoegd\n\n";

    // Verify insertion
    echo "→ Verificatie van opgeslagen credentials:\n";
    $result = $db->fetchAll("
        SELECT `key`,
               CASE
                   WHEN `key` = 'metricool_api_key' THEN CONCAT(LEFT(`value`, 15), '...', RIGHT(`value`, 10))
                   ELSE `value`
               END as value_display,
               updated_at
        FROM settings
        WHERE `key` LIKE 'metricool%'
        ORDER BY `key`
    ");

    if (empty($result)) {
        echo "  ❌ FOUT: Geen credentials gevonden na insert!\n";
        exit(1);
    }

    foreach ($result as $row) {
        echo "  ✓ {$row['key']}: {$row['value_display']}\n";
        echo "    Updated: {$row['updated_at']}\n";
    }

    echo "\n✅ SETUP COMPLEET!\n\n";
    echo "Volgende stap: Test de API connectie\n";
    echo "  http://localhost/socialbit-live/scripts/test_metricool_cli.php\n\n";

    echo "⚠️  BELANGRIJK: Verwijder dit bestand na gebruik:\n";
    echo "  DELETE: scripts/insert_metricool_credentials.php\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
