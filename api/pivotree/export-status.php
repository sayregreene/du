<?php
header('Content-Type: application/json');

// Get job ID
$jobId = $_GET['jobId'] ?? null;
if (!$jobId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit;
}

// Check if job should be cancelled
$cancel = isset($_GET['cancel']) && $_GET['cancel'] === 'true';

// Get job data
$jobsDir = __DIR__ . '/../../data/export-jobs';
$jobDataFile = "$jobsDir/$jobId.json";

if (!file_exists($jobDataFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Export job not found']);
    exit;
}

$jobData = json_decode(file_get_contents($jobDataFile), true);

// Handle cancellation request
if ($cancel) {
    $jobData['status'] = 'cancelled';
    file_put_contents($jobDataFile, json_encode($jobData));
    echo json_encode([
        'success' => true,
        'message' => 'Export job cancelled',
        'state' => 'cancelled'
    ]);
    exit;
}

// Map job status to state for client
$stateMap = [
    'pending' => 'pending',
    'processing' => 'processing',
    'completed' => 'completed',
    'failed' => 'failed',
    'cancelled' => 'cancelled'
];

$state = $stateMap[$jobData['status']] ?? 'processing';

// Prepare response data
$response = [
    'success' => true,
    'state' => $state,
    'progress' => $jobData['progress'] ?? 0,
    'processed' => $jobData['processed'] ?? 0,
    'total' => $jobData['total'] ?? 0,
];

// Add appropriate message based on state
switch ($state) {
    case 'pending':
        $response['message'] = 'Export job is queued and waiting to start...';
        break;
    case 'processing':
        $progress = round(($jobData['progress'] ?? 0) * 100);
        $response['message'] = "Exporting products ($progress%)...";
        break;
    case 'completed':
        $response['message'] = 'Export completed successfully!';
        $response['downloadUrl'] = "/du/api/pivotree/export-download.php?jobId=$jobId";
        break;
    case 'failed':
        $response['message'] = 'Export failed: ' . ($jobData['error'] ?? 'Unknown error');
        break;
    case 'cancelled':
        $response['message'] = 'Export was cancelled';
        break;
}

echo json_encode($response);