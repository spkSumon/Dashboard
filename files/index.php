<?php
/**
 * index.php - Dashboard homepage
 */
require_once __DIR__ . '/ui/ui_helpers.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Metricool Dashboard</title>
    <?php renderStyles('#1976d2'); ?>
    <style>
        .platform-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        .platform-tile {
            background: white;
            border-radius: 14px;
            padding: 28px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            text-decoration: none;
            color: inherit;
            transition: transform .15s, box-shadow .15s;
            border-top: 5px solid var(--tile-color, #1976d2);
            display: block;
        }
        .platform-tile:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        .tile-icon { font-size: 36px; margin-bottom: 12px; }
        .tile-title { font-size: 20px; font-weight: 800; color: #1a1a1a; margin-bottom: 6px; }
        .tile-desc  { font-size: 13px; color: #888; line-height: 1.5; }
        .tile-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            background: var(--tile-light);
            color: var(--tile-color);
        }
    </style>
</head>
<body>

<?php renderNavbar(''); ?>

<div class="header-card">
    <h1>📊 Metricool Dashboard</h1>
    <p style="color:#888;margin:0;">Kies een platform om analytics te bekijken.</p>
</div>

<div class="platform-grid">
    <a href="facebook.php" class="platform-tile" style="--tile-color:#1877F2;--tile-light:#e3f2fd;">
        <div class="tile-icon">📘</div>
        <div class="tile-title">Facebook</div>
        <div class="tile-desc">Posts, Reels — engagement, interacties, video views.</div>
        <span class="tile-badge">Posts · Reels</span>
    </a>

    <a href="instagram.php" class="platform-tile" style="--tile-color:#E1306C;--tile-light:#fce4ec;">
        <div class="tile-icon">📸</div>
        <div class="tile-title">Instagram</div>
        <div class="tile-desc">Posts, Reels, Stories — engagement, interacties, video views.</div>
        <span class="tile-badge">Posts · Reels · Stories</span>
    </a>

    <a href="tiktok.php" class="platform-tile" style="--tile-color:#69C9D0;--tile-light:#e0f7fa;">
        <div class="tile-icon">🎵</div>
        <div class="tile-title">TikTok</div>
        <div class="tile-desc">Videos — interacties, video views. Business-accounts krijgen extra metrics.</div>
        <span class="tile-badge">Videos</span>
    </a>

    <a href="inbox.php" class="platform-tile" style="--tile-color:#e91e63;--tile-light:#fce4ec;">
        <div class="tile-icon">📬</div>
        <div class="tile-title">Inbox</div>
        <div class="tile-desc">Niet beschikbaar via Metricool API. Bekijk alternatieven en uitleg.</div>
        <span class="tile-badge">Info &amp; Alternatieven</span>
    </a>
</div>

</body>
</html>
