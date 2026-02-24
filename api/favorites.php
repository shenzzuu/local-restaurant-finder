<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Path to favorites.json (adjust if needed)
$favFile = '../data/favorites.json';
$method = $_SERVER['REQUEST_METHOD'];

// Load existing favorites
$favorites = file_exists($favFile) ? json_decode(file_get_contents($favFile), true) : [];

if (!is_array($favorites)) {
    $favorites = [];
}

// GET: Return favorites
if ($method === 'GET') {
    echo json_encode($favorites);
    exit;
}

// POST: Add new favorite
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (!is_array($input) || empty($input['name'])) {
        echo json_encode(['error' => 'Invalid favorite data']);
        exit;
    }

    // Check for duplicate
    foreach ($favorites as $fav) {
        if ($fav['name'] === $input['name']) {
            echo json_encode(['message' => 'Already in favorites']);
            exit;
        }
    }

    // Append and save
    $favorites[] = $input;
    if (file_put_contents($favFile, json_encode($favorites, JSON_PRETTY_PRINT))) {
        echo json_encode(['message' => 'Added to favorites']);
    } else {
        echo json_encode(['error' => 'Failed to save favorite']);
    }
    exit;
}

// DELETE: Remove by name
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';

    $favorites = array_filter($favorites, function ($item) use ($name) {
        return $item['name'] !== $name;
    });

    if (file_put_contents($favFile, json_encode(array_values($favorites), JSON_PRETTY_PRINT))) {
        echo json_encode(['message' => 'Removed from favorites']);
    } else {
        echo json_encode(['error' => 'Failed to remove favorite']);
    }
    exit;
}

// Unsupported method
echo json_encode(['error' => 'Unsupported request']);