<?php
 

 
//
// SITE_ID=WGXUYRJQ
 
$API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo";
$SITE_ID = "WGXUYRJQ";
 
 
$range = $_GET['range'] ?? '7days';
// for buttons in html to check from which range the user wants to see data
switch ($range) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        $date_grouping = 'hour';
        break;
 
    case '30days':
        $dateFrom = date('Y-m-d', strtotime('-29 days'));
        $dateTo = date('Y-m-d');
        $date_grouping = 'day';
        break;
 
    default: // 7 days
        $dateFrom = date('Y-m-d', strtotime('-6 days'));
        $dateTo = date('Y-m-d');
        $date_grouping = 'day';
        break;
}
// our api's url to get data from our site
$urlsite = "https://api.usefathom.com/v1/sites/$SITE_ID";
 
$url = "https://api.usefathom.com/v1/current_visitors?site_id=$SITE_ID";
 
 
$urldata = "https://api.usefathom.com/v1/aggregations";
// parameters for our api call that we use
$params = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits,pageviews,bounce_rate,avg_duration",
    "field_grouping" => "referrer_hostname",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
   
   
];
 
 
 
 
$paramsWeekDaily = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits",
    "date_grouping" => $date_grouping,
    "sort_by" => "timestamp:asc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
    "timezone" => "Europe/Brussels"
];
 
 
$paramsUTM = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits,pageviews,bounce_rate,avg_duration",
    "field_grouping" => "utm_source,utm_medium,utm_campaign",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",  
];
 
 
// function to call the api with the url, parameters and api key
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
 
 
 
$currentVisitors = callFathom($url, [], $API_KEY);
// we call the function to get data from the api
$data = callFathom($urldata, $params, $API_KEY);
echo "<script>console.log(" . json_encode($data) . ");</script>";
$UTM = callFathom($urldata, $paramsUTM, $API_KEY);
$graphic = callFathom($urldata, $paramsWeekDaily, $API_KEY);
 
$siteData = callFathom($urlsite, [], $API_KEY);
 
 
$graphicinfo = array_values($graphic);
$values = [];
$datums = [];
foreach ($graphicinfo as $element){
   
    $values[] = $element["visits"];
    $datums[] = $element["date"];
   
 
 
}
 
 
$currentVisitors = json_encode($currentVisitors['total'] ?? 0);
 
 
echo "<script>console.log(" . json_encode($values) . ");</script>";
echo "<script>console.log(" . json_encode($datums) . ");</script>";
 
 
 
// function to get the main domain from a url, we use this to group the data by main domain instead of subdomains
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
 
 
 
//
$totalVisits = 0;
$totalPageViews = 0;
$totalBounceWeighted = 0;
$totalAvg_duration = 0;
$grouped = [];
$utmGrouped = [];
// we loop through the data to group it by main domain and calculate the total and average values for each domain
 
if (is_array($UTM)) {
    foreach ($UTM as $key => $item){
       
 
       
        if (!empty($item['utm_source']) or !empty($item['utm_medium']) or !empty($item['utm_campaign'])) {
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
}
 
foreach ($data as $loll => $site) {
 
    $visits = (int)($site['visits'] ?? 0);
    $views = (int)($site['pageviews'] ?? 0);
    $bounce = (float)($site['bounce_rate'] ?? 0);
    $avg_duration = (float)($site['avg_duration'] ?? 0);
 
    $name = empty($site['referrer_hostname'])
        ? 'unknown'
        : getMainDomain($site['referrer_hostname']);
 
    if (!isset($grouped[$name])) {
        $grouped[$name] = [
            'name' => $name,
            'visits' => 0,
            'views' => 0,
            'duration_sum' => 0,
            'bounce_sum' => 0,
        ];
    }
 
    $totalVisits += $visits;
    $totalPageViews += $views;
    $totalBounceWeighted += $bounce * $visits;
    $totalAvg_duration += $avg_duration * $visits;
    $grouped[$name]['visits'] += $visits;
    $grouped[$name]['views'] += $views;
    $grouped[$name]['duration_sum'] += $avg_duration * $visits;
    $grouped[$name]['bounce_sum'] += $bounce * $visits;
 
    $grouped[$name]['views_per_visit'] =
    $grouped[$name]['visits'] > 0
        ? $grouped[$name]['views'] / $grouped[$name]['visits']
        : 0;
    // $grouped[$name]['views_per_visit'] =
    //     $totalVisits > 0 ? $site['views'] / $totalVisits : 0;
   
    $grouped[$name]['avg_duration'] =
        $grouped[$name]['visits'] > 0 ? $grouped[$name]['duration_sum'] / $grouped[$name]['visits'] : 0;
 
    $grouped[$name]['bounce_rate'] =
        $grouped[$name]['visits'] > 0 ? $grouped[$name]['bounce_sum'] / $grouped[$name]['visits'] : 0;
}
echo "<script>console.log(" . json_encode($grouped) . ");</script>";
// foreach ($grouped as $name => $site) {
 
//     $visits = $site['visits'];
 
//     $grouped[$name]['views_per_visit'] =
//         $visits > 0 ? $site['views'] / $visits : 0;
 
//     $grouped[$name]['avg_duration'] =
//         $visits > 0 ? $site['duration_sum'] / $visits : 0;
 
//     $grouped[$name]['bounce_rate'] =
//         $visits > 0 ? $site['bounce_sum'] / $visits : 0;
// }
 
    //     echo "<script>console.log(" . json_encode(
    // $name . ': ' . (
    //     ($grouped[$name]['visits'] > 0 && $grouped[$name]['avg_duration'] > 0)
    //         ? $grouped[$name]['avg_duration'] / $grouped[$name]['visits']
    //         : 0
    // )
// ) . ");</script>";
        // $grouped[$name]['avg_duration'] =
        //     ($grouped[$name]['visits'] > 0 and $grouped[$name]['avg_duration'] > 0)
        //     ? $grouped[$name]['avg_duration'] / $grouped[$name]['visits']
        //     : 0;
        // $grouped[$name]['avg_duration'] = ($grouped[$name]['avg_duration'] * $grouped[$name]['visits']) / ($grouped[$name]['visits']);
   
$totalViewsPerVisit = $totalVisits > 0 ? $totalPageViews / $totalVisits : 0;
$totalBounceRate = $totalVisits > 0 ? $totalBounceWeighted / $totalVisits : 0;
$totalAvgDuration = $totalVisits > 0 ? $totalAvg_duration / $totalVisits : 0;
 
 
//
 
 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 
</head>
<head>
    <nav class="bg-[#5603AD] text-white p-4 flex gap-6">
        <a href="fathom-info2.php" class="font-semibold hover:text-gray-300">Fathom</a>
        <a href="../api/instagram_dashboard.php" class="font-semibold hover:text-gray-300">Instagram</a>
        <a href="../api/tiktok_dashboard.php" class="font-semibold hover:text-gray-300">TikTok</a>
        <a href="../api/facebook_dashboard.php" class="font-semibold hover:text-gray-300">Facebook</a>
    </nav>
</head>
<body class="bg-gray-100 ">
 
<section class="flex justify-between items-center w-full p-4 rounded gap-16 mb-8 bg-[#5603AD]">
 
    <!-- Realtime -->
    <div id="realtimeD" class="relative text-white p-4 rounded">
        Totaal realtime visitors: <?= $currentVisitors ?>
        <div id="exp-realtime"
     class="hidden transition-none absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 w-full text-white">
            Het aantal mensen dat nu op je website zit.
        </div>
    </div>
 
    <!-- Visits -->
    <div id="visitsD" class="relative text-white p-4 rounded">
        Totaal visits: <?= $totalVisits ?>
        <div id="exp-visits" class="hidden absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 mt-2  w-full p-4 text-white">
            Het aantal bezoeken aan je website. Als iemand 3 pagina’s bekijkt, telt dat als 1 visit.
        </div>
    </div>
 
    <!-- Pageviews -->
    <div id="pageviewsD" class="relative text-white p-4 rounded">
        Totaal pageviews: <?= $totalPageViews ?>
        <div id="exp-pageviews" class="hidden absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 mt-2 w-full p-4 text-white">
            Hoeveel pagina’s in totaal bekeken zijn.
        </div>
    </div>
 
    <!-- Views per visit -->
    <div id="viewsD" class="relative text-white p-4 rounded">
        Views per visit: <?= number_format($totalViewsPerVisit, 2) ?>
        <div id="exp-vpv" class="hidden absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 mt-2 w-full p-4 text-white">
            Gemiddeld aantal pagina’s per bezoek.
        </div>
    </div>
 
    <!-- Bounce -->
    <div id="bounceD" class="relative text-white p-4 rounded">
        Bounce rate: <?= number_format($totalBounceRate, 2) ?>%
        <div id="exp-bounce" class="hidden absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 mt-2 w-full p-4 text-white">
            Percentage dat direct weggaat na 1 pagina.
        </div>
    </div>
 
    <!-- Duration -->
    <div id="durationD" class="relative text-white p-4 rounded">
        Gemiddelde sessieduur: <?= gmdate("i:s", (int)round($totalAvgDuration)) ?>
        <div id="exp-duration" class="hidden absolute bg-[#240046] border-2 border-red-500 p-2 rounded top-full left-1/2 -translate-x-1/2 mt-2 w-full p-4 text-white">
            Gemiddelde tijd dat iemand op je site blijft.
        </div>
    </div>
 
</section>
 
<script>
function setupTooltip(triggerId, tooltipId) {
    const trigger = document.getElementById(triggerId);
    const tooltip = document.getElementById(tooltipId);
 
    let hoverTimer;
 
trigger.addEventListener("mouseenter", () => {
    hoverTimer = setTimeout(() => {
        tooltip.classList.remove("hidden");
    }, 1000);
});
 
trigger.addEventListener("mouseleave", () => {
    clearTimeout(hoverTimer);
    tooltip.classList.add("hidden");
});
}
 
// Apply to ALL blocks
setupTooltip("realtimeD", "exp-realtime");
setupTooltip("visitsD", "exp-visits");
setupTooltip("pageviewsD", "exp-pageviews");
setupTooltip("viewsD", "exp-vpv");
setupTooltip("bounceD", "exp-bounce");
setupTooltip("durationD", "exp-duration");
</script>
 
 
 
<section>
    <div class="m-auto w-[100%] max-w-[90%]  ">
        <canvas id="Chart" class="" ></canvas>
    </div>
</section>
<section class="w-full max-w-[600px] mx-auto mt-10 mb-6">
    <form method="GET" class="flex gap-2 justify-center">
        <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'))) ?>" class="px-4 py-2 rounded border border-slate-300">
        <span class="px-4 py-2 text-slate-700 font-semibold">-</span>
        <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? date('Y-m-d')) ?>" class="px-4 py-2 rounded border border-slate-300">
        <button type="submit" class="px-4 py-2 rounded text-white bg-[#5603AD] hover:bg-black">
            Apply
        </button>
    </form>
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
<!-- <?php echo "<script>console.log(" . json_encode($data) . ");</script>"; ?> -->
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
                    <?= gmdate("i:s", (int)(round($site['avg_duration']))) ?> avg duration
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
 
<section class="w-full max-w-[600px] mx-auto mt-10">
   
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
            Bron (van welke site komt het verkeer)
        </label>
        <select id="sourceSelect"
            class="w-full max-w-md mr-auto ml-auto rounded-xl border border-slate-300 px-4 py-3 text-slate-800 bg-white">
            <option value="tiktok">TikTok</option>
            <option value="instagram">Instagram</option>
            <option value="facebook">Facebook</option>
            <option value="youtube">YouTube</option>
            <option value="custom">Anders</option>
        </select>
 
        <input type="text" id="sourceCustom" placeholder="Eigen source"
            class="w-full mt-3 hidden rounded-xl border border-slate-300 px-4 py-3">
    </div>
 
    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">
            Type verkeer
        </label>
        <select id="mediumSelect"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-800 bg-white">
            <option value="social">Social</option>
            <option value="custom">Anders</option>
        </select>
 
        <input type="text" id="mediumCustom" placeholder="Eigen type"
            class="w-full mt-3 hidden rounded-xl border border-slate-300 px-4 py-3">
    </div>
 
    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">
            Campagnenaam
        </label>
        <input type="text" id="campaignInput"
            class="w-full rounded-xl border border-slate-300 px-4 py-3">
    </div>
 
    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">
            Content / video
        </label>
        <input type="text" id="contentInput"
            class="w-full rounded-xl border border-slate-300 px-4 py-3">
    </div>
 
    <div class="mt-6">
        <button onclick="generateUTM()"
            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-white font-semibold">
            Genereer UTM link
        </button>
    </div>
 
    <div class="mt-6">
        <div id="result"
            class="min-h-[60px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm break-all">
        </div>
 
        <button onclick="copyLink()" class="mt-3 w-full bg-green-600  py-2 rounded-xl">
            Kopieer link
        </button>
    </div>
 
</section>
 
<script>
function generateUTM() {
    let source = document.getElementById("sourceSelect").value;
    let medium = document.getElementById("mediumSelect").value;
    let campaign = document.getElementById("campaignInput").value;
    let content = document.getElementById("contentInput").value;
 
    if (source === "custom") {
        source = document.getElementById("sourceCustom").value;
    }
 
    if (medium === "custom") {
        medium = document.getElementById("mediumCustom").value;
    }
 
    const url = `https://example.com/?utm_source=${source}&utm_medium=${medium}&utm_campaign=${campaign}&utm_content=${content}`;
 
    document.getElementById("result").innerText = url;
}
 
function copyLink() {
    const text = document.getElementById("result").innerText;
 
    navigator.clipboard.writeText(text)
        .then(() => alert("Скопировано!"));
}
</script>
<script defer>
    const mediumSelect = document.getElementById('mediumSelect');
    const mediumCustom = document.getElementById('mediumCustom');
 
   
    const sourceSelect = document.getElementById('sourceSelect');
    const sourceCustom = document.getElementById('sourceCustom');
    sourceSelect.addEventListener('change', () => {
        if (sourceSelect.value === 'custom') {
            sourceCustom.classList.remove('hidden');
        } else {
            sourceCustom.classList.add('hidden');
        }
    });
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
    let baseUrl = "https://giuditta.be/";
    let params = [];
 
    const sourceValue = sourceSelect.value === 'custom' ? sourceCustom.value : sourceSelect.value;
    if (sourceValue) {
        params.push('utm_source=' + encodeURIComponent(sourceValue));
    }
 
    const mediumValue = mediumSelect.value === 'custom' ? mediumCustom.value : mediumSelect.value;
    if (mediumValue) {
        params.push('utm_medium=' + encodeURIComponent(mediumValue));
    }
 
    if (campaign.value) {
        params.push('utm_campaign=' + encodeURIComponent(campaign.value));
    }
 
    if (content.value) {
        params.push('utm_content=' + encodeURIComponent(content.value));
    }
 
    const link = baseUrl + (params.length ? '?' + params.join('&') : '');
 
    document.getElementById('result').textContent = link;
}
 
function copyLink() {
  const link = document.getElementById("result").textContent;
  navigator.clipboard.writeText(link)
    .then(() => {
      alert("Copied: " + link);
    })
    .catch(err => {
      console.error("Failed to copy: ", err);
    });
}
 
   
   
   
const labels = <?= json_encode($datums) ?>;
const data = <?= json_encode($values) ?>;
 
const ctx = document.getElementById('Chart').getContext('2d');
 
const verticalLinePlugin = {
    id: 'verticalLine',
    afterDraw(chart) {
        const tooltip = chart.tooltip;
 
        if (!tooltip || !tooltip.dataPoints || tooltip.dataPoints.length === 0) {
            return;
        }
 
        const ctx = chart.ctx;
        const x = tooltip.dataPoints[0].element.x;
        const yScale = chart.scales.y;
 
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, yScale.top);
        ctx.lineTo(x, yScale.bottom);
        ctx.lineWidth = 1;
        ctx.strokeStyle = '#000000';
        ctx.stroke();
        ctx.restore();
    }
};
 
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Visits',
            data: data,
            borderColor: '#6C5CE7',
            backgroundColor: 'rgba(108, 92, 231, 0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 8,
            pointHitRadius: 20,
            pointBackgroundColor: '#6C5CE7',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#6C5CE7',
            pointHoverBorderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        hover: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Visits over time',
                color: '#aaa'
            },
            tooltip: {
                enabled: true,
                backgroundColor: '#111',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#666',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#aaa'
                },
                grid: {
                    color: 'rgba(255,255,255,0.08)'
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#aaa'
                },
                grid: {
                    color: 'rgba(255,255,255,0.08)'
                }
            }
        }
    },
    plugins: [verticalLinePlugin]
});
 
</script>
</body>
</html>