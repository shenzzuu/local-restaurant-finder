<?php
// Load local config if it exists (for local development)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

header('Content-Type: application/json');

// Get API key from environment variable (Render) or local config constant
$apiKey = getenv('GOOGLE_PLACES_API_KEY');
if (!$apiKey && defined('GOOGLE_PLACES_API_KEY')) {
    $apiKey = GOOGLE_PLACES_API_KEY;
}

if (!$apiKey) {
    echo json_encode(['error' => 'API Key is missing from environment variables']);
    exit;
}
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

$url = 'https://places.googleapis.com/v1/places:searchText';

$requestData = [
    'textQuery' => $query !== '' ? $query : 'restaurants near me'
];

if ($lat !== null && $lng !== null) {
    $requestData['locationBias'] = [
        'circle' => [
            'center' => [
                'latitude' => $lat,
                'longitude' => $lng
            ],
            'radius' => 5000.0 // 5km
        ]
    ];
}

$headers = [
    'Content-Type: application/json',
    'X-Goog-Api-Key: ' . $apiKey,
    'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.rating,places.userRatingCount,places.websiteUri,places.nationalPhoneNumber,places.googleMapsUri,places.location'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'Unable to fetch Google Places data', 'curl_error' => $error, 'details' => json_decode($response)]);
    exit;
}

$data = json_decode($response, true);
$results = [];

if (isset($data['places']) && is_array($data['places'])) {
    foreach ($data['places'] as $place) {
        $results[] = [
            'name' => $place['displayName']['text'] ?? 'Unnamed Restaurant',
            'address' => $place['formattedAddress'] ?? '',
            'phone' => $place['nationalPhoneNumber'] ?? '',
            'rating' => $place['rating'] ?? '',
            'reviews' => $place['userRatingCount'] ?? '',
            'website' => $place['websiteUri'] ?? '',
            'url' => $place['googleMapsUri'] ?? '',
            'location' => [
                'lat' => $place['location']['latitude'] ?? null,
                'lng' => $place['location']['longitude'] ?? null
            ]
        ];
    }
}

echo json_encode($results);