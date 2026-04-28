<?php
/**
 * inbox.php
 * Inbox / berichten pagina.
 *
 * ── BEVINDING NA API-ANALYSE ──────────────────────────────────────────────────
 * De Metricool API biedt GEEN inbox/messaging endpoints aan.
 * Dit is bevestigd door:
 *   1. De officiële API-documentatie (swagger.yaml / PDF) — geen /inbox of /messages endpoints.
 *   2. Het Airbyte-connector schema — geen messaging streams (enkel posts, timelines, analytics).
 *   3. De Metricool UI zelf heeft wel een Social Inbox (voor Business-plannen),
 *      maar dit is NIET via de publieke API beschikbaar.
 *
 * Reden (aanname Metricool): Berichtendata bevat PII en is platform-specifiek
 * (Facebook Messenger, Instagram DMs, ...). Metricool biedt dit enkel aan in
 * hun eigen UI, niet via de API.
 *
 * Alternatieve opties worden hieronder uitgelegd.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/api/metricool_service.php';
require_once __DIR__ . '/ui/ui_helpers.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inbox – Metricool Dashboard</title>
    <?php renderStyles('#e91e63'); ?>
    <style>
        .alternative-card {
            border-left: 4px solid #4caf50;
            background: #f1f8e9;
            padding: 20px 24px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .alternative-card h3 { color: #2e7d32; margin-top: 0; }
        .alternative-card p, .alternative-card ul { color: #33691e; font-size: 14px; }
        .limitation-card {
            border-left: 4px solid #ef5350;
            background: #ffebee;
            padding: 20px 24px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .limitation-card h3 { color: #b71c1c; margin-top: 0; }
        .check { color: #2e7d32; font-weight: 700; }
        .cross { color: #c62828; font-weight: 700; }
        .code-block {
            background: #263238;
            color: #aed581;
            padding: 14px 18px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 12px 0;
        }
    </style>
</head>
<body>

<?php renderNavbar('inbox'); ?>

<div class="header-card">
    <h1>
        📬 Inbox / Berichten
        <span class="platform-badge" style="background:#fce4ec;color:#e91e63;">Niet beschikbaar</span>
    </h1>
</div>

<!-- Hoofdmelding -->
<div class="inbox-notice">
    <h2 style="margin-top:0;">⚠️ Inbox is niet beschikbaar via de Metricool API</h2>
    <p>
        Na grondige analyse van de Metricool API-documentatie (swagger.yaml, PDF handleiding, Airbyte connector schema)
        is vastgesteld dat de Metricool API <strong>geen inbox- of messaging-endpoints aanbiedt</strong>.
    </p>
    <p>
        Metricool heeft wél een <strong>Social Inbox</strong> functie in hun UI (beschikbaar op Advanced/Custom plan),
        maar deze is <strong>bewust niet opgenomen in de publieke API</strong>.
    </p>
</div>

<!-- Reden -->
<div class="card">
    <h2>🔍 Bevindingen</h2>

    <div class="limitation-card">
        <h3>Wat ontbreekt in de Metricool API</h3>
        <ul>
            <li><span class="cross">✗</span> Geen <code>/api/inbox</code> endpoint</li>
            <li><span class="cross">✗</span> Geen <code>/api/messages</code> of <code>/api/threads</code></li>
            <li><span class="cross">✗</span> Geen DM/comment notificaties via API</li>
            <li><span class="cross">✗</span> Niet aanwezig in swagger.yaml / swagger.json</li>
            <li><span class="cross">✗</span> Niet aanwezig in Airbyte connector streams</li>
        </ul>
    </div>

    <div style="padding:16px;background:#e3f2fd;border-radius:8px;font-size:14px;color:#0d47a1;">
        <strong>Officiële Metricool Social Inbox (UI only):</strong><br>
        De Metricool UI biedt een Social Inbox voor Facebook- en Instagram-berichten en comments.
        Dit is enkel toegankelijk via <a href="https://app.metricool.com" target="_blank">app.metricool.com</a>
        en kan <em>niet</em> worden uitgelezen via de API.
    </div>
</div>

<!-- Alternatieven -->
<div class="card">
    <h2>✅ Alternatieven</h2>

    <div class="alternative-card">
        <h3>Optie 1: Meta Graph API (Facebook &amp; Instagram DMs)</h3>
        <p>
            Direct de officiële Meta API aanspreken voor berichten en comments.
            Vereist een Meta Developer App + Page Access Token.
        </p>
        <ul>
            <li>Facebook: <code>GET /{page-id}/conversations</code> + <code>GET /{conversation-id}/messages</code></li>
            <li>Instagram: <code>GET /me/conversations?platform=instagram</code></li>
        </ul>
        <div class="code-block">
// Voorbeeld: Facebook berichten ophalen via Graph API
$pageId    = 'JOUW_PAGE_ID';
$pageToken = 'JOUW_PAGE_ACCESS_TOKEN';

$url = "https://graph.facebook.com/v19.0/{$pageId}/conversations"
     . "?fields=id,snippet,updated_time,participants"
     . "&access_token={$pageToken}";

$response = file_get_contents($url);
$data     = json_decode($response, true);
        </div>
        <p><strong>Voordelen:</strong> Officieel, volledig, real-time.<br>
           <strong>Nadelen:</strong> Aparte authenticatie nodig, App Review vereist voor production.</p>
    </div>

    <div class="alternative-card">
        <h3>Optie 2: TikTok Comment API</h3>
        <p>
            TikTok biedt een officiele API voor commentaren op posts (niet voor DMs).
        </p>
        <ul>
            <li>Endpoint: <code>GET /v2/comment/list/</code></li>
            <li>Vereist: TikTok for Developers app + OAuth2</li>
        </ul>
        <p><strong>Let op:</strong> TikTok DMs zijn niet beschikbaar via enige third-party API.</p>
    </div>

    <div class="alternative-card">
        <h3>Optie 3: Dedicated social inbox tool koppelen</h3>
        <p>
            Tools die wél een API hebben voor unified inbox:
        </p>
        <ul>
            <li><strong>Agorapulse</strong> — Heeft een API met inbox-functies</li>
            <li><strong>Hootsuite</strong> — Streams API inclusief berichten</li>
            <li><strong>Respond.io</strong> — Dedicated messaging API voor social DMs</li>
        </ul>
        <p>Deze kunnen naast Metricool draaien voor het berichten-gedeelte.</p>
    </div>

    <div class="alternative-card">
        <h3>Optie 4: Metricool UI embed (quick win)</h3>
        <p>
            Als de Metricool Social Inbox actief is op het account, kan een directe link
            worden aangeboden naar de Metricool inbox. Geen integratie, wel centraal.
        </p>
        <div style="margin-top:10px;">
            <a href="https://app.metricool.com/inbox" target="_blank"
               style="display:inline-block;padding:10px 20px;background:#e91e63;color:white;border-radius:8px;text-decoration:none;font-weight:700;">
                📬 Open Metricool Inbox →
            </a>
        </div>
    </div>
</div>

<!-- Advies -->
<div class="card">
    <h2>💡 Aanbeveling</h2>
    <p style="font-size:15px;line-height:1.6;">
        Voor een <strong>snelle oplossing</strong>: gebruik de directe link naar de Metricool UI inbox (Optie 4).<br><br>
        Voor een <strong>echte integratie</strong>: implementeer de Meta Graph API (Optie 1) voor Facebook- en Instagram-berichten.
        Dit is de officiële weg en geeft volledige controle over de data. TikTok DMs zijn via geen enkele API beschikbaar.
    </p>
    <p style="color:#888;font-size:13px;">
        Wil je dat ik de Meta Graph API inbox-integratie uitwerk als aparte module? Voeg dan <code>?build_inbox=1</code> toe aan de URL.
    </p>
</div>

</body>
</html>
