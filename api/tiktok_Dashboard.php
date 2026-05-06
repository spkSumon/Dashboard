<?php

$userId = 4394337;
$blogId = 5668624;
$token  = 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB';

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: {$token}",
];

// ─────────────────────────────────────────────────────────────────────────────
// ORIGINELE FUNCTIES – ongewijzigd
// ─────────────────────────────────────────────────────────────────────────────

function callMetricool($endpoint, $params, $headers) {
    $baseUrl  = "https://app.metricool.com";
    $endpoint = '/' . ltrim($endpoint, '/');
    $url      = $baseUrl . $endpoint . '?' . http_build_query($params);

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

    foreach ($responseBody['data'] as $block) {
        if (($block['metric'] ?? null) === $metricName) {
            $metricBlock = $block;
            break;
        }
    }

    if (!isset($metricBlock)) {
        return ['error' => 'Metric "' . $metricName . '" niet gevonden in de response.'];
    }

    if (!isset($metricBlock['values']) || !is_array($metricBlock['values'])) {
        return ['error' => 'Geen geldige values-array gevonden voor metric "' . $metricName . '".'];
    }

    if (count($metricBlock['values']) === 0) {
        return ['empty' => true, 'metric' => $metricName, 'values' => []];
    }

    $values = $metricBlock['values'];

    usort($values, function ($a, $b) {
        return strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? '');
    });

    $numericValues = array_map(fn($row) => (float) ($row['value'] ?? 0), $values);

    $maxIndex = 0;
    $minIndex = 0;

    foreach ($values as $index => $row) {
        $currentValue = (float) ($row['value'] ?? 0);
        if ($currentValue > (float) ($values[$maxIndex]['value'] ?? 0)) $maxIndex = $index;
        if ($currentValue < (float) ($values[$minIndex]['value'] ?? 0)) $minIndex = $index;
    }

    $sortedNumericValues = $numericValues;
    sort($sortedNumericValues);
    $count = count($sortedNumericValues);

    $medianValue = $count % 2 === 0
        ? ($sortedNumericValues[$count / 2 - 1] + $sortedNumericValues[$count / 2]) / 2
        : $sortedNumericValues[(int) floor($count / 2)];

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
        'engagement'    => 'Engagement',
        'interactions'  => 'Interacties',
        'likes'         => 'Likes',
        'comments'      => 'Reacties',
        'shares'        => 'Delingen',
        'saved'         => 'Opgeslagen',
        'clicks'        => 'Klikken',
        'impressions'   => 'Vertoningen',
        'reach'         => 'Bereik',
        'videoviews'    => 'Video weergaven',
        'views'         => 'Weergaven',
        'profile_views' => 'Profiel weergaven',
        'followers'     => 'Volgers',
    ];
    return $labels[$metricName] ?? ucfirst(str_replace('_', ' ', $metricName));
}

function formatMetricValue($value, $metricName) {
    if (in_array($metricName, ['engagement', 'ctr'], true)) {
        return number_format((float) $value, 2, ',', '.') . '%';
    }
    return number_format((float) $value, 0, ',', '.');
}

// ─────────────────────────────────────────────────────────────────────────────
// NIEUW – Helper: haal beschikbare metrics uit een volledige API response
// Gebruikt voor de "posts" subject call die meerdere metrics tegelijk teruggeeft
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Geeft alle metrische namen terug die aanwezig zijn in de response body.
 * Handig als je niet vooraf weet welke metrics de API teruggeeft.
 */
function getAvailableMetricsFromResponse(array $responseBody): array {
    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
        return [];
    }
    return array_filter(
        array_column($responseBody['data'], 'metric'),
        fn($m) => $m !== null
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// GET parameters
// ─────────────────────────────────────────────────────────────────────────────

$from       = $_GET['from']        ?? date('Y-m-01');
$to         = $_GET['to']          ?? date('Y-m-d');
$testMode   = $_GET['test']        ?? 'posts';           // NIEUW: default = posts
$metricMode = $_GET['metric_mode'] ?? 'views_interactions';

// NIEUW – uitgebreide metric modes voor TikTok posts-data
$allowedMetricModes = ['views_interactions', 'engagement', 'interactions', 'reach_views', 'both'];

if (!in_array($metricMode, $allowedMetricModes, true)) {
    $metricMode = 'views_interactions';
}

$globalErrors = [];

if ($token === '') {
    $globalErrors[] = 'Geen Metricool token ingesteld.';
}

if ($from > $to) {
    $globalErrors[] = 'De startdatum mag niet later zijn dan de einddatum.';
}

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// ─────────────────────────────────────────────────────────────────────────────
// NIEUW – Configuraties uitgebreid met posts/videos subject endpoints
// Dit zijn de endpoints die Metricool zelf gebruikt voor de "Posts viewed" grafiek
// ─────────────────────────────────────────────────────────────────────────────

$baseParams = [
    'from'     => $fromIso,
    'to'       => $toIso,
    'network'  => 'tiktokBusiness',
    'timezone' => 'Europe/Brussels',
    'userId'   => $userId,
    'blogId'   => $blogId,
];

$configurations = [
    // Originele timeline (werkt niet voor engagement → behouden voor debug)
    'timeline' => [
        'endpoint' => '/api/v2/analytics/timelines',
        'params'   => $baseParams,
        'label'    => 'Timeline (profiel)',
    ],

    // NIEUW – Posts subject: equivalent van "Posts viewed in period" in Metricool UI
    'posts' => [
        'endpoint' => '/api/v2/analytics/timelines',
        'params'   => array_merge($baseParams, ['subject' => 'posts']),
        'label'    => 'Posts (per post)',
    ],

    // NIEUW – Videos subject: TikTok videos (zelfde data, andere subject naam)
    'videos' => [
        'endpoint' => '/api/v2/analytics/timelines',
        'params'   => array_merge($baseParams, ['subject' => 'videos']),
        'label'    => 'Videos',
    ],

    // NIEUW – Metrics endpoint (aggregaten, geen tijdreeks)
    'metrics' => [
        'endpoint' => '/api/v2/analytics/metrics',
        'params'   => $baseParams,
        'label'    => 'Metrics (totalen)',
    ],
];

$config = $configurations[$testMode] ?? $configurations['posts'];

// ─────────────────────────────────────────────────────────────────────────────
// NIEUW – Metric selectie gebaseerd op wat Metricool UI toont:
// "Posts views", "Likes", "Comments", "Shares"
// ─────────────────────────────────────────────────────────────────────────────

if ($metricMode === 'views_interactions') {
    // Exact wat Metricool UI toont onder "Posts viewed in period"
    $metrics = ['videoviews', 'likes', 'comments', 'shares'];
} elseif ($metricMode === 'engagement') {
    $metrics = ['engagement'];
} elseif ($metricMode === 'interactions') {
    $metrics = ['interactions', 'likes', 'comments', 'shares'];
} elseif ($metricMode === 'reach_views') {
    $metrics = ['reach', 'impressions', 'videoviews', 'views'];
} else {
    // both – alles
    $metrics = ['videoviews', 'likes', 'comments', 'shares', 'reach', 'impressions', 'engagement'];
}

// ─────────────────────────────────────────────────────────────────────────────
// Data ophalen – originele structuur behouden
// ─────────────────────────────────────────────────────────────────────────────

$results = [];
$errors  = [];
$empty   = [];

// NIEUW – bijhouden welke metrics de API effectief teruggeeft (voor debug)
$apiAvailableMetrics = [];

if (empty($globalErrors)) {
    foreach ($metrics as $metricName) {
        $params = array_merge($config['params'], [
            'metric' => $metricName,
        ]);

        $result = callMetricool($config['endpoint'], $params, $headers);

        $results[$metricName] = [
            'request' => $result,
            'parsed'  => null,
        ];

        if (isset($result['error'])) {
            $errors[$metricName] = $result['error'];
            continue;
        }

        if (($result['httpCode'] ?? 0) !== 200) {
            $apiMsg =
                $result['body']['detail']
                ?? $result['body']['message']
                ?? $result['body']['error']
                ?? $result['raw']
                ?? '';

            $errors[$metricName] =
                'HTTP ' . ($result['httpCode'] ?? '?') .
                ' voor ' . $metricName .
                ($apiMsg ? ' — API: ' . $apiMsg : '');

            // NIEUW – registreer beschikbare metrics uit de fout-response indien aanwezig
            if (!empty($result['body'])) {
                $apiAvailableMetrics[$metricName] = getAvailableMetricsFromResponse($result['body']);
            }

            continue;
        }

        // NIEUW – registreer welke metrics de API teruggeeft
        $apiAvailableMetrics[$metricName] = getAvailableMetricsFromResponse($result['body'] ?? []);

        $parsed = getMetricData($result['body'], $metricName);

        if (isset($parsed['error'])) {
            $errors[$metricName] = $parsed['error'];
            continue;
        }

        if (!empty($parsed['empty'])) {
            $empty[$metricName] = 'Geen data gevonden voor ' . formatMetricLabel($metricName) . '.';
            continue;
        }

        $results[$metricName]['parsed'] = $parsed;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Dashboard - Metrics</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="navbar">
    <a href="config.php" class="nav-link">📥 Inbox</a>
    <a href="facebook_dashboard.php" class="nav-link">👥 Facebook</a>
    <a href="instagram_dashboard.php" class="nav-link">📸 Instagram</a>
    <a href="tiktok_dashboard.php" class="nav-link active">🎵 TikTok</a>
    <a href="gmb_dashboard.php" class="nav-link">🏢 Google Business</a>
</nav>

<div class="header">
    <h1>🎵 TikTok Dashboard</h1>
    <p class="header-subtitle">Bekijk en analyseer je TikTok statistieken</p>
    <span class="test-mode">
        <strong>Modus:</strong> <?= htmlspecialchars($testMode) ?> |
        <strong>Metrics:</strong> <?= htmlspecialchars($metricMode) ?> |
        <strong>Endpoint label:</strong> <?= htmlspecialchars($configurations[$testMode]['label'] ?? $testMode) ?>
    </span>
</div>

<?php foreach ($globalErrors as $globalError): ?>
    <div class="error"><?= htmlspecialchars($globalError) ?></div>
<?php endforeach; ?>

<form method="get">
    <div class="form-row">
        <div class="form-group">
            <label for="from">📅 Van datum</label>
            <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>

        <div class="form-group">
            <label for="to">📅 Tot datum</label>
            <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>

        <div class="form-group">
            <label for="test">⚙️ API Configuratie</label>
            <select id="test" name="test">
                <!-- NIEUW – posts en videos als standaard opties -->
                <option value="posts"   <?= $testMode === 'posts'   ? 'selected' : '' ?>>Posts (aanbevolen)</option>
                <option value="videos"  <?= $testMode === 'videos'  ? 'selected' : '' ?>>Videos</option>
                <option value="metrics" <?= $testMode === 'metrics' ? 'selected' : '' ?>>Metrics (totalen)</option>
                <option value="timeline" <?= $testMode === 'timeline' ? 'selected' : '' ?>>Timeline (debug)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="metric_mode">📊 Welke metrics</label>
            <select id="metric_mode" name="metric_mode">
                <!-- NIEUW – views_interactions als standaard (wat Metricool UI toont) -->
                <option value="views_interactions" <?= $metricMode === 'views_interactions' ? 'selected' : '' ?>>
                    📹 Views + Likes + Reacties + Delingen
                </option>
                <option value="engagement"   <?= $metricMode === 'engagement'   ? 'selected' : '' ?>>Alleen engagement</option>
                <option value="interactions" <?= $metricMode === 'interactions' ? 'selected' : '' ?>>Interacties (likes/reacties/delingen)</option>
                <option value="reach_views"  <?= $metricMode === 'reach_views'  ? 'selected' : '' ?>>Bereik & weergaven</option>
                <option value="both"         <?= $metricMode === 'both'         ? 'selected' : '' ?>>Alle metrics</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit">🔍 Data ophalen</button>
        </div>
    </div>
</form>

<div class="endpoint-info">
    <p>
        <strong>📡 Basis endpoint:</strong> <?= htmlspecialchars($config['endpoint']) ?> |
        <strong>Subject:</strong> <?= htmlspecialchars($config['params']['subject'] ?? 'geen') ?> |
        <strong>Network:</strong> <?= htmlspecialchars($config['params']['network'] ?? '-') ?>
    </p>
    <?php foreach ($results as $metricName => $result): ?>
        <p>
            <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?>:</strong>
            <code><?= htmlspecialchars($result['request']['url'] ?? '-') ?></code>
        </p>
    <?php endforeach; ?>
</div>

<?php foreach ($results as $metricName => $result): ?>
    <?php
        $data     = $result['parsed'] ?? null;
        $error    = $errors[$metricName] ?? null;
        $emptyMsg = $empty[$metricName] ?? null;
    ?>

    <div class="metric-block">
        <?php if ($error): ?>
            <div class="error">
                <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?>:</strong>
                <?= htmlspecialchars($error) ?>
                <?php
                // NIEUW – toon welke metrics WEL beschikbaar zijn als hint
                $available = $apiAvailableMetrics[$metricName] ?? [];
                if (!empty($available)):
                ?>
                    <br><small>
                        <strong>Beschikbare metrics in deze response:</strong>
                        <?= htmlspecialchars(implode(', ', $available)) ?>
                    </small>
                <?php endif; ?>
            </div>

        <?php elseif ($emptyMsg): ?>
            <div class="info">
                <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?>:</strong>
                <?= htmlspecialchars($emptyMsg) ?>
            </div>

        <?php elseif ($data): ?>

            <?php if ($data['dataPointCount'] === 1): ?>
                <div class="warning">
                    Slechts één datapunt gevonden voor <?= htmlspecialchars(formatMetricLabel($metricName)) ?>.
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>
                    📊 <?= htmlspecialchars(formatMetricLabel($metricName)) ?> overzicht
                    <span class="data-point-badge"><?= $data['dataPointCount'] ?> datapunt(en)</span>
                </h3>

                <p style="color:#666; margin-bottom:20px;">
                    <strong>📅 Periode:</strong> <?= htmlspecialchars($from) ?> tot <?= htmlspecialchars($to) ?>
                </p>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">📍 Aantal datapunten</div>
                        <div class="stat-value"><?= (int) $data['dataPointCount'] ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">📊 Gemiddelde</div>
                        <div class="stat-value"><?= formatMetricValue($data['averageValue'], $metricName) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">🎯 Mediaan</div>
                        <div class="stat-value"><?= formatMetricValue($data['medianValue'], $metricName) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">⬆️ Hoogste waarde</div>
                        <div class="stat-value positive"><?= formatMetricValue($data['maxValue'], $metricName) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">🏆 Beste datapunt</div>
                        <div class="stat-value">#<?= $data['maxIndex'] + 1 ?></div>
                        <div class="stat-subtext"><?= htmlspecialchars($data['maxRow']['dateTime'] ?? '-') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">⬇️ Laagste waarde</div>
                        <div class="stat-value negative"><?= formatMetricValue($data['minValue'], $metricName) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">📉 Zwakste datapunt</div>
                        <div class="stat-value">#<?= $data['minIndex'] + 1 ?></div>
                        <div class="stat-subtext"><?= htmlspecialchars($data['minRow']['dateTime'] ?? '-') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">📏 Spreiding</div>
                        <div class="stat-value neutral"><?= formatMetricValue($data['rangeValue'], $metricName) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>📋 <?= htmlspecialchars(formatMetricLabel($metricName)) ?> per datapunt</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>📅 Datum & Tijd</th>
                            <th>📊 <?= htmlspecialchars(formatMetricLabel($metricName)) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['values'] as $index => $row): ?>
                            <?php
                                $isBest   = $index === $data['maxIndex'];
                                $isWorst  = $index === $data['minIndex'];
                                $rowClass = $isBest ? 'row-best' : ($isWorst ? 'row-worst' : '');
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <?= $index + 1 ?>
                                    <?php if ($isBest):  ?><span class="badge-best">🏆 Beste</span><?php endif; ?>
                                    <?php if ($isWorst): ?><span class="badge-worst">📉 Zwakste</span><?php endif; ?>
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

<div class="card">
    <h3>🐛 Debug Informatie</h3>

    <?php foreach ($results as $metricName => $result): ?>
        <div class="debug-box">
            <strong><?= htmlspecialchars(formatMetricLabel($metricName)) ?></strong>
            <?php
            // NIEUW – toon beschikbare metrics in debug
            $available = $apiAvailableMetrics[$metricName] ?? [];
            if (!empty($available)): ?>
                <br><em>Metrics in response: <?= htmlspecialchars(implode(', ', $available)) ?></em>
            <?php endif; ?>

            <?php if (($result['request']['httpCode'] ?? 0) !== 200): ?>
                <p style="color:#c62828;font-weight:700;">
                    HTTP <?= htmlspecialchars($result['request']['httpCode'] ?? '?') ?>
                </p>
                <pre><?= htmlspecialchars($result['request']['raw'] ?? '-') ?></pre>
            <?php else: ?>
                <pre><?php var_dump($result['request']['body'] ?? null); ?></pre>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>