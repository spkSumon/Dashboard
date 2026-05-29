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
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) { $error = curl_error($ch); curl_close($ch); return ['error' => $error]; }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['httpCode' => $code, 'body' => json_decode($response, true), 'raw' => $response];
}

function getMetricData($responseBody, $metricName) {
    if (!isset($responseBody['data']) || !is_array($responseBody['data'])) {
        return ['error' => 'Geen geldige data gevonden.'];
    }
    foreach ($responseBody['data'] as $block) {
        if (($block['metric'] ?? null) === $metricName) { $metricBlock = $block; break; }
    }
    if (!isset($metricBlock)) { return ['error' => 'Metric "' . $metricName . '" niet gevonden.']; }
    if (!isset($metricBlock['values']) || !is_array($metricBlock['values'])) { return ['error' => 'Geen values gevonden voor "' . $metricName . '".'];}
    if (count($metricBlock['values']) === 0) { return ['empty' => true, 'metric' => $metricName, 'values' => []]; }
    $values = $metricBlock['values'];
    usort($values, fn($a, $b) => strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? ''));
    $numericValues = array_map(fn($row) => (float)($row['value'] ?? 0), $values);
    $maxIndex = $minIndex = 0;
    foreach ($values as $i => $row) {
        $v = (float)($row['value'] ?? 0);
        if ($v > (float)($values[$maxIndex]['value'] ?? 0)) $maxIndex = $i;
        if ($v < (float)($values[$minIndex]['value'] ?? 0)) $minIndex = $i;
    }
    $sorted = $numericValues; sort($sorted); $c = count($sorted);
    $median = $c % 2 === 0 ? ($sorted[$c/2-1] + $sorted[$c/2]) / 2 : $sorted[(int)floor($c/2)];
    return [
        'metric'         => $metricName,
        'dataPointCount' => count($values),
        'averageValue'   => array_sum($numericValues) / count($numericValues),
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

function formatValue($value, $metricKey) {
    if ($metricKey === 'engagement') { return number_format((float)$value, 2, ',', '.') . '%'; }
    return number_format((float)$value, 0, ',', '.');
}

$from = $_POST['from'] ?? date('Y-m-01');
$to   = $_POST['to']   ?? date('Y-m-d');
$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

$availableMetrics = [
    'videos' => [
        ['key' => 'videoviews',   'label' => 'Video Views',   'api' => 'videoviews',  'page' => 'tiktok_videoviews_detail.php', 'color' => '#000000', 'bg' => '#f5f5f5', 'icon' => '▶️'],
        ['key' => 'engagement',   'label' => 'Engagement',    'api' => 'engagement',  'page' => 'tiktok_engagement_detail.php', 'color' => '#ff0050', 'bg' => '#fff0f5', 'icon' => '❤️'],
        ['key' => 'likes',        'label' => 'Likes',         'api' => 'likes',       'page' => 'tiktok_likes_detail.php',      'color' => '#ff0050', 'bg' => '#fff0f5', 'icon' => '👍'],
        ['key' => 'comments',     'label' => 'Comments',      'api' => 'comments',    'page' => 'tiktok_comments_detail.php',   'color' => '#3366ff', 'bg' => '#f0f7ff', 'icon' => '💬'],
        ['key' => 'shares',       'label' => 'Shares',        'api' => 'shares',      'page' => 'tiktok_shares_detail.php',     'color' => '#00b4d8', 'bg' => '#f0fafb', 'icon' => '🔁'],
    ],
];

$metricLookup = [];
foreach ($availableMetrics as $section => $metrics) {
    foreach ($metrics as $m) {
        $metricLookup[$section][$m['key']] = $m;
    }
}

$selectedMetricsJson = $_POST['selected_metrics'] ?? '[]';
$selectedMetrics = json_decode($selectedMetricsJson, true);
if (!is_array($selectedMetrics)) $selectedMetrics = [];

$selectedSection = 'videos'; // TikTok har apenas "videos"

$metricsData = [];

if (!empty($selectedMetrics) && $token !== '') {
    foreach ($selectedMetrics as $metricKey) {
        $metricInfo = $metricLookup[$selectedSection][$metricKey] ?? null;
        if (!$metricInfo) continue;

        $params = [
            'from'     => $fromIso,
            'to'       => $toIso,
            'network'  => 'tiktokBusiness',
            'timezone' => 'Europe/Brussels',
            'userId'   => $userId,
            'blogId'   => $blogId,
            'subject'  => 'videos',
            'metric'   => $metricInfo['api'],
        ];

        $result = callMetricool('/api/v2/analytics/timelines', $params, $headers);

        if (($result['httpCode'] ?? 0) === 200) {
            $parsed = getMetricData($result['body'], $metricInfo['api']);
            if (!isset($parsed['error']) && empty($parsed['empty'])) {
                $metricsData[$metricKey] = ['info' => $metricInfo, 'data' => $parsed];
            }
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
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background: #f0f2f7; }
        .sb-page { max-width: 1320px; margin: 0 auto; }
        .sb-title { font-size: 22px; font-weight: 700; color: #1a2233; letter-spacing: -0.3px; margin-bottom: 4px; }
        .sb-subtitle { font-size: 13px; color: #7a8599; margin-bottom: 28px; }
        .sb-toolbar {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            background: #fff; border: 1px solid #e4e8ef; border-radius: 12px;
            padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sb-toolbar label { font-size: 11px; font-weight: 700; color: #9aa3b4; text-transform: uppercase; letter-spacing: 0.6px; margin: 0; }
        .sb-toolbar input[type="date"] {
            border: 1px solid #dde2ec; border-radius: 8px; padding: 7px 11px;
            font-size: 13px; color: #1a2233; background: #fafbfd;
        }
        .sb-toolbar input[type="date"]:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
        .sb-divider { width: 1px; height: 28px; background: #e4e8ef; margin: 0 4px; }
        .sb-layout { display: grid; grid-template-columns: 210px 1fr; gap: 16px; align-items: start; }
        .sb-sidebar {
            background: #fff; border: 1px solid #e4e8ef; border-radius: 12px;
            padding: 16px; position: sticky; top: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sb-sidebar-title { font-size: 11px; font-weight: 700; color: #9aa3b4; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 12px; }
        .sb-chip {
            display: flex; align-items: center; gap: 9px; padding: 9px 11px; margin-bottom: 5px;
            border-radius: 8px; border: 1px solid #e8edf4; background: #fafbfd; cursor: grab;
            font-size: 13px; font-weight: 500; color: #3a4460; transition: all .15s; user-select: none;
        }
        .sb-chip:hover { transform: translateX(2px); box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .sb-chip.dragging { opacity: .35; }
        .sb-chip-icon { font-size: 14px; line-height: 1; }
        .sb-canvas {
            background: #fff; border: 2px dashed #dde2ec; border-radius: 12px;
            min-height: 480px; padding: 24px; transition: border-color .2s, background .2s;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sb-canvas.drag-over { border-color: #ff0050; background: #fff5f8; }
        .sb-canvas.has-content { border-style: solid; border-color: #e4e8ef; }
        .sb-empty { height: 100%; min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: #b0bac8; }
        .sb-empty-icon { width: 52px; height: 52px; border-radius: 14px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; font-size: 24px; }
        .sb-empty p { font-size: 13px; font-weight: 500; color: #b0bac8; margin: 0; }
        .sb-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
        .sb-card-link { display: block; text-decoration: none; color: inherit; border-radius: 12px; transition: transform .18s, box-shadow .18s; }
        .sb-card-link:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.11); }
        .sb-card-link:hover .sb-card-open { opacity: 1; transform: translateX(0); }
        .sb-card { border: 1px solid #e4e8ef; border-radius: 12px; padding: 18px; position: relative; background: #fafbfd; overflow: hidden; }
        .sb-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--card-color, #ff0050); border-radius: 12px 12px 0 0; }
        .sb-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .sb-card-label-wrap { display: flex; align-items: center; gap: 7px; }
        .sb-card-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 14px; background: var(--card-bg, #fff0f5); flex-shrink: 0; }
        .sb-card-label { font-size: 11px; font-weight: 700; color: #7a8599; text-transform: uppercase; letter-spacing: 0.5px; }
        .sb-card-actions { display: flex; align-items: center; gap: 6px; }
        .sb-card-open { display: flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 600; color: var(--card-color, #ff0050); background: var(--card-bg, #fff0f5); padding: 3px 8px; border-radius: 20px; opacity: 0; transform: translateX(-4px); transition: opacity .18s, transform .18s; white-space: nowrap; }
        .sb-card-open svg { width: 11px; height: 11px; }
        .sb-card-remove { width: 24px; height: 24px; border-radius: 6px; border: 1px solid #e4e8ef; background: #fff; color: #b0bac8; font-size: 15px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; padding: 0; flex-shrink: 0; z-index: 2; position: relative; }
        .sb-card-remove:hover { background: #fff0f0; border-color: #fca5a5; color: #ef4444; }
        .sb-card-avg { font-size: 34px; font-weight: 800; color: var(--card-color, #1a2233); letter-spacing: -1.5px; margin-bottom: 14px; line-height: 1; }
        .sb-card-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
        .sb-card-stat { background: #fff; border: 1px solid #edf0f5; border-radius: 8px; padding: 8px 10px; }
        .sb-card-stat-label { font-size: 10px; font-weight: 700; color: #b0bac8; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; }
        .sb-card-stat-value { font-size: 15px; font-weight: 700; color: #3a4460; }
        .sb-reset { display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; padding: 8px 14px; border: 1px solid #e4e8ef; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; color: #7a8599; cursor: pointer; transition: all .15s; }
        .sb-reset:hover { border-color: #ef4444; color: #ef4444; background: #fff8f8; }
        @media (max-width: 768px) { .sb-layout { grid-template-columns: 1fr; } .sb-sidebar { position: static; } }
    </style>
</head>
<body>

<div class="sb-page">

    <nav class="navbar">
        <a href="config.php" class="nav-link">Inbox</a>
        <a href="facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="tiktok_dashboard.php" class="nav-link active">TikTok</a>
        <a href="gmb_dashboard.php" class="nav-link">Google Business</a>
    </nav>

    <p class="sb-title">TikTok</p>
    <p class="sb-subtitle">Sleep metrics naar het overzicht om te analyseren</p>

    <form method="POST" id="dashboardForm">
        <input type="hidden" name="selected_metrics" id="selectedMetrics" value="">

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
                    <?php foreach ($availableMetrics['videos'] as $metric): ?>
                        <div class="sb-chip" draggable="true" data-metric="<?= htmlspecialchars($metric['key']) ?>"
                             style="border-left: 3px solid <?= htmlspecialchars($metric['color']) ?>;">
                            <span class="sb-chip-icon"><?= $metric['icon'] ?></span>
                            <?= htmlspecialchars($metric['label']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sb-canvas <?= !empty($metricsData) ? 'has-content' : '' ?>" id="dropZone">

                <?php if (empty($metricsData)): ?>
                    <div class="sb-empty">
                        <div class="sb-empty-icon">🎬</div>
                        <p>Sleep een metric hierheen</p>
                        <p style="font-size:12px; color:#c8d0de;">Kies links wat je wilt zien</p>
                    </div>

                <?php else: ?>
                    <div class="sb-cards" id="metricsDisplay">
                        <?php foreach ($metricsData as $key => $metric):
                            $color = htmlspecialchars($metric['info']['color'] ?? '#ff0050');
                            $bg    = htmlspecialchars($metric['info']['bg']    ?? '#fff0f5');
                            $page  = htmlspecialchars($metric['info']['page']  ?? '#');
                            $icon  = $metric['info']['icon'] ?? '📊';
                            $detailUrl = $page . '?from=' . urlencode($from) . '&to=' . urlencode($to);
                        ?>
                            <a href="<?= $detailUrl ?>" class="sb-card-link" data-metric="<?= htmlspecialchars($key) ?>"
                               style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>;">
                                <div class="sb-card" style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>;">
                                    <div class="sb-card-header">
                                        <div class="sb-card-label-wrap">
                                            <div class="sb-card-icon"><?= $icon ?></div>
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
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Datapoints</div><div class="sb-card-stat-value"><?= $metric['data']['dataPointCount'] ?></div></div>
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

</div>

<script>
let selectedMetricsList = <?= json_encode(array_keys($metricsData)) ?>;

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

document.querySelectorAll('.sb-chip').forEach(chip => {
    chip.addEventListener('dragstart', e => {
        chip.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('metric', chip.dataset.metric);
    });
    chip.addEventListener('dragend', () => chip.classList.remove('dragging'));
});

const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const metric = e.dataTransfer.getData('metric');
    if (metric && !selectedMetricsList.includes(metric)) {
        selectedMetricsList.push(metric);
        submitForm();
    }
});

document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', submitForm);
});
</script>

</body>
</html>
