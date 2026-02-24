<?php
header('Content-Type: application/json');

$lat = $_GET['lat'] ?? '';
$lng = $_GET['lng'] ?? '';

if (!$lat || !$lng) {
    echo json_encode(['error' => 'Missing lat or lng']);
    exit;
}

$url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lng&current=temperature_2m,weathercode,windspeed_10m&timezone=Asia%2FSingapore";

$response = file_get_contents($url);

if (!$response) {
    echo json_encode(['error' => 'Failed to fetch weather']);
    exit;
}

$data = json_decode($response, true);
$current = $data['current'] ?? [];

$weatherCodes = [
    0 => "Clear", 1 => "Mainly clear", 2 => "Partly cloudy", 3 => "Overcast",
    45 => "Fog", 48 => "Rime fog", 51 => "Light drizzle", 61 => "Light rain",
    63 => "Moderate rain", 65 => "Heavy rain", 80 => "Showers", 95 => "Thunderstorm"
];

$code = $current['weathercode'] ?? null;
$condition = $weatherCodes[$code] ?? "Unknown";

echo json_encode([
    "temperature" => $current['temperature_2m'] ?? null,
    "wind_speed" => $current['windspeed_10m'] ?? null,
    "condition" => $condition
]);