<?php

$userId = 4394337;
$blogId = 5668624;
$token  = "";

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: $token",
];

// ─────────────────────────────────────────────────────────────────────────────
// REPLY-HANDLER
// Wanneer de gebruiker op "Verstuur" klikt in de UI, stuurt JavaScript
// een POST-request naar deze pagina met action=reply.
// Wij sturen dat dan door naar Metricool.
// ─────────────────────────────────────────────────────────────────────────────

if (($_POST['action'] ?? '') === 'reply') {
    // We sturen een JSON-antwoord terug naar JavaScript
    header('Content-Type: application/json');

    $messageId    = $_POST['messageId']    ?? '';
    $provider     = $_POST['provider']     ?? '';
    $endpointType = $_POST['endpointType'] ?? '';
    $replyText    = trim($_POST['replyText'] ?? '');

    // Basis-validatie
    if ($replyText === '' || $messageId === '') {
        echo json_encode(['success' => false, 'error' => 'Antwoord of bericht-ID ontbreekt.']);
        exit;
    }

    if ($token === '') {
        echo json_encode(['success' => false, 'error' => 'Geen Metricool token ingesteld.']);
        exit;
    }

    // Metricool gebruikt verschillende endpoints afhankelijk van het type bericht.
    // We proberen de meest waarschijnlijke endpoints één voor één.
    // Zodra er een werkt (HTTP 200/201), stoppen we.
    $endpointsToTry = [
        '/api/v2/inbox/' . $endpointType . '/' . $messageId . '/reply',
        '/api/v2/inbox/' . $endpointType . '/' . $messageId . '/answer',
        '/api/v2/inbox/' . $endpointType . '/' . $messageId . '/respond',
        '/api/v2/inbox/reply',
    ];

    $body = json_encode([
        'text'     => $replyText,
        'provider' => $provider,
        'userId'   => $userId,
        'blogId'   => $blogId,
        'messageId'=> $messageId,
    ]);

    $tried = [];
    foreach ($endpointsToTry as $endpoint) {
        $url = "https://app.metricool.com" . $endpoint . "?" . http_build_query([
            'userId' => $userId,
            'blogId' => $blogId,
        ]);

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

        // 200 of 201 betekent: gelukt
        if ($httpCode === 200 || $httpCode === 201) {
            echo json_encode([
                'success'  => true,
                'endpoint' => $endpoint,
                'response' => json_decode($response, true),
            ]);
            exit;
        }
    }

    // Niets werkte — we geven een nette foutmelding terug met wat we geprobeerd hebben.
    echo json_encode([
        'success' => false,
        'error'   => 'Geen werkend reply-endpoint gevonden.',
        'tried'   => $tried,
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// HIER BEGINT DE NORMALE PAGINA (inbox tonen)
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

function callMetricool($endpoint, $params, $headers) {
    $url = "https://app.metricool.com" . $endpoint . "?" . http_build_query($params);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            'error' => $error,
            'httpCode' => $httpCode,
            'raw' => null,
            'data' => null,
        ];
    }

    $decoded = json_decode($response, true);

    return [
        'error' => null,
        'httpCode' => $httpCode,
        'raw' => $response,
        'data' => $decoded
    ];
}

function extractItems($decodedData) {
    if (!is_array($decodedData)) {
        return [];
    }

    $possibleKeys = ['data', 'results', 'conversations', 'reviews', 'comments', 'items'];

    foreach ($possibleKeys as $key) {
        if (isset($decodedData[$key]) && is_array($decodedData[$key])) {
            return $decodedData[$key];
        }
    }

    if (array_keys($decodedData) === range(0, count($decodedData) - 1)) {
        return $decodedData;
    }

    return [];
}

function pickFirstNonEmpty($values, $default = '') {
    foreach ($values as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
    }
    return $default;
}

function getInitials($name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 'O';
    }

    $parts = preg_split('/\s+/', $name);
    $parts = array_values(array_filter($parts));

    if (count($parts) === 1) {
        return strtoupper(mb_substr($parts[0], 0, 1));
    }

    return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
}

function normalizeMessage($msg, $provider, $endpointType) {
    $ownAccountNames = ['giudittaleuven'];

    $id = (string)($msg['id'] ?? uniqid($provider . '_', true));
    $name = 'Onbekend';
    $avatar = '';
    $fullMessage = '';
    $time = '';
    $subject = ucfirst($endpointType) . ' via ' . ucfirst($provider);
    $status = '';
    $extraMeta = '';
    $postLink = ''; // link naar de originele post (Instagram, Facebook, ...)

    if ($endpointType === 'conversations') {
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $participant) {
                $participantName = trim((string)($participant['name'] ?? ''));
                if (
                    $participantName !== '' &&
                    !in_array(mb_strtolower($participantName), $ownAccountNames, true)
                ) {
                    $name = $participantName;
                    $avatar = $participant['imageProfileUrl'] ?? '';
                    break;
                }
            }

            if ($name === 'Onbekend' && !empty($msg['participants'][0]['name'])) {
                $name = $msg['participants'][0]['name'];
                $avatar = $msg['participants'][0]['imageProfileUrl'] ?? '';
            }
        }

        if (!empty($msg['messages']) && is_array($msg['messages'])) {
            $messageCount = count($msg['messages']);
            $lastMessage = $msg['messages'][$messageCount - 1] ?? null;

            if (is_array($lastMessage)) {
                $fullMessage = trim((string)($lastMessage['text'] ?? ''));
                $time = $lastMessage['publicationDateTime'] ?? '';
                $status = $lastMessage['status'] ?? ($msg['status'] ?? '');
            }
        }

        if ($time === '') {
            $time = $msg['lastReadTime'] ?? $msg['lastUpdateTime'] ?? $msg['creationDate'] ?? '';
        }

        $subject = 'Conversatie via ' . ucfirst($provider);
    }

    elseif ($endpointType === 'post-comments') {
        // Bij Instagram (en soms andere platforms) zit de naam van de reageerder
        // in "participants", net als bij conversaties en reviews.
        // We proberen dat eerst.
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $participant) {
                $participantName = trim((string)($participant['name'] ?? ''));
                if ($participantName !== '') {
                    $name = $participantName;
                    $avatar = $participant['imageProfileUrl'] ?? '';
                    break;
                }
            }
        }

        // Lukt dat niet, dan proberen we de oude velden (voor andere platforms).
        if ($name === 'Onbekend') {
            $name = pickFirstNonEmpty([
                $msg['name'] ?? '',
                $msg['userName'] ?? '',
                $msg['username'] ?? '',
                $msg['author']['name'] ?? '',
                $msg['from']['name'] ?? '',
                $msg['root']['owner'] ?? '',   // bij Instagram staat de username ook hier
            ], 'Onbekend');
        }

        // Link naar de originele post — handig om door te klikken naar Instagram/Facebook.
        $postLink = (string)($msg['root']['element']['link'] ?? '');

        // De reactietekst zit bij Instagram in "root" -> "text".
        // We proberen die eerst, daarna de losse velden voor andere platforms.
        $fullMessage = pickFirstNonEmpty([
            $msg['root']['text'] ?? '',    // <-- Instagram reactietekst
            $msg['text'] ?? '',
            $msg['comment'] ?? '',
            $msg['message'] ?? '',
            $msg['body'] ?? '',
            $msg['content'] ?? '',
        ], '');

        $time = pickFirstNonEmpty([
            $msg['creationDate'] ?? '',
            $msg['publicationDateTime'] ?? '',
            $msg['date'] ?? '',
            $msg['createdAt'] ?? '',
        ], '');

        $status = (string)($msg['status'] ?? '');

        $subject = 'Reactie via ' . ucfirst($provider);
    }

    elseif ($endpointType === 'reviews') {
        // Bij GMB reviews zit de naam van de reviewer in "participants",
        // net zoals bij conversaties. De reviewer is de deelnemer met een "name".
        // De andere deelnemer is ons eigen account (die heeft een id dat begint met "accounts/").
        if (!empty($msg['participants']) && is_array($msg['participants'])) {
            foreach ($msg['participants'] as $participant) {
                $participantName = trim((string)($participant['name'] ?? ''));
                // We nemen de deelnemer die een echte naam heeft
                if ($participantName !== '') {
                    $name = $participantName;
                    $avatar = $participant['imageProfileUrl'] ?? '';
                    break;
                }
            }
        }

        // De sterren van de review zitten in "stars" (een getal van 1 tot 5)
        $stars = $msg['stars'] ?? null;
        if ($stars !== null) {
            $extraMeta = (string)$stars;
        }

        // GMB reviews hebben vaak geen geschreven tekst, alleen sterren.
        // We tonen dan een nette melding in plaats van lege tekst.
        $fullMessage = pickFirstNonEmpty([
            $msg['comment'] ?? '',
            $msg['text'] ?? '',
            $msg['review'] ?? '',
            $msg['message'] ?? '',
        ], '');

        if ($fullMessage === '' && $stars !== null) {
            $fullMessage = 'Deze review heeft ' . $stars . ' van de 5 sterren (geen geschreven tekst).';
        }

        $time = pickFirstNonEmpty([
            $msg['creationDate'] ?? '',
            $msg['date'] ?? '',
            $msg['lastUpdateTime'] ?? '',
        ], '');

        $status = (string)($msg['status'] ?? '');

        $subject = 'Review via ' . ucfirst($provider);
    }

    if ($fullMessage === '') {
        $fullMessage = 'Geen berichtinhoud beschikbaar.';
    }

    $preview = mb_substr($fullMessage, 0, 90);
    if (mb_strlen($fullMessage) > 90) {
        $preview .= '...';
    }

    if ($preview === '') {
        $preview = '[Geen tekst gevonden]';
    }

    return [
        'id' => $id,
        'name' => $name,
        'time' => $time,
        'preview' => $preview,
        'subject' => $subject,
        'fullMessage' => $fullMessage,
        'provider' => $provider,
        'endpointType' => $endpointType,
        'avatar' => $avatar,
        'status' => $status,
        'extraMeta' => $extraMeta,
        'postLink' => $postLink,
        'initials' => getInitials($name),
        'raw' => $msg
    ];
}

$allMessages = [];
$debugInfo = [];

foreach ($providerEndpoints as $provider => $endpointKeys) {
    foreach ($endpointKeys as $endpointKey) {
        $endpoint = $endpointMap[$endpointKey];

        $params = [
            "provider" => $provider,
            "userId"   => $userId,
            "blogId"   => $blogId,
        ];

        $result = callMetricool($endpoint, $params, $headers);

        $decodedData = $result['data'] ?? null;
        $items = extractItems($decodedData);

        $debugInfo[] = [
            'provider' => $provider,
            'endpointType' => $endpointKey,
            'httpCode' => $result['httpCode'] ?? null,
            'error' => $result['error'] ?? null,
            'count' => count($items),
            'rawDataKeys' => $decodedData ? array_keys($decodedData) : []
        ];

        foreach ($items as $msg) {
            if (is_array($msg)) {
                $allMessages[] = normalizeMessage($msg, $provider, $endpointKey);
            }
        }
    }
}

usort($allMessages, function ($a, $b) {
    return strcmp((string)($b['time'] ?? ''), (string)($a['time'] ?? ''));
});

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox — SkyByte</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background: #f0f2f7; }

        .sb-page { max-width: 1600px; margin: 0 auto; }

        /* ── Navbar (zelfde als dashboards) ── */
        .sb-title { font-size: 22px; font-weight: 700; color: #1a2233; letter-spacing: -0.3px; margin-bottom: 4px; }
        .sb-subtitle { font-size: 13px; color: #7a8599; margin-bottom: 24px; }

        /* ── Stat pills ── */
        .sb-stats-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .sb-stat-pill {
            background: #fff; border: 1px solid #e4e8ef; padding: 8px 16px;
            border-radius: 100px; font-size: 13px; font-weight: 600; color: #7a8599;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sb-stat-pill strong { color: #06b6d4; margin-right: 6px; }

        /* ── Layout ── */
        .sb-inbox-layout {
            display: grid; grid-template-columns: 440px 1fr; gap: 16px;
            min-height: calc(100vh - 240px); align-items: start;
        }

        .sb-panel {
            background: #fff; border: 1px solid #e4e8ef; border-radius: 14px;
            overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }

        /* ── Sidebar ── */
        .sb-sidebar-inbox { display: flex; flex-direction: column; height: calc(100vh - 240px); }
        .sb-sidebar-header { padding: 20px; border-bottom: 1px solid #eef1f6; background: #fafbfd; }
        .sb-sidebar-header h2 {
            font-size: 11px; font-weight: 700; margin-bottom: 14px;
            text-transform: uppercase; letter-spacing: 0.6px; color: #9aa3b4;
        }
        .sb-filters { display: grid; gap: 10px; }
        .sb-search-input, .sb-filter-select {
            width: 100%; border: 1px solid #dde2ec; background: #fff; color: #1a2233;
            padding: 10px 13px; font-size: 14px; border-radius: 9px; font-weight: 500;
            outline: none; transition: all 0.18s;
        }
        .sb-search-input:focus, .sb-filter-select:focus {
            border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,.12);
        }
        .sb-search-input::placeholder { color: #b0bac8; }

        /* ── Datumfilter ── */
        .sb-date-row { display: flex; gap: 8px; }
        .sb-date-field { flex: 1; }
        .sb-date-field label {
            display: block; font-size: 10px; font-weight: 700; color: #9aa3b4;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
        }
        .sb-date-field input[type="date"] { padding: 8px 10px; font-size: 13px; }

        /* ── Sterrenfilter knoppen ── */
        .sb-stars-filter { margin-top: 4px; }
        .sb-stars-label {
            font-size: 10px; font-weight: 700; color: #9aa3b4;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .sb-stars-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .sb-star-btn {
            flex: 1; min-width: 38px; padding: 7px 6px; font-size: 12px; font-weight: 600;
            border: 1px solid #dde2ec; background: #fff; color: #7a8599;
            border-radius: 8px; cursor: pointer; transition: all 0.15s;
        }
        .sb-star-btn:hover { border-color: #fbbc04; color: #f59e0b; }
        .sb-star-btn.active { background: #fbbc04; border-color: #fbbc04; color: #fff; }

        /* ── Message list ── */
        .sb-message-list { overflow-y: auto; flex: 1; padding: 8px; }
        .sb-message-list::-webkit-scrollbar { width: 7px; }
        .sb-message-list::-webkit-scrollbar-track { background: transparent; }
        .sb-message-list::-webkit-scrollbar-thumb { background: #dde2ec; border-radius: 4px; }
        .sb-message-list::-webkit-scrollbar-thumb:hover { background: #b0bac8; }

        .sb-message-item {
            display: flex; gap: 12px; padding: 14px; margin-bottom: 5px; border-radius: 11px;
            cursor: pointer; transition: all 0.15s; border: 1px solid transparent;
            position: relative; overflow: hidden;
        }
        .sb-message-item::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            background: #06b6d4; transform: scaleY(0); transition: transform 0.2s;
        }
        .sb-message-item:hover { background: #f7f9fc; border-color: #eef1f6; }
        .sb-message-item.active { background: #f0fbfd; border-color: #06b6d4; }
        .sb-message-item.active::before { transform: scaleY(1); }

        /* ── Avatar ── */
        .sb-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, #06b6d4, #a855f7); color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; flex-shrink: 0; overflow: hidden;
        }
        .sb-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* ── Message meta ── */
        .sb-message-meta { min-width: 0; flex: 1; }
        .sb-message-row-top { display: flex; justify-content: space-between; gap: 10px; align-items: baseline; margin-bottom: 6px; }
        .sb-message-name {
            font-weight: 700; font-size: 14px; color: #1a2233;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sb-message-time { font-size: 11px; color: #b0bac8; flex-shrink: 0; }

        .sb-badge-row { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 7px; }
        .sb-provider-badge, .sb-type-badge, .sb-status-badge {
            display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 0.04em;
            text-transform: uppercase; padding: 3px 9px; border-radius: 6px;
        }
        .sb-provider-badge { color: white; }
        .sb-provider-badge.facebook { background: #1877f2; }
        .sb-provider-badge.instagram { background: #e1306c; }
        .sb-provider-badge.gmb { background: #4285f4; }
        .sb-provider-badge.tiktokBusiness { background: #1a2233; }
        .sb-type-badge { background: #f0f2f7; color: #7a8599; border: 1px solid #e4e8ef; }
        .sb-status-badge { background: #10b981; color: white; }

        .sb-message-preview {
            font-size: 13px; line-height: 1.5; color: #7a8599;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        /* ── Content panel ── */
        .sb-content { display: flex; flex-direction: column; height: calc(100vh - 240px); }
        .sb-content-header { padding: 26px 28px; border-bottom: 1px solid #eef1f6; background: #fafbfd; }
        .sb-content-subject { font-size: 22px; font-weight: 800; margin: 0 0 14px 0; letter-spacing: -0.4px; line-height: 1.2; color: #1a2233; }
        .sb-content-meta { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; font-size: 13px; color: #7a8599; }
        .sb-content-meta strong { color: #06b6d4; margin-right: 5px; }

        .sb-content-body { padding: 28px; font-size: 15px; line-height: 1.8; color: #3a4460; white-space: pre-wrap; overflow-y: auto; flex: 1; }
        .sb-content-body::-webkit-scrollbar { width: 7px; }
        .sb-content-body::-webkit-scrollbar-thumb { background: #dde2ec; border-radius: 4px; }

        /* ── Bekijk-knop (link naar originele post) ── */
        .sb-view-btn {
            display: inline-flex; align-items: center; gap: 4px;
            background: #f0f6ff; color: #3b82f6; border: 1px solid #dbeafe;
            padding: 4px 10px; border-radius: 7px; font-size: 12px; font-weight: 600;
            text-decoration: none; transition: all 0.15s;
        }
        .sb-view-btn:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }

        /* ── Antwoord-blok onderaan de berichtweergave ── */
        .sb-reply-box {
            padding: 20px 28px 24px; border-top: 1px solid #eef1f6; background: #fafbfd;
        }
        .sb-reply-label {
            font-size: 11px; font-weight: 700; color: #9aa3b4;
            text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;
        }

        /* Snel-antwoord knoppen */
        .sb-quick-replies { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
        .sb-quick-btn {
            background: #fff; border: 1px solid #dde2ec; color: #7a8599;
            padding: 6px 11px; border-radius: 20px; font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all 0.15s;
        }
        .sb-quick-btn:hover { border-color: #06b6d4; color: #06b6d4; background: #f0fbfd; }

        /* Tekstveld voor het antwoord */
        .sb-reply-input {
            width: 100%; border: 1px solid #dde2ec; background: #fff; color: #1a2233;
            padding: 12px 14px; font-size: 14px; border-radius: 10px;
            font-family: inherit; resize: vertical; outline: none;
            transition: all 0.15s; min-height: 70px;
        }
        .sb-reply-input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,.12); }

        /* Verzendknop + status */
        .sb-reply-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; gap: 10px; }
        .sb-reply-status { font-size: 12px; font-weight: 600; color: #7a8599; }
        .sb-reply-status.ok { color: #10b981; }
        .sb-reply-status.err { color: #ef4444; }
        .sb-reply-send {
            background: #06b6d4; color: #fff; border: none; padding: 9px 20px;
            border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer;
            transition: all 0.15s;
        }
        .sb-reply-send:hover { background: #0891b2; }

        /* ── Empty states ── */
        .sb-empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: #b0bac8; gap: 14px; padding: 60px 30px; text-align: center;
        }
        .sb-empty-state-icon { font-size: 52px; opacity: 0.4; }
        .sb-empty-state-text { font-size: 16px; font-weight: 600; color: #b0bac8; }
        .sb-empty-list { padding: 32px 20px; text-align: center; color: #b0bac8; font-size: 14px; }

        /* ── Debug section ── */
        .sb-debug-section { margin-top: 20px; }
        .sb-debug-toggle {
            background: #fff; border: 1px solid #e4e8ef; color: #7a8599;
            padding: 9px 18px; border-radius: 9px; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.15s;
        }
        .sb-debug-toggle:hover { border-color: #06b6d4; color: #06b6d4; }
        .sb-debug-content {
            display: none; margin-top: 14px; background: #fff; border: 1px solid #e4e8ef;
            border-radius: 14px; padding: 20px;
        }
        .sb-debug-content.visible { display: block; }
        .sb-debug-content h3 { margin: 0 0 16px 0; font-size: 12px; color: #06b6d4; text-transform: uppercase; letter-spacing: 0.05em; }
        .sb-debug-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
        .sb-debug-card { background: #fafbfd; border: 1px solid #eef1f6; border-radius: 10px; padding: 14px; font-size: 12px; }
        .sb-debug-card strong { display: block; margin-bottom: 8px; font-size: 13px; color: #1a2233; }
        .sb-debug-card div { margin-bottom: 5px; color: #7a8599; }
        .sb-debug-card .err { color: #ef4444; }
        .sb-debug-card .ok { color: #10b981; }

        @media (max-width: 1100px) {
            .sb-inbox-layout { grid-template-columns: 1fr; }
            .sb-sidebar-inbox { height: auto; max-height: 500px; }
            .sb-content { height: auto; min-height: 500px; }
        }
    </style>
</head>
<body>

<div class="sb-page">

    <nav class="navbar">
        <a href="config.php" class="nav-link active">Inbox</a>
        <a href="facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="tiktok_dashboard.php" class="nav-link">TikTok</a>
        <a href="gmb_dashboard.php" class="nav-link">Google Business</a>
        <a href="../fathem/fathom-info2.php" class="nav-link">Fathom Analytics</a>
    </nav>

    <p class="sb-title">Inbox</p>
    <p class="sb-subtitle">Al je berichten, reacties en reviews op één plek</p>

    <div class="sb-stats-pills">
        <div class="sb-stat-pill"><strong><?php echo count($allMessages); ?></strong> berichten</div>
        <?php $providers = array_unique(array_column($allMessages, 'provider')); ?>
        <div class="sb-stat-pill"><strong><?php echo count($providers); ?></strong> platforms</div>
    </div>

    <div class="sb-inbox-layout">
        <div class="sb-panel sb-sidebar-inbox">
            <div class="sb-sidebar-header">
                <h2>Berichten</h2>
                <div class="sb-filters">
                    <input type="text" id="searchInput" class="sb-search-input" placeholder="Zoeken...">
                    <select id="providerFilter" class="sb-filter-select">
                        <option value="">Alle platforms</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="gmb">Google Business</option>
                        <option value="tiktokBusiness">TikTok</option>
                    </select>
                    <select id="typeFilter" class="sb-filter-select">
                        <option value="">Alle types</option>
                        <option value="conversations">Conversaties</option>
                        <option value="post-comments">Reacties</option>
                        <option value="reviews">Reviews</option>
                    </select>

                    <!-- Datumfilter: van datum tot datum -->
                    <div class="sb-date-row">
                        <div class="sb-date-field">
                            <label for="dateFrom">Van</label>
                            <input type="date" id="dateFrom" class="sb-filter-select">
                        </div>
                        <div class="sb-date-field">
                            <label for="dateTo">Tot</label>
                            <input type="date" id="dateTo" class="sb-filter-select">
                        </div>
                    </div>

                    <!-- Sterrenfilter: knoppen om reviews op sterren te filteren.
                         Dit blok is standaard verborgen (style="display:none").
                         Het verschijnt alleen als je op Google Business filtert. -->
                    <div class="sb-stars-filter" id="starsFilter" style="display:none;">
                        <div class="sb-stars-label">Sterren (reviews)</div>
                        <div class="sb-stars-buttons">
                            <button type="button" class="sb-star-btn active" data-stars="0" onclick="setStarFilter(0, this)">Alle</button>
                            <button type="button" class="sb-star-btn" data-stars="5" onclick="setStarFilter(5, this)">5★</button>
                            <button type="button" class="sb-star-btn" data-stars="4" onclick="setStarFilter(4, this)">4★</button>
                            <button type="button" class="sb-star-btn" data-stars="3" onclick="setStarFilter(3, this)">3★</button>
                            <button type="button" class="sb-star-btn" data-stars="2" onclick="setStarFilter(2, this)">2★</button>
                            <button type="button" class="sb-star-btn" data-stars="1" onclick="setStarFilter(1, this)">1★</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sb-message-list" id="messageList"></div>
        </div>

        <div class="sb-panel sb-content" id="contentPanel">
            <div class="sb-empty-state">
                <div class="sb-empty-state-icon">💬</div>
                <div class="sb-empty-state-text">Selecteer een bericht om te lezen</div>
            </div>
        </div>
    </div>

    <div class="sb-debug-section">
        <button class="sb-debug-toggle" onclick="toggleDebug()">🔧 Debug info</button>
        <div class="sb-debug-content" id="debugContent">
            <h3>API endpoints status</h3>
            <div class="sb-debug-grid">
                <?php foreach ($debugInfo as $dbg): ?>
                    <div class="sb-debug-card">
                        <strong><?php echo htmlspecialchars($dbg['provider']); ?></strong>
                        <div>Type: <?php echo htmlspecialchars($dbg['endpointType']); ?></div>
                        <div>HTTP: <span class="<?php echo $dbg['httpCode'] == 200 ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars((string) $dbg['httpCode']); ?></span></div>
                        <div>Items: <?php echo htmlspecialchars((string) $dbg['count']); ?></div>
                        <?php if (!empty($dbg['rawDataKeys'])): ?>
                            <div>Keys: <?php echo htmlspecialchars(implode(', ', $dbg['rawDataKeys'])); ?></div>
                        <?php endif; ?>
                        <div class="<?php echo $dbg['error'] ? 'err' : 'ok'; ?>">
                            <?php echo $dbg['error'] ? htmlspecialchars($dbg['error']) : '✓ OK'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<script>
    const messages = <?php echo json_encode($allMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const messageList = document.getElementById('messageList');
    const contentPanel = document.getElementById('contentPanel');
    const searchInput = document.getElementById('searchInput');
    const providerFilter = document.getElementById('providerFilter');
    const typeFilter = document.getElementById('typeFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');

    // Hier bewaren we welk sterrenfilter actief is.
    // 0 = alle reviews tonen, 1 t/m 5 = alleen reviews met dat aantal sterren.
    let activeStarFilter = 0;

    // Deze functie toont of verbergt de sterrenknoppen.
    // Sterren bestaan alleen bij Google Business reviews,
    // dus we tonen de knoppen enkel als het platform op "gmb" staat.
    function updateStarFilterVisibility() {
        const starsFilter = document.getElementById('starsFilter');

        if (providerFilter.value === 'gmb') {
            starsFilter.style.display = 'block'; // tonen
        } else {
            starsFilter.style.display = 'none';  // verbergen

            // Als we wegklikken van Google Business, zetten we het sterrenfilter
            // terug op "Alle", zodat er geen verborgen filter actief blijft.
            activeStarFilter = 0;
            document.querySelectorAll('.sb-star-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.sb-star-btn[data-stars="0"]').classList.add('active');
        }
    }

    // Als het platformfilter verandert: eerst de sterrenknoppen tonen/verbergen,
    // daarna de lijst opnieuw opbouwen.
    function onProviderChange() {
        updateStarFilterVisibility();
        renderList();
    }

    // Deze functie wordt aangeroepen als je op een sterrenknop klikt.
    function setStarFilter(stars, button) {
        activeStarFilter = stars;

        // Maak alle knoppen weer "uit" en zet alleen de aangeklikte "aan"
        document.querySelectorAll('.sb-star-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Toon de lijst opnieuw met het nieuwe filter
        renderList();
    }

    function toggleDebug() {
        document.getElementById('debugContent').classList.toggle('visible');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function nl2br(str) {
        return escapeHtml(str).replace(/\n/g, '<br>');
    }

    function formatTime(time) {
        if (!time) return '';
        try {
            const date = new Date(time);
            return date.toLocaleString('nl-NL', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch {
            return time;
        }
    }

    function providerLabel(provider) {
        const labels = {
            'tiktokBusiness': 'TikTok', 'gmb': 'Google Business',
            'facebook': 'Facebook', 'instagram': 'Instagram'
        };
        return labels[provider] || provider;
    }

    function typeLabel(type) {
        const labels = {
            'conversations': 'Conversatie', 'post-comments': 'Reactie', 'reviews': 'Review'
        };
        return labels[type] || type;
    }

    function ratingStars(extraMeta) {
        const n = parseInt(extraMeta, 10);
        if (!n || n < 1 || n > 5) return '';
        return '⭐'.repeat(n);
    }

    function renderContent(message) {
        const stars = message.endpointType === 'reviews' ? ratingStars(message.extraMeta) : '';

        // "Bekijk post"-knop: alleen tonen als er een link beschikbaar is
        const viewBtn = message.postLink
            ? `<a href="${escapeHtml(message.postLink)}" target="_blank" rel="noopener" class="sb-view-btn">↗ Bekijk origineel</a>`
            : '';

        // Snelle antwoord-templates die je kan klikken om in te vullen
        const quickReplies = [
            'Bedankt voor je bericht! 🙏',
            'Bedankt voor je positieve review! ⭐',
            'Dank je wel, we waarderen je feedback!',
            'Sorry voor het ongemak, we nemen contact op.',
        ];
        const quickBtns = quickReplies.map(t =>
            `<button type="button" class="sb-quick-btn" onclick="fillReply(this.dataset.txt)" data-txt="${escapeHtml(t)}">${escapeHtml(t)}</button>`
        ).join('');

        contentPanel.innerHTML = `
            <div class="sb-content-header">
                <h2 class="sb-content-subject">${escapeHtml(message.subject || 'Bericht')} ${stars}</h2>
                <div class="sb-content-meta">
                    <span><strong>Van:</strong> ${escapeHtml(message.name || 'Onbekend')}</span>
                    <span><strong>Platform:</strong> ${escapeHtml(providerLabel(message.provider || ''))}</span>
                    <span><strong>Type:</strong> ${escapeHtml(typeLabel(message.endpointType || ''))}</span>
                    ${message.status ? `<span><strong>Status:</strong> ${escapeHtml(message.status)}</span>` : ''}
                    <span><strong>Tijd:</strong> ${escapeHtml(formatTime(message.time || ''))}</span>
                    ${viewBtn}
                </div>
            </div>
            <div class="sb-content-body">
                ${nl2br(message.fullMessage || 'Geen inhoud beschikbaar.')}
            </div>

            <!-- Antwoord-blok onderaan -->
            <div class="sb-reply-box">
                <div class="sb-reply-label">Antwoorden</div>
                <div class="sb-quick-replies">${quickBtns}</div>
                <textarea id="replyText" class="sb-reply-input" placeholder="Schrijf je antwoord..." rows="3"></textarea>
                <div class="sb-reply-actions">
                    <span class="sb-reply-status" id="replyStatus"></span>
                    <button type="button" class="sb-reply-send" onclick="sendReply('${escapeHtml(message.id)}', '${escapeHtml(message.provider)}', '${escapeHtml(message.endpointType)}')">
                        Verstuur
                    </button>
                </div>
            </div>
        `;
    }

    // Vult het tekstveld met een gekozen snel-antwoord
    function fillReply(text) {
        const input = document.getElementById('replyText');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    // Stuurt het antwoord naar config.php (action=reply), die het doorstuurt naar Metricool.
    async function sendReply(messageId, provider, endpointType) {
        const input = document.getElementById('replyText');
        const status = document.getElementById('replyStatus');
        const replyText = input.value.trim();

        if (replyText === '') {
            status.textContent = 'Schrijf eerst een antwoord.';
            status.className = 'sb-reply-status err';
            return;
        }

        status.textContent = 'Bezig met versturen...';
        status.className = 'sb-reply-status';

        // We bouwen de form-data op die we naar PHP sturen
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
                console.log('Geprobeerde endpoints:', result.tried);
            }
        } catch (err) {
            status.textContent = '✗ Netwerk-fout: ' + err.message;
            status.className = 'sb-reply-status err';
        }
    }

    function getFilteredMessages() {
        const term = searchInput.value.trim().toLowerCase();
        const providerValue = providerFilter.value;
        const typeValue = typeFilter.value;
        const fromValue = dateFrom.value; // bv. "2026-05-01" of leeg
        const toValue = dateTo.value;     // bv. "2026-05-29" of leeg

        return messages.filter(message => {
            const haystack = [
                message.name, message.provider, message.endpointType,
                message.subject, message.preview, message.fullMessage, message.status
            ].join(' ').toLowerCase();

            const matchesSearch = haystack.includes(term);
            const matchesProvider = providerValue === '' || message.provider === providerValue;
            const matchesType = typeValue === '' || message.endpointType === typeValue;

            // ── Datumfilter ──
            // We pakken alleen het datum-gedeelte (de eerste 10 tekens, bv. "2026-05-23").
            // Zo kunnen we makkelijk vergelijken met de gekozen datums.
            let matchesDate = true;
            const messageDate = (message.time || '').substring(0, 10);
            if (fromValue !== '' && messageDate < fromValue) {
                matchesDate = false;
            }
            if (toValue !== '' && messageDate > toValue) {
                matchesDate = false;
            }

            // ── Sterrenfilter ──
            // Dit geldt alleen voor reviews. Andere berichten blijven gewoon zichtbaar.
            let matchesStars = true;
            if (activeStarFilter > 0) {
                if (message.endpointType === 'reviews') {
                    // extraMeta bevat het aantal sterren als tekst, dus we maken er een getal van
                    const stars = parseInt(message.extraMeta, 10);
                    matchesStars = (stars === activeStarFilter);
                } else {
                    // Geen review? Dan verbergen we het als er op sterren gefilterd wordt.
                    matchesStars = false;
                }
            }

            return matchesSearch && matchesProvider && matchesType && matchesDate && matchesStars;
        });
    }

    function renderList() {
        const filtered = getFilteredMessages();

        if (filtered.length === 0) {
            messageList.innerHTML = `<div class="sb-empty-list">Geen berichten gevonden.</div>`;
            contentPanel.innerHTML = `
                <div class="sb-empty-state">
                    <div class="sb-empty-state-icon">🔍</div>
                    <div class="sb-empty-state-text">Geen resultaat voor de huidige filters</div>
                </div>
            `;
            return;
        }

        messageList.innerHTML = filtered.map((message, index) => {
            const avatarHtml = message.avatar
                ? `<div class="sb-avatar"><img src="${escapeHtml(message.avatar)}" alt=""></div>`
                : `<div class="sb-avatar">${escapeHtml(message.initials || 'O')}</div>`;

            const statusHtml = message.status
                ? `<span class="sb-status-badge">${escapeHtml(message.status)}</span>`
                : '';

            // Bij reviews tonen we de sterren in de preview
            let previewHtml = escapeHtml(message.preview || '');
            if (message.endpointType === 'reviews') {
                const stars = ratingStars(message.extraMeta);
                if (stars) {
                    previewHtml = stars + '<br>' + previewHtml;
                }
            }

            return `
                <div class="sb-message-item ${index === 0 ? 'active' : ''}" data-id="${escapeHtml(message.id)}">
                    ${avatarHtml}
                    <div class="sb-message-meta">
                        <div class="sb-badge-row">
                            <span class="sb-provider-badge ${escapeHtml(message.provider)}">${escapeHtml(providerLabel(message.provider))}</span>
                            <span class="sb-type-badge">${escapeHtml(typeLabel(message.endpointType || ''))}</span>
                            ${statusHtml}
                        </div>
                        <div class="sb-message-row-top">
                            <div class="sb-message-name">${escapeHtml(message.name || 'Onbekend')}</div>
                            <div class="sb-message-time">${escapeHtml(formatTime(message.time || ''))}</div>
                        </div>
                        <div class="sb-message-preview">${previewHtml}</div>
                    </div>
                </div>
            `;
        }).join('');

        const first = filtered[0];
        if (first) {
            renderContent(first);
        }

        document.querySelectorAll('.sb-message-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.sb-message-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');

                const id = item.getAttribute('data-id');
                const message = filtered.find(m => String(m.id) === String(id));

                if (message) {
                    renderContent(message);
                }
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