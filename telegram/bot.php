<?php

declare(strict_types=1);

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=63072000');
header('X-Robots-Tag: noindex, nofollow', true);

$botToken = '<Bot Token>';
$apiUrl = "https://api.telegram.org/bot$botToken/";
$randomWordsApi = "<Random Words JSON API URL>";

function sendPushbulletNotification($accessToken, $deviceIden, $title, $message) {
  
    if(empty($accessToken) || empty($deviceIden) || empty($title) || empty($message)) {
        return;
    }

    $accessToken = htmlspecialchars($accessToken, ENT_QUOTES, 'UTF-8');
    $deviceIden = htmlspecialchars($deviceIden, ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $url = 'https://api.pushbullet.com/v2/pushes';
    
    $data = [
        'type' => 'note',
        'device_iden' => $deviceIden,
        'title' => $title,
        'body' => $message
    ];

    $headers = [
        'Access-Token: ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function sendMessage(int $chatId, string $text): ?array
{
    global $apiUrl;

    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . "sendMessage",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Failed to send message: HTTP $httpCode - Response: $response");
        return null;
    }

    $decodedResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error after sending message: " . json_last_error_msg());
        return null;
    }

    return $decodedResponse;
}

function getRandomWord(): ?array
{
    global $randomWordsApi;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $randomWordsApi,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ]);

    $wordData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Failed to get random word from the API: HTTP $httpCode - Response: $wordData");
        return null;
    }

    $decodedData = json_decode($wordData, true);
    if ($decodedData === null) {
        error_log("Failed to decode JSON data from the API: " . json_last_error_msg());
        return null;
    }

    if (!is_array($decodedData) || empty($decodedData)) {
        error_log("Invalid response format from the API");
        return null;
    }

    $wordItem = $decodedData[0] ?? null;
    if ($wordItem === null) {
        error_log("No word data found in the response");
        return null;
    }

    return $wordItem;
}

function sendWordData(int $chatId, string $word, string $definition, string $pronunciation): ?array
{
    $messageText = "✍️ Word: " . htmlspecialchars($word, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "\n\n📚 Definition: " . htmlspecialchars($definition, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "\n\n🗣 Pronunciation: " . htmlspecialchars($pronunciation, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return sendMessage($chatId, $messageText);
}

$request = file_get_contents("php://input");
if (!$request) {
    http_response_code(400);
    error_log("Bad Request: No request data");
    exit("Bad Request");
}

$update = json_decode($request, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    error_log("Invalid JSON: " . json_last_error_msg());
    exit("Invalid JSON");
}

if (!isset($update['message'], $update['message']['chat']['id'])) {
    http_response_code(400);
    error_log("Invalid request: Missing message or chat id");
    exit("Invalid request");
}

$chatId = (int)$update['message']['chat']['id'];
$username = $update["message"]["chat"]["first_name"];
$chattype = $update["message"]["chat"]["type"];
$message = $update['message'];

$accessToken = "<Pushbullet API Access Token>";
$title = "$username RW BOT";
$body = "Chat ID : $chatId\nuserType: $chattype";
$deviceIden = "<Pushbullet Device ID>";

if($chatId) {
   sendPushbulletNotification($accessToken, $deviceIden, $title, $body);
} else {
    http_response_code(200);
    error_log("Pushbullet Error Not working");
    exit("OK");
}

if (!isset($message['text'])) {
    sendMessage($chatId, "Warning: Only text messages are allowed. Please refrain from sending media, stickers, etc.");
    http_response_code(200);
    exit("OK");
}

$text = trim($message['text']);

if ($text === '/start' || $text === 'start') {
        sendMessage($chatId, "Hi, $username\nRandom Words with Definition and Pronunciation\nby calling the Below Commands\n/random or random");
} else if ($text === '/random' || $text === 'random') {
    $wordData = getRandomWord();
    if ($wordData !== null) {
        $word = $wordData['word'] ?? 'Unknown';
        $definition = $wordData['definition'] ?? 'Definition not available';
        $pronunciation = $wordData['pronunciation'] ?? 'Pronunciation not available';

        sendWordData($chatId, $word, $definition, $pronunciation);
    } else {
        sendMessage($chatId, "Sorry, I couldn't retrieve a random word at this time.");
    }
} else {
    sendMessage($chatId, "I received your text message: " . htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

http_response_code(200);
exit("OK");

?>