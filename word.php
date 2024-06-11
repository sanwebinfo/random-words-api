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

function fetchRandomWord(PDO $pdo): array {
    try {
        $stmt = $pdo->query('SELECT * FROM words ORDER BY RAND() LIMIT 1');
        $word = $stmt->fetch();
        if ($word) {
            return [[
                'word' => htmlspecialchars($word['word'], ENT_QUOTES, 'UTF-8'),
                'definition' => htmlspecialchars($word['definition'], ENT_QUOTES, 'UTF-8'),
                'pronunciation' => htmlspecialchars($word['pronunciation'], ENT_QUOTES, 'UTF-8')
            ]];
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
$response = fetchRandomWord($pdo);

echo json_encode($response);

?>