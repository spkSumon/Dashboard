<?php
// Direct database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'social_media_analytics', 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all tables
$result = $conn->query('SHOW TABLES');
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

$inventory = [];

foreach ($tables as $table) {
    // Skip views (they have production user definer issues)
    $checkView = $conn->query("SHOW CREATE TABLE $table");
    $createStmt = $checkView->fetch_array()[1];
    if (stripos($createStmt, 'CREATE ALGORITHM') !== false) {
        continue; // Skip views
    }

    $columns = [];
    $colResult = $conn->query("DESCRIBE $table");
    while ($col = $colResult->fetch_assoc()) {
        $columns[] = $col['Field'];
    }

    $countResult = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    $count = $countResult->fetch_assoc()['cnt'];

    $inventory[$table] = [
        'columns' => $columns,
        'count' => $count
    ];
}

// Get sample post to see what metrics have data
$sampleResult = $conn->query('SELECT * FROM posts WHERE views > 0 LIMIT 1');
$samplePost = $sampleResult->fetch_assoc();
$availableMetrics = [];
if ($samplePost) {
    foreach ($samplePost as $key => $value) {
        if ($value !== null && $value !== '') {
            $availableMetrics[$key] = $value;
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'tables' => $inventory,
    'sample_metrics' => $availableMetrics
], JSON_PRETTY_PRINT);

$conn->close();
