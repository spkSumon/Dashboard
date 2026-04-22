<?php

$userId = 4394337;
$blogId = 5668624;
$token  = "YTQGUMFFSNCTTTRMJPHRVFOHDACWTAULVIIPDJQOUIJDTONUCOIJUELBHLAZQDUB";

$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "X-Mc-Auth: $token",
];

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

    // Try different possible array keys
    $possibleKeys = ['data', 'results', 'conversations', 'reviews', 'comments', 'items'];
    
    foreach ($possibleKeys as $key) {
        if (isset($decodedData[$key]) && is_array($decodedData[$key])) {
            return $decodedData[$key];
        }
    }

    // Check if the data itself is a numeric array
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
        $name = pickFirstNonEmpty([
            $msg['name'] ?? '',
            $msg['userName'] ?? '',
            $msg['username'] ?? '',
            $msg['author']['name'] ?? '',
            $msg['from']['name'] ?? '',
            $msg['commenter']['name'] ?? '',
            $msg['user']['name'] ?? '',
        ], 'Onbekend');

        $avatar = pickFirstNonEmpty([
            $msg['imageProfileUrl'] ?? '',
            $msg['avatar'] ?? '',
            $msg['avatarUrl'] ?? '',
            $msg['author']['avatar'] ?? '',
            $msg['user']['avatar'] ?? '',
            $msg['author']['imageProfileUrl'] ?? '',
        ], '');

        $fullMessage = pickFirstNonEmpty([
            $msg['text'] ?? '',
            $msg['comment'] ?? '',
            $msg['message'] ?? '',
            $msg['body'] ?? '',
            $msg['content'] ?? '',
        ], '');

        $time = pickFirstNonEmpty([
            $msg['publicationDateTime'] ?? '',
            $msg['date'] ?? '',
            $msg['createdAt'] ?? '',
            $msg['created_at'] ?? '',
            $msg['creationDate'] ?? '',
            $msg['timestamp'] ?? '',
        ], '');

        $status = pickFirstNonEmpty([
            $msg['status'] ?? '',
            $msg['commentStatus'] ?? '',
        ], '');

        $subject = 'Reactie via ' . ucfirst($provider);
    }

    elseif ($endpointType === 'reviews') {
        $name = pickFirstNonEmpty([
            $msg['reviewer']['name'] ?? '',
            $msg['author']['name'] ?? '',
            $msg['name'] ?? '',
            $msg['userName'] ?? '',
        ], 'Onbekend');

        $avatar = pickFirstNonEmpty([
            $msg['reviewer']['imageProfileUrl'] ?? '',
            $msg['reviewer']['avatar'] ?? '',
            $msg['avatar'] ?? '',
        ], '');

        $fullMessage = pickFirstNonEmpty([
            $msg['comment'] ?? '',
            $msg['text'] ?? '',
            $msg['review'] ?? '',
            $msg['message'] ?? '',
        ], '');

        $time = pickFirstNonEmpty([
            $msg['date'] ?? '',
            $msg['creationDate'] ?? '',
            $msg['createdAt'] ?? '',
            $msg['created_at'] ?? '',
            $msg['publicationDateTime'] ?? '',
        ], '');

        $status = pickFirstNonEmpty([
            $msg['status'] ?? '',
        ], '');

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
    <title>Inbox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-tertiary: #1a202e;
            --bg-hover: #1e2432;
            --border: #2d3748;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent: #06b6d4;
            --accent-soft: #0891b2;
            --accent-glow: rgba(6, 182, 212, 0.15);
            --orange: #fb923c;
            --pink: #ec4899;
            --green: #10b981;
            --purple: #a855f7;
            
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-main);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Background effects */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 100%;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .app-shell {
            max-width: 1600px;
            margin: 0 auto;
            padding: 32px 24px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .topbar {
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .topbar h1 {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-pills {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .stat-pill {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            font-family: var(--font-mono);
        }

        .stat-pill strong {
            color: var(--accent);
            margin-right: 6px;
        }

        /* Layout */
        .layout {
            display: grid;
            grid-template-columns: 460px 1fr;
            gap: 24px;
            min-height: calc(100vh - 200px);
        }

        .panel {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            position: relative;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0.5;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }

        .sidebar-header {
            padding: 28px 24px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-tertiary);
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            font-size: 12px;
            color: var(--text-muted);
        }

        .filters {
            display: grid;
            gap: 12px;
        }

        .search-input,
        .filter-select {
            width: 100%;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 12px 16px;
            font-size: 14px;
            border-radius: 12px;
            font-family: var(--font-main);
            font-weight: 500;
            outline: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-input:focus,
        .filter-select:focus {
            border-color: var(--accent);
            background: var(--bg-primary);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        /* Message list */
        .message-list {
            overflow-y: auto;
            flex: 1;
            padding: 8px;
        }

        .message-list::-webkit-scrollbar {
            width: 8px;
        }

        .message-list::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .message-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .message-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        .message-item {
            display: flex;
            gap: 14px;
            padding: 16px;
            margin-bottom: 6px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .message-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent);
            transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-item:hover {
            background: var(--bg-hover);
            border-color: var(--border);
        }

        .message-item.active {
            background: var(--bg-tertiary);
            border-color: var(--accent);
        }

        .message-item.active::before {
            transform: scaleY(1);
        }

        /* Avatar */
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--purple));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            overflow: hidden;
            border: 2px solid var(--bg-secondary);
            position: relative;
        }

        .avatar::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 50%;
            padding: 2px;
            background: linear-gradient(135deg, var(--accent), var(--purple));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.3;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Message meta */
        .message-meta {
            min-width: 0;
            flex: 1;
        }

        .message-row-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: baseline;
            margin-bottom: 8px;
        }

        .message-name {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-muted);
            flex-shrink: 0;
            font-family: var(--font-mono);
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .provider-badge,
        .type-badge,
        .status-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: var(--font-mono);
        }

        .provider-badge {
            background: linear-gradient(135deg, var(--accent), var(--accent-soft));
            color: white;
        }

        .type-badge {
            background: var(--bg-primary);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .status-badge {
            background: linear-gradient(135deg, var(--green), #059669);
            color: white;
        }

        .message-preview {
            font-size: 13px;
            line-height: 1.5;
            color: var(--text-secondary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Content panel */
        .content {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }

        .content-header {
            padding: 32px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-tertiary);
        }

        .content-subject {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 16px 0;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .content-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            font-size: 13px;
            color: var(--text-secondary);
            font-family: var(--font-mono);
        }

        .content-meta strong {
            color: var(--accent);
            margin-right: 6px;
        }

        .content-body {
            padding: 32px;
            font-size: 15px;
            line-height: 1.8;
            color: var(--text-secondary);
            white-space: pre-wrap;
            overflow-y: auto;
            flex: 1;
        }

        .content-body::-webkit-scrollbar {
            width: 8px;
        }

        .content-body::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .content-body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        /* Empty states */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            gap: 16px;
            padding: 60px 30px;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 64px;
            opacity: 0.3;
        }

        .empty-state-text {
            font-size: 18px;
            font-weight: 600;
        }

        .empty-list {
            padding: 32px 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Debug section */
        .debug-section {
            margin-top: 32px;
        }

        .debug-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 12px;
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .debug-toggle:hover {
            background: var(--bg-tertiary);
            border-color: var(--accent);
        }

        .debug-content {
            display: none;
            margin-top: 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
        }

        .debug-content.visible {
            display: block;
        }

        .debug-content h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: var(--font-mono);
        }

        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .debug-card {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            font-size: 12px;
            font-family: var(--font-mono);
        }

        .debug-card strong {
            display: block;
            margin-bottom: 10px;
            font-size: 13px;
            color: var(--accent);
        }

        .debug-card div {
            margin-bottom: 6px;
            color: var(--text-secondary);
        }

        .debug-card .error {
            color: var(--orange);
        }

        .debug-card .success {
            color: var(--green);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                height: auto;
                max-height: 500px;
            }

            .content {
                height: auto;
                min-height: 600px;
            }
        }

        @media (max-width: 640px) {
            .app-shell {
                padding: 16px;
            }

            .topbar h1 {
                font-size: 32px;
            }

            .stat-pill {
                font-size: 11px;
                padding: 6px 12px;
            }

            .content-subject {
                font-size: 24px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-item {
            animation: fadeInUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-item:nth-child(1) { animation-delay: 0.05s; }
        .message-item:nth-child(2) { animation-delay: 0.1s; }
        .message-item:nth-child(3) { animation-delay: 0.15s; }
        .message-item:nth-child(4) { animation-delay: 0.2s; }
        .message-item:nth-child(5) { animation-delay: 0.25s; }
        /* Navbar Styles */
        .navbar {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .nav-link:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        .nav-link.active {
            background: var(--accent);
            color: white;
        }

    </style>
</head>
<body>
    <!-- Let's add a navbar from which you can switch between different social media platforms -->
    <nav class="navbar">
        <a href="config.php" class="nav-link active">Inbox</a>
        <a href="facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="tiktok_dashboard.php" class="nav-link">TikTok</a>
        <a href="gmb_dashboard.php" class="nav-link">Google Business</a>
    </nav>
    <div class="app-shell">
        <div class="topbar">
            <h1>Inbox</h1>
            <div class="stats-pills">
                <div class="stat-pill">
                    <strong><?php echo count($allMessages); ?></strong> berichten
                </div>
                <?php
                $providers = array_unique(array_column($allMessages, 'provider'));
                ?>
                <div class="stat-pill">
                    <strong><?php echo count($providers); ?></strong> platforms
                </div>
            </div>
        </div>

        <div class="layout">
            <div class="panel sidebar">
                <div class="sidebar-header">
                    <h2>Berichten</h2>
                    <div class="filters">
                        <input
                            type="text"
                            id="searchInput"
                            class="search-input"
                            placeholder="Zoeken..."
                        >

                        <select id="providerFilter" class="filter-select">
                            <option value="">Alle providers</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="gmb">GMB</option>
                            <option value="tiktokBusiness">TikTok</option>
                        </select>

                        <select id="typeFilter" class="filter-select">
                            <option value="">Alle types</option>
                            <option value="conversations">Conversations</option>
                            <option value="post-comments">Comments</option>
                            <option value="reviews">Reviews</option>
                        </select>
                    </div>
                </div>

                <div class="message-list" id="messageList"></div>
            </div>

            <div class="panel content" id="contentPanel">
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <div class="empty-state-text">Selecteer een bericht om te lezen</div>
                </div>
            </div>
        </div>

        <div class="debug-section">
            <button class="debug-toggle" onclick="toggleDebug()">
                🔧 Debug Info
            </button>
            <div class="debug-content" id="debugContent">
                <h3>API Endpoints Status</h3>
                <div class="debug-grid">
                    <?php foreach ($debugInfo as $dbg): ?>
                        <div class="debug-card">
                            <strong><?php echo htmlspecialchars($dbg['provider']); ?></strong>
                            <div>Type: <?php echo htmlspecialchars($dbg['endpointType']); ?></div>
                            <div>HTTP: <span class="<?php echo $dbg['httpCode'] == 200 ? 'success' : 'error'; ?>"><?php echo htmlspecialchars((string) $dbg['httpCode']); ?></span></div>
                            <div>Items: <?php echo htmlspecialchars((string) $dbg['count']); ?></div>
                            <?php if (!empty($dbg['rawDataKeys'])): ?>
                                <div>Keys: <?php echo htmlspecialchars(implode(', ', $dbg['rawDataKeys'])); ?></div>
                            <?php endif; ?>
                            <div class="<?php echo $dbg['error'] ? 'error' : 'success'; ?>">
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
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch {
                return time;
            }
        }

        function providerLabel(provider) {
            const labels = {
                'tiktokBusiness': 'TikTok',
                'gmb': 'GMB',
                'facebook': 'Facebook',
                'instagram': 'Instagram'
            };
            return labels[provider] || provider;
        }

        function renderContent(message) {
            contentPanel.innerHTML = `
                <div class="content-header">
                    <h2 class="content-subject">${escapeHtml(message.subject || 'Bericht')}</h2>
                    <div class="content-meta">
                        <span><strong>Van:</strong> ${escapeHtml(message.name || 'Onbekend')}</span>
                        <span><strong>Platform:</strong> ${escapeHtml(providerLabel(message.provider || ''))}</span>
                        <span><strong>Type:</strong> ${escapeHtml(message.endpointType || '')}</span>
                        ${message.status ? `<span><strong>Status:</strong> ${escapeHtml(message.status)}</span>` : ''}
                        <span><strong>Tijd:</strong> ${escapeHtml(formatTime(message.time || ''))}</span>
                    </div>
                </div>
                <div class="content-body">
                    ${nl2br(message.fullMessage || 'Geen inhoud beschikbaar.')}
                </div>
            `;
        }

        function getFilteredMessages() {
            const term = searchInput.value.trim().toLowerCase();
            const providerValue = providerFilter.value;
            const typeValue = typeFilter.value;

            return messages.filter(message => {
                const haystack = [
                    message.name,
                    message.provider,
                    message.endpointType,
                    message.subject,
                    message.preview,
                    message.fullMessage,
                    message.status
                ].join(' ').toLowerCase();

                const matchesSearch = haystack.includes(term);
                const matchesProvider = providerValue === '' || message.provider === providerValue;
                const matchesType = typeValue === '' || message.endpointType === typeValue;

                return matchesSearch && matchesProvider && matchesType;
            });
        }

        function renderList() {
            const filtered = getFilteredMessages();

            if (filtered.length === 0) {
                messageList.innerHTML = `<div class="empty-list">Geen berichten gevonden.</div>`;
                contentPanel.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <div class="empty-state-text">Geen resultaat voor de huidige filters</div>
                    </div>
                `;
                return;
            }

            messageList.innerHTML = filtered.map((message, index) => {
                const avatarHtml = message.avatar
                    ? `<div class="avatar"><img src="${escapeHtml(message.avatar)}" alt=""></div>`
                    : `<div class="avatar">${escapeHtml(message.initials || 'O')}</div>`;

                const statusHtml = message.status
                    ? `<span class="status-badge">${escapeHtml(message.status)}</span>`
                    : '';

                return `
                    <div class="message-item ${index === 0 ? 'active' : ''}" data-id="${escapeHtml(message.id)}">
                        ${avatarHtml}
                        <div class="message-meta">
                            <div class="badge-row">
                                <span class="provider-badge">${escapeHtml(providerLabel(message.provider))}</span>
                                <span class="type-badge">${escapeHtml(message.endpointType || '')}</span>
                                ${statusHtml}
                            </div>
                            <div class="message-row-top">
                                <div class="message-name">${escapeHtml(message.name || 'Onbekend')}</div>
                                <div class="message-time">${escapeHtml(formatTime(message.time || ''))}</div>
                            </div>
                            <div class="message-preview">${escapeHtml(message.preview || '')}</div>
                        </div>
                    </div>
                `;
            }).join('');

            const first = filtered[0];
            if (first) {
                renderContent(first);
            }

            document.querySelectorAll('.message-item').forEach(item => {
                item.addEventListener('click', () => {
                    document.querySelectorAll('.message-item').forEach(el => el.classList.remove('active'));
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
        providerFilter.addEventListener('change', renderList);
        typeFilter.addEventListener('change', renderList);

        renderList();
    </script>
</body>
</html>