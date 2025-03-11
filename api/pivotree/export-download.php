<?php
// Get job ID
$jobId = $_GET['jobId'] ?? null;
if (!$jobId) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit;
}

// Get job data
$jobsDir = __DIR__ . '/../../data/export-jobs';
$jobDataFile = "$jobsDir/$jobId.json";

if (!file_exists($jobDataFile)) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Export job not found']);
    exit;
}

$jobData = json_decode(file_get_contents($jobDataFile), true);

// Check if job is completed and file exists
if ($jobData['status'] !== 'completed' || !isset($jobData['exportFile']) || !file_exists($jobData['exportFile'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Export file not ready or not found']);
    exit;
}

// Determine file type and name
$format = $jobData['format'];
$filename = "pivotree-products-export";

switch ($format) {
    case 'csv':
        $contentType = 'text/csv';
        $filename .= '.csv';
        break;
    case 'json':
        $contentType = 'application/json';
        $filename .= '.json';
        break;
    case 'excel':
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename .= '.xlsx';
        break;
    default:
        $contentType = 'application/octet-stream';
        $filename .= '.dat';
}

// Set headers for download
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($jobData['exportFile']));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($jobData['exportFile']);