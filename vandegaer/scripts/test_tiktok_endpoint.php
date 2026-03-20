<?php
/**
 * Metricool API - TikTok Endpoint Discovery
 *
 * Test different TikTok endpoint variations to find the correct one
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/SettingsRepository.php';

use App\Core\Database;
use App\Repositories\SettingsRepository;

$config = require __DIR__ . '/../config/app.php';
$db = new Database($config['db']);
$settingsRepo = new SettingsRepository($db);

$apiKey = $settingsRepo->get('metricool_api_key')['value'];
$userId = $settingsRepo->get('metricool_user_id')['value'];
$blogId = '5668624';

echo "==========================================\n";
echo "  TIKTOK ENDPOINT DISCOVERY\n";
echo "==========================================\n\n";

$endDate = date('Ymd');
$startDate = date('Ymd', strtotime('-90 days'));

// Test different TikTok endpoint variations
$endpoints = [
    '/stats/tiktok/posts',
    '/stats/tiktok/videos',
    '/stats/tiktok/content',
    '/stats/TikTok/posts',
    '/stats/tiktok',
    '/tiktok/posts',
    '/posts/tiktok',
];

foreach ($endpoints as $endpoint) {
    echo "Testing: {$endpoint}\n";

    $url = "https://app.metricool.com/api{$endpoint}?userId={$userId}&blogId={$blogId}&start={$startDate}&end={$endDate}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Mc-Auth: ' . $apiKey,
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "  HTTP Code: {$httpCode}\n";

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && !isset($data['error'])) {
            echo "  ✓ SUCCESS! Found working endpoint\n";
            echo "  Response structure:\n";
            if (isset($data['data']) && is_array($data['data'])) {
                echo "    Posts count: " . count($data['data']) . "\n";
                if (!empty($data['data'])) {
                    echo "    Sample post fields: " . implode(', ', array_keys($data['data'][0])) . "\n";
                    echo "\n    Full sample post:\n";
                    echo json_encode($data['data'][0], JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "    " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
            }
            break;
        } else {
            echo "  Response: " . substr($response, 0, 100) . "...\n";
        }
    } else {
        echo "  Error: " . substr($response, 0, 100) . "\n";
    }
    echo "\n";
}

// Also test the Metricool API documentation endpoint
echo "\n--- Testing API documentation endpoint ---\n";
$url = "https://app.metricool.com/api/swagger.json";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$swagger = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $swaggerData = json_decode($swagger, true);
    if ($swaggerData && isset($swaggerData['paths'])) {
        echo "✓ Swagger documentation found\n";
        echo "Searching for TikTok endpoints...\n\n";

        foreach ($swaggerData['paths'] as $path => $methods) {
            if (stripos($path, 'tiktok') !== false) {
                echo "Found: {$path}\n";
                if (isset($methods['get'])) {
                    echo "  Method: GET\n";
                    if (isset($methods['get']['summary'])) {
                        echo "  Summary: {$methods['get']['summary']}\n";
                    }
                    if (isset($methods['get']['parameters'])) {
                        echo "  Parameters: ";
                        $params = array_map(function($p) { return $p['name'] ?? '?'; }, $methods['get']['parameters']);
                        echo implode(', ', $params) . "\n";
                    }
                }
                echo "\n";
            }
        }
    }
} else {
    echo "✗ Could not fetch Swagger documentation\n";
}

echo "==========================================\n";
