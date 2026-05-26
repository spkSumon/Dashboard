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

        if ($currentValue > (float) ($values[$maxIndex]['value'] ?? 0)) {
            $maxIndex = $index;
        }

        if ($currentValue < (float) ($values[$minIndex]['value'] ?? 0)) {
            $minIndex = $index;
        }
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

$from = $_POST['from'] ?? date('Y-m-01');
$to   = $_POST['to']   ?? date('Y-m-d');

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// Alle beschikbare metrics
$availableMetrics = [
    'posts' => [
        ['key' => 'engagement', 'label' => 'Engagement', 'icon' => '📊', 'api' => 'engagement'],
        ['key' => 'interactions', 'label' => 'Interacties', 'icon' => '💬', 'api' => 'interactions'],
        ['key' => 'likes', 'label' => 'Likes', 'icon' => '👍', 'api' => 'likes'],
        ['key' => 'comments', 'label' => 'Reacties', 'icon' => '💭', 'api' => 'comments'],
        ['key' => 'shares', 'label' => 'Delingen', 'icon' => '↗️', 'api' => 'shares'],
        ['key' => 'reach', 'label' => 'Bereik', 'icon' => '👥', 'api' => 'reach'],
        ['key' => 'impressions', 'label' => 'Vertoningen', 'icon' => '👀', 'api' => 'impressions'],
    ],
    'reels' => [
        ['key' => 'engagement', 'label' => 'Engagement', 'icon' => '📊', 'api' => 'engagement'],
        ['key' => 'interactions', 'label' => 'Interacties', 'icon' => '💬', 'api' => 'interactions'],
        ['key' => 'likes', 'label' => 'Likes', 'icon' => '👍', 'api' => 'likes'],
        ['key' => 'comments', 'label' => 'Reacties', 'icon' => '💭', 'api' => 'comments'],
        ['key' => 'shares', 'label' => 'Delingen', 'icon' => '↗️', 'api' => 'shares'],
        ['key' => 'video_views', 'label' => 'Video views', 'icon' => '▶️', 'api' => 'blue_reels_play_count'],
        ['key' => 'reach', 'label' => 'Bereik', 'icon' => '👥', 'api' => 'reach'],
    ],
];

// Data ophalen voor de geselecteerde metrics
$selectedMetricsJson = $_POST['selected_metrics'] ?? '[]';
$selectedMetrics = json_decode($selectedMetricsJson, true);

if (!is_array($selectedMetrics)) {
    $selectedMetrics = [];
}

$selectedSection = $_POST['selected_section'] ?? 'posts';

if (!isset($availableMetrics[$selectedSection])) {
    $selectedSection = 'posts';
}

$metricsData = [];

if (!empty($selectedMetrics) && $token !== '') {
    $subject = $selectedSection;
    
    foreach ($selectedMetrics as $metricKey) {
        // Vind de metric info
        $metricInfo = null;
        foreach ($availableMetrics[$selectedSection] as $m) {
            if ($m['key'] === $metricKey) {
                $metricInfo = $m;
                break;
            }
        }
        
        if (!$metricInfo) continue;
        
        $params = [
            'from'     => $fromIso,
            'to'       => $toIso,
            'network'  => 'facebook',
            'timezone' => 'Europe/Brussels',
            'userId'   => $userId,
            'blogId'   => $blogId,
            'subject'  => $subject,
            'metric'   => $metricInfo['api'],
        ];
        
        $result = callMetricool('/api/v2/analytics/timelines', $params, $headers);
        
        if (($result['httpCode'] ?? 0) === 200) {
            $parsed = getMetricData($result['body'], $metricInfo['api']);
            
            if (!isset($parsed['error']) && empty($parsed['empty'])) {
                $metricsData[$metricKey] = [
                    'info' => $metricInfo,
                    'data' => $parsed,
                ];
            }
        }
    }
}

function formatValue($value, $metricKey) {
    if ($metricKey === 'engagement') {
        return number_format((float) $value, 2, ',', '.') . '%';
    }
    return number_format((float) $value, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .date-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .date-selector label {
            font-weight: 600;
        }
        
        .date-selector input,
        .date-selector select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .section-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .section-tab {
            flex: 1;
            padding: 15px;
            background: rgba(255,255,255,0.2);
            border: 2px solid transparent;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .section-tab:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .section-tab.active {
            background: white;
            color: #667eea;
            border-color: white;
        }
        
        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            min-height: 500px;
        }
        
        .metrics-sidebar {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .metrics-sidebar h3 {
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .metric-chip {
            background: #f0f0f0;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: move;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            user-select: none;
        }
        
        .metric-chip:hover {
            background: #e0e0e0;
            transform: translateX(5px);
        }
        
        .metric-chip.dragging {
            opacity: 0.5;
        }
        
        .metric-chip .icon {
            font-size: 20px;
        }
        
        .drop-zone {
            background: rgba(255,255,255,0.95);
            border: 3px dashed rgba(102,126,234,0.3);
            border-radius: 12px;
            padding: 40px;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .drop-zone.drag-over {
            background: rgba(102,126,234,0.1);
            border-color: #667eea;
        }
        
        .drop-zone.has-content {
            align-items: stretch;
            justify-content: flex-start;
        }
        
        .drop-placeholder {
            text-align: center;
            color: #999;
        }
        
        .drop-placeholder .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .metrics-display {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .metric-card .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4444;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: all 0.2s;
        }
        
        .metric-card .remove-btn:hover {
            background: #cc0000;
        }
        
        .metric-card h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .metric-card .icon {
            font-size: 24px;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-value.large {
            font-size: 32px;
            grid-column: 1 / -1;
            color: #667eea;
        }
        
        .analyze-btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 15px;
        }
        
        .analyze-btn:hover {
            background: #5568d3;
        }
        
        .analyze-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .metrics-sidebar {
                order: 2;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="config.php" class="nav-link">📥 Inbox</a>
    <a href="facebook_dashboard.php" class="nav-link">👥 Facebook</a>
    <a href="instagram_dashboard.php" class="nav-link active">📸 Instagram</a>
    <a href="tiktok_dashboard.php" class="nav-link">🎵 TikTok</a>
    <a href="gmb_dashboard.php" class="nav-link">🏢 Google Business</a>
</nav>
<div class="container">
    <div class="header">
        <h1>👥 Facebook Dashboard</h1>
        <p>Sleep metrics naar het midden om ze te bekijken</p>
    </div>
    
    <form method="POST" id="dashboardForm">
        <div class="date-selector">
            <label>📅 Van:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            
            <label>Tot:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>
        
        <div class="section-tabs">
            <div class="section-tab <?= $selectedSection === 'posts' ? 'active' : '' ?>" 
                 onclick="selectSection('posts')">
                📝 Posts
            </div>
            <div class="section-tab <?= $selectedSection === 'reels' ? 'active' : '' ?>" 
                 onclick="selectSection('reels')">
                🎥 Reels
            </div>
        </div>
        
        <input type="hidden" name="selected_section" id="selectedSection" value="<?= htmlspecialchars($selectedSection) ?>">
        <input type="hidden" name="selected_metrics" id="selectedMetrics" value="">
        
        <div class="dashboard-layout">
            <div class="metrics-sidebar">
                <h3>Beschikbare Metrics</h3>
                <div id="metricsList">
                    <?php foreach ($availableMetrics[$selectedSection] as $metric): ?>
                        <div class="metric-chip" draggable="true" data-metric="<?= htmlspecialchars($metric['key']) ?>">
                            <span class="icon"><?= $metric['icon'] ?></span>
                            <span><?= htmlspecialchars($metric['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="drop-zone <?= !empty($metricsData) ? 'has-content' : '' ?>" id="dropZone">
                <?php if (empty($metricsData)): ?>
                    <div class="drop-placeholder">
                        <div class="icon">📊</div>
                        <h3>Sleep metrics hierheen</h3>
                        <p>Kies de metrics die je wilt bekijken door ze hierheen te slepen</p>
                    </div>
                <?php else: ?>
                    <div class="metrics-display" id="metricsDisplay">
                        <?php foreach ($metricsData as $key => $metric): ?>
                            <div class="metric-card" data-metric="<?= htmlspecialchars($key) ?>">
                                <button type="button" class="remove-btn" onclick="removeMetric('<?= htmlspecialchars($key) ?>')">×</button>
                                <h4>
                                    <span class="icon"><?= $metric['info']['icon'] ?></span>
                                    <?= htmlspecialchars($metric['info']['label']) ?>
                                </h4>
                                
                                <div class="stat-grid">
                                    <div class="stat-item" style="grid-column: 1 / -1;">
                                        <div class="stat-label">Gemiddelde</div>
                                        <div class="stat-value large">
                                            <?= formatValue($metric['data']['averageValue'], $key) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">⬆️ Hoogste</div>
                                        <div class="stat-value">
                                            <?= formatValue($metric['data']['maxValue'], $key) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">⬇️ Laagste</div>
                                        <div class="stat-value">
                                            <?= formatValue($metric['data']['minValue'], $key) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">📍 Datapunten</div>
                                        <div class="stat-value">
                                            <?= $metric['data']['dataPointCount'] ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">🎯 Mediaan</div>
                                        <div class="stat-value">
                                            <?= formatValue($metric['data']['medianValue'], $key) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="analyze-btn" onclick="clearAll()">
                        🔄 Opnieuw beginnen
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
let selectedMetricsList = <?= json_encode(array_keys($metricsData)) ?>;
const currentSection = '<?= htmlspecialchars($selectedSection) ?>';

// Metrics per section
const metricsConfig = <?= json_encode($availableMetrics) ?>;

function selectSection(section) {
    document.getElementById('selectedSection').value = section;
    selectedMetricsList = [];
    submitForm();
}

function updateMetricsList() {
    const metricsList = document.getElementById('metricsList');
    metricsList.innerHTML = '';
    
    metricsConfig[currentSection].forEach(metric => {
        if (!selectedMetricsList.includes(metric.key)) {
            const chip = document.createElement('div');
            chip.className = 'metric-chip';
            chip.draggable = true;
            chip.dataset.metric = metric.key;
            chip.innerHTML = `
                <span class="icon">${metric.icon}</span>
                <span>${metric.label}</span>
            `;
            
            chip.addEventListener('dragstart', handleDragStart);
            chip.addEventListener('dragend', handleDragEnd);
            
            metricsList.appendChild(chip);
        }
    });
}

function handleDragStart(e) {
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.target.innerHTML);
    e.dataTransfer.setData('metric', e.target.dataset.metric);
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
}

const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    dropZone.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    
    const metric = e.dataTransfer.getData('metric');
    
    if (metric && !selectedMetricsList.includes(metric)) {
        selectedMetricsList.push(metric);
        submitForm();
    }
});

function removeMetric(metric) {
    selectedMetricsList = selectedMetricsList.filter(m => m !== metric);
    submitForm();
}

function clearAll() {
    selectedMetricsList = [];
    submitForm();
}

function submitForm() {
    document.getElementById('selectedMetrics').value = JSON.stringify(selectedMetricsList);
    document.getElementById('dashboardForm').submit();
}

// Initialize drag and drop
document.querySelectorAll('.metric-chip').forEach(chip => {
    chip.addEventListener('dragstart', handleDragStart);
    chip.addEventListener('dragend', handleDragEnd);
});

// Handle date changes
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', () => {
        submitForm();
    });
});
</script>

</body>
</html>