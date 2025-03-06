<?php
require_once 'db.php'; // Include database connection

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Simulates sending progress updates.
 * Replace this with your actual sync logic.
 */
function sendProgress($message, $percentage) {
    echo "data: {\"message\": \"$message\", \"percentage\": $percentage}\n\n";
    ob_flush();
    flush();
}

// Simulate sync process
$steps = 10; // Total number of steps
for ($i = 1; $i <= $steps; $i++) {
    // Simulate processing time
    sleep(1);

    // Calculate progress
    $percentage = intval(($i / $steps) * 100);

    // Send progress update
    sendProgress("Processing step $i of $steps...", $percentage);
}

sendProgress("Sync complete!", 100);
?>
