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
