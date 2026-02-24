<?php
header('Content-Type: application/json');

$url = 'https://api.apify.com/v2/datasets/dWkDSGMfA4PiClc7b/items?clean=true&format=json';

$response = file_get_contents($url);
if ($response === false) {
    echo json_encode(['error' => 'Failed to connect to Apify']);
    exit;
}

$data = json_decode($response, true);
$results = [];

foreach ($data as $item) {
    $results[] = [
        'name' => $item['title'] ?? '',
        'address' => $item['address'] ?? '',
        'rating' => $item['totalScore'] ?? '',
        'reviews' => $item['reviewsCount'] ?? '',
        'phone' => $item['phone'] ?? '',
        'url' => $item['url'] ?? '',
        'location' => $item['location'] ?? null
    ];    
}

echo json_encode($results);