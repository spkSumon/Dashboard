<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use Core\Database;

$db = Database::getInstance();

echo "=== METRICOOL KEYS CHECK ===\n\n";

$result = $db->query("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'metricool%'")->fetchAll();

if (empty($result)) {
    echo "❌ GEEN Metricool keys gevonden in settings table\n";
    exit(1);
}

echo "✅ Metricool keys gevonden:\n";
foreach ($result as $row) {
    $maskedValue = strlen($row['value']) > 10
        ? substr($row['value'], 0, 10) . '...' . substr($row['value'], -5)
        : '***';
    echo "  - {$row['key']}: {$maskedValue}\n";
}

exit(0);
