<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/config.php';

define('CACHE_FILE', __DIR__ . '/history_cache.json');
define('CACHE_TTL',  3600); // 1 hour — historical data doesn't change

if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
    $raw = file_get_contents(CACHE_FILE);
} else {
    $url = sprintf(
        'https://api.weather.com/v2/pws/observations/hourly/7day?stationId=%s&format=json&units=e&apiKey=%s',
        STATION_ID,
        API_KEY
    );
    $raw = @file_get_contents($url);
    if ($raw === false) {
        http_response_code(503);
        echo json_encode(['error' => 'Failed to fetch history data']);
        exit;
    }
    file_put_contents(CACHE_FILE, $raw);
}

$json         = json_decode($raw, true);
$observations = $json['observations'] ?? [];

// most recent 24 hours, oldest first
$recent = array_slice($observations, -24);

$data = array_map(function($obs) {
    return [
        'time' => $obs['obsTimeLocal'],
        'temp' => $obs['imperial']['tempAvg'],
        'high' => $obs['imperial']['tempHigh'],
        'low'  => $obs['imperial']['tempLow'],
    ];
}, $recent);

echo json_encode($data);
