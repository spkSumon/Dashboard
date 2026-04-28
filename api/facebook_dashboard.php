<?php

$userId = 4394337;
$blogId = 5668624;
$token  = 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB';

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: {$token}",
];

function callMetricool($endpoint, $params, $headers) {
    $baseUrl = "https://app.metricool.com";
    $endpoint = '/' . ltrim($endpoint, '/');
    $url = $baseUrl . $endpoint . '?' . http_build_query($params);

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

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'httpCode' => $httpCode,
        'body'     => json_decode($response, true),
        'raw'      => $response,
        'url'      => $url,
    ];
}

function getMetricData($responseBody, $metricName) {
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
        return ['error' => 'Metric "' . $metricName . '" niet gevonden in de response.'];
    }

    if (!isset($metricBlock['values']) || !is_array($metricBlock['values'])) {
        return ['error' => 'Geen geldige values-array gevonden voor metric "' . $metricName . '".'];
    }

    if (count($metricBlock['values']) === 0) {
        return [
            'empty'  => true,
            'metric' => $metricName,
            'values' => []
        ];
    }

    $values = $metricBlock['values'];

    usort($values, function ($a, $b) {
        return strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? '');
    });

    $numericValues = array_map(function ($row) {
        return (float) ($row['value'] ?? 0);
    }, $values);

    $maxIndex = 0;
    $minIndex = 0;

    foreach ($values as $index => $row) {
        $currentValue = (float) ($row['value'] ?? 0);
        $maxValue = (float) ($values[$maxIndex]['value'] ?? 0);
        $minValue = (float) ($values[$minIndex]['value'] ?? 0);

        if ($currentValue > $maxValue) {
            $maxIndex = $index;
        }

        if ($currentValue < $minValue) {
            $minIndex = $index;
        }
    }

    $sortedNumericValues = $numericValues;
    sort($sortedNumericValues);
    $count = count($sortedNumericValues);

    if ($count % 2 === 0) {
        $medianValue = ($sortedNumericValues[$count / 2 - 1] + $sortedNumericValues[$count / 2]) / 2;
    } else {
        $medianValue = $sortedNumericValues[(int) floor($count / 2)];
    }

    return [
        'metric'         => $metricName,
        'dataPointCount' => count($values),
        'averageValue'   => array_sum($numericValues) / count($numericValues),
        'medianValue'    => $medianValue,
        'minValue'       => (float) ($values[$minIndex]['value'] ?? 0),
        'maxValue'       => (float) ($values[$maxIndex]['value'] ?? 0),
        'rangeValue'     => (float) ($values[$maxIndex]['value'] ?? 0) - (float) ($values[$minIndex]['value'] ?? 0),
        'minIndex'       => $minIndex,
        'maxIndex'       => $maxIndex,
        'minRow'         => $values[$minIndex],
        'maxRow'         => $values[$maxIndex],
        'values'         => $values,
    ];
}

function formatMetricLabel($metricName) {
    $labels = [
        'engagement'   => 'Engagement',
        'interactions' => 'Interacties',
        'video_views'  => 'Video views',
    ];

    return $labels[$metricName] ?? ucfirst(str_replace('_', ' ', $metricName));
}

function formatMetricValue($value, $metricName) {
    if ($metricName === 'engagement') {
        return number_format((float) $value, 2, ',', '.') . '%';
    }

    if ($metricName === 'interactions' || $metricName === 'video_views') {
        return number_format((float) $value, 0, ',', '.');
    }

    return number_format((float) $value, 2, ',', '.');
}

function formatSectionLabel($sectionKey) {
    $labels = [
        'posts' => 'Posts',
        'reels' => 'Reels',
    ];

    return $labels[$sectionKey] ?? ucfirst($sectionKey);
}

function getMetricsForSection($sectionKey, $metricMode) {
    $baseMetrics = [];

    if ($metricMode === 'engagement') {
        $baseMetrics = ['engagement'];
    } elseif ($metricMode === 'interactions') {
        $baseMetrics = ['interactions'];
    } else {
        $baseMetrics = ['engagement', 'interactions'];
    }

    if ($sectionKey === 'reels') {
        $baseMetrics[] = 'video_views';
    }

    return $baseMetrics;
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$testMode = $_GET['test'] ?? 'timeline';
$metricMode = $_GET['metric_mode'] ?? 'engagement';
$sectionMode = $_GET['section_mode'] ?? 'both';

$allowedMetricModes = ['engagement', 'interactions', 'both'];
if (!in_array($metricMode, $allowedMetricModes, true)) {
    $metricMode = 'engagement';
}

$allowedSectionModes = ['posts', 'reels', 'both'];
if (!in_array($sectionMode, $allowedSectionModes, true)) {
    $sectionMode = 'both';
}

$globalErrors = [];

if ($token === '') {
    $globalErrors[] = 'Geen Metricool token ingesteld.';
}

if ($from > $to) {
    $globalErrors[] = 'De startdatum mag niet later zijn dan de einddatum.';
}

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to . 'T23:59:59+01:00';

$configurations = [
    'timeline' => [
        'endpoint' => '/api/v2/analytics/timelines',
        'params' => [
            'from'     => $fromIso,
            'to'       => $toIso,
            'network'  => 'facebook',
            'timezone' => 'Europe/Brussels',
            'userId'   => $userId,
            'blogId'   => $blogId,
        ]
    ],
    'timeline_profile' => [
        'endpoint' => '/api/v2/analytics/timelines',
        'params' => [
            'from'     => $fromIso,
            'to'       => $toIso,
            'network'  => 'facebook',
            'timezone' => 'Europe/Brussels',
            'subject'  => 'profile',
            'userId'   => $userId,
            'blogId'   => $blogId,
        ]
    ],
    'metrics' => [
        'endpoint' => '/api/v2/analytics/metrics',
        'params' => [
            'from'     => $fromIso,
            'to'       => $toIso,
            'network'  => 'facebook',
            'timezone' => 'Europe/Brussels',
            'userId'   => $userId,
            'blogId'   => $blogId,
        ]
    ],
];

$config = $configurations[$testMode] ?? $configurations['timeline'];

$availableSections = [
    'posts' => [
        'label' => 'Posts',
        'params' => [
            'subject' => 'posts',
        ]
    ],
    'reels' => [
        'label' => 'Reels',
        'params' => [
            'subject' => 'reels',
        ]
    ],
];

$metricApiMap = [
    'engagement'   => 'engagement',
    'interactions' => 'interactions',
    'video_views'  => 'blue_reels_play_count',
];

$sectionsToLoad = [];
if ($sectionMode === 'both') {
    $sectionsToLoad = ['posts', 'reels'];
} else {
    $sectionsToLoad = [$sectionMode];
}

$resultsBySection = [];
$errorsBySection = [];
$emptyBySection = [];

if (empty($globalErrors)) {
    foreach ($sectionsToLoad as $sectionKey) {
        if (!isset($availableSections[$sectionKey])) {
            continue;
        }

        $resultsBySection[$sectionKey] = [];
        $errorsBySection[$sectionKey] = [];
        $emptyBySection[$sectionKey] = [];

        $sectionParams = $config['params'];
        if (isset($availableSections[$sectionKey]['params'])) {
            $sectionParams = array_merge($sectionParams, $availableSections[$sectionKey]['params']);
        }

        $metrics = getMetricsForSection($sectionKey, $metricMode);

        foreach ($metrics as $metricName) {
            $apiMetricName = $metricApiMap[$metricName] ?? $metricName;

            $params = array_merge($sectionParams, [
                'metric' => $apiMetricName
            ]);

            $result = callMetricool($config['endpoint'], $params, $headers);

            $resultsBySection[$sectionKey][$metricName] = [
                'request' => $result,
                'parsed'  => null,
                'apiMetricName' => $apiMetricName,
            ];

            if (isset($result['error'])) {
                $errorsBySection[$sectionKey][$metricName] = $result['error'];
                continue;
            }

            if (($result['httpCode'] ?? 0) !== 200) {
                $errorsBySection[$sectionKey][$metricName] = 'HTTP-fout voor ' . $metricName . ' in ' . $sectionKey . ': ' . ($result['httpCode'] ?? 'onbekend');
                continue;
            }

            $parsed = getMetricData($result['body'], $apiMetricName);

            if (isset($parsed['error'])) {
                $errorsBySection[$sectionKey][$metricName] = $parsed['error'];
                continue;
            }

            if (!empty($parsed['empty'])) {
                $emptyBySection[$sectionKey][$metricName] = 'Geen data gevonden voor ' . formatMetricLabel($metricName) . ' in ' . formatSectionLabel($sectionKey) . '.';
                continue;
            }

            $resultsBySection[$sectionKey][$metricName]['parsed'] = $parsed;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Metric Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1300px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header, form, .card, .section-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            margin-top: 0;
            color: #333;
        }
        .test-mode {
            display: inline-block;
            padding: 6px 12px;
            background: #e3f2fd;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #1976d2;
        }
        .form-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 180px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
            font-size: 14px;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        button {
            background: #1976d2;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .error {
            background: #ffebee;
            border-left: 4px solid #c62828;
            padding: 15px;
            border-radius: 8px;
            color: #b71c1c;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 15px;
            border-radius: 8px;
            color: #0d47a1;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #f57c00;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .endpoint-info {
            background: #e8f5e9;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 15px;
        }
        .stat-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #1976d2;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 24px;
            color: #333;
            font-weight: 700;
        }
        .stat-subtext {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
        }
        .positive { color: #2e7d32; }
        .negative { color: #c62828; }
        .neutral { color: #f57c00; }
        .data-point-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #1976d2;
            margin-left: 10px;
        }
        .metric-block {
            margin-bottom: 30px;
        }
        .section-card {
            border: 2px solid #e3f2fd;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .section-pill {
            display: inline-block;
            padding: 6px 12px;
            background: #1976d2;
            color: white;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
            color: #555;
            font-size: 13px;
            text-transform: uppercase;
        }
        .row-best {
            background: #f1f8e9;
        }
        .row-worst {
            background: #ffebee;
        }
        .badge-best {
            color: #2e7d32;
            font-weight: 700;
            margin-left: 6px;
        }
        .badge-worst {
            color: #c62828;
            font-weight: 700;
            margin-left: 6px;
        }
        .debug-box {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 12px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
        }
        .debug-box pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        code {
            word-break: break-all;
        }
        .navbar {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            margin-right: 20px;
            font-weight: 600;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            border-bottom: 2px solid #1976d2;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="facebook_dashboard.php" class="nav-link active">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="#" class="nav-link">TikTok</a>
        <a href="#" class="nav-link">Google Business</a>
        <a href="config.php" class="nav-link">Inbox</a>
    </nav>

    <div class="header">
        <h1>Facebook Metrics Dashboard</h1>
        <span class="test-mode">
            Modus: <?= htmlspecialchars($testMode) ?> |
            Metrics: <?= htmlspecialchars($metricMode) ?> |
            Secties: <?= htmlspecialchars($sectionMode) ?>
        </span>
    </div>

    <?php foreach ($globalErrors as $globalError): ?>
        <div class="error"><?= htmlspecialchars($globalError) ?></div>
    <?php endforeach; ?>

    <form method="get">
        <div class="form-row">
            <div class="form-group">
                <label for="from">Van datum</label>
                <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>

            <div class="form-group">
                <label for="to">Tot datum</label>
                <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>

            <div class="form-group">
                <label for="test">API Configuratie</label>
                <select id="test" name="test">
                    <option value="timeline" <?= $testMode === 'timeline' ? 'selected' : '' ?>>Timeline</option>
                    <option value="timeline_profile" <?= $testMode === 'timeline_profile' ? 'selected' : '' ?>>Timeline (profile)</option>
                    <option value="metrics" <?= $testMode === 'metrics' ? 'selected' : '' ?>>Metrics endpoint</option>
                </select>
            </div>

            <div class="form-group">
                <label for="metric_mode">Welke metric tonen</label>
                <select id="metric_mode" name="metric_mode">
                    <option value="engagement" <?= $metricMode === 'engagement' ? 'selected' : '' ?>>Alleen engagement</option>
                    <option value="interactions" <?= $metricMode === 'interactions' ? 'selected' : '' ?>>Alleen interacties</option>
                    <option value="both" <?= $metricMode === 'both' ? 'selected' : '' ?>>Beide</option>
                </select>
            </div>

            <div class="form-group">
                <label for="section_mode">Welke secties tonen</label>
                <select id="section_mode" name="section_mode">
                    <option value="posts" <?= $sectionMode === 'posts' ? 'selected' : '' ?>>Alleen posts</option>
                    <option value="reels" <?= $sectionMode === 'reels' ? 'selected' : '' ?>>Alleen reels</option>
                    <option value="both" <?= $sectionMode === 'both' ? 'selected' : '' ?>>Posts + reels</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit">Data ophalen</button>
            </div>
        </div>
    </form>

    <div class="endpoint-info">
        <strong>Basis endpoint:</strong> <?= htmlspecialchars($config['endpoint']) ?><br>
        <?php foreach ($sectionsToLoad as $sectionKey): ?>
            <?php foreach (($resultsBySection[$sectionKey] ?? []) as $metricName => $metricResult): ?>
                <strong><?= htmlspecialchars(formatSectionLabel($sectionKey)) ?> - <?= htmlspecialchars(formatMetricLabel($metricName)) ?> URL:</strong>
                <code><?= htmlspecialchars($metricResult['request']['url'] ?? '-') ?></code><br>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <?php foreach ($sectionsToLoad as $sectionKey): ?>
        <div class="section-card">
            <div class="section-header">
                <h2><?= htmlspecialchars(formatSectionLabel($sectionKey)) ?></h2>
                <span class="section-pill"><?= htmlspecialchars(formatSectionLabel($sectionKey)) ?></span>
            </div>

            <?php foreach (($resultsBySection[$sectionKey] ?? []) as $metricName => $metricResult): ?>
                <?php
                    $data  = $metricResult['parsed'] ?? null;
                    $error = $errorsBySection[$sectionKey][$metricName] ?? null;
                    $empty = $emptyBySection[$sectionKey][$metricName] ?? null;
                ?>

                <div class="metric-block">
                    <?php if ($error): ?>
                        <div class="error">
                            <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?>:</strong>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php elseif ($empty): ?>
                        <div class="info">
                            <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?>:</strong>
                            <?= htmlspecialchars($empty) ?>
                        </div>
                    <?php elseif ($data): ?>

                        <?php if ($data['dataPointCount'] === 1): ?>
                            <div class="warning">
                                Slechts één datapunt gevonden voor <?= htmlspecialchars(formatMetricLabel($metricName)) ?> in <?= htmlspecialchars(formatSectionLabel($sectionKey)) ?>.
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <h3>
                                <?= htmlspecialchars(formatMetricLabel($metricName)) ?> overzicht
                                <span class="data-point-badge"><?= $data['dataPointCount'] ?> datapunt(en)</span>
                            </h3>

                            <p style="color:#666; margin-bottom:20px;">
                                <strong>Periode:</strong> <?= htmlspecialchars($from) ?> tot <?= htmlspecialchars($to) ?>
                            </p>

                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-label">Aantal datapunt(en)</div>
                                    <div class="stat-value"><?= (int) $data['dataPointCount'] ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Gemiddelde</div>
                                    <div class="stat-value"><?= formatMetricValue($data['averageValue'], $metricName) ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Mediaan</div>
                                    <div class="stat-value"><?= formatMetricValue($data['medianValue'], $metricName) ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Hoogste waarde</div>
                                    <div class="stat-value positive"><?= formatMetricValue($data['maxValue'], $metricName) ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Beste datapunt</div>
                                    <div class="stat-value">#<?= $data['maxIndex'] + 1 ?></div>
                                    <div class="stat-subtext"><?= htmlspecialchars($data['maxRow']['dateTime'] ?? '-') ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Laagste waarde</div>
                                    <div class="stat-value negative"><?= formatMetricValue($data['minValue'], $metricName) ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Zwakste datapunt</div>
                                    <div class="stat-value">#<?= $data['minIndex'] + 1 ?></div>
                                    <div class="stat-subtext"><?= htmlspecialchars($data['minRow']['dateTime'] ?? '-') ?></div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-label">Spreiding</div>
                                    <div class="stat-value neutral"><?= formatMetricValue($data['rangeValue'], $metricName) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <h3><?= htmlspecialchars(formatMetricLabel($metricName)) ?> per datapunt</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Datum & Tijd</th>
                                        <th><?= htmlspecialchars(formatMetricLabel($metricName)) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['values'] as $index => $row): ?>
                                        <?php
                                            $isBest = $index === $data['maxIndex'];
                                            $isWorst = $index === $data['minIndex'];
                                            $rowClass = $isBest ? 'row-best' : ($isWorst ? 'row-worst' : '');
                                        ?>
                                        <tr class="<?= $rowClass ?>">
                                            <td>
                                                <?= $index + 1 ?>
                                                <?php if ($isBest): ?>
                                                    <span class="badge-best">🏆 Beste</span>
                                                <?php elseif ($isWorst): ?>
                                                    <span class="badge-worst">⚠ Zwakste</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['dateTime'] ?? '-') ?></td>
                                            <td><strong><?= formatMetricValue($row['value'] ?? 0, $metricName) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="card">
        <h2>Debug</h2>

        <?php foreach ($sectionsToLoad as $sectionKey): ?>
            <?php foreach (($resultsBySection[$sectionKey] ?? []) as $metricName => $metricResult): ?>
                <div class="debug-box">
                    <strong>
                        <?= htmlspecialchars(formatSectionLabel($sectionKey)) ?>
                        -
                        <?= htmlspecialchars(formatMetricLabel($metricName)) ?>
                    </strong>
                    <pre><?php var_dump($metricResult['request']['body'] ?? null); ?></pre>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

</body>
</html>