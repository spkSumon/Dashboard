<?php
/**
 * tiktok_dashboard.php — TikTok metrics dashboard met drag-and-drop.
 */
require_once __DIR__ . '/token.php';

$from    = $_POST['from'] ?? date('Y-m-01');
$to      = $_POST['to']   ?? date('Y-m-d');
$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

$availableMetrics = [
    ['key' => 'videoviews', 'label' => 'Video Views', 'api' => 'videoviews',
     'api_candidates' => ['videoviews', 'video_views', 'videoViews', 'views'],
     'color' => '#000000', 'bg' => '#f5f5f5'],
    ['key' => 'engagement', 'label' => 'Engagement', 'api' => 'engagement',
     'api_candidates' => ['engagement'],
     'color' => '#ff0050', 'bg' => '#fff0f5'],
    ['key' => 'likes', 'label' => 'Likes', 'api' => 'likes',
     'api_candidates' => ['likes'],
     'color' => '#ff0050', 'bg' => '#fff0f5'],
    ['key' => 'comments', 'label' => 'Reacties', 'api' => 'comments',
     'api_candidates' => ['comments'],
     'color' => '#3366ff', 'bg' => '#f0f7ff'],
    ['key' => 'shares', 'label' => 'Delingen', 'api' => 'shares',
     'api_candidates' => ['shares'],
     'color' => '#00b4d8', 'bg' => '#f0fafb'],
];

$metricLookup = [];
foreach ($availableMetrics as $m) { $metricLookup[$m['key']] = $m; }

$selectedMetrics = json_decode($_POST['selected_metrics'] ?? '[]', true);
if (!is_array($selectedMetrics)) $selectedMetrics = [];
$selectedMetrics = array_values(array_unique(array_filter($selectedMetrics, fn($k) => is_string($k) && trim($k) !== '')));

$metricsData  = [];
$metricErrors = [];

foreach ($selectedMetrics as $metricKey) {
    $metricInfo = $metricLookup[$metricKey] ?? null;
    if (!$metricInfo) { $metricErrors[$metricKey] = 'Metric bestaat niet.'; continue; }

    $apiCandidates = $metricInfo['api_candidates'] ?? [$metricInfo['api']];
    $loaded    = false;
    $lastError = '';

    foreach ($apiCandidates as $apiMetricName) {
        $result = callMetricool('/api/v2/analytics/timelines', [
            'from' => $fromIso, 'to' => $toIso,
            'network' => 'tiktok', 'timezone' => 'Europe/Brussels',
            'userId' => $userId, 'blogId' => $blogId,
            'subject' => 'video', 'metric' => $apiMetricName,
        ], $headers);

        if (isset($result['error'])) { $lastError = 'cURL: ' . $result['error']; continue; }
        if (($result['httpCode'] ?? 0) !== 200) { $lastError = 'HTTP ' . ($result['httpCode'] ?? '?'); continue; }

        $parsed = getMetricData($result['body'], $apiMetricName);
        if (isset($parsed['error'])) { $lastError = $parsed['error']; continue; }
        if (!empty($parsed['empty'])) { $lastError = 'Geen data in deze periode.'; continue; }

        $metricInfo['api'] = $apiMetricName;
        $metricsData[$metricKey] = ['info' => $metricInfo, 'data' => $parsed];
        $loaded = true;
        break;
    }

    if (!$loaded) { $metricErrors[$metricKey] = $lastError ?: 'Kon niet laden.'; }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok — SkyByte</title>
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body>
<div class="sb-page">
    <div id="sbToast" class="sb-toast" aria-live="polite"></div>

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
        <input type="hidden" name="selected_metrics" id="selectedMetrics"
               value="<?= htmlspecialchars(json_encode($selectedMetrics)) ?>">

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
                    <?php foreach ($availableMetrics as $metric): ?>
                        <div class="sb-chip" draggable="true"
                             data-metric="<?= htmlspecialchars($metric['key']) ?>"
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
                            <?php foreach ($metricErrors as $mKey => $error): ?>
                                <li><strong><?= htmlspecialchars($mKey) ?>:</strong> <?= htmlspecialchars($error) ?></li>
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
                            $color     = htmlspecialchars($metric['info']['color']);
                            $bg        = htmlspecialchars($metric['info']['bg']);
                            $detailUrl = 'metric_detail.php?metric=' . urlencode($key) . '&network=tiktokBusiness&from=' . urlencode($from) . '&to=' . urlencode($to) . '&section=video';
                        ?>
                            <div class="sb-card-link" data-metric="<?= htmlspecialchars($key) ?>"
                                 style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>; cursor: pointer;"
                                 onclick="window.location.href='<?= $detailUrl ?>'">
                                <div class="sb-card" style="--card-color: <?= $color ?>; --card-bg: <?= $bg ?>;">
                                    <div class="sb-card-header">
                                        <div class="sb-card-label-wrap">
                                            <span class="sb-card-label"><?= htmlspecialchars($metric['info']['label']) ?></span>
                                        </div>
                                        <div class="sb-card-actions">
                                            <span class="sb-card-open">Details <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                                            <button type="button" class="sb-card-remove"
                                                    onclick="event.stopPropagation(); removeMetric('<?= htmlspecialchars($key) ?>')">×</button>
                                        </div>
                                    </div>
                                    <div class="sb-card-avg"><?= formatValue($metric['data']['averageValue'], $key) ?></div>
                                    <div class="sb-card-stats">
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Hoogste</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['maxValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Laagste</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['minValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Mediaan</div><div class="sb-card-stat-value"><?= formatValue($metric['data']['medianValue'], $key) ?></div></div>
                                        <div class="sb-card-stat"><div class="sb-card-stat-label">Datapunten</div><div class="sb-card-stat-value"><?= (int)$metric['data']['dataPointCount'] ?></div></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="sb-reset" onclick="clearAll()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/>
                        </svg>
                        Alles wissen
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
function showDropNotice(title = 'Metric toegevoegd', detail = 'De metric staat nu in het overzicht.') {
    const toast = document.getElementById('sbToast');
    if (!toast) return;
    toast.innerHTML = `${title}<small>${detail}</small>`;
    toast.classList.add('visible');
    clearTimeout(window.__sbToastTimer);
    window.__sbToastTimer = setTimeout(() => toast.classList.remove('visible'), 3200);
}

if (sessionStorage.getItem('sbMetricDropped') === '1') {
    sessionStorage.removeItem('sbMetricDropped');
    addEventListener('DOMContentLoaded', () => showDropNotice());
}

let selectedMetricsList = <?= json_encode($selectedMetrics) ?>;

function submitForm() {
    document.getElementById('selectedMetrics').value = JSON.stringify(selectedMetricsList);
    document.getElementById('dashboardForm').submit();
}
function removeMetric(m) { selectedMetricsList = selectedMetricsList.filter(x => x !== m); submitForm(); }
function clearAll()      { selectedMetricsList = []; submitForm(); }

document.querySelectorAll('.sb-chip').forEach(chip => {
    chip.addEventListener('dragstart', e => {
        chip.classList.add('dragging');
        e.dataTransfer.setData('metric', chip.dataset.metric);
        e.dataTransfer.setData('text/plain', chip.dataset.metric);
    });
    chip.addEventListener('dragend', () => chip.classList.remove('dragging'));
});

const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const metric = e.dataTransfer.getData('metric') || e.dataTransfer.getData('text/plain');
    if (metric && !selectedMetricsList.includes(metric)) {
        selectedMetricsList.push(metric);
        sessionStorage.setItem('sbMetricDropped', '1');
        showDropNotice();
        submitForm();
    }
});

document.querySelectorAll('input[type="date"]').forEach(input => input.addEventListener('change', submitForm));
</script>
</body>
</html>
