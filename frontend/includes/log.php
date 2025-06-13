<?php
// frontend/includes/log.php
function logToFile($message) {
    $file = __DIR__ . '/../../logs/store_debug.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($file, "[$date] $message\n", FILE_APPEND);
}
