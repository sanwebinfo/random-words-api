<?php

declare(strict_types=1);

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=63072000');
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

// Custom prefix for Redis cache keys
$cachePrefix = 'random_words:';

function getPdoConnection(array $config): PDO {
    try {
        return new PDO($config['dsn'], $config['user'], $config['pass'], $config['options']);
    } catch (PDOException $e) {
        logError('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
        exit;
    }
}

function fetchRandomWord(PDO $pdo, Redis $redis, string $cachePrefix): array {
    try {
        if (!$redis->isConnected()) {
            logError('Redis server is not connected.');
            http_response_code(500);
            return ['error' => 'Redis server is not connected'];
        }

        // Check if all words exist in Redis
        $allWords = $redis->sMembers($cachePrefix . 'all_words');
        if (empty($allWords)) {
            // If no words found in Redis, fetch all words from the database and store them in Redis
            $stmt = $pdo->query('SELECT * FROM words');
            $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $redis->sAdd($cachePrefix . 'all_words', ...array_map('json_encode', $words)); // Store all words in a Redis set
            $allWords = $redis->sMembers($cachePrefix . 'all_words');
        }

        if (!empty($allWords)) {
            // If words found in Redis, pick a random word
            $randomWord = $redis->sRandMember($cachePrefix . 'all_words');
            return [json_decode($randomWord, true)];
        } else {
            http_response_code(404);
            return [['word' => 'Not Found', 'definition' => 'Not Found', 'pronunciation' => 'Not Found']];
        }
    } catch (Exception $e) {
        logError('Failed to retrieve random word: ' . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Internal Server Error'];
    }
}

$config = [
    'dsn' => $dsn,
    'user' => $user,
    'pass' => $pass,
    'options' => $options,
];

$pdo = getPdoConnection($config);

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$response = fetchRandomWord($pdo, $redis, $cachePrefix);
echo json_encode($response);

?>