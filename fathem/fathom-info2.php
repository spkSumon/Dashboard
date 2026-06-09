<?php


// Fathom Analytics dashboard
// SITE_ID=WGXUYRJQ

$API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo";
$SITE_ID = "WGXUYRJQ";


$range = $_GET['range'] ?? '7days';

// Custom datumkiezer — als date_from en date_to meegegeven worden via GET,
// gebruiken we die i.p.v. de range-knoppen. Zo werkt het datumformulier ook echt.
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $dateFrom = $_GET['date_from'];
    $dateTo   = $_GET['date_to'];
    $range    = 'custom';

    // Bereken hoeveel dagen het bereik is om te bepalen of we per uur of per dag groeperen
    $diffDays = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
    $date_grouping = $diffDays < 2 ? 'hour' : 'day';
} else {
    // Standaard range-knoppen
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
}

// API URLs
$url = "https://api.usefathom.com/v1/current_visitors?site_id=$SITE_ID";
$urldata = "https://api.usefathom.com/v1/aggregations";

// Parameters voor de API-calls
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


// Functie om de Fathom API aan te roepen met URL, parameters en API key
function callFathom($url, $params, $apiKey) {
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Accept: application/json"
        ],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        return ['error' => curl_error($ch)];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return ['error' => 'Invalid JSON'];
    }

    return $data;
}

function cleanCache($dir, $maxAge = 3600) {
    if (!is_dir($dir)) return;

    foreach (glob($dir . '*.json') as $file) {
        if (time() - filemtime($file) > $maxAge) {
            unlink($file);
        }
    }
}

function limitCacheSize($dir, $maxFiles = 20) {
    if (!is_dir($dir)) return;

    $files = glob($dir . '*.json');

    if (count($files) <= $maxFiles) return;

    usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

    $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);

    foreach ($filesToDelete as $file) {
        unlink($file);
    }
}

function cachedCall($key, $callback, $ttl = 60) {
    $dir = __DIR__ . '/cache/';
    $file = $dir . md5($key) . '.json';
    
    if (rand(1, 20) === 1) {
    cleanCache($dir, 3600);    
    limitCacheSize($dir, 200); 
}
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (file_exists($file)) {
        if (time() - filemtime($file) < $ttl) {
            return json_decode(file_get_contents($file), true);
        } else {
            unlink($file); 
        }
    }

    $data = $callback();
    file_put_contents($file, json_encode($data));

    return $data;
}


$currentVisitors = cachedCall('visitors_'.$dateFrom.$dateTo, fn() => callFathom($url, [], $API_KEY));
$data = cachedCall('data_'.$dateFrom.$dateTo, fn() => callFathom($urldata, $params, $API_KEY));
$UTM = cachedCall('utm_'.$dateFrom.$dateTo, fn() => callFathom($urldata, $paramsUTM, $API_KEY));
$graphic = cachedCall('graphic_'.$dateFrom.$dateTo, fn() => callFathom($urldata, $paramsWeekDaily, $API_KEY));


$graphicinfo = array_values($graphic);
$values = [];
$datums = [];
foreach ($graphicinfo as $element){
    $values[] = $element["visits"];
    $datums[] = $element["date"];
}


$currentVisitors = json_encode($currentVisitors['total'] ?? 0);


// Functie om het hoofddomein uit een URL te halen,
// zodat we de data per domein groeperen i.p.v. per subdomein
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

// UTM data groeperen
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

// Referrer data groeperen per hoofddomein en totalen berekenen
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

    $grouped[$name]['avg_duration'] =
        $grouped[$name]['visits'] > 0 ? $grouped[$name]['duration_sum'] / $grouped[$name]['visits'] : 0;

    $grouped[$name]['bounce_rate'] =
        $grouped[$name]['visits'] > 0 ? $grouped[$name]['bounce_sum'] / $grouped[$name]['visits'] : 0;
}

$totalViewsPerVisit = $totalVisits > 0 ? $totalPageViews / $totalVisits : 0;
$totalBounceRate = $totalVisits > 0 ? $totalBounceWeighted / $totalVisits : 0;
$totalAvgDuration = $totalVisits > 0 ? $totalAvg_duration / $totalVisits : 0;


?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Website Analytics — SkyByte</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<!--
Styling:
    - styles.css bevat regels die gebruik maken van body:has(#realtimeD)
    - Hierdoor wordt deze pagina automatisch gestyled op basis van de HTML-structuur
-->
<body >

<!--
Navigatie:
    - Wordt gestyled via body:has(#realtimeD) nav in styles.css
    - Het SkyByte-woordmerk wordt automatisch toegevoegd via ::before
-->
<nav class="navbar">
    <a href="../api/config.php" class="nav-link">Inbox</a>
    <a href="../api/instagram_dashboard.php">Instagram</a>
    <a href="../api/tiktok_dashboard.php">TikTok</a>
    <a href="../api/facebook_dashboard.php">Facebook</a>
    <a href="fathom-info2.php">Fathom Analytics</a>
</nav>


<!--
KPI-strip:
    - Wordt automatisch als grid weergegeven
    - Paginatitel "Website analytics" wordt toegevoegd via ::before
-->
<section>

    <div id="realtimeD">
        Totaal realtime visitors: <?= $currentVisitors ?>
        <div id="exp-realtime" class="hidden">
            Het aantal mensen dat nu op je website zit.
        </div>
    </div>

    <div id="visitsD">
        Totaal visits: <?= $totalVisits ?>
        <div id="exp-visits" class="hidden">
            Het aantal bezoeken aan je website. Als iemand 3 pagina's bekijkt, telt dat als 1 visit.
        </div>
    </div>

    <div id="pageviewsD">
        Totaal pageviews: <?= $totalPageViews ?>
        <div id="exp-pageviews" class="hidden">
            Hoeveel pagina's in totaal bekeken zijn.
        </div>
    </div>

    <div id="viewsD">
        Views per visit: <?= number_format($totalViewsPerVisit, 2) ?>
        <div id="exp-vpv" class="hidden">
            Gemiddeld aantal pagina's per bezoek.
        </div>
    </div>

    <div id="bounceD">
        Bounce rate: <?= number_format($totalBounceRate, 2) ?>%
        <div id="exp-bounce" class="hidden">
            Percentage dat direct weggaat na 1 pagina.
        </div>
    </div>

    <div id="durationD">
        Gemiddelde sessieduur: <?= gmdate("i:s", (int)round($totalAvgDuration)) ?>
        <div id="exp-duration" class="hidden">
            Gemiddelde tijd dat iemand op je site blijft.
        </div>
    </div>

</section>

<!-- Tooltip JS: toont uitleg na 1 seconde hoveren -->
<script>
function setupTooltip(triggerId, tooltipId) {
    const trigger = document.getElementById(triggerId);
    const tooltip = document.getElementById(tooltipId);
    let hoverTimer;

    trigger.addEventListener("mouseenter", () => {
        hoverTimer = setTimeout(() => {
            tooltip.classList.remove("hidden");
        }, 700);
    });

    trigger.addEventListener("mouseleave", () => {
        clearTimeout(hoverTimer);
        tooltip.classList.add("hidden");
    });
}
const tooltips = [
    ["realtimeD", "exp-realtime"],
    ["visitsD", "exp-visits"],
    ["pageviewsD", "exp-pageviews"],
    ["viewsD", "exp-vpv"],
    ["bounceD", "exp-bounce"],
    ["durationD", "exp-duration"],
];

tooltips.forEach(([trigger, tooltip]) => {
    setupTooltip(trigger, tooltip);
});
</script>


<!-- Grafiek: styles.css target dit via section:has(#Chart)
     en geeft de container een vaste hoogte van 360px -->
<section>
    <div>
        <canvas id="Chart"></canvas>
    </div>
</section>

<!-- Datumkiezer: styles.css target het formulier via
     form:has(> input[type="date"][name="date_from"]) -->
<section>
    <form method="GET">
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        <span>—</span>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        <button type="submit">Apply</button>
    </form>
</section>

<!-- Range-knoppen: styles.css target via form:has(> button[name="range"])
     De actieve knop krijgt class="bg-black" die de CSS gebruikt
     om een accentkleur-onderlijning te tonen -->
<section>
    <form method="GET">
        <button name="range" value="today" class="<?= $range === 'today' ? 'bg-black' : '' ?>">
            Vandaag
        </button>
        <button name="range" value="7days" class="<?= $range === '7days' ? 'bg-black' : '' ?>">
            7 dagen
        </button>
        <button name="range" value="30days" class="<?= $range === '30days' ? 'bg-black' : '' ?>">
            30 dagen
        </button>
    </form>
</section>

<!-- Referrer-lijst: styles.css target via section > ul > li
     en maakt er automatisch een platte lijst met hairlines van -->
<section>
    <ul>
        <?php foreach ($grouped as $site): ?>
            <li>
                <span><?= htmlspecialchars($site['name']) ?></span>
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
<section>
    <table>
    <caption>Website analytics</caption>

    <thead>
        <tr>
            <th scope="col">Page</th>
            <th scope="col">Visits</th>
            <th scope="col">Views</th>
            <th scope="col">Views per visit</th>
            <th scope="col">Bounce rate</th>
            <th scope="col">Avg duration</th>
        </tr>
    </thead>

    <tbody>
        
        <?php foreach ($grouped as $site): ?>
            <tr>
                <th scope="row"><?= htmlspecialchars($site['name']) ?></th>
                <td><?= $site['visits'] ?></td>
                <td><?= $site['views'] ?></td>
                <td><?= number_format($site['views_per_visit'], 2) ?> vpv</td>
                <td><?= number_format($site['bounce_rate'], 2) ?>% bounce rate</td>
                <td><?= gmdate("i:s", (int)(round($site['avg_duration']))) ?> avg duration</td>
            </tr>
        <?php endforeach; ?>
    </tbody>

</table>
</section>
<!-- UTM-data lijst -->
<section>
    <ul>
    <?php foreach ($utmGrouped as $item): ?>
        <li>
            <?php if (!empty($item['utm_source'])): ?>
                <div>Source: <?= htmlspecialchars($item['utm_source']) ?></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_medium'])): ?>
                <div>Medium: <?= htmlspecialchars($item['utm_medium']) ?></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_campaign'])): ?>
                <div>Campaign: <?= htmlspecialchars($item['utm_campaign']) ?></div>
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

<!-- UTM-generator: styles.css target via section:has(#sourceSelect) -->
<section>

    <div>
        <label>Bron (van welke site komt het verkeer)</label>
        <select id="sourceSelect">
            <option value="tiktok">TikTok</option>
            <option value="instagram">Instagram</option>
            <option value="facebook">Facebook</option>
            <option value="youtube">YouTube</option>
            <option value="custom">Anders</option>
        </select>
        <input type="text" id="sourceCustom" placeholder="Eigen source" class="hidden">
    </div>

    <div>
        <label>Type verkeer</label>
        <select id="mediumSelect">
            <option value="social">Social</option>
            <option value="custom">Anders</option>
        </select>
        <input type="text" id="mediumCustom" placeholder="Eigen type" class="hidden">
    </div>

    <div>
        <label>Campagnenaam</label>
        <input type="text" id="campaignInput">
    </div>

    <div>
        <label>Content / video</label>
        <input type="text" id="contentInput">
    </div>

    <div>
        <button onclick="generateUTM()">Genereer UTM link</button>
    </div>

    <div>
        <div id="result"></div>
        <button onclick="copyLink()">Kopieer link</button>
    </div>
</section>

<!-- Alle JavaScript in één blok — geen dubbele functies meer -->
<script defer>
    const mediumSelect = document.getElementById('mediumSelect');
    const mediumCustom = document.getElementById('mediumCustom');
    const sourceSelect = document.getElementById('sourceSelect');
    const sourceCustom = document.getElementById('sourceCustom');
    const campaign = document.getElementById('campaignInput');
    const content = document.getElementById('contentInput');

    // Toon het custom-veld als "Anders" geselecteerd is
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

    // UTM link genereren op basis van de ingevulde velden
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

    // Link kopiëren naar klembord
    function copyLink() {
        const link = document.getElementById("result").textContent;
        navigator.clipboard.writeText(link)
            .then(() => {
                alert("Link gekopieerd!");
            })
            .catch(err => {
                console.error("Kopiëren mislukt: ", err);
            });
    }


    // Chart.js grafiek — visits over tijd
    const labels = <?= json_encode($datums) ?>;
    const data = <?= json_encode($values) ?>;

    const ctx = document.getElementById('Chart').getContext('2d');

    // Plugin die een verticale lijn toont bij de tooltip
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