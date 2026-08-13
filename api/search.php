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
    $latOffset = 5.0 / 111.32;
    $lngOffset = 5.0 / (111.32 * cos(deg2rad($lat)));
    
    $requestData['locationRestriction'] = [
        'rectangle' => [
            'low' => [
                'latitude' => $lat - $latOffset,
                'longitude' => $lng - $lngOffset
            ],
            'high' => [
                'latitude' => $lat + $latOffset,
                'longitude' => $lng + $lngOffset
            ]
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
        $placeLat = $place['location']['latitude'] ?? null;
        $placeLng = $place['location']['longitude'] ?? null;
        
        // Filter out places that are too far if lat/lng are provided
        if ($lat !== null && $lng !== null && $placeLat !== null && $placeLng !== null) {
            $earthRadius = 6371; // km
            $dLat = deg2rad($placeLat - $lat);
            $dLng = deg2rad($placeLng - $lng);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat)) * cos(deg2rad($placeLat)) * sin($dLng/2) * sin($dLng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earthRadius * $c;
            
            if ($distance > 10.0) { // Keep within 10km max
                continue; 
            }
        }

        $results[] = [
            'name' => $place['displayName']['text'] ?? 'Unnamed Restaurant',
            'address' => $place['formattedAddress'] ?? '',
            'phone' => $place['nationalPhoneNumber'] ?? '',
            'rating' => $place['rating'] ?? '',
            'reviews' => $place['userRatingCount'] ?? '',
            'website' => $place['websiteUri'] ?? '',
            'url' => $place['googleMapsUri'] ?? '',
            'location' => [
                'lat' => $placeLat,
                'lng' => $placeLng
            ]
        ];
    }
}

echo json_encode(array_values($results));