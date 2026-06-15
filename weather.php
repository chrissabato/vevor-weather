<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/config.php';

define('CACHE_FILE', __DIR__ . '/weather_cache.json');
define('CACHE_TTL',  300); // seconds — WU updates every ~5 min

if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
    $raw = file_get_contents(CACHE_FILE);
} else {
    $url = sprintf(
        'https://api.weather.com/v2/pws/observations/current?stationId=%s&format=json&units=e&apiKey=%s',
        STATION_ID,
        API_KEY
    );
    $raw = @file_get_contents($url);
    if ($raw === false) {
        http_response_code(503);
        echo json_encode(['error' => 'Failed to fetch weather data']);
        exit;
    }
    file_put_contents(CACHE_FILE, $raw);
}

$json = json_decode($raw, true);
$obs  = $json['observations'][0];
$imp  = $obs['imperial'];

$data = [
    'station_id'       => $obs['stationID'],
    'location'         => STATION_LOCATION,
    'time_utc'         => $obs['obsTimeUtc'],
    'time_local'       => $obs['obsTimeLocal'],
    'temp_f'           => $imp['temp'],
    'heat_index_f'     => $imp['heatIndex'],
    'wind_chill_f'     => $imp['windChill'],
    'dew_point_f'      => $imp['dewpt'],
    'humidity'         => $obs['humidity'],
    'wind_speed_mph'   => $imp['windSpeed'],
    'wind_gust_mph'    => $imp['windGust'],
    'wind_dir_deg'     => $obs['winddir'],
    'pressure_inhg'    => $imp['pressure'],
    'precip_rate_in'   => $imp['precipRate'],
    'precip_total_in'  => $imp['precipTotal'],
    'uv_index'         => $obs['uv'],
    'solar_radiation'  => $obs['solarRadiation'],
];

echo json_encode([$data]);
