<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('X-Robots-Tag: noindex, nofollow', true);

require 'config.php';
require 'logger.php';

if (!extension_loaded('redis')) {
    logError('Redis extension is not installed.');
    http_response_code(500);
    echo json_encode(['error' => 'Redis extension is not installed']);
    exit;
}

// Function to clear Redis cache for words data
function clearWordsCache(): bool {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379); // Update with your Redis server details
    $keys = $redis->keys('random_words:*'); // Get all keys related to words data cache
    foreach ($keys as $key) {
        $redis->del($key); // Delete each key
    }
    return true;
}

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Call the function to clear words data cache
    if (clearWordsCache()) {
        echo json_encode(['success' => 'Words data cache cleared successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to clear words data cache']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

?>