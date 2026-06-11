<?php

require_once __DIR__ . '/../api/token.php';
// =======================
// 1. BASIS INSTELLINGEN
// =======================

// Fathom Analytics dashboard


$API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo";
$SITE_ID = "WGXUYRJQ";

// Lees wat gebruiker kiest in de URL (bijv. ?range=30days)

$range = $_GET['range'] ?? '7days';

// Custom datumkiezer — als date_from en date_to meegegeven worden via GET,
// gebruiken we die i.p.v. de range-knoppen. Zo werkt het datumformulier ook echt.


if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $dateFrom = $_GET['date_from'];
    $dateTo   = $_GET['date_to'];
    $range    = 'custom';

    // Bereken hoeveel dagen het bereik is om te bepalen of we per uur of per dag groeperen
    $diffDays = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
    if ($diffDays <= 1) {
    $date_grouping = 'hour';
} 
elseif ($diffDays <= 90) {
    $date_grouping = 'day';} 
    else {
    $date_grouping = 'month';
}
    
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
    "date_grouping" => $date_grouping,
    "field_grouping" => "referrer_hostname",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
];


$paramsWeekDaily = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "aggregates" => "visits",
    "date_grouping" => "$date_grouping",
    "sort_by" => "timestamp:asc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
    "timezone" => "Europe/Brussels"
];


$paramsUTM = [
    "entity" => "pageview",
    "entity_id" => $SITE_ID,
    "date_grouping" => $date_grouping,
    "aggregates" => "visits,pageviews,bounce_rate,avg_duration",
    "field_grouping" => "utm_source,utm_medium,utm_campaign",
    "sort_by" => "visits:desc",
    "date_from" => $dateFrom . " 00:00:00",
    "date_to" => $dateTo . " 23:59:59",
];


// Functie om de Fathom API aan te roepen met URL, parameters en API key



// =======================
// DATA OPHALEN
// =======================

$currentVisitors = callFathom($url, [], $API_KEY);
$data = cachedCall("data_{$dateFrom}_{$dateTo}_{$date_grouping}", fn() => callFathom($urldata, $params, $API_KEY));
$graphic = cachedCall("graphic_{$dateFrom}_{$dateTo}_{$date_grouping}", fn() => callFathom($urldata, $paramsWeekDaily, $API_KEY));
$UTM = cachedCall("utm_{$dateFrom}_{$dateTo}_{$date_grouping}", fn() => callFathom($urldata, $paramsUTM, $API_KEY));





// =======================
// GRAFIEK DATA VERWERKEN
// =======================
$graphicinfo = array_values($graphic);
$values = [];
$datums = [];

foreach ($graphicinfo as $element) {
    $values[] = $element["visits"] ?? 0;;
    $datums[] = $element["date"] ?? '';
}


$currentVisitors = json_encode($currentVisitors['total'] ?? 0);


// Functie om het hoofddomein uit een URL te halen,
// zodat we de data per domein groeperen i.p.v. per subdomein



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
if (is_array($data)) {
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
}}


$totalViewsPerVisit = $totalVisits > 0 ? $totalPageViews / $totalVisits : 0;
$totalBounceRate = $totalVisits > 0 ? $totalBounceWeighted / $totalVisits : 0;
$totalAvgDuration = $totalVisits > 0 ? $totalAvg_duration / $totalVisits : 0;

$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'asc';


usort($grouped, function($a, $b) use ($sort, $order) {
    $result = $a[$sort] <=> $b[$sort];
    return $order === 'asc' ? $result : -$result;
});
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
<body class="fathom-page">


<header class="sb-header">
    

    <nav class="navbar">
        <a href="../api/config.php" class="nav-link">Inbox</a>
        <a href="../api/instagram_dashboard.php" class="nav-link">Instagram</a>
        <a href="../api/tiktok_dashboard.php" class="nav-link">TikTok</a>
        <a href="../api/facebook_dashboard.php" class="nav-link">Facebook</a>
        <a href="fathom-info2.php" class="nav-link active">Fathom Analytics</a>
    </nav>
</header>

<main class="sb-main-content">
    <h1>Fathom Analytics</h1>
    <p>Overzicht van je websiteverkeer</p>
<!-- KPI-strip — class .sb-kpi-strip styled de grid; data-title voor de
     paginatitel via ::before -->
<section class="sb-kpi-strip" >

    <div id="realtimeD" class="sb-kpi">
        <span>Totaal realtime visitors: <?= $currentVisitors ?></span>
        <div id="exp-realtime" class="sb-tooltip is-hidden">
            <span>Het aantal mensen dat nu op je website zit.</span> 
        </div>
    </div>

    <div id="visitsD" class="sb-kpi">
        <span>Totaal visits: <?= $totalVisits ?></span>
        <div id="exp-visits" class="sb-tooltip is-hidden">
            <span>Het aantal bezoeken aan je website. Als iemand 3 pagina's bekijkt, telt dat als 1 visit.</span>
        </div>
    </div>

    <div id="pageviewsD" class="sb-kpi">
        <span>Totaal pageviews: <?= $totalPageViews ?></span>
        <div id="exp-pageviews" class="sb-tooltip is-hidden">
            <span>Hoeveel pagina's in totaal bekeken zijn.</span>
        </div>
    </div>

    <div id="viewsD" class="sb-kpi">
        <span>Views per visit: <?= number_format($totalViewsPerVisit, 2) ?></span>
        <div id="exp-vpv" class="sb-tooltip is-hidden">
            <span>Gemiddeld aantal pagina's per bezoek.</span>
        </div>
    </div>

    <div id="bounceD" class="sb-kpi">
        <span>Bounce rate: <?= number_format($totalBounceRate, 0) ?>%</span>
        <div id="exp-bounce" class="sb-tooltip is-hidden">
            <span>Percentage dat direct weggaat na 1 pagina.</span>
        </div>
    </div>

    <div id="durationD" class="sb-kpi">
        <span> Gemiddelde sessieduur: <?= gmdate("i:s", (int)round($totalAvgDuration)) ?></span>
        <div id="exp-duration" class="sb-tooltip is-hidden">
            <span>Gemiddelde tijd dat iemand op je site blijft.</span>
        </div>
    </div>

</section>


<!-- Grafiek -->
<section class="sb-chart-section">
    <div>
        <canvas id="Chart" class="sb-chart-canvas"></canvas>
    </div>
</section>

<!-- Datumkiezer -->
<section>
    <p>Kies een specifiek datumbereik:</p>
    <form method="GET" class="sb-date-form">
        <input type="date" name="date_from" class="sb-field sb-field--inline" value="<?= htmlspecialchars($dateFrom) ?>">
        
        <input type="date" name="date_to" class="sb-field sb-field--inline" value="<?= htmlspecialchars($dateTo) ?>">
        <button type="submit" class="sb-btn-dark">Apply</button>
    </form>
</section>

<!-- Range-knoppen — active variant via .is-active -->
<section>
    <form method="GET" class="sb-range-form">
        <button name="range" value="today" class="sb-range-btn <?= $range === 'today' ? 'is-active' : '' ?>">
            Vandaag
        </button>
        <button name="range" value="7days" class="sb-range-btn <?= $range === '7days' ? 'is-active' : '' ?>">
            7 dagen
        </button>
        <button name="range" value="30days" class="sb-range-btn <?= $range === '30days' ? 'is-active' : '' ?>">
            30 dagen
        </button>
    </form>
</section>

<!-- Referrer-lijst: styles.css target via section > ul > li
     en maakt er automatisch een platte lijst met hairlines van -->


<section>
    <table>
        <thead>
            <tr>
                <th scope="col" class="sb-kpi" id="pagenameTable" class="">
                    <a class="link" href="<?= sortUrl('name', $sort, $order) ?>">
                        Page<?= getArrow('name', $sort, $order) ?>
                    </a>
                    <div id="table-pagename" class="sb-tooltip is-hidden">
                        <span>Dit is de bron van de bezoekers (waar ze vandaan komen).</span>
                        
                    </div>
                </th>
                <th scope="col" id="visitsTable" class="sb-kpi"  >
                    <a class="link" href="<?= sortUrl('visits', $sort, $order) ?>">Visits<?= getArrow('visits', $sort, $order) ?></a>
                    <div id="table-visits" class="sb-tooltip is-hidden">
                        <span>Het aantal keren dat mensen de website bezoeken.</span>
                    </div>
                </th>
                <th scope="col" id="pageviewsTable" class="sb-kpi"  >
                    <a class="link" href="<?= sortUrl('views', $sort, $order) ?>">
                        Views<?= getArrow('views', $sort, $order) ?>
                    </a>
                    <div id="table-pageviews" class="sb-tooltip is-hidden">
                        <span>Het totaal aantal pagina’s dat bekeken wordt.</span>
                    </div>
                </th>

                <th scope="col" id="viewsPerVisitTable" class="sb-kpi">
                    <a class="link" href="<?= sortUrl('views_per_visit', $sort, $order) ?>">
                        Views per visit<?= getArrow('views_per_visit', $sort, $order) ?>
                    </a>
                    <div id="table-views-per-visit" class="sb-tooltip is-hidden">
                        <span>Het gemiddelde aantal pagina’s dat iemand bekijkt per bezoek.</span>
                    </div>
                </th>
                <th scope="col" id="bounceRateTable" class="sb-kpi"  >
                    <a class="link" href="<?= sortUrl('bounce_rate', $sort, $order) ?>">
                        Bounce rate<?= getArrow('bounce_rate', $sort, $order) ?>
                    </a>
                    <div id="table-bounce-rate" class="sb-tooltip is-hidden">
                        <span>Het percentage bezoekers dat de website opent en meteen weggaat zonder iets anders te bekijken.</span>
                    </div>
                </th>
                <th scope="col" id="avgDurationTable" class="sb-kpi"  >
                    <a class="link" href="<?= sortUrl('avg_duration', $sort, $order) ?>">
                        Avg duration<?= getArrow('avg_duration', $sort, $order) ?>
                    </a>
                    <div id="table-avg-duration" class="sb-tooltip is-hidden">
                        <span>De gemiddelde tijd dat een bezoeker op de website blijft.</span>
                    </div>
                </th>
            </tr>
        </thead>
    
    <tbody>
        
        <?php foreach ($grouped as $site): ?>
            <tr>
                <th scope="row"><?= htmlspecialchars($site['name']) ?></th>
                <td><?= $site['visits'] ?></td>
                <td><?= $site['views'] ?></td>
                <td><?= number_format($site['views_per_visit'], 2) ?> </td>
                <td><?= number_format($site['bounce_rate'], 0) ?>% </td>
                <td><?= gmdate("i:s", (int)(round($site['avg_duration']))) ?> </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

</table>
</section>
<!-- UTM-data lijst -->
<section>
    <ul class="sb-data-list">
    <?php foreach ($utmGrouped as $item): ?>
        <li>
            <?php if (!empty($item['utm_source'])): ?>
                <div><span>Source: <?= htmlspecialchars($item['utm_source']) ?></span></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_medium'])): ?>
                <div><span>Medium: <?= htmlspecialchars($item['utm_medium']) ?></span></div>
            <?php endif; ?>

            <?php if (!empty($item['utm_campaign'])): ?>
                <div><span>Campaign: <?= htmlspecialchars($item['utm_campaign']) ?></span></div>
            <?php endif; ?>

            <div><span>
                <?= $item['visits'] ?> visits |
                <?= $item['pageviews'] ?> views |
                <?= number_format($item['bounce_rate'], 2) ?>% bounce rate |
                <?= gmdate("i:s", (int)$item['avg_duration']) ?> avg duration
                </span>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
</section>

<!-- UTM-generator -->
<section class="sb-utm-section">

    <div>
        <label>Bron (van welke site komt het verkeer)</label>
        <select id="sourceSelect" class="sb-field">
            <option value="tiktok">TikTok</option>
            <option value="instagram">Instagram</option>
            <option value="facebook">Facebook</option>
            <option value="youtube">YouTube</option>
            <option value="custom">Anders</option>
        </select>
        <input type="text" id="sourceCustom" class="sb-field is-hidden" placeholder="Eigen source">
    </div>

    <div>
        <label>Type verkeer</label>
        <select id="mediumSelect" class="sb-field">
            <option value="social">Social</option>
            <option value="custom">Anders</option>
        </select>
        <input type="text" id="mediumCustom" class="sb-field is-hidden" placeholder="Eigen type">
    </div>

    <div>
        <label>Campagnenaam</label>
        <input type="text" id="campaignInput" class="sb-field">
    </div>

    <div>
        <label>Content / video</label>
        <input type="text" id="contentInput" class="sb-field">
    </div>

    <div>
        <button type="button" class="sb-btn-dark" onclick="generateUTM()">Genereer UTM link</button>
    </div>

    <div>
        <div id="result" class="sb-utm-result"></div>
        <button type="button" class="sb-btn-outline" onclick="copyLink()">Kopieer link</button>
    </div>
</section>
</main>
<!-- Alle JavaScript in één blok — geen dubbele functies meer -->
<script defer>
    function setupTooltip(triggerId, tooltipId) {
    const trigger = document.getElementById(triggerId);
    const tooltip = document.getElementById(tooltipId);
    let hoverTimer;

    trigger.addEventListener("mouseenter", () => {
        hoverTimer = setTimeout(() => {
            tooltip.classList.remove("is-hidden");
        }, 700);
    });

    trigger.addEventListener("mouseleave", () => {
        clearTimeout(hoverTimer);
        tooltip.classList.add("is-hidden");
    });
}
const tooltips = [
    ["realtimeD", "exp-realtime"],
    ["visitsD", "exp-visits"],
    ["pageviewsD", "exp-pageviews"],
    ["viewsD", "exp-vpv"],
    ["bounceD", "exp-bounce"],
    ["durationD", "exp-duration"],
    ["pagenameTable", "table-pagename"],
    ["visitsTable", "table-visits"],
    ["pageviewsTable", "table-pageviews"],
    ["viewsPerVisitTable", "table-views-per-visit"],
    ["bounceRateTable", "table-bounce-rate"],
    ["avgDurationTable", "table-avg-duration"],
];

tooltips.forEach(([trigger, tooltip]) => {
    setupTooltip(trigger, tooltip);
});
    

    const mediumSelect = document.getElementById('mediumSelect');
    const mediumCustom = document.getElementById('mediumCustom');
    const sourceSelect = document.getElementById('sourceSelect');
    const sourceCustom = document.getElementById('sourceCustom');
    const campaign = document.getElementById('campaignInput');
    const content = document.getElementById('contentInput');

    // Toon het custom-veld als "Anders" geselecteerd is
    sourceSelect.addEventListener('change', () => {
        if (sourceSelect.value === 'custom') {
            sourceCustom.classList.remove('is-hidden');
        } else {
            sourceCustom.classList.add('is-hidden');
        }
    });
    mediumSelect.addEventListener('change', () => {
        if (mediumSelect.value === 'custom') {
            mediumCustom.classList.remove('is-hidden');
        } else {
            mediumCustom.classList.add('is-hidden');
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