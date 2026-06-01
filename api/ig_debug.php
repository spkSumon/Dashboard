<?php
/**
 * instagram_comments_debug.php
 * Tijdelijk script om de structuur van Instagram reacties (post-comments) te bekijken.
 * Open dit in je browser, kopieer de output, en stuur die naar Claude.
 * Daarna mag je dit bestand weer verwijderen.
 */

$userId = 4394337;
$blogId = 5668624;
$token  = "YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB"; // <-- vul hier je token in zoals in je andere bestanden

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: $token",
];

$url = "https://app.metricool.com/api/v2/inbox/post-comments?" . http_build_query([
    'provider' => 'instagram',
    'userId'   => $userId,
    'blogId'   => $blogId,
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

header('Content-Type: text/plain; charset=utf-8');

echo "HTTP CODE: $httpCode\n";
echo str_repeat('=', 60) . "\n\n";

// Vind de items-array
$items = [];
if (is_array($decoded)) {
    foreach (['data', 'results', 'comments', 'items'] as $key) {
        if (isset($decoded[$key]) && is_array($decoded[$key])) {
            $items = $decoded[$key];
            echo "Items gevonden onder key: '$key'\n";
            break;
        }
    }
    if (empty($items) && array_keys($decoded) === range(0, count($decoded) - 1)) {
        $items = $decoded;
        echo "Items zijn een directe array (geen wrapper key)\n";
    }
}

echo "Aantal reacties: " . count($items) . "\n\n";
echo str_repeat('=', 60) . "\n";
echo "EERSTE 3 REACTIES (volledige structuur):\n";
echo str_repeat('=', 60) . "\n\n";

foreach (array_slice($items, 0, 3) as $i => $comment) {
    echo "--- REACTIE #" . ($i + 1) . " ---\n";
    echo json_encode($comment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n\n";
}

echo str_repeat('=', 60) . "\n";
echo "ALLE TOP-LEVEL KEYS IN EERSTE REACTIE:\n";
echo str_repeat('=', 60) . "\n";
if (!empty($items[0]) && is_array($items[0])) {
    foreach ($items[0] as $key => $val) {
        $type = is_array($val) ? 'array(' . implode(', ', array_keys($val)) . ')' : gettype($val);
        $preview = is_array($val) ? '' : ' = ' . mb_substr((string)$val, 0, 50);
        echo "  • $key  [$type]$preview\n";
    }
}