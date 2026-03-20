<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use Core\Database;

$db = Database::getInstance();

echo "=== METRICOOL SETUP ===\n\n";

// Delete existing
echo "→ Verwijderen oude credentials...\n";
$db->query("DELETE FROM settings WHERE `key` IN ('metricool_api_key', 'metricool_user_id', 'metricool_blog_id_giuditta')");

// Insert new
echo "→ Toevoegen nieuwe credentials...\n";
$db->query("
    INSERT INTO settings (`key`, `value`, updated_at) VALUES
      ('metricool_api_key', 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB', NOW()),
      ('metricool_user_id', '4394337', NOW()),
      ('metricool_blog_id_giuditta', '5668624', NOW())
");

echo "✅ Credentials opgeslagen!\n\n";

// Verify
echo "→ Verificatie:\n";
$result = $db->query("
    SELECT `key`, LEFT(`value`, 20) as value_preview, updated_at
    FROM settings
    WHERE `key` LIKE 'metricool%'
")->fetchAll();

foreach ($result as $row) {
    echo "  ✓ {$row['key']}: {$row['value_preview']}...\n";
}

echo "\n→ Klaar! Test nu met:\n";
echo "   php scripts/test_metricool_api.php\n";
