<?php
/**
 * facebook.php
 * Facebook metrics dashboard — gebruikt de gedeelde service layer.
 */

require_once __DIR__ . '/api/metricool_service.php';
require_once __DIR__ . '/ui/ui_helpers.php';

// ─── Parameters ───────────────────────────────────────────────────────────────

$from        = $_GET['from']         ?? date('Y-m-01');
$to          = $_GET['to']           ?? date('Y-m-d');
$metricMode  = $_GET['metric_mode']  ?? 'both';
$sectionMode = $_GET['section_mode'] ?? 'all';
$showDebug   = isset($_GET['debug']);

// Validatie
if (!in_array($metricMode,  ['engagement','interactions','both'], true)) $metricMode  = 'both';
if ($from > $to) $from = $to;

$fromIso = $from . 'T00:00:00+01:00';
$toIso   = $to   . 'T23:59:59+01:00';

// ─── Data laden ───────────────────────────────────────────────────────────────

$data   = loadPlatformData('facebook', $fromIso, $toIso, $sectionMode, $metricMode);
$config = $data['config'] ?? [];

$sectionOptions = array_map(fn($s) => $s['label'], $config['sections'] ?? []);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facebook – Metricool Dashboard</title>
    <?php renderStyles($config['color'] ?? '#1877F2'); ?>
</head>
<body>

<?php renderNavbar('facebook'); ?>

<div class="header-card">
    <h1>
        📘 Facebook Dashboard
        <span class="platform-badge">Facebook</span>
    </h1>
    <p style="color:#888;margin:0;font-size:14px;">
        Periode: <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?>
    </p>
</div>

<?php renderFilterForm($from, $to, $metricMode, $sectionMode, $sectionOptions); ?>

<?php if ($data['globalError'] ?? null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($data['globalError']) ?></div>
<?php else: ?>
    <?php renderPlatformData($data, $from, $to, $showDebug); ?>
<?php endif; ?>

</body>
</html>
