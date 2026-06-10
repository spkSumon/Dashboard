<?php

$userId = 4394337;
$blogId = 5668624;
$token  = 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB'; // Vul hier je Metricool-token in. Belangrijk: vóór $headers.

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

        return [
            'error'    => $error,
            'httpCode' => null,
            'body'     => null,
            'raw'      => null,
            'url'      => $url,
        ];
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'httpCode' => $code,
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
        $available = [];
        foreach ($responseBody['data'] as $block) {
            if (isset($block['metric'])) {
                $available[] = $block['metric'];
            }
        }

        return [
            'error' => 'Metric "' . $metricName . '" niet gevonden.' .
                (!empty($available) ? ' Beschikbaar in response: ' . implode(', ', $available) : '')
        ];
    }

    if (!isset($metricBlock['values']) || !is_array($metricBlock['values'])) {
        return ['error' => 'Geen values gevonden voor "' . $metricName . '".'];
    }

    if (count($metricBlock['values']) === 0) {
        return [
            'empty'  => true,
            'metric' => $metricName,
            'values' => [],
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

    foreach ($values as $i => $row) {
        $v = (float) ($row['value'] ?? 0);

        if ($v > (float) ($values[$maxIndex]['value'] ?? 0)) {
            $maxIndex = $i;
        }

        if ($v < (float) ($values[$minIndex]['value'] ?? 0)) {
            $minIndex = $i;
        }
    }

    $sorted = $numericValues;
    sort($sorted);
    $count = count($sorted);

    if ($count % 2 === 0) {
        $median = ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
    } else {
        $median = $sorted[(int) floor($count / 2)];
    }

    return [
        'metric'         => $metricName,
        'dataPointCount' => count($values),
        'averageValue'   => array_sum($numericValues) / count($numericValues),
        'medianValue'    => $median,
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

function formatValue($value, $metricKey) {
    if ($metricKey === 'engagement') {
        return number_format((float) $value, 2, ',', '.') . '%';
    }

    return number_format((float) $value, 0, ',', '.');
}

function shortApiError($raw) {
    if (!$raw) {
        return 'Geen response ontvangen.';
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        if (!empty($decoded['detail'])) {
            return $decoded['detail'];
        }
        if (!empty($decoded['title'])) {
            return $decoded['title'];
        }
        if (!empty($decoded['status'])) {
            return $decoded['status'];
        }
    }

    return mb_substr((string) $raw, 0, 300);
}

$debug = isset($_GET['debug']);

$from = $_POST['from'] ?? date('Y-m-01');
$to   = $_POST['to']   ?? date('Y-m-d');

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

$availableMetrics = [
    'video' => [
        [
            'key' => 'videoviews',
            'label' => 'Video Views',
            'api' => 'videoviews',
            'api_candidates' => ['videoviews', 'video_views', 'videoViews', 'views'],
            'color' => '#000000',
            'bg' => '#f5f5f5'
        ],
        [
            'key' => 'engagement',
            'label' => 'Engagement',
            'api' => 'engagement',
            'api_candidates' => ['engagement'],
            'color' => '#ff0050',
            'bg' => '#fff0f5'
        ],
        [
            'key' => 'likes',
            'label' => 'Likes',
            'api' => 'likes',
            'api_candidates' => ['likes'],
            'color' => '#ff0050',
            'bg' => '#fff0f5'
        ],
        [
            'key' => 'comments',
            'label' => 'Reacties',
            'api' => 'comments',
            'api_candidates' => ['comments'],
            'color' => '#3366ff',
            'bg' => '#f0f7ff'
        ],
        [
            'key' => 'shares',
            'label' => 'Delingen',
            'api' => 'shares',
            'api_candidates' => ['shares'],
            'color' => '#00b4d8',
            'bg' => '#f0fafb'
        ],
    ],
];

$metricLookup = [];
foreach ($availableMetrics as $section => $metrics) {
    foreach ($metrics as $metric) {
        $metricLookup[$section][$metric['key']] = $metric;
    }
}

$selectedMetricsJson = $_POST['selected_metrics'] ?? '[]';
$selectedMetrics = json_decode($selectedMetricsJson, true);

if (!is_array($selectedMetrics)) {
    $selectedMetrics = [];
}

$selectedMetrics = array_values(array_unique(array_filter($selectedMetrics, function ($metricKey) {
    return is_string($metricKey) && trim($metricKey) !== '';
})));

$selectedSection = 'video';
$networkCandidates = ['tiktok'];

$metricsData = [];
$metricErrors = [];
$debugAttempts = [];

if (!empty($selectedMetrics)) {
    foreach ($selectedMetrics as $metricKey) {
        $metricInfo = $metricLookup[$selectedSection][$metricKey] ?? null;

        if (!$metricInfo) {
            $metricErrors[$metricKey] = 'Deze metric bestaat niet in metricLookup.';
            continue;
        }

        if ($token === '') {
            $metricErrors[$metricKey] = 'Geen Metricool-token ingesteld.';
            continue;
        }

        $apiCandidates = $metricInfo['api_candidates'] ?? [$metricInfo['api']];
        $loaded = false;
        $lastError = '';

        foreach ($networkCandidates as $networkCandidate) {
            foreach ($apiCandidates as $apiMetricName) {
                $params = [
                    'from'     => $fromIso,
                    'to'       => $toIso,
                    'network'  => $networkCandidate,
                    'timezone' => 'Europe/Brussels',
                    'userId'   => $userId,
                    'blogId'   => $blogId,
                    'subject'  => 'video',
                    'metric'   => $apiMetricName,
                ];

                $result = callMetricool('/api/v2/analytics/timelines', $params, $headers);

                $debugAttempts[] = [
                    'metricKey' => $metricKey,
                    'apiMetric' => $apiMetricName,
                    'network' => $networkCandidate,
                    'httpCode' => $result['httpCode'] ?? null,
                    'url' => $result['url'] ?? '',
                    'raw' => $result['raw'] ?? '',
                    'error' => $result['error'] ?? null,
                ];

                if (isset($result['error'])) {
                    $lastError = 'cURL-fout: ' . $result['error'];
                    continue;
                }

                if (($result['httpCode'] ?? 0) !== 200) {
                    $lastError = 'API gaf HTTP ' . ($result['httpCode'] ?? 'onbekend') . ': ' . shortApiError($result['raw'] ?? '');
                    continue;
                }

                $parsed = getMetricData($result['body'], $apiMetricName);

                if (isset($parsed['error'])) {
                    $lastError = $parsed['error'];
                    continue;
                }

                if (!empty($parsed['empty'])) {
                    $lastError = 'Geen data gevonden voor deze metric in deze periode.';
                    continue;
                }

                $metricInfo['api'] = $apiMetricName;
                $metricInfo['network'] = $networkCandidate;

                $metricsData[$metricKey] = [
                    'info' => $metricInfo,
                    'data' => $parsed,
                ];

                $loaded = true;
                break 2;
            }
        }

        if (!$loaded) {
            $metricErrors[$metricKey] = $lastError ?: 'Metric kon niet geladen worden.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok — SkyByte</title>
    <link rel="stylesheet" href="tst/styles_existing_updated.css">
</head>
<body>

<div class="sb-page">

    <nav class="navbar">
        <a href="config.php" class="nav-link">Inbox</a>
        <a href="facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="tiktok_dashboard.php" class="nav-link active">TikTok</a>
        <a href="../fathem/fathom-info2.php" class="nav-link">Fathom Analytics</a>
    </nav>

    <p class="sb-title">TikTok</p>
    <p class="sb-subtitle">Sleep metrics naar het overzicht om te analyseren</p>

    <form method="POST" id="dashboardForm">
        <input type="hidden" name="selected_metrics" id="selectedMetrics" value="<?= htmlspecialchars(json_encode($selectedMetrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">

        <div class="sb-toolbar">
            <label>Van</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            <label>Tot</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>

        <div class="sb-layout">

            <div class="sb-sidebar">
                <div class="sb-sidebar-title">Metrics</div>
                <div id="metricsList">
                    <?php foreach ($availableMetrics['video'] as $metric): ?>
                        <div class="sb-chip" draggable="true" data-metric="<?= htmlspecialchars($metric['key']) ?>"
                             style="border-left: 3px solid <?= htmlspecialchars($metric['color']) ?>;">
                            <?= htmlspecialchars($metric['label']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sb-canvas <?= (!empty($metricsData) || !empty($metricErrors)) ? 'has-content' : '' ?>" id="dropZone">

                <?php if (!empty($metricErrors)): ?>
                    <div class="warning">
                        <strong>Metric geselecteerd, maar niet getoond:</strong>
                        <ul>
                            <?php foreach ($metricErrors as $metricKey => $error): ?>
                                <li>
                                    <strong><?= htmlspecialchars($metricKey) ?>:</strong>
                                    <?= htmlspecialchars($error) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($metricsData)): ?>
                    <div class="sb-empty">
                        <p>Sleep een metric hierheen</p>
                        <p style="font-size:12px; color:#c8d0de;">Kies links wat je wilt zien</p>
                    </div>

                <?php else: ?>
                    <div class="sb-cards" id="metricsDisplay">
                        <?php foreach ($metricsData as $key => $metric):
                            $color = htmlspecialchars($metric['info']['color'] ?? '#ff0050');
                            $bg    = htmlspecialchars($metric['info']['bg']    ?? '#fff0f5');
                            $detailNetwork = $metric['info']['network'] ?? 'tiktok';
                            $detailUrl = 'metric_detail.php?metric=' . urlencode($key) . '&network=' . urlencode($detailNetwork) . '&from=' . urlencode($from) . '&to=' . urlencode($to) . '&section=video';
                        ?>
                            <a href="<?= $detailUrl ?>" class="sb-card-link" data-metric="<?= htmlspecialchars($key) ?>"
                               style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>;">
                                <div class="sb-card" style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>;">
                                    <div class="sb-card-header">
                                        <div class="sb-card-label-wrap">
                                            <span class="sb-card-label"><?= htmlspecialchars($metric['info']['label']) ?></span>
                                        </div>
                                        <div class="sb-card-actions">
                                            <span class="sb-card-open">Details <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                                            <button type="button" class="sb-card-remove" onclick="event.preventDefault(); removeMetric('<?= htmlspecialchars($key) ?>')">×</button>
                                        </div>
                                    </div>
                                    <div class="sb-card-avg"><?= formatValue($metric['data']['averageValue'], $key) ?></div>
                                    <div class="sb-card-stats">
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Highest</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['maxValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Lowest</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['minValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Median</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['medianValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Datapoints</div><div class="sb-card-stat-value"><?= (int) $metric['data']['dataPointCount'] ?></div></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="sb-reset" onclick="clearAll()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/>
                        </svg>
                        Clear all
                    </button>
                <?php endif; ?>

            </div>
        </div>
    </form>

    <?php if ($debug): ?>
        <div class="sb-debug-section">
            <button class="sb-debug-toggle" type="button" onclick="document.getElementById('debugContent').classList.toggle('visible')">Debug tonen/verbergen</button>
            <div class="sb-debug-content visible" id="debugContent">
                <h3>Debug attempts</h3>
                <div class="sb-debug-grid">
                    <?php foreach ($debugAttempts as $attempt): ?>
                        <div class="sb-debug-card">
                            <strong><?= htmlspecialchars($attempt['metricKey']) ?></strong>
                            <div>Network: <?= htmlspecialchars($attempt['network']) ?></div>
                            <div>API metric: <?= htmlspecialchars($attempt['apiMetric']) ?></div>
                            <div>HTTP: <?= htmlspecialchars((string) $attempt['httpCode']) ?></div>
                            <div>URL: <?= htmlspecialchars($attempt['url']) ?></div>
                            <div>Response: <?= htmlspecialchars(mb_substr((string) $attempt['raw'], 0, 500)) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<div class="sb-toast" id="dropToast">Metric toegevoegd aan het kader</div>

<script>
let selectedMetricsList = <?= json_encode($selectedMetrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function submitForm() {
    document.getElementById('selectedMetrics').value = JSON.stringify(selectedMetricsList);
    document.getElementById('dashboardForm').submit();
}

function removeMetric(metric) {
    selectedMetricsList = selectedMetricsList.filter(m => m !== metric);
    submitForm();
}

function clearAll() {
    selectedMetricsList = [];
    submitForm();
}

function showDropToast() {
    const toast = document.getElementById('dropToast');
    if (!toast) return;
    toast.classList.add('visible');
    setTimeout(() => toast.classList.remove('visible'), 1800);
}

if (sessionStorage.getItem('sbMetricDropped') === '1') {
    sessionStorage.removeItem('sbMetricDropped');
    showDropToast();
}

document.querySelectorAll('.sb-chip').forEach(chip => {
    chip.addEventListener('dragstart', e => {
        chip.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', chip.dataset.metric);
        e.dataTransfer.setData('metric', chip.dataset.metric);
    });

    chip.addEventListener('dragend', () => chip.classList.remove('dragging'));
});

const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');

    const metric = e.dataTransfer.getData('metric') || e.dataTransfer.getData('text/plain');

    if (metric && !selectedMetricsList.includes(metric)) {
        selectedMetricsList.push(metric);
        sessionStorage.setItem('sbMetricDropped', '1');
        submitForm();
    }
});

document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', submitForm);
});
</script>

</body>
</html>
