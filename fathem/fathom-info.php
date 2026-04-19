<?php

$API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo";
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

$urlsite = "https://api.usefathom.com/v1/sites/$SITE_ID";


$urldata = "https://api.usefathom.com/v1/aggregations";

$params = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits,pageviews,bounce_rate,avg_duration",
    "field_grouping" => "referrer_hostname",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
    // "filters" => json_encode([
    //     [
    //         "field" => "visits",
    //         "operator" => "is not",
    //         "value" => 10
    //     ]
    // ])
    
];

$paramsUTM = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits,pageviews,bounce_rate,avg_duration",
    "field_grouping" => " utm_source,utm_medium,utm_campaign",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
    // "filters" => json_encode([
    //     [
    //         "field" => "utm_medium",
    //         "operator" => "is",
    //         "value" => "social"
    //     ]
    // ])
];

function callFathom($url, $params, $apiKey) {
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Accept: application/json"
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    
    return json_decode($response, true); 
    }

// $query = http_build_query($params);



// $ch = curl_init("$url?$query");

// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Authorization: Bearer $API_KEY",
//     "Accept: application/json"
// ]);

// $response = curl_exec($ch);
// curl_close($ch);




$data = callFathom($urldata, $params, $API_KEY);

$UTM = callFathom($urldata, $paramsUTM, $API_KEY);


echo "<script>console.log(" . json_encode($data) . ");</script>";
echo "<script>console.log(" . json_encode($UTM) . ");</script>";
$siteData = callFathom($urlsite, [], $API_KEY);


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




$totalVisits = 0;
$totalPageViews = 0;
$totalBounceWeighted = 0;
$totalAvg_duration = 0;
$grouped = [];
$utmGrouped = [];


if (is_array($UTM)) {
    foreach ($UTM as $item) {
        $source = $item['utm_source'] ?? 'unknown';
        $medium = $item['utm_medium'] ?? 'unknown';
        $campaign = $item['utm_campaign'] ?? 'unknown';

        $utmGrouped[] = [
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'visits' => (int)($item['visits'] ?? 0),
            'pageviews' => (int)($item['pageviews'] ?? 0),
            'bounce_rate' => (float)($item['bounce_rate'] ?? 0),
            'avg_duration' => (float)($item['avg_duration'] ?? 0),
        ];
    }
}

if (is_array($data)) {
    foreach ($data as $item) {

        $visits = (int)($item['visits'] ?? 0);
        $views = (int)($item['pageviews'] ?? 0);
        $bounce = (float)($item['bounce_rate'] ?? 0);
        $avg_duration = (float)($item['avg_duration'] ?? 0);


        $totalVisits += $visits;
        $totalPageViews += $views;
        $totalBounceWeighted += $bounce * $visits;
        $totalAvg_duration += $avg_duration * $visits;

        
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
                "avg_duration" => 0,
            ];
        }



        $grouped[$name]['visits'] += $visits;
        $grouped[$name]['views'] += $views;
        $grouped[$name]['bounce_rate'] += $bounce * $visits;
        $grouped[$name]['avg_duration'] += $avg_duration * $visits;

        $grouped[$name]['views_per_visit'] =
            $grouped[$name]['visits'] > 0
            ? $grouped[$name]['views'] / $grouped[$name]['visits']
            : 0;

        $grouped[$name]['bounce_rate'] =
            $grouped[$name]['visits'] > 0
            ? $grouped[$name]['bounce_rate'] / $grouped[$name]['visits']
            : 0;

        $grouped[$name]['avg_duration'] =
            $grouped[$name]['visits'] > 0
            ? $grouped[$name]['avg_duration'] / $grouped[$name]['visits']
            : 0;
        
    }
}




$totalViewsPerVisit = $totalVisits > 0 ? $totalPageViews / $totalVisits : 0;
$totalBounceRate = $totalVisits > 0 ? $totalBounceWeighted / $totalVisits : 0;
$totalAvgDuration = $totalVisits > 0 ? $totalAvg_duration / $totalVisits : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    

</head>
<body class="bg-gray-100 p-6">

<?= "<h1 class='text-2xl font-bold text-center my-4'>" . ($siteData['name'] ?? 'Unknown') . "</h1>" ?>


<section class="flex justify-between text-center  items-center w-full p-4 rounded gap-4 mb-8 bg-[#5603AD]">

    <div class=" text-white p-4 rounded ">
        Totaal visits: <?= $totalVisits ?>
    </div>

    <div class=" text-white p-4 rounded ">
        Totaal pageviews: <?= $totalPageViews ?>
    </div>

    <div class=" text-white p-4 rounded ">
        Views per visit: <?= number_format($totalViewsPerVisit, 2) ?>
    </div>

    <div class=" text-white p-4 rounded">
        Bounce rate: <?= number_format($totalBounceRate, 2) ?>%
    </div>
    <div>
    <div class=" text-white p-4 rounded ">
        Gemiddelde sessieduur: <?= gmdate("i:s", (int)$totalAvgDuration) ?>
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
<?php echo "<script>console.log(" . json_encode($data) . ");</script>"; ?>
<section class="flex justify-center">
    <ul class="w-full max-w-2xl">
        <?php foreach ($grouped as $site): ?>
            <li class="flex justify-between items-center p-4 bg-[#5603AD] text-white rounded my-2">
                <span class="font-bold"><?= htmlspecialchars($site['name']) ?></span>
                <span>
                    <?= $site['visits'] ?> visits |
                    <?= $site['views'] ?> views |
                    <?= number_format($site['views_per_visit'], 2) ?> vpv |
                    <?= number_format($site['bounce_rate'], 2) ?>% bounce rate |
                    <?= gmdate("i:s", $site['avg_duration']) ?> avg duration 
                    
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php echo "<script>console.log(" . json_encode($data) . ");</script>"; ?>
<section class="flex justify-center">
    <ul class="w-full max-w-2xl">
    <?php foreach ($utmGrouped as $item): ?>
        <li class="p-4 bg-[#5603AD] text-white rounded my-2">

            <?php if (!empty($item['utm_source'])): ?>
                <div>Source: <?= htmlspecialchars($item['utm_source']) ?></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_medium'])): ?>
                <div>Medium: <?= htmlspecialchars($item['utm_medium']) ?></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_campaign'])): ?>
                <div>Campaign: <?= htmlspecialchars($item['utm_campaign']) ?></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_content'])): ?>
                <div>Content: <?= htmlspecialchars($item['utm_content']) ?></div>
            <?php endif; ?>

            <div>
                <?= $item['visits'] ?> visits |
                <?= $item['pageviews'] ?> views |
                <?= number_format($item['bounce_rate'], 2) ?>% bounce rate |
                <?= gmdate("i:s", (int)$item['avg_duration']) ?> avg duration
            </div>

        </li>
    <?php endforeach; ?>
</ul>
</section>

<section>
    <form class="max-w-md" action="">
        
    </form>
    <div>
        <label for="website-naam" class="block text-smfont-mediumtext-slate-700 mb-2">
            Naam van je website
        </label>
        <input type="text" id="website-naam" placeholder="https://giuditta.be/" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800bg-white focus:outline-none  focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label for="source" class="block text-smfont-mediumtext-slate-700 mb-2">
            Bron(van welke site komt het verkeer)
        </label>
        <input type="text" id="bronLabel" placeholder="https://www.tiktok.com/" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800bg-white focus:outline-none  focus:ring-blue-500 focus:border-blue-500">
    </div>
        <div>
            <label for="medium" class="block text-smfont-medium text-slate-700 mb-2">
                Type verkeer(soort van verkeer)
            </label>
            <select
                id="mediumSelect"
                class="w-full rounded-xl borderborder-slate-300 px-4 py-3 text-slate-800bg-white focus:outline-none  focus:ring-blue-500 focus:border-blue-500"
            >
                <option value="social">Social (organisch)<option>
                <option value="cpc">Advertentie (CPC)</option>
                <option value="influencer">Influencer</option>
                <option value="email">E-mail</option>
                <option value="referral">Referral</option>
                <option value="custom">Anders</option>
            </select>
            <input
                type="text"
                id="mediumCustom"
                placeholder="Eigen type"
                class="w-full mt-3 hidden rounded-xl border border-slate-300 px-4 py-3">
        </div>

        

            <div>
                <label for="campaign" class="block text-sm font-medium text-slate-700 mb-2">
                    Campagnenaam
                </label>
                <input
                    type="text"
                    id="campaignInput"
                    placeholder="bv. pizza_march"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-slate-700 mb-2">
                    Content / video (utm_content)
                </label>
                <input
                    type="text"
                    id="contentInput"
                    placeholder="bv. video1"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <div>
                <button
                    onclick="generateUTM()"
                    class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700 transition"
                >
                    Genereer UTM link
                </button>
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Gegenereerde link
            </label>
            <div
                id="result"
                class="min-h-[80px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 break-all">
                
            </div>
        </div>
    </div>
</section>
<script defer>
    const mediumSelect = document.getElementById('mediumSelect');
    const mediumCustom = document.getElementById('mediumCustom');

    mediumSelect.addEventListener('change', () => {
        if (mediumSelect.value === 'custom') {
            mediumCustom.classList.remove('hidden');
        } else {
            mediumCustom.classList.add('hidden');
        }
    });
   
    const source = document.getElementById('bronLabel');
    const campaign = document.getElementById('campaignInput');
    const content = document.getElementById('contentInput');

    

     
    
    function generateUTM() {
        const link = source.value + '?utm_source=' + encodeURIComponent(source.value) +
        '&utm_medium=' + encodeURIComponent(mediumSelect.value === 'custom' ? mediumCustom.value : mediumSelect.value) +
        '&utm_campaign=' + encodeURIComponent(campaign.value) +
        '&utm_content=' + encodeURIComponent(content.value);
        const result = document.getElementById('result');
        console.log(link);
        result.textContent = link;
    }
   </script>
</body>
</html>