<?php

include 'store.php';

try {
    (new DevCoder\DotEnv(__DIR__ . '/.env'))->load();
} catch (InvalidArgumentException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Environment configuration file not found']);
    exit;
}

$host = getenv('DBHOST');
$db = getenv('DBNAME');
$user = getenv('DBUSER');
$pass = getenv('DBPASSWORD');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

?>