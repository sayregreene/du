<?php
// /var/www/html/du/api/pivotree/attribute-values.php
header('Content-Type: application/json');

// Include the configuration file
require_once '../../config.php';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get query parameters
$attributeName = isset($_GET['attribute_name']) ? $_GET['attribute_name'] : '';

if (empty($attributeName)) {
    http_response_code(400);
    echo json_encode(['error' => 'attribute_name parameter is required']);
    exit;
}

// Prepare the response
$response = [
    'attribute_name' => $attributeName,
    'values' => []
];

try {
    // Get all unique values for this attribute
    $query = "
        SELECT 
            attribute_value,
            uom,
            COUNT(DISTINCT pvt_sku_id) as product_count
        FROM 
            pivotree_product_attributes
        WHERE 
            attribute_name = :attribute_name
        GROUP BY 
            attribute_value, uom
        ORDER BY 
            attribute_value
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':attribute_name', $attributeName);
    $stmt->execute();
    
    $response['values'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing value mappings if they exist
    try {
        // Check if attribute_value_mappings table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'attribute_value_mappings'");
        if ($tableCheck->rowCount() > 0) {
            $stmtMappings = $pdo->prepare("
                SELECT * FROM attribute_value_mappings
                WHERE pivotree_attribute_name = :attribute_name
            ");
            $stmtMappings->bindValue(':attribute_name', $attributeName);
            $stmtMappings->execute();
            
            $response['mappings'] = $stmtMappings->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $response['mappings'] = [];
        }
    } catch (PDOException $e) {
        // If there's an error, just continue with empty mappings
        $response['mappings'] = [];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}