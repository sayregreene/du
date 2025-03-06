<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'akeneodata';
$usernameDb = 'dm_db';
$passwordDb = 'Borealis5609!';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $usernameDb, $passwordDb, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->query("SELECT * FROM akeneo_sync_log ORDER BY sync_date DESC LIMIT 100");
    $rows = $stmt->fetchAll();
    echo json_encode($rows);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
}
