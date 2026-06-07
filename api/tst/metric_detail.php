<?php
/**
 * metric_detail.php — Generieke detailpagina voor één metric op één platform
 *
 * Gebruik:
 *   - Via stub-bestand (aanbevolen):
 *       <?php
 *       $metricKeyOverride = 'likes';
 *       $networkOverride   = 'instagram';
 *       require __DIR__ . '/metric_detail.php';
 *       ?>
 *   - Direct via URL:
 *       metric_detail.php?metric=likes&network=facebook&from=2025-01-01&to=2025-01-31&section=posts
 *
 * Per platform staat alle config (kleuren, API-keys, endpoints, ...) in $networkConfigs.
 * Zo hoef ik maar één bestand te onderhouden voor alle 17 detailpagina's.
 */

// ── 1. Authenticatie (zelfde token als de dashboards) ──
// userId en token zijn voor alle platforms hetzelfde.
// blogId verschilt per platform — staat daarom in de networkConfigs hieronder.
$userId = 4394337;
$token  = 'YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB';

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: {$token}",
];

// ── 2. Per-network configuratie ──
// Elke key is een Metricool-netwerknaam. Bevat per platform:
//   - label:        wat we tonen in breadcrumb/titel
//   - dashboard:    bestand om naar terug te linken
//   - cssPath:      pad naar de stylesheet vanuit deze pagina
//   - postUrlBase:  fallback-URL als de API geen directe link teruggeeft
//   - postEndpoints: lijst kandidaat-endpoints voor de posts-ranking
//                    (Metricool documenteert dit niet publiek — we proberen er meerdere)
//   - metrics:      per metric-key de bijbehorende API-naam, kleur, icoon, etc.
//
// Belangrijk: de api-namen verschillen per platform!
//   FB bereik     = 'impressionsunique'   IG bereik     = 'reach'
//   FB videoviews = 'blue_reels_play_count'   IG/TT       = 'videoviews'
$networkConfigs = [
    'facebook' => [
        'label'        => 'Facebook',
        'blogId'       => 5668624,
        'dashboard'    => 'facebook_dashboard.php',
        'cssPath'      => 'styles.css',
        'postUrlBase'  => 'https://www.facebook.com/',
        'postEndpoints'=> [
            '/api/v2/analytics/posts/facebook',
            '/api/v2/analytics/facebook/posts',
            '/api/v2/analytics/{section}/facebook',
            '/api/v2/analytics/facebook/{section}',
        ],
        'metrics' => [
            'engagement'   => ['label' => 'Engagement',  'api' => 'engagement',           'color' => '#f59e0b', 'bg' => '#fffbeb', 'isPercent' => true],
            'interactions' => ['label' => 'Interacties', 'api' => 'interactions',          'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'isPercent' => false],
            'likes'        => ['label' => 'Likes',       'api' => 'likes',                 'color' => '#ec4899', 'bg' => '#fdf2f8', 'isPercent' => false],
            'comments'     => ['label' => 'Reacties',    'api' => 'comments',              'color' => '#06b6d4', 'bg' => '#ecfeff', 'isPercent' => false],
            'shares'       => ['label' => 'Delingen',    'api' => 'shares',                'color' => '#10b981', 'bg' => '#ecfdf5', 'isPercent' => false],
            'reach'        => ['label' => 'Bereik',      'api' => 'impressionsunique',     'color' => '#3b82f6', 'bg' => '#eff6ff', 'isPercent' => false],
            'video_views'  => ['label' => 'Video views', 'api' => 'blue_reels_play_count', 'color' => '#f43f5e', 'bg' => '#fff1f2', 'isPercent' => false],
        ],
    ],

    'instagram' => [
        'label'        => 'Instagram',
        'blogId'       => 6174355,
        'dashboard'    => 'instagram_dashboard.php',
        'cssPath'      => '../CSS/styles.css',
        'postUrlBase'  => 'https://www.instagram.com/p/',
        'postEndpoints'=> [
            '/api/v2/analytics/posts/instagram',
            '/api/v2/analytics/instagram/posts',
            '/api/v2/analytics/{section}/instagram',
            '/api/v2/analytics/instagram/{section}',
        ],
        'metrics' => [
            'engagement'   => ['label' => 'Engagement',  'api' => 'engagement',   'color' => '#e1306c', 'bg' => '#fde8f0', 'isPercent' => true],
            'interactions' => ['label' => 'Interacties', 'api' => 'interactions', 'color' => '#405de6', 'bg' => '#f0f2ff', 'isPercent' => false],
            'likes'        => ['label' => 'Likes',       'api' => 'likes',        'color' => '#e1306c', 'bg' => '#fde8f0', 'isPercent' => false],
            'comments'     => ['label' => 'Reacties',    'api' => 'comments',     'color' => '#5b51d8', 'bg' => '#f3f1ff', 'isPercent' => false],
            'reach'        => ['label' => 'Bereik',      'api' => 'reach',        'color' => '#833ab4', 'bg' => '#fef0ff', 'isPercent' => false],
            'videoviews'   => ['label' => 'Video views', 'api' => 'videoviews',   'color' => '#f77737', 'bg' => '#fff4e6', 'isPercent' => false],
        ],
    ],

    'tiktokBusiness' => [
        'label'        => 'TikTok',
        'blogId'       => 5668624,  // Check of dit klopt — kijk bovenaan tiktok_dashboard.php
        'dashboard'    => 'tiktok_dashboard.php',
        'cssPath'      => '../CSS/styles.css',
        'postUrlBase'  => 'https://www.tiktok.com/',
        'postEndpoints'=> [
            '/api/v2/analytics/posts/tiktokBusiness',
            '/api/v2/analytics/tiktokBusiness/posts',
            '/api/v2/analytics/videos/tiktokBusiness',
            '/api/v2/analytics/tiktokBusiness/videos',
            '/api/v2/analytics/posts/tiktok',
            '/api/v2/analytics/tiktok/posts',
        ],
        'metrics' => [
            'engagement' => ['label' => 'Engagement', 'api' => 'engagement', 'color' => '#ff0050', 'bg' => '#fff0f5', 'isPercent' => true],
            'likes'      => ['label' => 'Likes',      'api' => 'likes',      'color' => '#ff0050', 'bg' => '#fff0f5', 'isPercent' => false],
            'comments'   => ['label' => 'Reacties',   'api' => 'comments',   'color' => '#3366ff', 'bg' => '#f0f7ff', 'isPercent' => false],
            'shares'     => ['label' => 'Delingen',   'api' => 'shares',     'color' => '#00b4d8', 'bg' => '#f0fafb', 'isPercent' => false],
            'videoviews' => ['label' => 'Video views','api' => 'videoviews', 'color' => '#000000', 'bg' => '#f5f5f5', 'isPercent' => false],
        ],
    ],
];

// ── 3. Parameters ophalen ──
// Volgorde van prioriteit: URL-parameter > override-variabele uit stub > default
$network   = $_GET['network'] ?? ($networkOverride   ?? 'facebook');
$metricKey = $_GET['metric']  ?? ($metricKeyOverride ?? 'engagement');
$from      = $_GET['from']    ?? date('Y-m-01');
$to        = $_GET['to']      ?? date('Y-m-d');
$section   = $_GET['section'] ?? 'posts';

// Veiligheid: als er een rare network-naam binnenkomt, val terug op facebook
if (!isset($networkConfigs[$network])) {
    $network = 'facebook';
}
$netConfig = $networkConfigs[$network];

// Veiligheid: als de metric niet bestaat voor dit platform, val terug op de eerste
if (!isset($netConfig['metrics'][$metricKey])) {
    $metricKey = array_key_first($netConfig['metrics']);
}
$metricInfo = $netConfig['metrics'][$metricKey];

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// ── 4. Helper-functies ──
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
    if (curl_errno($ch)) { $e = curl_error($ch); curl_close($ch); return ['error' => $e]; }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['httpCode' => $code, 'body' => json_decode($response, true), 'raw' => $response];
}

// Formatteer waarden: percentages krijgen 2 decimalen + %, andere zijn gehele getallen
function fmt($value, $isPercent) {
    if ($isPercent) return number_format((float)$value, 2, ',', '.') . '%';
    return number_format((float)$value, 0, ',', '.');
}

// ── 5. Timeline-data ophalen ──
$timelineData  = [];
$timelineError = null;
$postsData     = [];
$postsError    = null;

if ($token !== '') {
    $res = callMetricool('/api/v2/analytics/timelines', [
        'from'     => $fromIso,
        'to'       => $toIso,
        'network'  => $network,           // dynamisch i.p.v. hardcoded 'facebook'
        'timezone' => 'Europe/Brussels',
        'userId'   => $userId,
        'blogId'   => $netConfig['blogId'],  // per platform ander blogId
        'subject'  => $section,
        'metric'   => $metricInfo['api'],
    ], $headers);

    if (($res['httpCode'] ?? 0) === 200) {
        // Zoek het juiste data-blok op basis van metric-naam (de API kan meerdere blokken teruggeven)
        foreach (($res['body']['data'] ?? []) as $block) {
            if (($block['metric'] ?? '') === $metricInfo['api']) {
                $vals = $block['values'] ?? [];
                // Sorteer chronologisch zodat de grafiek correct is
                usort($vals, fn($a,$b) => strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? ''));
                $timelineData = $vals;
                break;
            }
        }
    } else {
        $timelineError = 'API fout: HTTP ' . ($res['httpCode'] ?? '?');
    }

    // ── 6. Posts ranking ophalen ──
    // Metricool documenteert geen publiek posts-endpoint, dus we proberen er meerdere
    // tot er één HTTP 200 met data teruggeeft. {section} wordt vervangen door 'posts'/'reels'/'videos'.
    $basePostParams = [
        'from'     => $fromIso,
        'to'       => $toIso,
        'timezone' => 'Europe/Brussels',
        'userId'   => $userId,
        'blogId'   => $netConfig['blogId'],  // per platform ander blogId
        'orderBy'  => $metricInfo['api'],
        'orderDir' => 'desc',
        'limit'    => 10,
    ];

    $triedEndpoints = [];
    foreach ($netConfig['postEndpoints'] as $epTemplate) {
        $ep = str_replace('{section}', $section, $epTemplate);
        $postsRes = callMetricool($ep, $basePostParams, $headers);
        $triedEndpoints[] = $ep . ' → HTTP ' . ($postsRes['httpCode'] ?? '?');
        if (($postsRes['httpCode'] ?? 0) === 200) {
            $postsData = $postsRes['body']['data'] ?? $postsRes['body'] ?? [];
            if (!empty($postsData)) break;
        }
    }

    if (empty($postsData)) {
        $postsError = 'Geen posts data gevonden. Geprobeerde endpoints:<br><code style="font-size:11px">'
            . implode('<br>', array_map('htmlspecialchars', $triedEndpoints))
            . '</code><br><br>'
            . '<strong>Tip: Hoe het juiste endpoint vinden:</strong> Open Metricool in Chrome, '
            . 'ga naar Analytics &gt; ' . htmlspecialchars($netConfig['label']) . ', open DevTools (F12) &gt; Network &gt; Fetch/XHR, '
            . 'en kijk welk endpoint Metricool zelf gebruikt voor de posts-lijst. '
            . 'Voeg dat endpoint toe aan <code>postEndpoints</code> in <code>metric_detail.php</code>.';
    }
}

// ── 7. Statistieken berekenen ──
$numericVals = array_map(fn($r) => (float)($r['value'] ?? 0), $timelineData);
$avg    = count($numericVals) ? array_sum($numericVals) / count($numericVals) : 0;
$maxVal = count($numericVals) ? max($numericVals) : 0;
$minVal = count($numericVals) ? min($numericVals) : 0;
// Mediaan: middelste waarde. Bij even aantal nemen we het gemiddelde van de twee middelste
$sorted = $numericVals; sort($sorted); $c = count($sorted);
$median = $c ? ($c % 2 === 0 ? ($sorted[$c/2-1] + $sorted[$c/2]) / 2 : $sorted[(int)floor($c/2)]) : 0;

// Data voor de Chart.js grafiek (alleen datum-deel, geen tijd)
$chartLabels = array_map(fn($r) => substr($r['dateTime'] ?? '', 0, 10), $timelineData);
$chartValues = $numericVals;

// Korte aliassen voor in de HTML
$color  = $metricInfo['color'];
$bg     = $metricInfo['bg'];

$label  = $metricInfo['label'];
$isPct  = $metricInfo['isPercent'];

// Voor breadcrumb en terug-knop
$dashboardLink = $netConfig['dashboard'];
$platformLabel = $netConfig['label'];
$cssPath       = $netConfig['cssPath'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($label) ?> — <?= htmlspecialchars($platformLabel) ?> · SkyByte</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background: #f0f2f7; }

        .sb-page { max-width: 1320px; margin: 0 auto; }

        /* ── Breadcrumb ── */
        .sb-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #9aa3b4;
            margin-bottom: 20px;
        }
        .sb-breadcrumb a { color: #9aa3b4; text-decoration: none; }
        .sb-breadcrumb a:hover { color: #3b82f6; }
        .sb-breadcrumb-sep { font-size: 11px; }
        .sb-breadcrumb-current { color: #1a2233; font-weight: 600; }

        /* ── Header ── */
        .sb-detail-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .sb-detail-title {
            font-size: 24px;
            font-weight: 800;
            color: #1a2233;
            letter-spacing: -0.5px;
        }
        .sb-detail-subtitle {
            font-size: 13px;
            color: #9aa3b4;
            margin-top: 2px;
        }

        /* ── Stat cards ── */
        .sb-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .sb-stat-card {
            background: #fff;
            border: 1px solid #e4e8ef;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            position: relative;
            overflow: hidden;
        }
        .sb-stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: <?= $color ?>;
        }
        .sb-stat-card-label {
            font-size: 11px;
            font-weight: 700;
            color: #9aa3b4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .sb-stat-card-value {
            font-size: 26px;
            font-weight: 800;
            color: <?= $color ?>;
            letter-spacing: -1px;
            line-height: 1;
        }

        /* ── Panel (chart + posts) ── */
        .sb-panel {
            background: #fff;
            border: 1px solid #e4e8ef;
            border-radius: 12px;
            padding: 22px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            margin-bottom: 18px;
        }
        .sb-panel-title {
            font-size: 13px;
            font-weight: 700;
            color: #1a2233;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sb-panel-title-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: <?= $color ?>;
            flex-shrink: 0;
        }

        .sb-chart-wrap {
            position: relative;
            height: 260px;
        }

        /* ── Posts tabel ── */
        .sb-posts-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sb-posts-table th {
            font-size: 10px;
            font-weight: 700;
            color: #9aa3b4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 12px 10px;
            text-align: left;
            border-bottom: 1px solid #f0f2f7;
        }
        .sb-posts-table th:last-child { text-align: right; }
        .sb-posts-table td {
            padding: 11px 12px;
            font-size: 13px;
            color: #3a4460;
            border-bottom: 1px solid #f7f8fb;
            vertical-align: middle;
        }
        .sb-posts-table tr:last-child td { border-bottom: none; }
        .sb-posts-table tr:hover td { background: #fafbfd; }

        .sb-post-row.clickable { cursor: pointer; }
        .sb-post-row.clickable:hover td { background: <?= $bg ?>; }

        .sb-post-value-wrap {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
        }
        .sb-post-ext {
            width: 13px;
            height: 13px;
            color: <?= $color ?>;
            opacity: 0;
            transition: opacity .15s;
            flex-shrink: 0;
        }
        .sb-post-row.clickable:hover .sb-post-ext { opacity: 1; }

        .sb-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            background: <?= $bg ?>;
            color: <?= $color ?>;
        }
        .sb-rank-badge.top { background: <?= $color ?>; color: #fff; }

        .sb-post-text {
            max-width: 380px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 13px;
        }
        .sb-post-date {
            font-size: 12px;
            color: #9aa3b4;
            white-space: nowrap;
        }
        .sb-post-value {
            font-size: 15px;
            font-weight: 700;
            color: <?= $color ?>;
            text-align: right;
        }

        .sb-post-thumb {
            width: 38px;
            height: 38px;
            border-radius: 7px;
            object-fit: cover;
            background: <?= $bg ?>;
            flex-shrink: 0;
        }
        .sb-post-thumb-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sb-info-state {
            padding: 40px 20px;
            text-align: center;
            color: #b0bac8;
            font-size: 13px;
        }

        .sb-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid #e4e8ef;
            border-radius: 8px;
            background: #fff;
            font-size: 13px;
            font-weight: 600;
            color: #7a8599;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all .15s;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sb-back:hover { color: <?= $color ?>; border-color: <?= $color ?>; }

        @media (max-width: 768px) {
            .sb-stats-row { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .sb-stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="sb-page" style="--metric-color: <?= htmlspecialchars($color) ?>; --metric-bg: <?= htmlspecialchars($bg) ?>;">

    <!-- Navbar: het 'active' platform wordt dynamisch gezet op basis van $network -->
    <nav class="navbar">
        <a href="config.php" class="nav-link">Inbox</a>
        <a href="facebook_dashboard.php"  class="nav-link <?= $network === 'facebook'       ? 'active' : '' ?>">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link <?= $network === 'instagram'      ? 'active' : '' ?>">Instagram</a>
        <a href="tiktok_dashboard.php"    class="nav-link <?= $network === 'tiktokBusiness' ? 'active' : '' ?>">TikTok</a>
    </nav>

    <!-- Breadcrumb -->
    <div class="sb-breadcrumb">
        <a href="<?= htmlspecialchars($dashboardLink) ?>"><?= htmlspecialchars($platformLabel) ?></a>
        <span class="sb-breadcrumb-sep">›</span>
        <span class="sb-breadcrumb-current"><?= htmlspecialchars($label) ?></span>
    </div>

    <!-- Terug-knop met behoud van datumrange -->
    <a href="<?= htmlspecialchars($dashboardLink) ?>?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
       class="sb-back">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Terug naar dashboard
    </a>

    <!-- Header met icoon, titel en datumrange -->
    <div class="sb-detail-header">
        <div>
            <div class="sb-detail-title"><?= htmlspecialchars($label) ?></div>
            <div class="sb-detail-subtitle">
                <?= htmlspecialchars($platformLabel) ?> ·
                <?= htmlspecialchars(ucfirst($section)) ?> ·
                <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?>
            </div>
        </div>
    </div>

    <!-- Statistiek-kaartjes: gemiddelde / hoogste / laagste / mediaan -->
    <div class="sb-stats-row">
        <div class="sb-stat-card">
            <div class="sb-stat-card-label">Gemiddelde</div>
            <div class="sb-stat-card-value"><?= fmt($avg, $isPct) ?></div>
        </div>
        <div class="sb-stat-card">
            <div class="sb-stat-card-label">Hoogste</div>
            <div class="sb-stat-card-value"><?= fmt($maxVal, $isPct) ?></div>
        </div>
        <div class="sb-stat-card">
            <div class="sb-stat-card-label">Laagste</div>
            <div class="sb-stat-card-value"><?= fmt($minVal, $isPct) ?></div>
        </div>
        <div class="sb-stat-card">
            <div class="sb-stat-card-label">Mediaan</div>
            <div class="sb-stat-card-value"><?= fmt($median, $isPct) ?></div>
        </div>
    </div>

    <!-- Grafiek: evolutie over tijd -->
    <div class="sb-panel">
        <div class="sb-panel-title">
            <div class="sb-panel-title-dot"></div>
            Evolutie over tijd
        </div>
        <?php if (empty($timelineData)): ?>
            <div class="sb-info-state">
                <?= $token === '' ? 'Voeg je API-token toe om data te laden.' : ($timelineError ?? 'Geen data beschikbaar voor deze periode.') ?>
            </div>
        <?php else: ?>
            <div class="sb-chart-wrap">
                <canvas id="timelineChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <!-- Posts ranking: beste posts/reels/videos voor deze metric -->
    <div class="sb-panel">
        <div class="sb-panel-title">
            <div class="sb-panel-title-dot"></div>
            Beste <?= htmlspecialchars($section) ?> op <?= htmlspecialchars($label) ?>
        </div>

        <?php if ($token === ''): ?>
            <div class="sb-info-state">Voeg je API-token toe om posts te laden.</div>
        <?php elseif (!empty($postsError) && empty($postsData)): ?>
            <div class="sb-info-state" style="text-align:left; padding: 20px 24px; line-height:1.8;"><?= $postsError ?></div>
        <?php elseif (empty($postsData)): ?>
            <div class="sb-info-state">Geen posts gevonden in deze periode.</div>
        <?php else: ?>
            <table class="sb-posts-table">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Post</th>
                        <th style="width:110px;">Datum</th>
                        <th style="width:100px;"><?= htmlspecialchars($label) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postsData as $i => $post):
                        $rank     = $i + 1;
                        // Verschillende API endpoints gebruiken verschillende veldnamen voor tekst
                        // FB: 'text'/'message', IG: 'content', TT: 'caption'
                        $text     = $post['text'] ?? $post['message'] ?? $post['content'] ?? $post['caption'] ?? '(geen tekst)';
                        // Datum: FB = 'publishDate', IG = 'publishedAt' (genest object met dateTime)
                        $dateRaw  = $post['publishDate'] ?? $post['date'] ?? $post['createdAt'] ?? $post['publishedAt']['dateTime'] ?? '';
                        $dateStr  = $dateRaw ? date('d M Y', strtotime($dateRaw)) : '—';
                        // Thumbnail: FB = 'thumbnail', IG = 'imageUrl'
                        $thumb    = $post['thumbnail'] ?? $post['image'] ?? $post['imageUrl'] ?? null;

                        // Link naar originele post: zoek in alle bekende veldnamen
                        $postUrl = $post['url']
                            ?? $post['postUrl']
                            ?? $post['permalink']
                            ?? $post['permalinkUrl']
                            ?? $post['link']
                            ?? $post['externalUrl']
                            ?? null;

                        // Als geen directe URL maar wel een post-id, bouw de URL zelf op
                        // (de basis-URL komt uit netConfig, dus verschilt per platform)
                        if (!$postUrl) {
                            $postId = $post['id'] ?? $post['postId'] ?? $post['providerPostId'] ?? null;
                            if ($postId) {
                                $postUrl = $netConfig['postUrlBase'] . urlencode($postId);
                            }
                        }

                        // Metric-waarde voor deze post: probeer meerdere veldlocaties
                        $metricVal = $post[$metricInfo['api']]
                            ?? $post[$metricKey]
                            ?? $post['stats'][$metricInfo['api']]
                            ?? $post['metrics'][$metricInfo['api']]
                            ?? null;
                    ?>
                    <tr class="<?= $postUrl ? 'sb-post-row clickable' : 'sb-post-row' ?>"
                        <?php if ($postUrl): ?>onclick="window.open('<?= htmlspecialchars($postUrl) ?>', '_blank', 'noopener')"<?php endif; ?>>
                        <td>
                            <span class="sb-rank-badge <?= $rank <= 3 ? 'top' : '' ?>">
                                <?= $rank ?>
                            </span>
                        </td>
                        <td>
                            <div class="sb-post-thumb-wrap">
                                <?php if ($thumb): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" class="sb-post-thumb" alt="">
                                <?php else: ?>
                                    <div class="sb-post-thumb"></div>
                                <?php endif; ?>
                                <span class="sb-post-text" title="<?= htmlspecialchars($text) ?>">
                                    <?= htmlspecialchars($text) ?>
                                </span>
                            </div>
                        </td>
                        <td class="sb-post-date"><?= $dateStr ?></td>
                        <td class="sb-post-value">
                            <div class="sb-post-value-wrap">
                                <span><?= $metricVal !== null ? fmt($metricVal, $isPct) : '—' ?></span>
                                <?php if ($postUrl): ?>
                                    <svg class="sb-post-ext" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                        <polyline points="15 3 21 3 21 9"/>
                                        <line x1="10" y1="14" x2="21" y2="3"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<?php if (!empty($timelineData)): ?>
<script>
// Data uit PHP doorgeven aan JavaScript voor de Chart.js grafiek
const labels = <?= json_encode($chartLabels) ?>;
const values = <?= json_encode($chartValues) ?>;
const color  = '<?= $color ?>';
const bg     = '<?= $bg ?>';
const isPct  = <?= $isPct ? 'true' : 'false' ?>;

const ctx = document.getElementById('timelineChart').getContext('2d');

// Gradient-vulling voor onder de lijn (van metric-kleur naar transparant)
const gradient = ctx.createLinearGradient(0, 0, 0, 260);
gradient.addColorStop(0, color + '30');
gradient.addColorStop(1, color + '00');

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            data: values,
            borderColor: color,
            borderWidth: 2.5,
            backgroundColor: gradient,
            fill: true,
            tension: 0.4,
            // Bij veel datapunten geen losse stippen, anders 4px
            pointRadius: values.length > 30 ? 0 : 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderColor: color,
            pointBorderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a2233',
                titleColor: '#9aa3b4',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    // Tooltip-waarden formatteren met komma's voor NL en %-teken indien percentage
                    label: ctx => {
                        const v = ctx.parsed.y;
                        return ' ' + (isPct
                            ? v.toFixed(2).replace('.', ',') + '%'
                            : Math.round(v).toLocaleString('nl-BE'));
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: {
                    color: '#9aa3b4',
                    font: { size: 11 },
                    maxTicksLimit: 10,
                    maxRotation: 0,
                }
            },
            y: {
                grid: { color: '#f0f2f7', drawBorder: false },
                border: { display: false, dash: [4,4] },
                ticks: {
                    color: '#9aa3b4',
                    font: { size: 11 },
                    callback: v => isPct ? v.toFixed(1) + '%' : v.toLocaleString('nl-BE'),
                }
            }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>