<?php
 
$API_KEY = "";
$SITE_ID = "WGXUYRJQ";
 
 
$range = $_GET['range'] ?? '7days';
 
switch ($range) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        break;
 
    case '30days':
        $dateFrom = date('Y-m-d', strtotime('-29 days'));
        $dateTo = date('Y-m-d');
        break;
 
    default: // 7 days
        $dateFrom = date('Y-m-d', strtotime('-6 days'));
        $dateTo = date('Y-m-d');
        break;
}
 
 
$url = "https://api.usefathom.com/v1/aggregations";
 
$params = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits,pageviews,bounce_rate",
    "field_grouping" => "referrer_hostname",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
    "limit" => 50,
    "timezone" => "Europe/Brussels"
];
 
$query = http_build_query($params);
 
$ch = curl_init("$url?$query");
 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $API_KEY",
    "Accept: application/json"
]);
 
$response = curl_exec($ch);
curl_close($ch);
 
 
function getMainDomain($hostname) {
    $hostname = preg_replace('/^(www|m|l)\./', '', $hostname);
    $parts = explode('.', $hostname);
    $count = count($parts);
 
    if ($count >= 3 && strlen($parts[$count - 2]) <= 3) {
        return $parts[$count - 3];
    }
 
    if ($count >= 2) {
        return $parts[$count - 2];
    }
 
    return $hostname;
}
 
$data = json_decode($response, true);
 
$totalVisits = 0;
$totalPageViews = 0;
$totalBounceWeighted = 0;
$grouped = [];
 
if (is_array($data)) {
    foreach ($data as $item) {
 
        $visits = (int)($item['visits'] ?? 0);
        $views = (int)($item['pageviews'] ?? 0);
        $bounce = (float)($item['bounce_rate'] ?? 0);
 
        $totalVisits += $visits;
        $totalPageViews += $views;
        $totalBounceWeighted += $bounce * $visits;
 
        // unknown referrer
        if (empty($item['referrer_hostname'])) {
            $name = "unknown";
        } else {
            $name = getMainDomain($item['referrer_hostname']);
        }
 
        if (!isset($grouped[$name])) {
            $grouped[$name] = [
                "name" => $name,
                "visits" => 0,
                "views" => 0,
                "views_per_visit" => 0,
                "bounce_rate" => 0,
                "bounce_weighted" => 0
            ];
        }
 
        $grouped[$name]['visits'] += $visits;
        $grouped[$name]['views'] += $views;
        $grouped[$name]['bounce_weighted'] += $bounce * $visits;
 
        $grouped[$name]['views_per_visit'] =
            $grouped[$name]['visits'] > 0
                ? $grouped[$name]['views'] / $grouped[$name]['visits']
                : 0;
 
        $grouped[$name]['bounce_rate'] =
            $grouped[$name]['visits'] > 0
                ? $grouped[$name]['bounce_weighted'] / $grouped[$name]['visits']
                : 0;
    }
}
 
$totalViewsPerVisit = $totalVisits > 0 ? $totalPageViews / $totalVisits : 0;
$totalBounceRate = $totalVisits > 0 ? $totalBounceWeighted / $totalVisits : 0;
 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
 
 
 
 
 
<section class="flex items-center gap-4 mb-8">
 
    <div class="bg-[#5603AD] text-white p-4 rounded w-full max-w-md">
        Totaal visits: <?= $totalVisits ?>
    </div>
 
    <div class="bg-[#5603AD] text-white p-4 rounded w-full max-w-md">
        Totaal pageviews: <?= $totalPageViews ?>
    </div>
 
    <div class="bg-[#5603AD] text-white p-4 rounded w-full max-w-md">
        Views per visit: <?= number_format($totalViewsPerVisit, 2) ?>
    </div>
 
    <div class="bg-[#5603AD] text-white p-4 rounded w-full max-w-md">
        Bounce rate: <?= number_format($totalBounceRate, 2) ?>%
    </div>
 
</section>
 
<section>
    <form method="GET" class="flex gap-2 justify-center my-6">
    <button name="range" value="today"
        class="px-4 py-2 rounded text-white <?= $range === 'today' ? 'bg-black' : 'bg-[#5603AD]' ?>">
        Vandaag
    </button>
 
    <button name="range" value="7days"
        class="px-4 py-2 rounded text-white <?= $range === '7days' ? 'bg-black' : 'bg-[#5603AD]' ?>">
        7 dagen
    </button>
 
    <button name="range" value="30days"
        class="px-4 py-2 rounded text-white <?= $range === '30days' ? 'bg-black' : 'bg-[#5603AD]' ?>">
        30 dagen
    </button>
</form>
</section>
 
<section class="flex justify-center">
    <ul class="w-full max-w-2xl">
        <?php foreach ($grouped as $site): ?>
            <li class="flex justify-between items-center p-4 bg-[#5603AD] text-white rounded my-2">
                <span class="font-bold"><?= htmlspecialchars($site['name']) ?></span>
                <span>
                    <?= $site['visits'] ?> visits |
                    <?= $site['views'] ?> views |
                    <?= number_format($site['views_per_visit'], 2) ?> vpv |
                    <?= number_format($site['bounce_rate'], 2) ?>%
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
 
</body>
</html>