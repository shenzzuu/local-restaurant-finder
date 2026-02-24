<?php
header('Content-Type: application/json');

$query = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$datasetUrl = 'https://api.apify.com/v2/datasets/dWkDSGMfA4PiClc7b/items?clean=true&format=json';

$response = file_get_contents($datasetUrl);

if (!$response) {
    echo json_encode(['error' => 'Unable to fetch Apify data']);
    exit;
}

$data = json_decode($response, true);
$results = [];

foreach ($data as $item) {
    if (!isset($item['title'])) continue;

    $name = strtolower($item['title']);
    if ($query === '' || strpos($name, $query) !== false) {
        $results[] = [
            'name' => $item['title'] ?? '',
            'address' => $item['address'] ?? '',
            'phone' => $item['phone'] ?? '',
            'rating' => $item['totalScore'] ?? '',
            'reviews' => $item['reviewsCount'] ?? '',
            'website' => $item['website'] ?? '',
            'url' => $item['url'] ?? '',
            'location' => $item['location'] ?? null
        ];        
    }
}

echo json_encode($results);