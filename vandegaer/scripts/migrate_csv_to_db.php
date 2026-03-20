<?php
/**
 * CSV naar Database Migratie Script
 *
 * Migreert data van content_tracker_enhanced.csv naar de MySQL database.
 *
 * Usage:
 *   php scripts/migrate_csv_to_db.php
 *
 * Features:
 * - Flexible CSV parsing (UTF-8 BOM support)
 * - Data validatie en transformatie
 * - Hashtag extractie en normalisatie
 * - Duplicate detection (platform_post_id)
 * - Engagement rate berekening
 * - Import history logging
 */

// Config laden
$config = require __DIR__ . '/../config/app.php';

// Database connectie
try {
    $mysqli = new mysqli(
        $config['db']['host'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['name'],
        $config['db']['port']
    );

    if ($mysqli->connect_error) {
        die("Database connectie gefaald: " . $mysqli->connect_error . "\n");
    }

    $mysqli->set_charset($config['db']['charset']);
    echo "✓ Database connectie succesvol\n";

} catch (Exception $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

// CSV bestand pad
$csvPath = __DIR__ . '/../../data/content_tracker_enhanced.csv';

if (!file_exists($csvPath)) {
    die("CSV bestand niet gevonden: $csvPath\n");
}

echo "✓ CSV bestand gevonden: $csvPath\n";

// CSV lezen met UTF-8 BOM support
$csvContent = file_get_contents($csvPath);
if ($csvContent === false) {
    die("Kan CSV bestand niet lezen\n");
}

// Remove UTF-8 BOM indien aanwezig
$csvContent = str_replace("\xEF\xBB\xBF", '', $csvContent);

// Parse CSV
$lines = array_map('str_getcsv', explode("\n", $csvContent));
$header = array_shift($lines);

echo "✓ CSV headers gevonden: " . count($header) . " kolommen\n";
echo "Headers: " . implode(', ', array_slice($header, 0, 8)) . "...\n\n";

// Kolom mapping: CSV → Database
$columnMapping = [
    'Content_ID' => 'platform_post_id',
    'Platform' => 'platform',
    'Content_Type' => 'content_type',  // wordt getransformeerd naar post_type
    'Datum' => 'posted_date',
    'Post_Tijd' => 'posted_time',
    'Caption_Kort' => 'caption',
    'Hashtags' => 'hashtags',
    'Likes' => 'likes',
    'Comments' => 'comments',
    'Shares' => 'shares',
    'Saves' => 'saves',
    'Bereik' => 'reach',
    'Impressies' => 'impressions',
    'Video_Views' => 'views',
    'Notities' => 'internal_notes',
    'Post_Onderwerp' => 'topic',
];

// Helper functies
function cleanHashtags($hashtags) {
    if (empty($hashtags) || $hashtags === 'N.v.t.') {
        return null;
    }

    // Remove # symbols en split op spaties
    $hashtags = str_replace('#', '', $hashtags);
    $hashtags = preg_replace('/\s+/', ' ', trim($hashtags));

    // Convert naar comma-separated
    return str_replace(' ', ',', $hashtags);
}

function normalizeContentType($contentType) {
    if (empty($contentType)) return null;

    $contentType = strtolower(trim($contentType));

    // Mapping
    $mapping = [
        'feed post' => 'feed_post',
        'video' => 'video',
        'reel' => 'reel',
        'stories' => 'story',
        'story' => 'story',
        'carousel' => 'carousel',
        'post' => 'post',
    ];

    return $mapping[$contentType] ?? null;
}

function normalizePlatform($platform) {
    if (empty($platform)) return null;

    $platform = strtolower(trim($platform));

    // Valid platforms
    $valid = ['tiktok', 'instagram', 'facebook'];

    return in_array($platform, $valid) ? $platform : null;
}

function calculateEngagementRate($likes, $comments, $shares, $reach) {
    if (empty($reach) || $reach == 0) {
        return null;
    }

    $engagement = ($likes + $comments + $shares) / $reach * 100;
    return round($engagement, 2);
}

// Statistieken
$stats = [
    'imported' => 0,
    'skipped' => 0,
    'failed' => 0,
    'errors' => []
];

// Start import
echo "Starting import...\n";
echo str_repeat('-', 80) . "\n";

foreach ($lines as $lineNumber => $row) {
    // Skip lege rijen
    if (empty($row) || count($row) < count($header)) {
        continue;
    }

    // Combineer header + row
    $data = array_combine($header, $row);

    // Validatie: skip rijen zonder datum (planning data, nog niet gepost)
    if (empty($data['Datum']) || $data['Datum'] === '') {
        $stats['skipped']++;
        echo "⊘ Regel " . ($lineNumber + 2) . ": Skipped (geen datum - planning data)\n";
        continue;
    }

    // Validatie: platform moet geldig zijn
    $platform = normalizePlatform($data['Platform'] ?? '');
    if (!$platform) {
        $stats['failed']++;
        $stats['errors'][] = "Regel " . ($lineNumber + 2) . ": Ongeldig platform: " . ($data['Platform'] ?? 'leeg');
        echo "✗ Regel " . ($lineNumber + 2) . ": FAILED (ongeldig platform)\n";
        continue;
    }

    // Data transformatie
    $postData = [
        'platform_post_id' => $data['Content_ID'] ?? null,
        'platform' => $platform,
        'post_type' => normalizeContentType($data['Content_Type'] ?? ''),
        'topic' => $data['Post_Onderwerp'] ?? null,
        'caption' => $data['Caption_Kort'] ?? null,
        'hashtags' => cleanHashtags($data['Hashtags'] ?? ''),
        'posted_date' => $data['Datum'] ?? null,
        'posted_time' => ($data['Post_Tijd'] ?? null) ?: null,
        'internal_notes' => $data['Notities'] ?? null,
        'views' => (int)($data['Video_Views'] ?? 0),
        'likes' => (int)($data['Likes'] ?? 0),
        'comments' => (int)($data['Comments'] ?? 0),
        'shares' => (int)($data['Shares'] ?? 0),
        'saves' => (int)($data['Saves'] ?? 0),
        'reach' => (int)($data['Bereik'] ?? 0),
        'impressions' => (int)($data['Impressies'] ?? 0),
        'import_source' => 'csv',
    ];

    // Bereken engagement rate
    $postData['engagement_rate'] = calculateEngagementRate(
        $postData['likes'],
        $postData['comments'],
        $postData['shares'],
        $postData['reach'] ?: $postData['views']  // Fallback naar views als reach leeg is
    );

    // Insert/Update query (UPSERT)
    // Als platform_post_id al bestaat, update de metrics
    $sql = "INSERT INTO posts (
        platform_post_id, platform, post_type, topic, caption, hashtags,
        posted_date, posted_time, internal_notes,
        views, likes, comments, shares, saves, reach, impressions,
        engagement_rate, import_source
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        views = VALUES(views),
        likes = VALUES(likes),
        comments = VALUES(comments),
        shares = VALUES(shares),
        saves = VALUES(saves),
        reach = VALUES(reach),
        impressions = VALUES(impressions),
        engagement_rate = VALUES(engagement_rate),
        last_updated = CURRENT_TIMESTAMP";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        $stats['failed']++;
        $stats['errors'][] = "Regel " . ($lineNumber + 2) . ": SQL prepare error: " . $mysqli->error;
        echo "✗ Regel " . ($lineNumber + 2) . ": FAILED (SQL error)\n";
        continue;
    }

    $stmt->bind_param(
        'sssssssssiiiiiidss',
        $postData['platform_post_id'],
        $postData['platform'],
        $postData['post_type'],
        $postData['topic'],
        $postData['caption'],
        $postData['hashtags'],
        $postData['posted_date'],
        $postData['posted_time'],
        $postData['internal_notes'],
        $postData['views'],
        $postData['likes'],
        $postData['comments'],
        $postData['shares'],
        $postData['saves'],
        $postData['reach'],
        $postData['impressions'],
        $postData['engagement_rate'],
        $postData['import_source']
    );

    if ($stmt->execute()) {
        $postId = $stmt->insert_id ?: $mysqli->insert_id;
        $stats['imported']++;

        echo "✓ Regel " . ($lineNumber + 2) . ": Imported - " .
             $postData['platform'] . " - " .
             ($postData['caption'] ? substr($postData['caption'], 0, 40) : 'no caption') .
             "...\n";

        // Indien dit een nieuwe insert was, ook metrics_history snapshot maken
        if ($stmt->insert_id > 0) {
            $snapshotSql = "INSERT INTO metrics_history
                (post_id, views, likes, comments, shares, saves, reach, impressions, snapshot_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $snapshotStmt = $mysqli->prepare($snapshotSql);
            if ($snapshotStmt) {
                $snapshotStmt->bind_param(
                    'iiiiiiii',
                    $postId,
                    $postData['views'],
                    $postData['likes'],
                    $postData['comments'],
                    $postData['shares'],
                    $postData['saves'],
                    $postData['reach'],
                    $postData['impressions']
                );
                $snapshotStmt->execute();
                $snapshotStmt->close();
            }
        }

    } else {
        $stats['failed']++;
        $stats['errors'][] = "Regel " . ($lineNumber + 2) . ": Execute error: " . $stmt->error;
        echo "✗ Regel " . ($lineNumber + 2) . ": FAILED (" . $stmt->error . ")\n";
    }

    $stmt->close();
}

echo str_repeat('-', 80) . "\n";

// Log import naar import_history
$importSql = "INSERT INTO import_history (filename, platform, import_type, rows_imported, rows_skipped, rows_failed)
              VALUES (?, ?, ?, ?, ?, ?)";
$importStmt = $mysqli->prepare($importSql);
if ($importStmt) {
    $filename = 'content_tracker_enhanced.csv';
    $platform = null;  // Mixed platforms
    $importType = 'csv';

    $importStmt->bind_param(
        'sssiii',
        $filename,
        $platform,
        $importType,
        $stats['imported'],
        $stats['skipped'],
        $stats['failed']
    );
    $importStmt->execute();
    $importStmt->close();
}

// Resultaten
echo "\n";
echo "╔═══════════════════════════════════════╗\n";
echo "║        IMPORT RESULTATEN              ║\n";
echo "╠═══════════════════════════════════════╣\n";
echo "║ ✓ Imported:  " . str_pad($stats['imported'], 4, ' ', STR_PAD_LEFT) . " rijen              ║\n";
echo "║ ⊘ Skipped:   " . str_pad($stats['skipped'], 4, ' ', STR_PAD_LEFT) . " rijen              ║\n";
echo "║ ✗ Failed:    " . str_pad($stats['failed'], 4, ' ', STR_PAD_LEFT) . " rijen              ║\n";
echo "╚═══════════════════════════════════════╝\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n✓ Import completed!\n";
echo "  Check database: SELECT COUNT(*) FROM posts;\n";

$mysqli->close();
