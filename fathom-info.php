<?php

$API_KEY = "3377699761000144|ZhxxOuBDflYHpimJd7QtnAaf5TbSeu9c3mARhsFo";
$SITE_ID = "WGXUYRJQ";

// ==========================
// Helper functie (API call)
// ==========================
function fathomRequest($url, $params, $apiKey) {
    $query = http_build_query($params);

    $ch = curl_init("$url?$query");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Accept: application/json"
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        "status" => $status,
        "data" => json_decode($response, true),
        "raw" => $response
    ];
}

// ==========================
// 1. Site info
// ==========================
function site_info($SITE_ID, $API_KEY) {
    $url = "https://api.usefathom.com/v1/sites/$SITE_ID";
    $res = fathomRequest($url, [], $API_KEY);

    if ($res["status"] == 200) {
        echo "Site naam: " . $res["data"]["name"] . "\n";
        echo "Aangemaakt op: " . $res["data"]["created_at"] . "\n";
    } else {
        echo "Fout bij site info:\n";
        print_r($res["raw"]);
    }
}

// ==========================
// 2. Bezoeken deze maand
// ==========================
function bezoeken_deze_maand($SITE_ID, $API_KEY) {
    $url = "https://api.usefathom.com/v1/aggregations";

    $params = [
        "entity" => "pageview",
        "entity_id" => $SITE_ID,
        "aggregates" => "visits,pageviews",
        "date_from" => "2026-03-01",
        "date_to" => "2026-03-24"
    ];

    $res = fathomRequest($url, $params, $API_KEY);

    if ($res["status"] == 200 && isset($res["data"][0])) {
        echo "Bezoekers: " . $res["data"][0]["visits"] . "\n";
        echo "Pageviews: " . $res["data"][0]["pageviews"] . "\n";
    } else {
        echo "Fout bij bezoeken:\n";
        print_r($res["raw"]);
    }
}

// ==========================
// 3. Populaire pagina’s
// ==========================
function populaire_paginas($SITE_ID, $API_KEY) {
    $url = "https://api.usefathom.com/v1/aggregations";

    $params = [
        "entity" => "pageview",
        "entity_id" => $SITE_ID,
        "aggregates" => "visits,pageviews",
        "field_grouping" => "pathname",
        "sort_by" => "pageviews:desc",
        "limit" => 5,
        "date_from" => "2026-03-01",
        "date_to" => "2026-03-24"
    ];

    $res = fathomRequest($url, $params, $API_KEY);

    if ($res["status"] == 200) {
        foreach ($res["data"] as $i => $pagina) {
            echo ($i + 1) . ". " . $pagina["pathname"] . " — " . $pagina["pageviews"] . " views\n";
        }
    } else {
        echo "Fout bij populaire pagina’s:\n";
        print_r($res["raw"]);
    }
}

// ==========================
// 4. Verwijzende sites
// ==========================
function sites_bezoeken($SITE_ID, $API_KEY) {
    $url = "https://api.usefathom.com/v1/aggregations";

    $params = [
        "entity" => "pageview",
        "entity_id" => $SITE_ID,
        "aggregates" => "visits,pageviews",
        "field_grouping" => "referrer_hostname",
        "sort_by" => "visits:desc",
        "date_from" => "2026-03-01 00:00:00",
        "date_to" => "2026-03-24 23:59:59",
        "limit" => 50
    ];

    $res = fathomRequest($url, $params, $API_KEY);

    echo "Status: " . $res["status"] . "\n";

    if ($res["status"] == 200) {
        foreach ($res["data"] as $site) {
            echo $site["referrer_hostname"] . " — "
                . $site["visits"] . " visits / "
                . $site["pageviews"] . " views\n";
        }
    } else {
        echo "Fout bij verwijzende sites:\n";
        print_r($res["raw"]);
    }
}

// ==========================
// MAIN
// ==========================
echo "=== SITE INFO ===\n";
site_info($SITE_ID, $API_KEY);

echo "\n=== BEZOEKEN DEZE MAAND ===\n";
bezoeken_deze_maand($SITE_ID, $API_KEY);

echo "\n=== POPULAIRE PAGINA'S ===\n";
populaire_paginas($SITE_ID, $API_KEY);

echo "\n=== VERWIJZENDE SITES ===\n";
sites_bezoeken($SITE_ID, $API_KEY);