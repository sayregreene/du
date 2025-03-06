<?php
// /var/www/html/du/api/pivotree/attribute-comparison.php
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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$uniqueAttributes = isset($_GET['unique_attributes']) && $_GET['unique_attributes'] === 'true';
$allAttributes = isset($_GET['all_attributes']) && $_GET['all_attributes'] === 'true';

// Validate inputs
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 50;
$offset = ($page - 1) * $limit;

// Prepare the response
$response = [
    'page' => $page,
    'limit' => $limit,
    'total' => 0,
    'attributes' => [],
    'akeneo' => [
        'attributes' => [],
    ],
    'mappings' => [
        'attributes' => [],
        'values' => []
    ]
];

$mappingStatus = isset($_GET['mapping_status']) ? $_GET['mapping_status'] : 'all';

// Then replace your existing query building section with:
try {
    // Build the query for attributes based on whether we want unique attributes or not
    if ($uniqueAttributes) {
        // Start with a base query that selects all attributes
        $baseQuery = "
            SELECT 
                ppa.attribute_name,
                COUNT(DISTINCT ppa.attribute_value) as value_count,
                COUNT(DISTINCT ppa.pvt_sku_id) as product_count
            FROM 
                pivotree_product_attributes ppa
        ";
        
        // Apply filtering logic based on mapping_status
        if ($mappingStatus !== 'all') {
            // For 'mapped' or 'unmapped' status, we need to join with attribute_mappings
            if ($mappingStatus === 'mapped') {
                // Get only attributes that have a mapping
                $baseQuery = "
                    SELECT 
                        ppa.attribute_name,
                        COUNT(DISTINCT ppa.attribute_value) as value_count,
                        COUNT(DISTINCT ppa.pvt_sku_id) as product_count
                    FROM 
                        pivotree_product_attributes ppa
                    INNER JOIN
                        attribute_mappings am ON ppa.attribute_name = am.pivotree_attribute_name
                ";
            } else if ($mappingStatus === 'unmapped') {
                // Get only attributes that don't have a mapping
                $baseQuery = "
                    SELECT 
                        ppa.attribute_name,
                        COUNT(DISTINCT ppa.attribute_value) as value_count,
                        COUNT(DISTINCT ppa.pvt_sku_id) as product_count
                    FROM 
                        pivotree_product_attributes ppa
                    LEFT JOIN
                        attribute_mappings am ON ppa.attribute_name = am.pivotree_attribute_name
                    WHERE
                        am.id IS NULL
                ";
            }
        }
        
        // Complete the query with additional conditions and grouping
        $query = $baseQuery;
        
        $params = [];
        
        // Add WHERE clause if we need to filter by search
        if (!empty($search)) {
            if (strpos($query, 'WHERE') !== false) {
                $query .= " AND ppa.attribute_name LIKE :search";
            } else {
                $query .= " WHERE ppa.attribute_name LIKE :search";
            }
            $params['search'] = "%$search%";
        }
        
        // Add GROUP BY, ORDER BY, and LIMIT clauses
        $query .= " GROUP BY ppa.attribute_name";
        $query .= " ORDER BY ppa.attribute_name";
        
        // Build a similar query for counting total records
        $countQuery = str_replace(
            "SELECT 
                ppa.attribute_name,
                COUNT(DISTINCT ppa.attribute_value) as value_count,
                COUNT(DISTINCT ppa.pvt_sku_id) as product_count",
            "SELECT COUNT(DISTINCT ppa.attribute_name) as total",
            $baseQuery
        );
        
        // Add the same WHERE clause to the count query
        if (!empty($search)) {
            if (strpos($countQuery, 'WHERE') !== false) {
                $countQuery .= " AND ppa.attribute_name LIKE :search";
            } else {
                $countQuery .= " WHERE ppa.attribute_name LIKE :search";
            }
        }
    } else {
        // Original query for attribute + value pairs, with similar modifications
        $baseQuery = "
            SELECT 
                ppa.attribute_name,
                ppa.attribute_value,
                ppa.uom,
                COUNT(DISTINCT ppa.pvt_sku_id) as product_count
            FROM 
                pivotree_product_attributes ppa
        ";
        
        // Apply filtering logic based on mapping_status
        if ($mappingStatus !== 'all') {
            if ($mappingStatus === 'mapped') {
                $baseQuery = "
                    SELECT 
                        ppa.attribute_name,
                        ppa.attribute_value,
                        ppa.uom,
                        COUNT(DISTINCT ppa.pvt_sku_id) as product_count
                    FROM 
                        pivotree_product_attributes ppa
                    INNER JOIN
                        attribute_mappings am ON ppa.attribute_name = am.pivotree_attribute_name
                ";
            } else if ($mappingStatus === 'unmapped') {
                $baseQuery = "
                    SELECT 
                        ppa.attribute_name,
                        ppa.attribute_value,
                        ppa.uom,
                        COUNT(DISTINCT ppa.pvt_sku_id) as product_count
                    FROM 
                        pivotree_product_attributes ppa
                    LEFT JOIN
                        attribute_mappings am ON ppa.attribute_name = am.pivotree_attribute_name
                    WHERE
                        am.id IS NULL
                ";
            }
        }
        
        $query = $baseQuery;
        $params = [];
        
        // Add search filter if provided
        if (!empty($search)) {
            if (strpos($query, 'WHERE') !== false) {
                $query .= " AND (ppa.attribute_name LIKE :search OR ppa.attribute_value LIKE :search)";
            } else {
                $query .= " WHERE (ppa.attribute_name LIKE :search OR ppa.attribute_value LIKE :search)";
            }
            $params['search'] = "%$search%";
        }
        
        // Group and order by
        $query .= " GROUP BY ppa.attribute_name, ppa.attribute_value, ppa.uom";
        $query .= " ORDER BY ppa.attribute_name, ppa.attribute_value";
        
        // Count total results - similar adjustment needed here
        $countQuery = "SELECT COUNT(*) FROM (
            SELECT DISTINCT ppa.attribute_name, ppa.attribute_value, ppa.uom
            FROM " . substr($baseQuery, strpos($baseQuery, 'FROM') + 5);

        // Make sure the WHERE clause is added properly
        if (!empty($search)) {
            if (strpos($countQuery, 'WHERE') !== false) {
                $countQuery .= " AND (ppa.attribute_name LIKE :search OR ppa.attribute_value LIKE :search)";
            } else {
                $countQuery .= " WHERE (ppa.attribute_name LIKE :search OR ppa.attribute_value LIKE :search)";
            }
        }
        
        $countQuery .= ") AS count_table";
    }
    
    // For debugging
    error_log("Query: $query");
    error_log("Params: " . json_encode($params));
    
    // Execute count query
    $stmtCount = $pdo->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $response['total'] = $stmtCount->fetchColumn();
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    
    // Execute the main query
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $response['attributes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Now try to fetch mapping data if the tables exist
    try {
        // Check if attribute_mappings table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'attribute_mappings'");
        if ($tableCheck->rowCount() > 0) {
            // Fetch existing attribute mappings
            $stmtAttrMappings = $pdo->prepare("
                SELECT * FROM attribute_mappings
            ");
            $stmtAttrMappings->execute();
            $response['mappings']['attributes'] = $stmtAttrMappings->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch a subset of value mappings (optional, for context)
            // You might want to limit this to avoid too much data transfer
            $stmtValueMappings = $pdo->prepare("
                SELECT * FROM attribute_value_mappings
            ");
            $stmtValueMappings->execute();
            $response['mappings']['values'] = $stmtValueMappings->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Tables don't exist yet, but we already have empty arrays in the response
            error_log('Mapping tables do not exist yet');
        }
    } catch (PDOException $e) {
        // If there's an error, just continue - we already have empty arrays in the response
        error_log('Error fetching mapping data: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Fetch attributes from Akeneo API - Optimized with caching
 */
function fetchAkeneoAttributes($allAttributes = false) {
    static $cachedAttributes = null;
    
    // If we've already fetched all attributes, use the cache
    if ($cachedAttributes !== null) {
        return $allAttributes ? $cachedAttributes : array_slice($cachedAttributes, 0, 100);
    }
    
    // Make request to the Akeneo attributes API using HTTP
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $apiUrl = "$protocol://$host/du/api/akeneo/attributes.php?mock=false";
    
    // Add parameter for all attributes if requested
    if ($allAttributes) {
        $apiUrl .= "&all_attributes=true";
    }
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception('Failed to get Akeneo attributes: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        if (!isset($data['attributes'])) {
            throw new Exception('Invalid Akeneo attributes response');
        }
        
        // Store in cache
        $cachedAttributes = $data['attributes'];
        
        return $cachedAttributes;
    } catch (Exception $e) {
        // If API request fails, return empty array
        error_log('Error fetching Akeneo attributes: ' . $e->getMessage());
        return [];
    }
}