<?php
/**
 * Config met automatische environment detectie
 * Detecteert lokaal vs productie op basis van hostname
 */

// Detecteer environment
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1'])
        || strpos($host, 'localhost:') === 0
        || strpos($host, '192.168.') === 0;

// Basis config (gedeeld)
$config = [
    'secret' => 'jouw-geheime-sleutel-hier',

    'tiktok' => [
        'client_key' => 'sbawuymthutwnltywk',
        'client_secret' => 'Gj3r1gpO1qP8dct7oVTaCZyDbmUxXPGM',
        // Redirect URI will be auto-detected based on environment
        'redirect_uri' => '',
    ],
];

if ($isLocal) {
    // === LOKALE SETTINGS ===
    $config['db'] = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'social_media_analytics',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ];
    $config['cors_origin'] = '*';
    $config['environment'] = 'local';

} else {
    // === PRODUCTIE SETTINGS ===
    $config['db'] = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'g-bit_socialbit',
        'user' => 'g-bit_socialbit',
        'pass' => 'MiNiMiN1L5uv5n!',
        'charset' => 'utf8mb4',
    ];
    $config['cors_origin'] = 'https://socialbit.g-bit.be';
    $config['environment'] = 'production';
}

return $config;
