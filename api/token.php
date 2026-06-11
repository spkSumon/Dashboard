<?php
/**
 * includes.php — Gedeelde configuratie en functies voor het SkyByte dashboard.
 */

$userId = 4394337;
$blogId = 5668624;
$token  = 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB';

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: {$token}",
];

/**
 * Voer een GET-request uit naar de Metricool API.
 */
function callMetricool(string $endpoint, array $params, array $headers): array {
    $url = "https://app.metricool.com/" . ltrim($endpoint, '/') . '?' . http_build_query($params);
    $ch  = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error, 'httpCode' => null, 'body' => null, 'raw' => null];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'httpCode' => $code,
        'body'     => json_decode($response, true),
        'raw'      => $response,
    ];
}

/**
 * Parseer timeline-data voor één metric uit een API-response.
 * Geeft statistieken terug (gemiddelde, mediaan, min, max, etc.).
 */
function getMetricData(array $responseBody, string $metricName): array {
    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
        return ['error' => 'Geen geldige data gevonden.'];
    }

    $metricBlock = null;
    foreach ($responseBody['data'] as $block) {
        if (($block['metric'] ?? null) === $metricName) {
            $metricBlock = $block;
            break;
        }
    }
    if ($metricBlock === null) {
        return ['error' => "Metric \"{$metricName}\" niet gevonden."];
    }
    if (empty($metricBlock['values']) || !is_array($metricBlock['values'])) {
        return ['error' => "Geen values gevonden voor \"{$metricName}\"."];
    }
    if (count($metricBlock['values']) === 0) {
        return ['empty' => true, 'metric' => $metricName, 'values' => []];
    }

    $values = $metricBlock['values'];
    usort($values, fn($a, $b) => strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? ''));

    $numericValues = array_map(fn($row) => (float)($row['value'] ?? 0), $values);
    $maxIdx = $minIdx = 0;
    foreach ($values as $i => $row) {
        $v = (float)($row['value'] ?? 0);
        if ($v > (float)($values[$maxIdx]['value'] ?? 0)) $maxIdx = $i;
        if ($v < (float)($values[$minIdx]['value'] ?? 0)) $minIdx = $i;
    }

    $sorted = $numericValues;
    sort($sorted);
    $c      = count($sorted);
    $median = $c % 2 === 0
        ? ($sorted[$c / 2 - 1] + $sorted[$c / 2]) / 2
        : $sorted[(int)floor($c / 2)];

    return [
        'metric'         => $metricName,
        'dataPointCount' => count($values),
        'averageValue'   => array_sum($numericValues) / count($numericValues),
        'medianValue'    => $median,
        'minValue'       => (float)($values[$minIdx]['value'] ?? 0),
        'maxValue'       => (float)($values[$maxIdx]['value'] ?? 0),
        'rangeValue'     => (float)($values[$maxIdx]['value'] ?? 0) - (float)($values[$minIdx]['value'] ?? 0),
        'minRow'         => $values[$minIdx],
        'maxRow'         => $values[$maxIdx],
        'values'         => $values,
    ];
}

/**
 * Formatteer een waarde: engagement als percentage, rest als geheel getal.
 */
function formatValue(float $value, string $metricKey): string {
    if ($metricKey === 'engagement') {
        return number_format($value, 2, ',', '.') . '%';
    }
    return number_format($value, 0, ',', '.');
}


function callFathom($url, $params, $apiKey) {
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Accept: application/json",
            "Connection: keep-alive"
        ],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
    ]);

    $response = curl_exec($ch);

    
    if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    error_log("cURL error: " . $error);

    return [
        'error' => true,
        'type' => 'curl',
        'message' => $error,
        'url' => $url
    ];
}

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

   
    if ($httpCode >= 400) {
        error_log("HTTP error $httpCode: $response");

        return [
            'error' => true,
            'code' => $httpCode,
            'raw' => $response
        ];
    }

    $data = json_decode($response, true);

    
    if (!is_array($data)) {
        error_log("Invalid JSON: " . $response);

        return [
            'error' => true,
            'message' => 'Invalid JSON',
            'raw' => $response
        ];
    }

    return $data;
}



function manageCache($dir, $maxAge = 3600, $maxFiles = 50) {
    if (!is_dir($dir)) return;

    $files = glob($dir . '*.json');

    
    foreach ($files as $file) {
        if (time() - filemtime($file) > $maxAge) {
            unlink($file);
        }
    }


    
    if (count($files) > $maxFiles) {
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
        $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);

        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }
}


function cachedCall($key, $callback, $ttl = 300) {
    $dir = __DIR__ . '/cache/';
    $file = $dir . md5($key) . '.json';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    
    if (rand(1, 50) === 1) {
    manageCache($dir, 3600, 50);
    }

    
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        $cached = json_decode(file_get_contents($file), true);

        if (is_array($cached)) {
            return $cached;
        }
    }

    
    $data = $callback();

    
    if (!is_array($data) || isset($data['error'])) {

        error_log("API ERROR [$key]: " . json_encode($data));

        
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return $data;
    }

    
    file_put_contents($file, json_encode($data));

    return $data;
}

function getMainDomain($hostname) {
    $hostname = preg_replace('/^(www|m|l)\./', '', $hostname);
    $parts = explode('.', $hostname);
    $count = count($parts);

    if ($count >= 3 && strlen($parts[$count - 2]) <= 3) {
        return $parts[$count - 3];
    }

    if ($count >= 2) {
        return $parts[$count - 2];
    }

    return $hostname;
}


function sortUrl(string $col, string $currentSort, string $currentOrder): string {
    $params = array_merge($_GET, [
        'sort'  => $col,
        'order' => $col === $currentSort && $currentOrder === 'asc' ? 'desc' : 'asc',
    ]);
    return '?' . http_build_query($params);
}

function getArrow(string $col, string $sort, string $order): string {
    if ($col !== $sort) return '';
    return $order === 'asc' ? ' ↓' : ' ↑';
}