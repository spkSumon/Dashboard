<?php

/**
 * Metricool API Service Layer
 * Gedeelde functies voor alle platformen (Facebook, Instagram, TikTok, ...)
 */

// ─── Configuratie ────────────────────────────────────────────────────────────

define('METRICOOL_USER_ID', 4394337);
define('METRICOOL_BLOG_ID', 5668624);
define('METRICOOL_TOKEN',   'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB');
define('METRICOOL_BASE_URL', 'https://app.metricool.com');

function getMetricoolHeaders(): array {
    return [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Mc-Auth: ' . METRICOOL_TOKEN,
    ];
}

// ─── Platform-definities ─────────────────────────────────────────────────────

/**
 * Centraliseert alle platform-specifieke configuratie.
 * network     = waarde die Metricool verwacht in de `network` param
 * label       = weergavenaam
 * color       = accent-kleur voor UI
 * sections    = beschikbare content-types met hun metrics
 * api_metric_map = vertaling van interne naam → Metricool API naam
 */
function getPlatformConfig(string $platform): array {
    $platforms = [

        'facebook' => [
            'network' => 'facebook',
            'label'   => 'Facebook',
            'color'   => '#1877F2',
            'sections' => [
                'posts' => [
                    'label'   => 'Posts',
                    'metrics' => ['engagement', 'interactions'],
                ],
                'reels' => [
                    'label'   => 'Reels',
                    'metrics' => ['engagement', 'interactions', 'video_views'],
                ],
            ],
            'api_metric_map' => [
                'engagement'   => 'engagement',
                'interactions' => 'interactions',
                'video_views'  => 'blue_reels_play_count',
            ],
        ],

        'instagram' => [
            'network' => 'instagram',
            'label'   => 'Instagram',
            'color'   => '#E1306C',
            'sections' => [
                'posts' => [
                    'label'   => 'Posts',
                    'metrics' => ['engagement', 'interactions'],
                ],
                'reels' => [
                    'label'   => 'Reels',
                    'metrics' => ['engagement', 'interactions', 'video_views'],
                ],
                'stories' => [
                    'label'   => 'Stories',
                    'metrics' => ['interactions'],
                ],
            ],
            'api_metric_map' => [
                'engagement'   => 'engagement',
                'interactions' => 'interactions',
                // Instagram reels gebruiken ig_reels_video_view_total
                'video_views'  => 'ig_reels_video_view_total',
            ],
        ],

        'tiktok' => [
            'network' => 'tiktok',
            'label'   => 'TikTok',
            'color'   => '#010101',
            'sections' => [
                'posts' => [
                    'label'   => 'Videos',
                    // TikTok heeft geen aparte reels; video_views wél beschikbaar
                    'metrics' => ['interactions', 'video_views'],
                ],
            ],
            'api_metric_map' => [
                'interactions' => 'interactions',
                // TikTok gebruikt video_views als directe naam
                'video_views'  => 'video_views',
                // Optioneel extra TikTok metrics
                'likes'        => 'likes',
                'comments'     => 'comments',
                'shares'       => 'shares',
            ],
        ],

    ];

    return $platforms[$platform] ?? [];
}

/**
 * Lijst van alle ondersteunde platformen voor de navbar.
 */
function getSupportedPlatforms(): array {
    return ['facebook', 'instagram', 'tiktok'];
}

// ─── HTTP helper ─────────────────────────────────────────────────────────────

/**
 * Generieke cURL-wrapper voor Metricool API calls.
 *
 * @param string $endpoint  bijv. '/api/v2/analytics/timelines'
 * @param array  $params    query-parameters
 * @param array  $headers   HTTP-headers
 * @return array            ['httpCode', 'body', 'raw', 'url'] of ['error' => ...]
 */
function callMetricool(string $endpoint, array $params, array $headers): array {
    $endpoint = '/' . ltrim($endpoint, '/');
    $url      = METRICOOL_BASE_URL . $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
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
        return ['error' => 'cURL-fout: ' . $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE && $response !== '') {
        return [
            'error' => 'Ongeldige JSON in response (HTTP ' . $httpCode . ')',
        ];
    }

    return [
        'httpCode' => $httpCode,
        'body'     => $decoded,
        'raw'      => $response,
        'url'      => $url,
    ];
}

// ─── Data parsing ─────────────────────────────────────────────────────────────

/**
 * Haal een specifieke metric op uit de Metricool timeline-response.
 */
function getMetricData(array $responseBody, string $metricName): array {
    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
        return ['error' => 'Geen geldige data-array in response.'];
    }

    $metricBlock = null;
    foreach ($responseBody['data'] as $block) {
        if (($block['metric'] ?? null) === $metricName) {
            $metricBlock = $block;
            break;
        }
    }

    if ($metricBlock === null) {
        return ['error' => 'Metric "' . $metricName . '" niet gevonden in response.'];
    }

    $values = $metricBlock['values'] ?? [];
    if (!is_array($values) || count($values) === 0) {
        return ['empty' => true, 'metric' => $metricName, 'values' => []];
    }

    usort($values, fn($a, $b) => strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? ''));

    $numericValues = array_map(fn($r) => (float)($r['value'] ?? 0), $values);
    $count         = count($numericValues);
    $sum           = array_sum($numericValues);

    // Min / max
    $maxIndex = $minIndex = 0;
    foreach ($values as $i => $row) {
        $v = (float)($row['value'] ?? 0);
        if ($v > (float)($values[$maxIndex]['value'] ?? 0)) $maxIndex = $i;
        if ($v < (float)($values[$minIndex]['value'] ?? 0)) $minIndex = $i;
    }

    // Mediaan
    $sorted = $numericValues;
    sort($sorted);
    $median = ($count % 2 === 0)
        ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
        : $sorted[(int)floor($count / 2)];

    return [
        'metric'         => $metricName,
        'dataPointCount' => $count,
        'averageValue'   => $sum / $count,
        'medianValue'    => $median,
        'minValue'       => (float)($values[$minIndex]['value'] ?? 0),
        'maxValue'       => (float)($values[$maxIndex]['value'] ?? 0),
        'rangeValue'     => (float)($values[$maxIndex]['value'] ?? 0) - (float)($values[$minIndex]['value'] ?? 0),
        'minIndex'       => $minIndex,
        'maxIndex'       => $maxIndex,
        'minRow'         => $values[$minIndex],
        'maxRow'         => $values[$maxIndex],
        'values'         => $values,
    ];
}

// ─── Formattering ─────────────────────────────────────────────────────────────

function formatMetricLabel(string $metricName): string {
    return [
        'engagement'   => 'Engagement',
        'interactions' => 'Interacties',
        'video_views'  => 'Video views',
        'likes'        => 'Likes',
        'comments'     => 'Comments',
        'shares'       => 'Shares',
    ][$metricName] ?? ucfirst(str_replace('_', ' ', $metricName));
}

function formatMetricValue($value, string $metricName): string {
    if ($metricName === 'engagement') {
        return number_format((float)$value, 2, ',', '.') . '%';
    }
    return number_format((float)$value, 0, ',', '.');
}

// ─── API loader ───────────────────────────────────────────────────────────────

/**
 * Laadt alle data voor een platform + datum-range.
 * Retourneert:
 *   resultsBySection[$section][$metric]['parsed']  → parsed data of null
 *   errorsBySection[$section][$metric]             → foutmelding string
 *   emptyBySection[$section][$metric]              → "geen data" melding
 */
function loadPlatformData(
    string $platform,
    string $fromIso,
    string $toIso,
    string $sectionMode,
    string $metricMode,
    string $timezone = 'Europe/Brussels'
): array {
    $config  = getPlatformConfig($platform);
    $headers = getMetricoolHeaders();

    if (empty($config)) {
        return [
            'globalError'      => 'Platform "' . $platform . '" wordt niet ondersteund.',
            'resultsBySection' => [],
            'errorsBySection'  => [],
            'emptyBySection'   => [],
        ];
    }

    // Bepaal welke secties we laden
    $allSections = array_keys($config['sections']);
    if ($sectionMode === 'all' || $sectionMode === 'both') {
        $sectionsToLoad = $allSections;
    } elseif (in_array($sectionMode, $allSections, true)) {
        $sectionsToLoad = [$sectionMode];
    } else {
        $sectionsToLoad = $allSections;
    }

    $resultsBySection = [];
    $errorsBySection  = [];
    $emptyBySection   = [];

    foreach ($sectionsToLoad as $sectionKey) {
        $sectionDef = $config['sections'][$sectionKey] ?? null;
        if (!$sectionDef) continue;

        // Bepaal metrics voor deze sectie op basis van metricMode
        $allMetrics = $sectionDef['metrics'];
        if ($metricMode === 'engagement') {
            $metrics = in_array('engagement', $allMetrics) ? ['engagement'] : $allMetrics;
        } elseif ($metricMode === 'interactions') {
            $metrics = in_array('interactions', $allMetrics) ? ['interactions'] : $allMetrics;
        } else {
            $metrics = $allMetrics; // 'both' / 'all'
        }

        $resultsBySection[$sectionKey] = [];
        $errorsBySection[$sectionKey]  = [];
        $emptyBySection[$sectionKey]   = [];

        foreach ($metrics as $metricName) {
            $apiMetricName = $config['api_metric_map'][$metricName] ?? $metricName;

            $params = [
                'from'     => $fromIso,
                'to'       => $toIso,
                'network'  => $config['network'],
                'timezone' => $timezone,
                'subject'  => $sectionKey,
                'metric'   => $apiMetricName,
                'userId'   => METRICOOL_USER_ID,
                'blogId'   => METRICOOL_BLOG_ID,
            ];

            $result = callMetricool('/api/v2/analytics/timelines', $params, $headers);

            $resultsBySection[$sectionKey][$metricName] = [
                'request'       => $result,
                'parsed'        => null,
                'apiMetricName' => $apiMetricName,
            ];

            if (isset($result['error'])) {
                $errorsBySection[$sectionKey][$metricName] = $result['error'];
                continue;
            }

            if (($result['httpCode'] ?? 0) !== 200) {
                $errorsBySection[$sectionKey][$metricName] =
                    'HTTP ' . ($result['httpCode'] ?? '?') . ' voor ' . $metricName . ' (' . $sectionKey . ')';
                continue;
            }

            $parsed = getMetricData($result['body'], $apiMetricName);

            if (isset($parsed['error'])) {
                $errorsBySection[$sectionKey][$metricName] = $parsed['error'];
                continue;
            }

            if (!empty($parsed['empty'])) {
                $emptyBySection[$sectionKey][$metricName] =
                    'Geen data voor ' . formatMetricLabel($metricName) . ' in ' . $sectionKey . '.';
                continue;
            }

            $resultsBySection[$sectionKey][$metricName]['parsed'] = $parsed;
        }
    }

    return [
        'globalError'      => null,
        'config'           => $config,
        'sectionsToLoad'   => $sectionsToLoad,
        'resultsBySection' => $resultsBySection,
        'errorsBySection'  => $errorsBySection,
        'emptyBySection'   => $emptyBySection,
    ];
}
