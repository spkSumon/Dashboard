<?php

/**
 * UI Helpers - gedeelde layout-componenten voor alle platform-pagina's
 * Gebruik: require_once 'ui_helpers.php';
 */

// ─── CSS (gedeeld) ────────────────────────────────────────────────────────────

function renderStyles(string $accentColor = '#1976d2'): void {
    echo <<<HTML
    <style>
        :root {
            --accent:       {$accentColor};
            --accent-light: color-mix(in srgb, var(--accent) 12%, white);
            --accent-dark:  color-mix(in srgb, var(--accent) 80%, black);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1300px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        /* ─── Navbar ─────────────────────────── */
        .navbar {
            background: white;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .nav-logo {
            font-weight: 800;
            font-size: 16px;
            color: #333;
            margin-right: 12px;
            letter-spacing: -.5px;
        }
        .nav-link {
            text-decoration: none;
            color: #555;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 14px;
            transition: background .15s, color .15s;
        }
        .nav-link:hover   { background: #f0f0f0; color: #111; }
        .nav-link.active  { background: var(--accent-light); color: var(--accent); }
        .nav-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            background: #f0f0f0;
            color: #888;
            margin-left: 4px;
            vertical-align: middle;
        }
        /* ─── Cards ───────────────────────────── */
        .card, .section-card, .header-card, .form-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,.07);
        }
        .section-card { border-top: 4px solid var(--accent); }
        h1, h2, h3 { margin-top: 0; color: #1a1a1a; }
        /* ─── Platform-badge ──────────────────── */
        .platform-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            background: var(--accent-light);
            color: var(--accent);
            margin-left: 10px;
            vertical-align: middle;
        }
        /* ─── Alerts ──────────────────────────── */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 14px;
        }
        .alert-error   { background:#ffebee; border-left:4px solid #c62828; color:#b71c1c; }
        .alert-info    { background:#e3f2fd; border-left:4px solid #1976d2; color:#0d47a1; }
        .alert-warning { background:#fff3e0; border-left:4px solid #f57c00; color:#e65100; }
        .alert-empty   { background:#f5f5f5; border-left:4px solid #9e9e9e; color:#555; }
        /* ─── Form ────────────────────────────── */
        .form-row {
            display: flex;
            gap: 14px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .form-group { flex: 1; min-width: 160px; }
        label { display:block; font-weight:600; font-size:13px; color:#555; margin-bottom:5px; }
        input, select {
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        input:focus, select:focus { outline: none; border-color: var(--accent); background: white; }
        .btn {
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: var(--accent);
            color: white;
            width: 100%;
        }
        .btn:hover { opacity: .9; }
        /* ─── Stats grid ──────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 16px;
        }
        .stat-item {
            padding: 16px;
            background: #f9f9f9;
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }
        .stat-label {
            font-size: 11px;
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }
        .stat-value { font-size: 26px; font-weight: 800; color: #1a1a1a; }
        .stat-subtext { font-size: 11px; color: #aaa; margin-top: 4px; }
        .positive { color: #2e7d32; }
        .negative { color: #c62828; }
        .neutral  { color: #e65100; }
        /* ─── Table ───────────────────────────── */
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { padding: 11px 14px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        th { background: #fafafa; font-weight: 700; color: #555; font-size: 12px; text-transform: uppercase; }
        .row-best  { background: #f1f8e9; }
        .row-worst { background: #ffebee; }
        .badge-best  { color: #2e7d32; font-weight: 700; margin-left: 6px; font-size: 12px; }
        .badge-worst { color: #c62828; font-weight: 700; margin-left: 6px; font-size: 12px; }
        /* ─── Misc ────────────────────────────── */
        .dp-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            background: var(--accent-light);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
        }
        .endpoint-info {
            background: #e8f5e9;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 12px;
            font-family: monospace;
            word-break: break-all;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-pill {
            padding: 5px 12px;
            background: var(--accent);
            color: white;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .metric-block { margin-bottom: 28px; }
        .debug-box {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .debug-box pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .inbox-notice {
            background: #fce4ec;
            border: 2px dashed #e91e63;
            border-radius: 10px;
            padding: 20px 24px;
            color: #880e4f;
        }
    </style>
HTML;
}

// ─── Navbar ───────────────────────────────────────────────────────────────────

function renderNavbar(string $activePlatform): void {
    $links = [
        'facebook'  => ['label' => 'Facebook',  'file' => 'facebook.php',  'icon' => '📘'],
        'instagram' => ['label' => 'Instagram',  'file' => 'instagram.php', 'icon' => '📸'],
        'tiktok'    => ['label' => 'TikTok',     'file' => 'tiktok.php',    'icon' => '🎵'],
        'inbox'     => ['label' => 'Inbox',      'file' => 'inbox.php',     'icon' => '📬'],
    ];

    echo '<nav class="navbar">';
    echo '<span class="nav-logo">📊 Metricool</span>';

    foreach ($links as $key => $link) {
        $active = ($key === $activePlatform) ? ' active' : '';
        $badge  = ($key === 'inbox') ? '<span class="nav-badge">info</span>' : '';
        echo '<a href="' . htmlspecialchars($link['file']) . '" class="nav-link' . $active . '">';
        echo $link['icon'] . ' ' . $link['label'] . $badge;
        echo '</a>';
    }

    echo '</nav>';
}

// ─── Filter-form ──────────────────────────────────────────────────────────────

/**
 * @param array $sectionOptions  ['key' => 'Label', ...]
 */
function renderFilterForm(
    string $from,
    string $to,
    string $metricMode,
    string $sectionMode,
    array  $sectionOptions
): void {
    echo '<div class="form-card">';
    echo '<form method="get">';
    echo '<div class="form-row">';

    // Van datum
    echo '<div class="form-group">';
    echo '<label for="from">Van datum</label>';
    echo '<input type="date" id="from" name="from" value="' . htmlspecialchars($from) . '">';
    echo '</div>';

    // Tot datum
    echo '<div class="form-group">';
    echo '<label for="to">Tot datum</label>';
    echo '<input type="date" id="to" name="to" value="' . htmlspecialchars($to) . '">';
    echo '</div>';

    // Metric mode
    echo '<div class="form-group">';
    echo '<label for="metric_mode">Metrics tonen</label>';
    echo '<select id="metric_mode" name="metric_mode">';
    $metricOptions = ['engagement' => 'Engagement', 'interactions' => 'Interacties', 'both' => 'Alle metrics'];
    foreach ($metricOptions as $val => $lbl) {
        $sel = ($metricMode === $val) ? ' selected' : '';
        echo '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Section mode
    if (count($sectionOptions) > 1) {
        echo '<div class="form-group">';
        echo '<label for="section_mode">Secties tonen</label>';
        echo '<select id="section_mode" name="section_mode">';
        echo '<option value="all"' . ($sectionMode === 'all' ? ' selected' : '') . '>Alle secties</option>';
        foreach ($sectionOptions as $val => $lbl) {
            $sel = ($sectionMode === $val) ? ' selected' : '';
            echo '<option value="' . $val . '"' . $sel . '>' . $lbl . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    // Submit
    echo '<div class="form-group">';
    echo '<button type="submit" class="btn">Data ophalen</button>';
    echo '</div>';

    echo '</div></form></div>';
}

// ─── Section renderer ─────────────────────────────────────────────────────────

function renderPlatformData(
    array  $data,
    string $from,
    string $to,
    bool   $showDebug = false
): void {
    $config           = $data['config'] ?? [];
    $sectionsToLoad   = $data['sectionsToLoad'] ?? [];
    $resultsBySection = $data['resultsBySection'] ?? [];
    $errorsBySection  = $data['errorsBySection'] ?? [];
    $emptyBySection   = $data['emptyBySection'] ?? [];

    if ($data['globalError'] ?? null) {
        echo '<div class="alert alert-error">' . htmlspecialchars($data['globalError']) . '</div>';
        return;
    }

    foreach ($sectionsToLoad as $sectionKey) {
        $sectionDef   = $config['sections'][$sectionKey] ?? [];
        $sectionLabel = $sectionDef['label'] ?? ucfirst($sectionKey);

        echo '<div class="section-card">';
        echo '<div class="section-header">';
        echo '<h2>' . htmlspecialchars($sectionLabel) . '</h2>';
        echo '<span class="section-pill">' . htmlspecialchars($sectionLabel) . '</span>';
        echo '</div>';

        $metricsInSection = $resultsBySection[$sectionKey] ?? [];

        if (empty($metricsInSection)) {
            echo '<div class="alert alert-empty">Geen metrics geladen voor deze sectie.</div>';
            echo '</div>';
            continue;
        }

        foreach ($metricsInSection as $metricName => $metricResult) {
            $parsedData = $metricResult['parsed'] ?? null;
            $error      = $errorsBySection[$sectionKey][$metricName] ?? null;
            $empty      = $emptyBySection[$sectionKey][$metricName] ?? null;

            echo '<div class="metric-block">';

            if ($error) {
                echo '<div class="alert alert-error"><strong>' . htmlspecialchars(formatMetricLabel($metricName)) . ':</strong> ' . htmlspecialchars($error) . '</div>';
            } elseif ($empty) {
                echo '<div class="alert alert-empty"><strong>' . htmlspecialchars(formatMetricLabel($metricName)) . ':</strong> ' . htmlspecialchars($empty) . '</div>';
            } elseif ($parsedData) {
                renderMetricCard($parsedData, $metricName, $from, $to);
                renderMetricTable($parsedData, $metricName, $sectionLabel);
            }

            echo '</div>';
        }

        echo '</div>'; // .section-card
    }

    // Debug
    if ($showDebug) {
        echo '<div class="card"><h2>🔍 Debug</h2>';
        foreach ($sectionsToLoad as $sectionKey) {
            foreach (($resultsBySection[$sectionKey] ?? []) as $metricName => $metricResult) {
                echo '<div class="debug-box">';
                echo '<strong>' . htmlspecialchars($sectionKey) . ' – ' . htmlspecialchars($metricName) . '</strong>';
                echo '<br><small>URL: ' . htmlspecialchars($metricResult['request']['url'] ?? '-') . '</small>';
                echo '<pre>';
                var_dump($metricResult['request']['body'] ?? null);
                echo '</pre></div>';
            }
        }
        echo '</div>';
    }
}

// ─── Metric stat card ─────────────────────────────────────────────────────────

function renderMetricCard(array $data, string $metricName, string $from, string $to): void {
    $label = formatMetricLabel($metricName);
    echo '<div class="card">';
    echo '<h3>' . htmlspecialchars($label) . ' overzicht';
    echo '<span class="dp-badge">' . $data['dataPointCount'] . ' datapunt(en)</span>';
    echo '</h3>';
    echo '<p style="color:#888;font-size:13px;margin-bottom:16px;">Periode: ' . htmlspecialchars($from) . ' → ' . htmlspecialchars($to) . '</p>';

    if ($data['dataPointCount'] === 1) {
        echo '<div class="alert alert-warning">⚠ Slechts één datapunt — statistieken zijn beperkt.</div>';
    }

    echo '<div class="stats-grid">';
    $stats = [
        ['Datapunten',       $data['dataPointCount'],       'neutral',   false],
        ['Gemiddelde',       $data['averageValue'],          '',          $metricName],
        ['Mediaan',          $data['medianValue'],           '',          $metricName],
        ['Hoogste waarde',   $data['maxValue'],              'positive',  $metricName],
        ['Laagste waarde',   $data['minValue'],              'negative',  $metricName],
        ['Spreiding',        $data['rangeValue'],            'neutral',   $metricName],
    ];
    foreach ($stats as [$lbl, $val, $cls, $fmt]) {
        $formatted = $fmt ? formatMetricValue($val, $fmt) : number_format((float)$val, 0, ',', '.');
        echo '<div class="stat-item">';
        echo '<div class="stat-label">' . $lbl . '</div>';
        echo '<div class="stat-value ' . $cls . '">' . $formatted . '</div>';
        echo '</div>';
    }
    // Beste / zwakste datapunt
    echo '<div class="stat-item">';
    echo '<div class="stat-label">Beste datapunt</div>';
    echo '<div class="stat-value positive">#' . ($data['maxIndex'] + 1) . '</div>';
    echo '<div class="stat-subtext">' . htmlspecialchars($data['maxRow']['dateTime'] ?? '-') . '</div>';
    echo '</div>';
    echo '<div class="stat-item">';
    echo '<div class="stat-label">Zwakste datapunt</div>';
    echo '<div class="stat-value negative">#' . ($data['minIndex'] + 1) . '</div>';
    echo '<div class="stat-subtext">' . htmlspecialchars($data['minRow']['dateTime'] ?? '-') . '</div>';
    echo '</div>';
    echo '</div>'; // .stats-grid
    echo '</div>'; // .card
}

// ─── Metric datapunt tabel ────────────────────────────────────────────────────

function renderMetricTable(array $data, string $metricName, string $sectionLabel): void {
    $label = formatMetricLabel($metricName);
    echo '<div class="card">';
    echo '<h3>' . htmlspecialchars($label) . ' per datapunt</h3>';
    echo '<table><thead><tr><th>#</th><th>Datum &amp; Tijd</th><th>' . htmlspecialchars($label) . '</th></tr></thead><tbody>';
    foreach ($data['values'] as $i => $row) {
        $isBest  = ($i === $data['maxIndex']);
        $isWorst = ($i === $data['minIndex']);
        $cls     = $isBest ? 'row-best' : ($isWorst ? 'row-worst' : '');
        echo '<tr class="' . $cls . '">';
        echo '<td>' . ($i + 1);
        if ($isBest)  echo ' <span class="badge-best">🏆 Beste</span>';
        if ($isWorst) echo ' <span class="badge-worst">⚠ Zwakste</span>';
        echo '</td>';
        echo '<td>' . htmlspecialchars($row['dateTime'] ?? '-') . '</td>';
        echo '<td><strong>' . formatMetricValue($row['value'] ?? 0, $metricName) . '</strong></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
