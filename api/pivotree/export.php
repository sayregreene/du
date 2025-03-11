<?php
header('Content-Type: application/json');

// Get the request data
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate request
if (!isset($requestData['format'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Export format is required']);
    exit;
}

// Create a unique job ID
$jobId = uniqid('export_', true);

// Store job details
$jobData = [
    'id' => $jobId,
    'format' => $requestData['format'],
    'exportAll' => $requestData['exportAll'] ?? true,
    'productIds' => $requestData['productIds'] ?? [],
    'createdAt' => date('Y-m-d H:i:s'),
    'status' => 'pending',
    'progress' => 0,
    'processed' => 0,
    'total' => 0,
];

// Save job data to a file or database
$jobsDir = __DIR__ . '/../../data/export-jobs';
if (!file_exists($jobsDir)) {
    mkdir($jobsDir, 0755, true);
}
file_put_contents("$jobsDir/$jobId.json", json_encode($jobData));

// Start the export process asynchronously
// In a production environment, you would use a proper job queue system
// Here we're using a simple approach by spawning a background process
$phpPath = '/usr/bin/php'; // Adjust based on your server configuration
$scriptPath = __DIR__ . '/process-export.php';
$command = "nohup $phpPath $scriptPath $jobId > /dev/null 2>&1 &";
exec($command);

// Return the job ID to the client
echo json_encode([
    'success' => true,
    'jobId' => $jobId,
    'message' => 'Export job started'
]);
