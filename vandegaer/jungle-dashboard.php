<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Jungle Survival API - Real data from database
$conn = new mysqli('127.0.0.1', 'root', '', 'social_media_analytics', 3306);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database down - survival mode failed']);
    exit;
}

$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'status':
        // Survival status check - what's alive vs dead
        $status = [
            'timestamp' => time(),
            'database' => 'alive',
            'sources' => []
        ];

        // Check posts data
        $postsCount = $conn->query('SELECT COUNT(*) as cnt FROM posts')->fetch_assoc()['cnt'];
        $status['sources']['posts'] = [
            'status' => $postsCount > 0 ? 'alive' : 'dead',
            'count' => (int)$postsCount,
            'health' => $postsCount > 0 ? 100 : 0
        ];

        // Check social accounts (API connections)
        $accountsCount = $conn->query('SELECT COUNT(*) as cnt FROM social_accounts WHERE is_active = 1')->fetch_assoc()['cnt'];
        $status['sources']['social_accounts'] = [
            'status' => $accountsCount > 0 ? 'alive' : 'starving',
            'count' => (int)$accountsCount,
            'health' => 0,
            'hint' => 'No API accounts connected - survival at risk!'
        ];

        // Check hashtag tracking
        $hashtagsCount = $conn->query('SELECT COUNT(*) as cnt FROM hashtag_performance')->fetch_assoc()['cnt'];
        $status['sources']['hashtags'] = [
            'status' => $hashtagsCount > 0 ? 'alive' : 'dead',
            'count' => (int)$hashtagsCount,
            'health' => $hashtagsCount > 0 ? 100 : 0
        ];

        // Check website analytics
        $webCount = $conn->query('SELECT COUNT(*) as cnt FROM website_analytics')->fetch_assoc()['cnt'];
        $status['sources']['website_analytics'] = [
            'status' => $webCount > 0 ? 'alive' : 'starving',
            'count' => (int)$webCount,
            'health' => 0,
            'hint' => 'No Fathom/Google Analytics connected'
        ];

        // Overall survival score
        $totalHealth = $status['sources']['posts']['health'] +
                       $status['sources']['hashtags']['health'];
        $status['survival_score'] = round($totalHealth / 4); // Out of 4 sources
        $status['threat_level'] = $status['survival_score'] < 50 ? 'critical' : ($status['survival_score'] < 75 ? 'warning' : 'safe');

        echo json_encode($status, JSON_PRETTY_PRINT);
        break;

    case 'metrics':
        // Get real metrics from posts
        $result = $conn->query("
            SELECT
                DATE(posted_date) as date,
                SUM(views) as total_views,
                SUM(likes) as total_likes,
                SUM(comments) as total_comments,
                SUM(shares) as total_shares,
                AVG(engagement_rate) as avg_engagement,
                AVG(completion_rate) as avg_completion,
                AVG(watch_time) as avg_watch_time,
                COUNT(*) as post_count
            FROM posts
            WHERE client_id = 1
            GROUP BY DATE(posted_date)
            ORDER BY posted_date DESC
            LIMIT 30
        ");

        $metrics = [];
        while ($row = $result->fetch_assoc()) {
            $metrics[] = [
                'date' => $row['date'],
                'views' => (int)$row['total_views'],
                'likes' => (int)$row['total_likes'],
                'comments' => (int)$row['total_comments'],
                'shares' => (int)$row['total_shares'],
                'engagement_rate' => round((float)$row['avg_engagement'], 2),
                'completion_rate' => round((float)$row['avg_completion'], 2),
                'watch_time' => (int)$row['avg_watch_time'],
                'posts' => (int)$row['post_count']
            ];
        }

        echo json_encode(array_reverse($metrics), JSON_PRETTY_PRINT);
        break;

    case 'top_posts':
        // Top performing posts (survivors)
        $result = $conn->query("
            SELECT
                id,
                platform,
                topic,
                SUBSTRING(caption, 1, 100) as caption_preview,
                views,
                likes,
                comments,
                shares,
                engagement_rate,
                completion_rate,
                posted_date
            FROM posts
            WHERE client_id = 1
            ORDER BY engagement_rate DESC
            LIMIT 10
        ");

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = [
                'id' => (int)$row['id'],
                'platform' => $row['platform'],
                'topic' => $row['topic'],
                'preview' => $row['caption_preview'],
                'views' => (int)$row['views'],
                'likes' => (int)$row['likes'],
                'comments' => (int)$row['comments'],
                'shares' => (int)$row['shares'],
                'engagement' => round((float)$row['engagement_rate'], 2),
                'completion' => round((float)$row['completion_rate'], 2),
                'date' => $row['posted_date'],
                'threat_level' => (float)$row['engagement_rate'] > 5 ? 'safe' : ((float)$row['engagement_rate'] > 2 ? 'warning' : 'critical')
            ];
        }

        echo json_encode($posts, JSON_PRETTY_PRINT);
        break;

    case 'hashtags':
        // Hashtag survival stats
        $result = $conn->query("
            SELECT
                hashtag,
                total_posts,
                total_views,
                total_likes,
                avg_engagement_rate,
                last_used
            FROM hashtag_performance
            WHERE client_id = 1
            ORDER BY avg_engagement_rate DESC
            LIMIT 20
        ");

        $hashtags = [];
        while ($row = $result->fetch_assoc()) {
            $hashtags[] = [
                'tag' => $row['hashtag'],
                'uses' => (int)$row['total_posts'],
                'views' => (int)$row['total_views'],
                'likes' => (int)$row['total_likes'],
                'engagement' => round((float)$row['avg_engagement_rate'], 2),
                'last_used' => $row['last_used'],
                'status' => (int)$row['total_posts'] > 3 ? 'strong' : 'weak'
            ];
        }

        echo json_encode($hashtags, JSON_PRETTY_PRINT);
        break;

    case 'api_check':
        // Check which APIs are configured
        $apis = [
            'tiktok' => [
                'name' => 'TikTok API',
                'status' => 'unknown',
                'hint' => 'Check tiktok_tokens table'
            ],
            'metricool' => [
                'name' => 'Metricool API',
                'status' => 'unknown',
                'hint' => 'Check settings table for metricool_api_key'
            ],
            'fathom' => [
                'name' => 'Fathom Analytics',
                'status' => 'unknown',
                'hint' => 'Check settings table for fathom_api_key'
            ],
            'google_business' => [
                'name' => 'Google Business',
                'status' => 'unknown',
                'hint' => 'No credentials found'
            ]
        ];

        // Check TikTok tokens
        $tikTokCount = $conn->query('SELECT COUNT(*) as cnt FROM tiktok_tokens WHERE expires_at > NOW()')->fetch_assoc()['cnt'];
        $apis['tiktok']['status'] = $tikTokCount > 0 ? 'alive' : 'expired';
        $apis['tiktok']['count'] = (int)$tikTokCount;

        // Check settings for API keys
        $settingsResult = $conn->query("SELECT `key`, `value` FROM settings WHERE `key` LIKE '%api%' OR `key` LIKE '%token%'");
        $settingsKeys = [];
        while ($row = $settingsResult->fetch_assoc()) {
            $settingsKeys[$row['key']] = !empty($row['value']);
        }

        $apis['metricool']['status'] = isset($settingsKeys['metricool_api_key']) && $settingsKeys['metricool_api_key'] ? 'configured' : 'missing';
        $apis['fathom']['status'] = isset($settingsKeys['fathom_api_key']) && $settingsKeys['fathom_api_key'] ? 'configured' : 'missing';

        echo json_encode($apis, JSON_PRETTY_PRINT);
        break;

    case 'website_traffic':
        // Get posts that drove most website traffic
        $result = $conn->query("
            SELECT
                p.id,
                p.platform,
                p.topic,
                p.posted_date,
                COALESCE(SUM(pwt.referral_visits), 0) as total_visits,
                AVG(pwt.bounce_rate) as avg_bounce_rate,
                AVG(pwt.avg_session_duration) as avg_session
            FROM posts p
            LEFT JOIN post_website_traffic pwt ON p.id = pwt.post_id
            WHERE p.client_id = 1
            GROUP BY p.id
            HAVING total_visits > 0
            ORDER BY total_visits DESC
            LIMIT 10
        ");

        $traffic = [];
        while ($row = $result->fetch_assoc()) {
            $traffic[] = [
                'id' => (int)$row['id'],
                'platform' => $row['platform'],
                'topic' => $row['topic'],
                'date' => $row['posted_date'],
                'visits' => (int)$row['total_visits'],
                'bounce_rate' => round((float)$row['avg_bounce_rate'], 2),
                'session_duration' => (int)$row['avg_session']
            ];
        }

        echo json_encode($traffic, JSON_PRETTY_PRINT);
        break;

    case 'algorithm_health':
        // Calculate algorithm health score based on 2026 metrics
        $result = $conn->query("
            SELECT
                AVG(completion_rate) as avg_completion,
                AVG(watch_time) as avg_watch_time,
                AVG(CASE WHEN platform = 'instagram' THEN skip_rate END) as avg_skip_rate,
                AVG(saves_count) as avg_saves,
                AVG(sends_count) as avg_sends,
                COUNT(*) as total_posts
            FROM posts
            WHERE client_id = 1
                AND posted_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $row = $result->fetch_assoc();

        // Calculate score (out of 100)
        $completionScore = min(($row['avg_completion'] ?? 0) / 75 * 30, 30); // 30 points max
        $watchTimeScore = min(($row['avg_watch_time'] ?? 0) / 60 * 20, 20); // 20 points max
        $skipScore = max(0, 20 - (($row['avg_skip_rate'] ?? 15) / 15 * 20)); // 20 points max (lower is better)
        $savesScore = min(($row['avg_saves'] ?? 0) / 5 * 15, 15); // 15 points max
        $sendsScore = min(($row['avg_sends'] ?? 0) / 3 * 15, 15); // 15 points max

        $totalScore = round($completionScore + $watchTimeScore + $skipScore + $savesScore + $sendsScore);

        $health = [
            'score' => $totalScore,
            'grade' => $totalScore >= 80 ? 'Excellent' : ($totalScore >= 60 ? 'Good' : ($totalScore >= 40 ? 'Fair' : 'Poor')),
            'metrics' => [
                'completion_rate' => round((float)$row['avg_completion'], 2),
                'watch_time' => (int)$row['avg_watch_time'],
                'skip_rate' => round((float)$row['avg_skip_rate'], 2),
                'saves' => round((float)$row['avg_saves'], 2),
                'sends' => round((float)$row['avg_sends'], 2)
            ],
            'posts_analyzed' => (int)$row['total_posts']
        ];

        echo json_encode($health, JSON_PRETTY_PRINT);
        break;

    case 'platform_breakdown':
        // Platform comparison
        $result = $conn->query("
            SELECT
                platform,
                COUNT(*) as post_count,
                SUM(views) as total_views,
                AVG(engagement_rate) as avg_engagement,
                AVG(completion_rate) as avg_completion
            FROM posts
            WHERE client_id = 1
            GROUP BY platform
            ORDER BY total_views DESC
        ");

        $platforms = [];
        while ($row = $result->fetch_assoc()) {
            $platforms[] = [
                'platform' => $row['platform'],
                'posts' => (int)$row['post_count'],
                'views' => (int)$row['total_views'],
                'engagement' => round((float)$row['avg_engagement'], 2),
                'completion' => round((float)$row['avg_completion'], 2)
            ];
        }

        echo json_encode($platforms, JSON_PRETTY_PRINT);
        break;

    case 'weekly_comparison':
        // This week vs last week
        $thisWeek = $conn->query("
            SELECT
                COUNT(*) as posts,
                SUM(views) as views,
                AVG(engagement_rate) as engagement,
                AVG(completion_rate) as completion
            FROM posts
            WHERE client_id = 1
                AND posted_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetch_assoc();

        $lastWeek = $conn->query("
            SELECT
                COUNT(*) as posts,
                SUM(views) as views,
                AVG(engagement_rate) as engagement,
                AVG(completion_rate) as completion
            FROM posts
            WHERE client_id = 1
                AND posted_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                AND posted_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetch_assoc();

        $comparison = [
            'this_week' => [
                'posts' => (int)$thisWeek['posts'],
                'views' => (int)$thisWeek['views'],
                'engagement' => round((float)$thisWeek['engagement'], 2),
                'completion' => round((float)$thisWeek['completion'], 2)
            ],
            'last_week' => [
                'posts' => (int)$lastWeek['posts'],
                'views' => (int)$lastWeek['views'],
                'engagement' => round((float)$lastWeek['engagement'], 2),
                'completion' => round((float)$lastWeek['completion'], 2)
            ],
            'changes' => [
                'posts' => (int)$thisWeek['posts'] - (int)$lastWeek['posts'],
                'views' => (int)$thisWeek['views'] - (int)$lastWeek['views'],
                'engagement' => round((float)$thisWeek['engagement'] - (float)$lastWeek['engagement'], 2),
                'completion' => round((float)$thisWeek['completion'] - (float)$lastWeek['completion'], 2)
            ]
        ];

        echo json_encode($comparison, JSON_PRETTY_PRINT);
        break;

    case 'hashtag_recommendations':
        // Recommend hashtags based on performance
        $result = $conn->query("
            SELECT
                hashtag,
                total_posts,
                avg_engagement_rate,
                last_used,
                DATEDIFF(NOW(), last_used) as days_since_use
            FROM hashtag_performance
            WHERE client_id = 1
                AND avg_engagement_rate >= 3.0
                AND total_posts >= 2
            ORDER BY avg_engagement_rate DESC
            LIMIT 15
        ");

        $recommendations = [];
        while ($row = $result->fetch_assoc()) {
            $status = 'recommended';
            if ($row['days_since_use'] > 30) {
                $status = 'underused';
            } elseif ($row['avg_engagement_rate'] > 5) {
                $status = 'star_performer';
            }

            $recommendations[] = [
                'hashtag' => $row['hashtag'],
                'uses' => (int)$row['total_posts'],
                'engagement' => round((float)$row['avg_engagement_rate'], 2),
                'last_used' => $row['last_used'],
                'days_ago' => (int)$row['days_since_use'],
                'status' => $status
            ];
        }

        echo json_encode($recommendations, JSON_PRETTY_PRINT);
        break;

    case 'export':
        // Export data as CSV or JSON
        $format = $_GET['format'] ?? 'json';
        $type = $_GET['type'] ?? 'posts';

        if ($type === 'posts') {
            $result = $conn->query("
                SELECT
                    id,
                    platform,
                    topic,
                    caption,
                    views,
                    likes,
                    comments,
                    shares,
                    engagement_rate,
                    completion_rate,
                    posted_date
                FROM posts
                WHERE client_id = 1
                ORDER BY posted_date DESC
                LIMIT 1000
            ");

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="posts_export_' . date('Y-m-d') . '.csv"');

                $output = fopen('php://output', 'w');
                fputcsv($output, ['ID', 'Platform', 'Topic', 'Caption', 'Views', 'Likes', 'Comments', 'Shares', 'Engagement', 'Completion', 'Date']);

                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, $row);
                }

                fclose($output);
                exit;
            } else {
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                echo json_encode($data, JSON_PRETTY_PRINT);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action - you died']);
        break;
}

$conn->close();
