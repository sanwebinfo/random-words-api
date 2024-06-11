<?php

function logError(string $message): void {
    $logFile = 'error_log.txt';
    $currentTime = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$currentTime] $message\n", FILE_APPEND);
}

?>