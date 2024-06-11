<?php

declare(strict_types=1);

require 'config.php';
require 'logger.php';

header('Content-Type: application/json; charset=utf-8');

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

function validateCsvHeader(array $header, array $expectedHeader): bool {
    return $header === $expectedHeader;
}

function importCsvData(PDO $pdo, string $csvFilePath): void {

    $csvData = array_map('str_getcsv', file($csvFilePath));
    $header = array_shift($csvData);
    $expectedHeader = ['Yogibogeybox', 'Materials used by a spiritualist', 'yojibojeboks'];

    if (!validateCsvHeader($header, $expectedHeader)) {
        logError("CSV format is incorrect. Expected columns: " . implode(", ", $expectedHeader));
        http_response_code(400);
        echo json_encode(['error' => 'CSV format is incorrect']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO words (word, definition, pronunciation) VALUES (:word, :definition, :pronunciation)");

    foreach ($csvData as $row) {
        if (count($row) !== 3) {
            logError("Skipping invalid row: " . implode(", ", $row));
            continue;
        }

        $word = trim($row[0]);
        $definition = trim($row[1]);
        $pronunciation = trim($row[2]);

        if (empty($word) || empty($definition) || empty($pronunciation)) {
            logError("Skipping row with empty fields: " . implode(", ", $row));
            continue;
        }

        try {
            $stmt->execute([
                ':word' => $word,
                ':definition' => $definition,
                ':pronunciation' => $pronunciation
            ]);
        } catch (PDOException $e) {
            logError('Failed to insert row: ' . implode(", ", $row) . ' Error: ' . $e->getMessage());
        }
    }

    echo json_encode(['message' => 'Data imported successfully']);
}

$config = [
    'dsn' => $dsn,
    'user' => $user,
    'pass' => $pass,
    'options' => $options,
];

$csvFilePath = 'RandomwordsData.csv';
$pdo = getPdoConnection($config);
importCsvData($pdo, $csvFilePath);

?>