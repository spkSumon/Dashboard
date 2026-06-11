<?php
/**
 * metric_detail.php — Detailpagina voor één metric op één platform.
 *
 * Gebruik: metric_detail.php?metric=likes&network=facebook&from=2025-01-01&to=2025-01-31&section=posts
 */
require_once __DIR__ . '/token.php';

// ── Per-network configuratie ──
$networkConfigs = [
    'facebook' => [
        'label'        => 'Facebook',
        'dashboard'    => 'facebook_dashboard.php',
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
        'dashboard'    => 'instagram_dashboard.php',
        'postUrlBase'  => 'https://www.instagram.com/p/',
        'postEndpoints'=> [
            '/api/v2/analytics/posts/instagram',
            '/api/v2/analytics/instagram/posts',
            '/api/v2/analytics/reels/instagram',
            '/api/v2/analytics/instagram/reels',
            '/api/v2/analytics/{section}/instagram',
            '/api/v2/analytics/instagram/{section}',
        ],
        'metrics' => [
            'engagement'   => ['label' => 'Engagement',  'api' => 'engagement',   'color' => '#e1306c', 'bg' => '#fde8f0', 'isPercent' => true],
            'interactions' => ['label' => 'Interacties', 'api' => 'interactions', 'color' => '#405de6', 'bg' => '#f0f2ff', 'isPercent' => false],
            'likes'        => ['label' => 'Likes',       'api' => 'likes',        'color' => '#e1306c', 'bg' => '#fde8f0', 'isPercent' => false],
            'comments'     => ['label' => 'Reacties',    'api' => 'comments',     'color' => '#5b51d8', 'bg' => '#f3f1ff', 'isPercent' => false],
            'shares'       => ['label' => 'Delingen',    'api' => 'shares',       'color' => '#10b981', 'bg' => '#ecfdf5', 'isPercent' => false],
            'plays'        => ['label' => 'Plays',       'api' => 'plays',        'color' => '#f77737', 'bg' => '#fff4e6', 'isPercent' => false],
            'reach'        => ['label' => 'Bereik',      'api' => 'reach',        'color' => '#833ab4', 'bg' => '#fef0ff', 'isPercent' => false],
            'videoviews'   => ['label' => 'Video Views', 'api' => 'views',        'color' => '#f77737', 'bg' => '#fff4e6', 'isPercent' => false],
        ],
    ],
    'tiktokBusiness' => [
        'label'        => 'TikTok',
        'dashboard'    => 'tiktok_dashboard.php',
        'apiNetwork'   => 'tiktok',
        'postUrlBase'  => 'https://www.tiktok.com/@user/video/',
        'postEndpoints'=> [
            '/api/v2/analytics/posts/tiktokBusiness',
            '/api/v2/analytics/tiktokBusiness/posts',
            '/api/v2/analytics/videos/tiktokBusiness',
            '/api/v2/analytics/tiktokBusiness/videos',
            '/api/v2/analytics/posts/tiktok',
            '/api/v2/analytics/tiktok/posts',
        ],
        'textFields'   => ['videoDescription', 'desc', 'text', 'message', 'content', 'caption', 'description'],
        'dateFields'   => ['createTime', 'publishDate', 'date', 'createdAt', 'publishedAt'],
        'urlFields'    => ['shareUrl', 'url', 'postUrl', 'permalink', 'link', 'videoUrl'],
        'metrics' => [
            'engagement' => ['label' => 'Engagement', 'api' => 'engagement',
                             'timelineApi' => ['engagement'], 'orderByApi' => 'engagement',
                             'color' => '#ff0050', 'bg' => '#fff0f5', 'isPercent' => true],
            'likes'      => ['label' => 'Likes', 'api' => 'likes',
                             'timelineApi' => ['likes'], 'orderByApi' => 'diggCount',
                             'color' => '#ff0050', 'bg' => '#fff0f5', 'isPercent' => false],
            'comments'   => ['label' => 'Reacties', 'api' => 'comments',
                             'timelineApi' => ['comments'], 'orderByApi' => 'commentCount',
                             'color' => '#3366ff', 'bg' => '#f0f7ff', 'isPercent' => false],
            'shares'     => ['label' => 'Delingen', 'api' => 'shares',
                             'timelineApi' => ['shares'], 'orderByApi' => 'shareCount',
                             'color' => '#00b4d8', 'bg' => '#f0fafb', 'isPercent' => false],
            'videoviews' => ['label' => 'Video views', 'api' => 'video',
                             'timelineApi' => ['video', 'videoViews', 'playCount', 'views', 'videoviews'],
                             'orderByApi' => 'playCount',
                             'color' => '#000000', 'bg' => '#f5f5f5', 'isPercent' => false],
        ],
    ],
];

// ── Parameters ophalen ──
$network   = $_GET['network'] ?? 'facebook';
$metricKey = $_GET['metric']  ?? 'engagement';
$from      = $_GET['from']    ?? date('Y-m-01');
$to        = $_GET['to']      ?? date('Y-m-d');
$section   = $_GET['section'] ?? 'posts';

// Aliassen normaliseren
$networkAliases = ['tiktok' => 'tiktokBusiness', 'tiktokbusiness' => 'tiktokBusiness'];
if (isset($networkAliases[strtolower($network)])) $network = $networkAliases[strtolower($network)];
if (!isset($networkConfigs[$network])) $network = 'facebook';

$netConfig  = $networkConfigs[$network];
$apiNetwork = $netConfig['apiNetwork'] ?? $network;

if (!isset($netConfig['metrics'][$metricKey])) $metricKey = array_key_first($netConfig['metrics']);
$metricInfo = $netConfig['metrics'][$metricKey];

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

function fmt(float $value, bool $isPercent): string {
    return $isPercent
        ? number_format($value, 2, ',', '.') . '%'
        : number_format($value, 0, ',', '.');
}

// ── Timeline-data ophalen ──
$timelineData   = [];
$timelineError  = null;
$postsData      = [];
$timelineLookup = [];

$timelineCandidates = $metricInfo['timelineApi'] ?? [$metricInfo['api']];

foreach ($timelineCandidates as $tlMetric) {
    $res = callMetricool('/api/v2/analytics/timelines', [
        'from' => $fromIso, 'to' => $toIso,
        'network' => $apiNetwork, 'timezone' => 'Europe/Brussels',
        'userId' => $userId, 'blogId' => $blogId,
        'subject' => $section, 'metric' => $tlMetric,
    ], $headers);

    if (($res['httpCode'] ?? 0) === 200) {
        foreach (($res['body']['data'] ?? []) as $block) {
            if (($block['metric'] ?? '') === $tlMetric && !empty($block['values'])) {
                $vals = $block['values'];
                usort($vals, fn($a, $b) => strcmp($a['dateTime'] ?? '', $b['dateTime'] ?? ''));
                $timelineData = $vals;
                break 2;
            }
        }
    } else {
        $timelineError = 'API fout: HTTP ' . ($res['httpCode'] ?? '?');
    }
}

// ── Posts/Reels ranking ophalen ──
$orderByField  = $metricInfo['orderByApi'] ?? $metricInfo['api'];
$basePostParams = [
    'from' => $fromIso, 'to' => $toIso,
    'timezone' => 'Europe/Brussels',
    'userId' => $userId, 'blogId' => $blogId,
    'orderBy' => $orderByField, 'orderDir' => 'desc', 'limit' => 10,
];

foreach ($netConfig['postEndpoints'] as $epTemplate) {
    $ep       = str_replace('{section}', $section, $epTemplate);
    $postsRes = callMetricool($ep, $basePostParams, $headers);
    if (($postsRes['httpCode'] ?? 0) === 200) {
        $postsData = $postsRes['body']['data'] ?? $postsRes['body'] ?? [];
        if (!empty($postsData)) break;
    }
}

// ── Datum-lookup van timeline ──
foreach ($timelineData as $row) {
    $day = substr($row['dateTime'] ?? '', 0, 10);
    if ($day) $timelineLookup[$day] = (float)($row['value'] ?? 0);
}

// ── Posts sorteren op datum-waarde ──
if (!empty($postsData)) {
    $cfgDateFields = $netConfig['dateFields'] ?? ['publishDate', 'date', 'createdAt'];
    foreach ($postsData as &$p) {
        $dr = null;
        foreach ($cfgDateFields as $f) { if (!empty($p[$f])) { $dr = $p[$f]; break; } }
        $p['_sortDate'] = is_numeric($dr) ? date('Y-m-d', (int)$dr) : ($dr ? substr($dr, 0, 10) : '');
    }
    unset($p);

    usort($postsData, function($a, $b) use ($timelineLookup) {
        return ($timelineLookup[$b['_sortDate']] ?? 0) <=> ($timelineLookup[$a['_sortDate']] ?? 0);
    });
}

// ── Statistieken berekenen ──
$numericVals = array_map(fn($r) => (float)($r['value'] ?? 0), $timelineData);
$avg    = count($numericVals) ? array_sum($numericVals) / count($numericVals) : 0;
$maxVal = count($numericVals) ? max($numericVals) : 0;
$minVal = count($numericVals) ? min($numericVals) : 0;
$sorted = $numericVals; sort($sorted); $c = count($sorted);
$median = $c ? ($c % 2 === 0 ? ($sorted[$c/2-1] + $sorted[$c/2]) / 2 : $sorted[(int)floor($c/2)]) : 0;

$chartLabels = array_map(fn($r) => substr($r['dateTime'] ?? '', 0, 10), $timelineData);
$chartValues = $numericVals;

$color         = $metricInfo['color'];
$bg            = $metricInfo['bg'];
$label         = $metricInfo['label'];
$isPct         = $metricInfo['isPercent'];
$dashboardLink = $netConfig['dashboard'];
$platformLabel = $netConfig['label'];
$sectionLabel  = ucfirst($section);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($label) ?> — <?= htmlspecialchars($platformLabel) ?> · SkyByte</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>:root { --accent: <?= $color ?>; }</style>
</head>
<body>
<div class="sb-page">

    <nav class="navbar">
        <a href="config.php" class="nav-link">Inbox</a>
        <a href="facebook_dashboard.php"  class="nav-link <?= $network === 'facebook'       ? 'active' : '' ?>">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link <?= $network === 'instagram'      ? 'active' : '' ?>">Instagram</a>
        <a href="tiktok_dashboard.php"    class="nav-link <?= $network === 'tiktokBusiness' ? 'active' : '' ?>">TikTok</a>
    </nav>

    <div class="sb-breadcrumb">
        <a href="<?= htmlspecialchars($dashboardLink) ?>"><?= htmlspecialchars($platformLabel) ?></a>
        <span class="sb-breadcrumb-sep">›</span>
        <span class="sb-breadcrumb-current"><?= htmlspecialchars($sectionLabel) ?> · <?= htmlspecialchars($label) ?></span>
    </div>

    <a href="<?= htmlspecialchars($dashboardLink) ?>?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="sb-back">
        ← Terug naar dashboard
    </a>

    <div class="sb-detail-header">
        <p class="sb-title"><?= htmlspecialchars($label) ?></p>
        <p class="sb-subtitle">
            <?= htmlspecialchars($platformLabel) ?> · <?= htmlspecialchars($sectionLabel) ?> ·
            <?= date('d M Y', strtotime($from)) ?> – <?= date('d M Y', strtotime($to)) ?>
        </p>
    </div>

    <div class="stats-grid sb-detail-stats">
        <div class="stat-item"><div class="stat-label">Gemiddelde</div><div class="stat-value"><?= fmt($avg, $isPct) ?></div></div>
        <div class="stat-item"><div class="stat-label">Hoogste</div><div class="stat-value"><?= fmt($maxVal, $isPct) ?></div></div>
        <div class="stat-item"><div class="stat-label">Laagste</div><div class="stat-value"><?= fmt($minVal, $isPct) ?></div></div>
        <div class="stat-item"><div class="stat-label">Mediaan</div><div class="stat-value"><?= fmt($median, $isPct) ?></div></div>
    </div>

    <!-- Grafiek -->
    <div class="sb-detail-section">
        <h2 class="sb-section-label">Evolutie over tijd</h2>
        <?php if (empty($timelineData)): ?>
            <p class="sb-empty-list"><?= htmlspecialchars($timelineError ?? 'Geen data beschikbaar voor deze periode.') ?></p>
        <?php else: ?>
            <div class="sb-chart-wrap"><canvas id="timelineChart"></canvas></div>
        <?php endif; ?>
    </div>

    <!-- Posts ranking -->
    <div class="sb-detail-section">
        <h2 class="sb-section-label">Beste <?= htmlspecialchars($sectionLabel) ?> op <?= htmlspecialchars($label) ?></h2>
        <?php if (empty($postsData)): ?>
            <p class="sb-empty-list">Geen <?= htmlspecialchars($section) ?> gevonden in deze periode.</p>
        <?php else: ?>
            <table class="sb-detail-table">
                <thead>
                    <tr>
                        <th class="col-rank">#</th>
                        <th><?= htmlspecialchars($sectionLabel) ?></th>
                        <th class="col-date">Datum</th>
                        <th class="col-metric"><?= htmlspecialchars($label) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cfgTextFields = $netConfig['textFields'] ?? ['text', 'message', 'content', 'caption'];
                    $cfgDateFields = $netConfig['dateFields'] ?? ['publishDate', 'date', 'createdAt'];
                    $cfgUrlFields  = $netConfig['urlFields']  ?? ['url', 'postUrl', 'permalink', 'permalinkUrl', 'link'];
                    $shownTotal    = 0;

                    foreach ($postsData as $i => $post):
                        $rank = $i + 1;

                        // Tekst
                        $text = null;
                        foreach ($cfgTextFields as $f) { if (!empty($post[$f])) { $text = $post[$f]; break; } }
                        $text = $text ?? '(geen tekst)';

                        // Datum
                        $dateRaw = null;
                        foreach ($cfgDateFields as $f) { if (!empty($post[$f])) { $dateRaw = $post[$f]; break; } }
                        $dateStr = is_numeric($dateRaw)
                            ? date('d M Y', (int)$dateRaw)
                            : ($dateRaw ? date('d M Y', strtotime($dateRaw)) : '—');

                        // Thumbnail
                        $thumb = $post['thumbnail'] ?? $post['thumbnailUrl'] ?? $post['coverUrl']
                               ?? $post['image'] ?? $post['imageUrl'] ?? null;

                        // URL
                        $postUrl = null;
                        foreach ($cfgUrlFields as $f) { if (!empty($post[$f])) { $postUrl = $post[$f]; break; } }
                        if (!$postUrl) {
                            $postId = $post['id'] ?? $post['postId'] ?? $post['videoId'] ?? $post['providerPostId'] ?? null;
                            if ($postId) $postUrl = $netConfig['postUrlBase'] . urlencode($postId);
                        }

                        // Metric-waarde: zoek in post-velden en timeline-lookup
                        $apiName       = $metricInfo['api'];
                        $valCandidates = [$apiName, $metricKey,
                            'videoViews', 'playCount', 'play_count', 'video_views', 'views',
                            'diggCount', 'digg_count', 'shareCount', 'share_count', 'commentCount', 'comment_count'];

                        $findVal = function(array $arr, array $keys) use (&$findVal) {
                            foreach ($keys as $k) {
                                if (isset($arr[$k]) && is_numeric($arr[$k])) return (float)$arr[$k];
                            }
                            foreach ($arr as $v) {
                                if (is_array($v)) { $found = $findVal($v, $keys); if ($found !== null) return $found; }
                            }
                            return null;
                        };

                        $metricVal = $findVal($post, $valCandidates);
                        $postDay   = $post['_sortDate'] ?? '';
                        if ($postDay && isset($timelineLookup[$postDay])) {
                            $tlVal = $timelineLookup[$postDay];
                            if ($metricVal === null || ($tlVal > 0 && $tlVal > $metricVal * 10)) {
                                $metricVal = $tlVal;
                            }
                        }

                        if ($metricVal !== null) $shownTotal += (float)$metricVal;

                        $rowClass = $postUrl ? 'sb-post-row sb-post-row--link' : 'sb-post-row';
                        $topRow   = $rank <= 3 ? 'sb-rank--top' : '';
                    ?>
                    <tr class="<?= $rowClass ?>"
                        <?php if ($postUrl): ?>onclick="window.open('<?= htmlspecialchars($postUrl) ?>', '_blank', 'noopener')"<?php endif; ?>>
                        <td><span class="sb-rank <?= $topRow ?>"><?= $rank ?></span></td>
                        <td>
                            <div class="sb-post-thumb-wrap">
                                <?php if ($thumb): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" class="sb-post-thumb" alt="">
                                <?php else: ?>
                                    <div class="sb-post-thumb sb-post-thumb--empty"></div>
                                <?php endif; ?>
                                <span class="sb-post-text" title="<?= htmlspecialchars($text) ?>"><?= htmlspecialchars($text) ?></span>
                            </div>
                        </td>
                        <td class="sb-post-date"><?= $dateStr ?></td>
                        <td class="sb-metric-value"><?= $metricVal !== null ? fmt($metricVal, $isPct) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="sb-total-row">
                        <td colspan="3" class="sb-total-label">Totaal (top <?= count($postsData) ?>)</td>
                        <td class="sb-metric-value sb-total-value"><?= fmt($shownTotal, $isPct) ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($timelineData)): ?>
<script>
const labels = <?= json_encode($chartLabels) ?>;
const values = <?= json_encode($chartValues) ?>;
const color  = '<?= $color ?>';
const isPct  = <?= $isPct ? 'true' : 'false' ?>;

const ctx = document.getElementById('timelineChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 280);
gradient.addColorStop(0, color + '22');
gradient.addColorStop(1, color + '00');

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            data: values,
            borderColor: color,
            borderWidth: 2,
            backgroundColor: gradient,
            fill: true,
            tension: 0.4,
            pointRadius: values.length > 30 ? 0 : 3,
            pointHoverRadius: 5,
            pointBackgroundColor: 'var(--bg)',
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
                backgroundColor: 'var(--ink)',
                titleColor: 'rgba(255,255,250,0.55)',
                bodyColor: 'var(--brand-white)',
                padding: 12,
                cornerRadius: 0,
                callbacks: {
                    label: ctx => {
                        const v = ctx.parsed.y;
                        return ' ' + (isPct ? v.toFixed(2).replace('.', ',') + '%' : Math.round(v).toLocaleString('nl-BE'));
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: 'var(--ink-soft)', font: { size: 11 }, maxTicksLimit: 10, maxRotation: 0 }
            },
            y: {
                grid: { color: 'var(--rule-soft)', drawBorder: false },
                border: { display: false },
                ticks: {
                    color: 'var(--ink-soft)',
                    font: { size: 11 },
                    callback: v => isPct ? v.toFixed(1) + '%' : v.toLocaleString('nl-BE')
                }
            }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>
