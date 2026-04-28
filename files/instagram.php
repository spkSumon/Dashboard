<?php
/**
 * instagram.php
 * Instagram metrics dashboard.
 *
 * Ondersteunde secties (via Metricool API):
 *   - posts   : engagement, interactions
 *   - reels   : engagement, interactions, video_views (ig_reels_video_view_total)
 *   - stories : interactions
 *
 * Metricool endpoint: /api/v2/analytics/timelines?network=instagram&subject=<section>&metric=<metric>
 */

require_once __DIR__ . '/api/metricool_service.php';
require_once __DIR__ . '/ui/ui_helpers.php';

// ─── Parameters ───────────────────────────────────────────────────────────────

$from        = $_GET['from']         ?? date('Y-m-01');
$to          = $_GET['to']           ?? date('Y-m-d');
$metricMode  = $_GET['metric_mode']  ?? 'both';
$sectionMode = $_GET['section_mode'] ?? 'all';
$showDebug   = isset($_GET['debug']);

if (!in_array($metricMode, ['engagement','interactions','both'], true)) $metricMode = 'both';
if ($from > $to) $from = $to;

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// ─── Data laden ───────────────────────────────────────────────────────────────

$data   = loadPlatformData('instagram', $fromIso, $toIso, $sectionMode, $metricMode);
$config = $data['config'] ?? [];

$sectionOptions = array_map(fn($s) => $s['label'], $config['sections'] ?? []);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instagram – Metricool Dashboard</title>
    <?php renderStyles($config['color'] ?? '#E1306C'); ?>
    <style>
        /* Instagram gradient accent override */
        .section-card { border-top-color: #E1306C; }
        .nav-link.active { background: #fce4ec; color: #E1306C; }
    </style>
</head>
<body>

<?php renderNavbar('instagram'); ?>

<div class="header-card">
    <h1>
        📸 Instagram Dashboard
        <span class="platform-badge" style="background:#fce4ec;color:#E1306C;">Instagram</span>
    </h1>
    <p style="color:#888;margin:0;font-size:14px;">
        Periode: <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?>
    </p>
    <div style="margin-top:12px;padding:10px 14px;background:#fce4ec;border-radius:8px;font-size:13px;color:#880e4f;">
        ℹ️ <strong>Instagram API notities:</strong>
        Stories-data wordt beperkt door de Meta API (max. 14 dagen terug).
        Engagement voor stories is niet beschikbaar via de Metricool API.
    </div>
</div>

<?php renderFilterForm($from, $to, $metricMode, $sectionMode, $sectionOptions); ?>

<?php if ($data['globalError'] ?? null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($data['globalError']) ?></div>
<?php else: ?>
    <?php renderPlatformData($data, $from, $to, $showDebug); ?>
<?php endif; ?>

</body>
</html>
