<?php
/**
 * config.php — Inbox: berichten, reacties en reviews op één plek.
 */
require_once __DIR__ . '/token.php';

// ─────────────────────────────────────────────────────────────────────────────
// REPLY-HANDLER (AJAX POST)
// ─────────────────────────────────────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'reply') {
    header('Content-Type: application/json');

    $messageId    = $_POST['messageId']    ?? '';
    $provider     = $_POST['provider']     ?? '';
    $endpointType = $_POST['endpointType'] ?? '';
    $replyText    = trim($_POST['replyText'] ?? '');

    if ($replyText === '' || $messageId === '') {
        echo json_encode(['success' => false, 'error' => 'Antwoord of bericht-ID ontbreekt.']);
        exit;
    }

    $endpointsToTry = [
        '/api/v2/inbox/' . $endpointType . '/' . $messageId . '/reply',
        '/api/v2/inbox/' . $endpointType . '/' . $messageId . '/answer',
        '/api/v2/inbox/' . $endpointType . '/reply',
        '/api/v2/inbox/reply',
    ];

    $bodyFull = json_encode([
        'text'      => $replyText,
        'provider'  => $provider,
        'userId'    => (int)$userId,
        'blogId'    => (int)$blogId,
        'messageId' => $messageId,
    ]);
    $bodyMinimal  = json_encode(['text' => $replyText]);
    $queryParams  = http_build_query(['userId' => $userId, 'blogId' => $blogId]);

    $tried = [];
    foreach ($endpointsToTry as $endpoint) {
        foreach ([$bodyFull, $bodyMinimal] as $body) {
            $url = "https://app.metricool.com" . $endpoint . "?" . $queryParams;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT        => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $tried[] = $endpoint . ' → HTTP ' . $httpCode;

            if ($httpCode === 200 || $httpCode === 201) {
                echo json_encode([
                    'success'  => true,
                    'endpoint' => $endpoint,
                    'response' => json_decode($response, true),
                ]);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => false,
        'error'   => 'Geen werkend reply-endpoint gevonden. Controleer of het bericht niet te oud is (Meta: max 24u voor reacties, 7 dagen voor DMs).',
        'tried'   => $tried,
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// INBOX OPHALEN
// ─────────────────────────────────────────────────────────────────────────────

$endpointMap = [
    'reviews'       => '/api/v2/inbox/reviews',
    'conversations' => '/api/v2/inbox/conversations',
    'post-comments' => '/api/v2/inbox/post-comments',
];

$providerEndpoints = [
    'gmb'            => ['reviews'],
    'facebook'       => ['conversations', 'post-comments'],
    'instagram'      => ['conversations', 'post-comments'],
    'tiktokBusiness' => ['post-comments'],
];

// ── Helperfuncties voor normalisatie ──

function extractItems($decodedData): array {
    if (!is_array($decodedData)) return [];
    foreach (['data', 'results', 'conversations', 'reviews', 'comments', 'items'] as $key) {
        if (isset($decodedData[$key]) && is_array($decodedData[$key])) return $decodedData[$key];
    }
    if (array_keys($decodedData) === range(0, count($decodedData) - 1)) return $decodedData;
    return [];
}

function pickFirstNonEmpty(array $values, string $default = ''): string {
    foreach ($values as $value) {
        if (is_string($value) && trim($value) !== '') return trim($value);
        if (is_numeric($value)) return (string)$value;
    }
    return $default;
}

function getInitials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'O';
    $parts = array_values(array_filter(preg_split('/\s+/', $name)));
    if (count($parts) === 1) return strtoupper(mb_substr($parts[0], 0, 1));
    return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
}

/** Labels voor niet-tekstuele berichttypes (story mentions, media shares, etc.) */
function getTypeLabel(string $type): string {
    $labels = [
        'story_mention'  => '📸 Heeft je Story vermeld',
        'story_reply'    => '💬 Heeft gereageerd op je Story',
        'media_share'    => '🖼 Heeft een afbeelding gedeeld',
        'reel_share'     => '🎬 Heeft een Reel gedeeld',
        'video_share'    => '🎬 Heeft een video gedeeld',
        'audio'          => '🎤 Heeft een audiobericht gestuurd',
        'image'          => '🖼 Heeft een afbeelding gestuurd',
        'video'          => '🎬 Heeft een video gestuurd',
        'like'           => '❤️ Heeft een like gestuurd',
        'sticker'        => '🎭 Heeft een sticker gestuurd',
        'animated_media' => '🎭 Heeft een GIF gestuurd',
        'share'          => '🔗 Heeft iets gedeeld',
        'product_share'  => '🛍 Heeft een product gedeeld',
        'xma_link'       => '🔗 Heeft een link gedeeld',
    ];
    return $labels[$type] ?? ($type !== '' ? "[$type]" : '');
}

function getShortTypeLabel(string $type): string {
    $labels = [
        'story_mention'  => '📸 Story vermeld',
        'story_reply'    => '💬 Story reactie',
        'media_share'    => '🖼 Afbeelding gedeeld',
        'reel_share'     => '🎬 Reel gedeeld',
        'video_share'    => '🎬 Video gedeeld',
        'audio'          => '🎤 Audiobericht',
        'image'          => '🖼 Afbeelding',
        'video'          => '🎬 Video',
        'like'           => '❤️ Like',
        'sticker'        => '🎭 Sticker',
        'animated_media' => '🎭 GIF',
        'share'          => '🔗 Gedeeld item',
        'xma_link'       => '🔗 Link',
    ];
    return $labels[$type] ?? ($type !== '' ? "[$type]" : '');
}

/**
 * Normaliseer een rauw API-bericht naar een uniforme structuur.
 */
function normalizeMessage(array $msg, string $provider, string $endpointType): array {
    $ownAccountNames = ['giudittalevuven'];
    $id       = (string)($msg['id'] ?? uniqid($provider . '_', true));
    $name     = 'Onbekend';
    $avatar   = '';
    $fullMessage = '';
    $time     = '';
    $subject  = '';
    $status   = '';
    $extraMeta = '';
    $postLink = '';
    $canReply = true;

    // ── Conversaties (DMs) ──
    if ($endpointType === 'conversations') {
        // Naam ophalen uit deelnemers (eigen account overslaan)
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $p) {
                $pName = trim((string)($p['name'] ?? ''));
                if ($pName !== '' && !in_array(mb_strtolower($pName), $ownAccountNames, true)) {
                    $name = $pName;
                    $avatar = $p['imageProfileUrl'] ?? '';
                    break;
                }
            }
            if ($name === 'Onbekend' && !empty($msg['participants'][0]['name'])) {
                $name   = $msg['participants'][0]['name'];
                $avatar = $msg['participants'][0]['imageProfileUrl'] ?? '';
            }
        }

        // Laatste bericht met inhoud zoeken
        if (!empty($msg['messages']) && is_array($msg['messages'])) {
            foreach (array_reverse($msg['messages']) as $m) {
                if (!is_array($m)) continue;

                if ($time === '') {
                    $time   = $m['publicationDateTime'] ?? $m['createdAt'] ?? $m['date'] ?? '';
                    $status = $m['status'] ?? ($msg['status'] ?? '');
                }

                $msgText = trim((string)($m['text'] ?? $m['message'] ?? $m['body'] ?? $m['content'] ?? ''));
                if ($msgText === '') {
                    $msgType = strtolower((string)($m['type'] ?? $m['messageType'] ?? ''));
                    $msgText = getTypeLabel($msgType);
                    if ($msgText === '') {
                        $mediaUrl = $m['mediaUrl'] ?? $m['imageUrl'] ?? $m['videoUrl'] ?? $m['attachmentUrl'] ?? '';
                        if ($mediaUrl) $msgText = '📎 Bijlage ontvangen';
                    }
                }

                if ($msgText !== '') { $fullMessage = $msgText; break; }
            }

            if ($fullMessage === '') {
                $fullMessage = trim((string)($msg['lastMessage'] ?? $msg['snippet'] ?? $msg['preview'] ?? ''));
            }
        }

        if ($time === '') {
            $time = $msg['lastReadTime'] ?? $msg['lastUpdateTime'] ?? $msg['creationDate'] ?? '';
        }

        // Volledige berichtgeschiedenis opbouwen
        $allMsgLines = [];
        if (!empty($msg['messages']) && is_array($msg['messages'])) {
            foreach ($msg['messages'] as $m) {
                if (!is_array($m)) continue;
                $mText = trim((string)($m['text'] ?? $m['message'] ?? $m['body'] ?? ''));
                if ($mText === '') {
                    $mType = strtolower((string)($m['type'] ?? $m['messageType'] ?? ''));
                    $mText = getShortTypeLabel($mType);
                    if ($mText === '') {
                        $mUrl = $m['mediaUrl'] ?? $m['imageUrl'] ?? $m['videoUrl'] ?? '';
                        if ($mUrl) $mText = '📎 Bijlage';
                    }
                }
                if ($mText === '') continue;
                $mTime    = $m['publicationDateTime'] ?? $m['createdAt'] ?? '';
                $mTimeStr = $mTime ? date('d/m H:i', strtotime($mTime)) : '';
                $allMsgLines[] = ($mTimeStr ? "[$mTimeStr] " : '') . $mText;
            }
        }
        if (!empty($allMsgLines)) {
            $fullMessage = implode("\n", $allMsgLines);
        }

        $subject = 'Conversatie via ' . ucfirst($provider);

        // Meta-beperking: DMs ouder dan 7 dagen
        if ($time && (time() - strtotime($time)) > 7 * 86400) $canReply = false;
    }

    // ── Post-reacties ──
    elseif ($endpointType === 'post-comments') {
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $p) {
                $pName = trim((string)($p['name'] ?? ''));
                if ($pName !== '') { $name = $pName; $avatar = $p['imageProfileUrl'] ?? ''; break; }
            }
        }
        if ($name === 'Onbekend') {
            $name = pickFirstNonEmpty([
                $msg['name'] ?? '', $msg['userName'] ?? '', $msg['username'] ?? '',
                $msg['author']['name'] ?? '', $msg['from']['name'] ?? '', $msg['root']['owner'] ?? '',
            ], 'Onbekend');
        }
        $postLink    = (string)($msg['root']['element']['link'] ?? '');
        $fullMessage = pickFirstNonEmpty([
            $msg['root']['text'] ?? '', $msg['text'] ?? '', $msg['comment'] ?? '',
            $msg['message'] ?? '', $msg['body'] ?? '', $msg['content'] ?? '',
        ]);
        $time    = pickFirstNonEmpty([$msg['creationDate'] ?? '', $msg['publicationDateTime'] ?? '', $msg['date'] ?? '', $msg['createdAt'] ?? '']);
        $status  = (string)($msg['status'] ?? '');
        $subject = 'Reactie via ' . ucfirst($provider);

        // Meta-beperking: reacties ouder dan 24u
        if ($time && (time() - strtotime($time)) > 86400) $canReply = false;
    }

    // ── Reviews (Google Business) ──
    elseif ($endpointType === 'reviews') {
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $p) {
                $pName = trim((string)($p['name'] ?? ''));
                if ($pName !== '') { $name = $pName; $avatar = $p['imageProfileUrl'] ?? ''; break; }
            }
        }
        $stars = $msg['stars'] ?? null;
        if ($stars !== null) $extraMeta = (string)$stars;
        $fullMessage = pickFirstNonEmpty([
            $msg['comment'] ?? '', $msg['text'] ?? '', $msg['review'] ?? '', $msg['message'] ?? '',
        ]);
        if ($fullMessage === '' && $stars !== null) {
            $fullMessage = "Deze review heeft {$stars} van de 5 sterren (geen geschreven tekst).";
        }
        $time    = pickFirstNonEmpty([$msg['creationDate'] ?? '', $msg['date'] ?? '', $msg['lastUpdateTime'] ?? '']);
        $status  = (string)($msg['status'] ?? '');
        $subject = 'Review via ' . ucfirst($provider);
        $canReply = true;
    }

    if ($fullMessage === '') $fullMessage = 'Geen berichtinhoud beschikbaar.';
    $preview = mb_substr($fullMessage, 0, 90) . (mb_strlen($fullMessage) > 90 ? '...' : '');

    return [
        'id'           => $id,
        'name'         => $name,
        'time'         => $time,
        'preview'      => $preview ?: '[Geen tekst gevonden]',
        'subject'      => $subject,
        'fullMessage'  => $fullMessage,
        'provider'     => $provider,
        'endpointType' => $endpointType,
        'avatar'       => $avatar,
        'status'       => $status,
        'extraMeta'    => $extraMeta,
        'postLink'     => $postLink,
        'initials'     => getInitials($name),
        'canReply'     => $canReply,
    ];
}

// ── Alle berichten ophalen ──
$allMessages = [];

foreach ($providerEndpoints as $provider => $endpointKeys) {
    foreach ($endpointKeys as $endpointKey) {
        $result = callMetricool($endpointMap[$endpointKey], [
            'provider' => $provider,
            'userId'   => $userId,
            'blogId'   => $blogId,
        ], $headers);

        $items = extractItems($result['body'] ?? null);
        foreach ($items as $msg) {
            if (is_array($msg)) {
                $allMessages[] = normalizeMessage($msg, $provider, $endpointKey);
            }
        }
    }
}

usort($allMessages, fn($a, $b) => strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? '')));
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox — SkyByte</title>
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body>
<div class="sb-page">

    <nav class="navbar">
        <a href="config.php" class="nav-link active">Inbox</a>
        <a href="facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="tiktok_dashboard.php" class="nav-link">TikTok</a>
        <a href="../fathem/fathom-info2.php" class="nav-link">Fathom Analytics</a>
    </nav>

    <p class="sb-title"><span>Inbox</span></p>
    <p class="sb-subtitle"><span>Al je berichten, reacties en reviews op één plek</span></p>

    <div class="sb-stats-pills">
        <div class="sb-stat-pill"><strong><?= count($allMessages) ?></strong> <span>berichten</span></div>
        <div class="sb-stat-pill"><strong><?= count(array_unique(array_column($allMessages, 'provider'))) ?></strong> <span>platforms</span></div>
    </div>

    <div class="sb-inbox-layout">

        <!-- Berichtenlijst -->
        <div class="sb-panel sb-sidebar-inbox">
            <div class="sb-sidebar-header">
                <h2>Berichten</h2>
                <div class="sb-filters">
                    <input type="text" id="searchInput" class="sb-field" placeholder="Zoeken...">
                    <select id="providerFilter" class="sb-field">
                        <option value="">Alle platforms</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="gmb">Google Business</option>
                        <option value="tiktokBusiness">TikTok</option>
                    </select>
                    <select id="typeFilter" class="sb-field">
                        <option value="">Alle types</option>
                        <option value="conversations">Conversaties</option>
                        <option value="post-comments">Reacties</option>
                        <option value="reviews">Reviews</option>
                    </select>
                    <div class="sb-date-row">
                        <div class="sb-date-field">
                            <label for="dateFrom">Van</label>
                            <input type="date" id="dateFrom" class="sb-field">
                        </div>
                        <div class="sb-date-field">
                            <label for="dateTo">Tot</label>
                            <input type="date" id="dateTo" class="sb-field">
                        </div>
                    </div>
                    <div class="sb-stars-filter is-hidden" id="starsFilter">
                        <div class="sb-stars-label"><span>Sterren (reviews)</span></div>
                        <div class="sb-stars-buttons">
                            <button type="button" class="sb-star-btn active" data-stars="0" onclick="setStarFilter(0, this)"><span>Alle</span></button>
                            <button type="button" class="sb-star-btn" data-stars="5" onclick="setStarFilter(5, this)"><span>5★</span></button>
                            <button type="button" class="sb-star-btn" data-stars="4" onclick="setStarFilter(4, this)"><span>4★</span></button>
                            <button type="button" class="sb-star-btn" data-stars="3" onclick="setStarFilter(3, this)"><span>3★</span></button>
                            <button type="button" class="sb-star-btn" data-stars="2" onclick="setStarFilter(2, this)"><span>2★</span></button>
                            <button type="button" class="sb-star-btn" data-stars="1" onclick="setStarFilter(1, this)"><span>1★</span></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sb-message-list" id="messageList"></div>
        </div>

        <!-- Berichtinhoud + reply -->
        <div class="sb-panel sb-content" id="contentPanel">
            <div class="sb-empty-state">
                <div class="sb-empty-state-icon"><span>💬</span></div>
                <div class="sb-empty-state-text"><span>Selecteer een bericht om te lezen</span></div>
            </div>
        </div>

    </div>
</div>

<script>
const messages = <?= json_encode($allMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const messageList    = document.getElementById('messageList');
const contentPanel   = document.getElementById('contentPanel');
const searchInput    = document.getElementById('searchInput');
const providerFilter = document.getElementById('providerFilter');
const typeFilter     = document.getElementById('typeFilter');
const dateFrom       = document.getElementById('dateFrom');
const dateTo         = document.getElementById('dateTo');
let activeStarFilter = 0;

function escapeHtml(str) {
    return String(str ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
}
function nl2br(str) { return escapeHtml(str).replace(/\n/g, '<br>'); }
function formatTime(time) {
    if (!time) return '';
    try { return new Date(time).toLocaleString('nl-NL', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }); }
    catch { return time; }
}
function providerLabel(p) {
    return {'tiktokBusiness':'TikTok','gmb':'Google Business','facebook':'Facebook','instagram':'Instagram'}[p] || p;
}
function typeLabel(t) {
    return {'conversations':'Conversatie','post-comments':'Reactie','reviews':'Review'}[t] || t;
}
function ratingStars(n) {
    const v = parseInt(n, 10);
    return (!v || v < 1 || v > 5) ? '' : '⭐'.repeat(v);
}

function updateStarFilterVisibility() {
    const sf = document.getElementById('starsFilter');
    if (providerFilter.value === 'gmb') {
        sf.classList.remove('is-hidden');
    } else {
        sf.classList.add('is-hidden');
        activeStarFilter = 0;
        document.querySelectorAll('.sb-star-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.sb-star-btn[data-stars="0"]').classList.add('active');
    }
}
function onProviderChange() { updateStarFilterVisibility(); renderList(); }
function setStarFilter(stars, button) {
    activeStarFilter = stars;
    document.querySelectorAll('.sb-star-btn').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    renderList();
}

function fillReply(text) {
    const input = document.getElementById('replyText');
    if (input) { input.value = text; input.focus(); }
}

async function sendReply(messageId, provider, endpointType) {
    const input  = document.getElementById('replyText');
    const status = document.getElementById('replyStatus');
    const btn    = document.getElementById('replyBtn');
    const replyText = input.value.trim();

    if (replyText === '') {
        status.textContent = 'Schrijf eerst een antwoord.';
        status.className = 'sb-reply-status err';
        return;
    }

    status.textContent = 'Bezig met versturen…';
    status.className = 'sb-reply-status';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'reply');
    formData.append('messageId', messageId);
    formData.append('provider', provider);
    formData.append('endpointType', endpointType);
    formData.append('replyText', replyText);

    try {
        const response = await fetch('config.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            status.textContent = '✓ Antwoord verstuurd!';
            status.className = 'sb-reply-status ok';
            input.value = '';
        } else {
            status.textContent = '✗ ' + (result.error || 'Onbekende fout');
            status.className = 'sb-reply-status err';
        }
    } catch (err) {
        status.textContent = '✗ Netwerk-fout: ' + err.message;
        status.className = 'sb-reply-status err';
    }
    btn.disabled = false;
}

function renderContent(message) {
    const stars   = message.endpointType === 'reviews' ? ratingStars(message.extraMeta) : '';
    const viewBtn = message.postLink
        ? `<a href="${escapeHtml(message.postLink)}" target="_blank" rel="noopener" class="sb-view-btn"><span>↗ Bekijk origineel</span></a>` : '';

    const quickReplies = [
        'Bedankt voor je bericht! 🙏',
        'Bedankt voor je positieve review! ⭐',
        'Dank je wel, we waarderen je feedback!',
        'Sorry voor het ongemak, we nemen contact op.',
    ];
    const quickBtns = quickReplies.map(t =>
        `<button type="button" class="sb-quick-btn" onclick="fillReply(this.dataset.txt)" data-txt="${escapeHtml(t)}"><span>${escapeHtml(t)}</span></button>`
    ).join('');

    const replyWarning = !message.canReply
        ? `<div class="sb-reply-warning"><span>⚠ Dit bericht is te oud om te beantwoorden via de API (Meta-beperking: reacties max 24u, DMs max 7 dagen).</span></div>` : '';
    const replyDisabled    = !message.canReply ? 'disabled' : '';
    const replyPlaceholder = !message.canReply ? 'Te oud om te beantwoorden via API…' : 'Schrijf je antwoord…';

    contentPanel.innerHTML = `
        <div class="sb-content-header">
            <h2 class="sb-content-subject"><span>${escapeHtml(message.subject || 'Bericht')} ${stars}</span></h2>
            <div class="sb-content-meta">
                <span><strong>Van:</strong> ${escapeHtml(message.name || 'Onbekend')}</span>
                <span><strong>Platform:</strong> ${escapeHtml(providerLabel(message.provider || ''))}</span>
                <span><strong>Type:</strong> ${escapeHtml(typeLabel(message.endpointType || ''))}</span>
                ${message.status ? `<span><strong>Status:</strong> ${escapeHtml(message.status)}</span>` : ''}
                <span><strong>Tijd:</strong> ${escapeHtml(formatTime(message.time || ''))}</span>
                ${viewBtn}
            </div>
        </div>
        <div class="sb-content-body">${nl2br(message.fullMessage || 'Geen inhoud beschikbaar.')}</div>
        <div class="sb-reply-box">
            <div class="sb-reply-label"><span>Antwoorden</span></div>
            ${replyWarning}
            <div class="sb-quick-replies">${quickBtns}</div>
            <textarea id="replyText" class="sb-reply-input" placeholder="${replyPlaceholder}" rows="3" ${replyDisabled}></textarea>
            <div class="sb-reply-actions">
                <span class="sb-reply-status" id="replyStatus"></span>
                <button type="button" id="replyBtn" class="sb-reply-send sb-btn-dark" ${replyDisabled}
                    onclick="sendReply('${escapeHtml(message.id)}', '${escapeHtml(message.provider)}', '${escapeHtml(message.endpointType)}')">
                    <span>Verstuur</span>
                </button>
            </div>
        </div>`;
}

function getFilteredMessages() {
    const term = searchInput.value.trim().toLowerCase();
    const pVal = providerFilter.value, tVal = typeFilter.value;
    const fromVal = dateFrom.value, toVal = dateTo.value;

    return messages.filter(m => {
        const hay     = [m.name, m.provider, m.endpointType, m.subject, m.preview, m.fullMessage, m.status].join(' ').toLowerCase();
        const msgDate = (m.time || '').substring(0, 10);
        let matchStars = true;
        if (activeStarFilter > 0) {
            matchStars = m.endpointType === 'reviews' && parseInt(m.extraMeta, 10) === activeStarFilter;
        }
        return hay.includes(term)
            && (pVal === '' || m.provider === pVal)
            && (tVal === '' || m.endpointType === tVal)
            && (fromVal === '' || msgDate >= fromVal)
            && (toVal  === '' || msgDate <= toVal)
            && matchStars;
    });
}

function renderList() {
    const filtered = getFilteredMessages();

    if (filtered.length === 0) {
        messageList.innerHTML  = `<div class="sb-empty-list"><span>Geen berichten gevonden.</span></div>`;
        contentPanel.innerHTML = `<div class="sb-empty-state"><div class="sb-empty-state-icon"><span>🔍</span></div><div class="sb-empty-state-text"><span>Geen resultaat voor de huidige filters</span></div></div>`;
        return;
    }

    messageList.innerHTML = filtered.map((msg, i) => {
        const avatarHtml = msg.avatar
            ? `<div class="sb-avatar"><img src="${escapeHtml(msg.avatar)}" alt=""></div>`
            : `<div class="sb-avatar"><span>${escapeHtml(msg.initials || 'O')}</span></div>`;
        const statusHtml = msg.status ? `<span class="sb-status-badge">${escapeHtml(msg.status)}</span>` : '';
        let previewHtml  = escapeHtml(msg.preview || '');
        if (msg.endpointType === 'reviews') {
            const s = ratingStars(msg.extraMeta);
            if (s) previewHtml = s + '<br>' + previewHtml;
        }
        const oldIndicator = !msg.canReply ? ' <span style="font-size:10px;opacity:.5;">(te oud)</span>' : '';

        return `
            <div class="sb-message-item ${i === 0 ? 'active' : ''}" data-id="${escapeHtml(msg.id)}">
                ${avatarHtml}
                <div class="sb-message-meta">
                    <div class="sb-badge-row">
                        <span class="sb-provider-badge ${escapeHtml(msg.provider)}">${escapeHtml(providerLabel(msg.provider))}</span>
                        <span class="sb-type-badge">${escapeHtml(typeLabel(msg.endpointType || ''))}</span>
                        ${statusHtml}
                    </div>
                    <div class="sb-message-row-top">
                        <div class="sb-message-name"><span>${escapeHtml(msg.name || 'Onbekend')}${oldIndicator}</span></div>
                        <div class="sb-message-time"><span>${escapeHtml(formatTime(msg.time || ''))}</span></div>
                    </div>
                    <div class="sb-message-preview"><span>${previewHtml}</span></div>
                </div>
            </div>`;
    }).join('');

    renderContent(filtered[0]);

    document.querySelectorAll('.sb-message-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.sb-message-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            const msg = filtered.find(m => String(m.id) === item.getAttribute('data-id'));
            if (msg) renderContent(msg);
        });
    });
}

searchInput.addEventListener('input', renderList);
providerFilter.addEventListener('change', onProviderChange);
typeFilter.addEventListener('change', renderList);
dateFrom.addEventListener('change', renderList);
dateTo.addEventListener('change', renderList);

renderList();
</script>
</body>
</html>