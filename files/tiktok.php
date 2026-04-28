<?php
/**
 * tiktok.php
 * TikTok metrics dashboard.
 *
 * Ondersteunde secties (via Metricool API):
 *   - posts : interactions, video_views
 *
 * Verschil t.o.v. Facebook/Instagram:
 *   - Geen engagement% metric beschikbaar via Metricool TikTok API
 *   - Geen reels/stories splitsing (alles is "videos")
 *   - Business accounts hebben meer metrics dan personal accounts
 *   - video_views = directe Metricool-naam (niet blue_reels_play_count)
 *
 * Metricool endpoint: /api/v2/analytics/timelines?network=tiktok&subject=posts&metric=<metric>
 */

require_once __DIR__ . '/api/metricool_service.php';
require_once __DIR__ . '/ui/ui_helpers.php';

// ─── Parameters ───────────────────────────────────────────────────────────────

$from        = $_GET['from']         ?? date('Y-m-01');
$to          = $_GET['to']           ?? date('Y-m-d');
$metricMode  = $_GET['metric_mode']  ?? 'both';
$sectionMode = $_GET['section_mode'] ?? 'all';
$showDebug   = isset($_GET['debug']);

// TikTok heeft geen engagement metric → forceer 'interactions' als 'engagement' gevraagd
if ($metricMode === 'engagement') $metricMode = 'interactions';
if ($from > $to) $from = $to;

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// ─── Data laden ───────────────────────────────────────────────────────────────

$data   = loadPlatformData('tiktok', $fromIso, $toIso, $sectionMode, $metricMode);
$config = $data['config'] ?? [];

$sectionOptions = array_map(fn($s) => $s['label'], $config['sections'] ?? []);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TikTok – Metricool Dashboard</title>
    <?php renderStyles($config['color'] ?? '#010101'); ?>
    <style>
        :root { --accent: #69C9D0; } /* TikTok teal als accent ipv zwart */
        body { background: #f0f0f0; }
        .section-card { border-top-color: #69C9D0; }
        .platform-badge { background: #e0f7fa; color: #00838f; }
    </style>
</head>
<body>

<?php renderNavbar('tiktok'); ?>

<div class="header-card">
    <h1>
        🎵 TikTok Dashboard
        <span class="platform-badge">TikTok</span>
    </h1>
    <p style="color:#888;margin:0;font-size:14px;">
        Periode: <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?>
    </p>
    <div style="margin-top:12px;padding:10px 14px;background:#e0f7fa;border-radius:8px;font-size:13px;color:#006064;">
        ℹ️ <strong>TikTok API notities:</strong>
        TikTok heeft geen <em>engagement%</em> metric via Metricool.
        Business-accounts hebben toegang tot meer metrics dan personal-accounts.
        Video's met copyright-issues worden gefilterd door TikTok en zijn niet opvraagbaar.
    </div>
</div>

<?php if ($metricMode === 'engagement'): ?>
    <div class="alert alert-warning">
        ⚠ TikTok biedt geen engagement%-metric aan via de API.
        Toont automatisch <strong>interacties</strong> en <strong>video views</strong>.
    </div>
<?php endif; ?>

<?php renderFilterForm($from, $to, $metricMode, $sectionMode, $sectionOptions); ?>

<?php if ($data['globalError'] ?? null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($data['globalError']) ?></div>
<?php else: ?>
    <?php renderPlatformData($data, $from, $to, $showDebug); ?>
<?php endif; ?>

</body>
</html>
