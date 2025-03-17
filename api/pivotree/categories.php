<?php
// /var/www/html/du/api/pivotree/categories.php
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

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Route to appropriate handler based on action
switch ($action) {
    case 'getPivotreeCategories':
        getPivotreeCategories($pdo);
        break;
    case 'getAkeneoCategories':
        getAkeneoCategories($pdo);
        break;
    case 'getCategoryMappings':
        getCategoryMappings($pdo);
        break;
    case 'saveMapping':
        saveMapping($pdo);
        break;
    case 'deleteMapping':
        deleteMapping($pdo);
        break;
    case 'syncPivotreeCategories':
        syncPivotreeCategories($pdo);
        break;
    case 'syncAkeneoCategories':
        syncAkeneoCategories($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action parameter']);
        break;
}

/**
 * Retrieve Pivotree categories with pagination and search
 */
function getPivotreeCategories($pdo) {
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $mappingStatus = isset($_GET['mappingStatus']) ? $_GET['mappingStatus'] : 'all';
    
    // Validate inputs
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 50;
    $offset = ($page - 1) * $limit;
    
    try {
        // Build query for categories
        $baseQuery = "
            SELECT 
                pc.id,
                pc.category_name,
                pc.source,
                pc.product_count,
                CASE WHEN cm.id IS NOT NULL THEN 'mapped' ELSE 'unmapped' END as status
            FROM 
                pivotree_categories pc
            LEFT JOIN 
                category_mappings cm ON pc.id = cm.pivotree_category_id
        ";
        
        $countQuery = "
            SELECT 
                COUNT(*) 
            FROM 
                pivotree_categories pc
            LEFT JOIN 
                category_mappings cm ON pc.id = cm.pivotree_category_id
        ";
        
        $whereConditions = [];
        $params = [];
        
        // Add search filter if provided
        if (!empty($search)) {
            $whereConditions[] = "pc.category_name LIKE ?";
            $params[] = "%$search%";
        }
        
        // Add mapping status filter
        if ($mappingStatus !== 'all') {
            if ($mappingStatus === 'mapped') {
                $whereConditions[] = "cm.id IS NOT NULL";
            } else {
                $whereConditions[] = "cm.id IS NULL";
            }
        }
        
        // Add WHERE clause if needed
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            $baseQuery .= $whereClause;
            $countQuery .= $whereClause;
        }
        
        // Add ORDER BY and LIMIT clauses to main query
        $baseQuery .= " ORDER BY pc.category_name LIMIT ? OFFSET ?";
        
        // Get total count
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $index => $value) {
            $countStmt->bindValue($index + 1, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        // Get paginated results
        $stmt = $pdo->prepare($baseQuery);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add mapping details for mapped categories
        foreach ($categories as &$category) {
            if ($category['status'] === 'mapped') {
                $mappingStmt = $pdo->prepare("
                    SELECT 
                        cm.id as mapping_id,
                        ac.id as akeneo_id,
                        ac.category as akeneo_code,
                        ac.category_name as akeneo_name
                    FROM 
                        category_mappings cm
                    JOIN 
                        akeneo_categories ac ON cm.akeneo_category_id = ac.id
                    WHERE 
                        cm.pivotree_category_id = ?
                ");
                $mappingStmt->execute([$category['id']]);
                $mapping = $mappingStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mapping) {
                    $category['mapping'] = [
                        'id' => $mapping['mapping_id'],
                        'akeneo_id' => $mapping['akeneo_id'],
                        'akeneo_code' => $mapping['akeneo_code'],
                        'akeneo_name' => $mapping['akeneo_name']
                    ];
                }
            }
        }
        
        // Return response
        echo json_encode([
            'success' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'categories' => $categories
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Retrieve Akeneo categories with pagination and search
 */
function getAkeneoCategories($pdo) {
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $mappingStatus = isset($_GET['mappingStatus']) ? $_GET['mappingStatus'] : 'all';
    
    // Validate inputs
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 50;
    $offset = ($page - 1) * $limit;
    
    try {
        // Build query for categories based on existing table structure
        // Check structure of existing table first and adapt query accordingly
        // This example assumes we have at least id, category/code, and category_name/label columns
        
        // First, check if the structure matches what we expect
        $tableInfoStmt = $pdo->query("DESCRIBE akeneo_categories");
        $columns = $tableInfoStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine column names based on what exists
        $codeColumn = in_array('category', $columns) ? 'category' : 
                     (in_array('code', $columns) ? 'code' : 'id');
        
        $nameColumn = in_array('category_name', $columns) ? 'category_name' : 
                     (in_array('label', $columns) ? 'label' : $codeColumn);
        
        $parentColumn = in_array('parent_category_id', $columns) ? 'parent_category_id' : 
                       (in_array('parent_id', $columns) ? 'parent_id' : null);
        
        // Build query
        $baseQuery = "
            SELECT 
                ac.id,
                ac.$codeColumn as category,
                ac.$nameColumn as category_name";
                
        if ($parentColumn) {
            $baseQuery .= ", ac.$parentColumn as parent_category_id";
        } else {
            $baseQuery .= ", NULL as parent_category_id";
        }
                
        $baseQuery .= ", CASE WHEN cm.id IS NOT NULL THEN 'mapped' ELSE 'unmapped' END as status
            FROM 
                akeneo_categories ac
            LEFT JOIN 
                category_mappings cm ON ac.id = cm.akeneo_category_id";
        
        // Similar adjustments for count query and rest of the function
        $countQuery = "
            SELECT 
                COUNT(*) 
            FROM 
                akeneo_categories ac
            LEFT JOIN 
                category_mappings cm ON ac.id = cm.akeneo_category_id";
        
        // Add search filter
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(ac.$codeColumn LIKE ? OR ac.$nameColumn LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Add mapping status filter
        if ($mappingStatus !== 'all') {
            if ($mappingStatus === 'mapped') {
                $whereConditions[] = "cm.id IS NOT NULL";
            } else {
                $whereConditions[] = "cm.id IS NULL";
            }
        }
        
        // Add WHERE clause if needed
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            $baseQuery .= $whereClause;
            $countQuery .= $whereClause;
        }
        
        // Add ORDER BY and LIMIT clauses
        $baseQuery .= " ORDER BY ac.$nameColumn LIMIT ? OFFSET ?";
        
        // Get total count
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $index => $value) {
            $countStmt->bindValue($index + 1, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        // Get paginated results
        $stmt = $pdo->prepare($baseQuery);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add mapping details for mapped categories
        foreach ($categories as &$category) {
            if ($category['status'] === 'mapped') {
                $mappingStmt = $pdo->prepare("
                    SELECT 
                        cm.id as mapping_id,
                        pc.id as pivotree_id,
                        pc.category_name as pivotree_name
                    FROM 
                        category_mappings cm
                    JOIN 
                        pivotree_categories pc ON cm.pivotree_category_id = pc.id
                    WHERE 
                        cm.akeneo_category_id = ?
                ");
                $mappingStmt->execute([$category['id']]);
                $mapping = $mappingStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mapping) {
                    $category['mapping'] = [
                        'id' => $mapping['mapping_id'],
                        'pivotree_id' => $mapping['pivotree_id'],
                        'pivotree_name' => $mapping['pivotree_name']
                    ];
                }
            }
            
            // Add path information - if parent columns exist
            if ($parentColumn) {
                try {
                    $category['path'] = buildCategoryPath($pdo, $category['id'], [], 
                                                         $codeColumn, $nameColumn, $parentColumn);
                } catch (Exception $e) {
                    $category['path'] = '/' . $category['category_name'];
                }
            } else {
                $category['path'] = '/' . $category['category_name'];
            }
        }
        
        // Return response
        echo json_encode([
            'success' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'categories' => $categories
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}


/**
 * Recursive function to build category path
 */
function buildCategoryPath($pdo, $categoryId, $visited = [], $codeColumn, $nameColumn, $parentColumn) {
    // Prevent infinite recursion
    if (in_array($categoryId, $visited)) {
        return '';
    }
    $visited[] = $categoryId;
    
    // Get category info
    $stmt = $pdo->prepare("SELECT $nameColumn as category_name, $parentColumn as parent_category_id FROM akeneo_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        return '';
    }
    
    // Base case - no parent
    if (!$category['parent_category_id']) {
        return '/' . $category['category_name'];
    }
    
    // Recursive case - has parent
    $parentPath = buildCategoryPath($pdo, $category['parent_category_id'], $visited, 
                                   $codeColumn, $nameColumn, $parentColumn);
    return $parentPath . '/' . $category['category_name'];
}


/**
 * Retrieve category mappings with pagination and search
 */
function getCategoryMappings($pdo) {
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Validate inputs
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 50;
    $offset = ($page - 1) * $limit;
    
    try {
        // Build query for mappings
        $baseQuery = "
            SELECT 
                cm.id,
                pc.id as pivotree_id,
                pc.category_name as pivotree_name,
                pc.product_count,
                ac.id as akeneo_id,
                ac.category as akeneo_code,
                ac.category_name as akeneo_name,
                cm.created_at,
                cm.created_by
            FROM 
                category_mappings cm
            JOIN 
                pivotree_categories pc ON cm.pivotree_category_id = pc.id
            JOIN 
                akeneo_categories ac ON cm.akeneo_category_id = ac.id
        ";
        
        $countQuery = "
            SELECT 
                COUNT(*) 
            FROM 
                category_mappings cm
            JOIN 
                pivotree_categories pc ON cm.pivotree_category_id = pc.id
            JOIN 
                akeneo_categories ac ON cm.akeneo_category_id = ac.id
        ";
        
        $whereConditions = [];
        $params = [];
        
        // Add search filter if provided
        if (!empty($search)) {
            $whereConditions[] = "(pc.category_name LIKE ? OR ac.category LIKE ? OR ac.category_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Add WHERE clause if needed
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            $baseQuery .= $whereClause;
            $countQuery .= $whereClause;
        }
        
        // Add ORDER BY and LIMIT clauses to main query
        $baseQuery .= " ORDER BY cm.created_at DESC LIMIT ? OFFSET ?";
        
        // Get total count
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $index => $value) {
            $countStmt->bindValue($index + 1, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        // Get paginated results
        $stmt = $pdo->prepare($baseQuery);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return response
        echo json_encode([
            'success' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'mappings' => $mappings
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Save a category mapping
 */
function saveMapping($pdo) {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['pivotree_id']) || !isset($data['akeneo_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: pivotree_id and akeneo_id']);
        return;
    }
    
    $pivotreeId = $data['pivotree_id'];
    $akeneoId = $data['akeneo_id'];
    $createdBy = $data['created_by'] ?? 'system';
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if mapping already exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM category_mappings 
            WHERE pivotree_category_id = ? AND akeneo_category_id = ?
        ");
        $checkStmt->execute([$pivotreeId, $akeneoId]);
        $existingMapping = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingMapping) {
            // Mapping already exists
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Mapping already exists',
                'mapping_id' => $existingMapping['id']
            ]);
            return;
        }
        
        // Create new mapping
        $insertStmt = $pdo->prepare("
            INSERT INTO category_mappings (pivotree_category_id, akeneo_category_id, created_by)
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([$pivotreeId, $akeneoId, $createdBy]);
        
        $mappingId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Mapping created successfully',
            'mapping_id' => $mappingId
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Delete a category mapping
 */
function deleteMapping($pdo) {
    // Check if this is a DELETE request
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // Get mapping ID from URL
    $mappingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($mappingId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid mapping ID']);
        return;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete the mapping
        $deleteStmt = $pdo->prepare("DELETE FROM category_mappings WHERE id = ?");
        $deleteStmt->execute([$mappingId]);
        
        // Check if any rows were affected
        if ($deleteStmt->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Mapping not found']);
            return;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Mapping deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Sync Pivotree categories from pivotree_products.terminal_node
 */
function syncPivotreeCategories($pdo) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get distinct terminal_node values and their counts
        $stmt = $pdo->prepare("
            SELECT 
                terminal_node as category_name,
                COUNT(*) as product_count
            FROM 
                pivotree_products
            WHERE 
                terminal_node IS NOT NULL AND terminal_node != ''
            GROUP BY 
                terminal_node
            ORDER BY 
                terminal_node
        ");
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $inserted = 0;
        $updated = 0;
        
        foreach ($categories as $category) {
            // Check if category already exists
            $checkStmt = $pdo->prepare("
                SELECT id, product_count FROM pivotree_categories 
                WHERE category_name = ?
            ");
            $checkStmt->execute([$category['category_name']]);
            $existingCategory = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCategory) {
                // Category exists, update product count if different
                if ($existingCategory['product_count'] != $category['product_count']) {
                    $updateStmt = $pdo->prepare("
                        UPDATE pivotree_categories 
                        SET product_count = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$category['product_count'], $existingCategory['id']]);
                    $updated++;
                }
            } else {
                // Category doesn't exist, insert it
                $insertStmt = $pdo->prepare("
                    INSERT INTO pivotree_categories (category_name, source, product_count)
                    VALUES (?, 'terminal_node', ?)
                ");
                $insertStmt->execute([$category['category_name'], $category['product_count']]);
                $inserted++;
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "Sync completed: $inserted categories inserted, $updated categories updated.",
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => count($categories)
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Sync Akeneo categories from Akeneo API
 */
function syncAkeneoCategories($pdo) {
    try {
        // Get Akeneo API token
        require_once '../../config.php';
        $token = getAkeneoToken($baseUrl, $clientId, $clientSecret, $username, $password);
        
        if (!$token) {
            throw new Exception('Failed to get Akeneo API token');
        }
        
        // Fetch categories from Akeneo API
        $categories = fetchAkeneoCategories($baseUrl, $token);
        
        if (empty($categories)) {
            throw new Exception('No categories returned from Akeneo API');
        }
        
        // Determine table structure
        $tableInfoStmt = $pdo->query("DESCRIBE akeneo_categories");
        $columns = $tableInfoStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine column names based on what exists
        $codeColumn = in_array('category', $columns) ? 'category' : 
                     (in_array('code', $columns) ? 'code' : null);
        
        $nameColumn = in_array('category_name', $columns) ? 'category_name' : 
                     (in_array('label', $columns) ? 'label' : null);
        
        $parentColumn = in_array('parent_category_id', $columns) ? 'parent_category_id' : 
                       (in_array('parent_id', $columns) ? 'parent_id' : null);
        
        if (!$codeColumn || !$nameColumn) {
            throw new Exception('Cannot determine required column names in akeneo_categories table');
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        $inserted = 0;
        $updated = 0;
        $categoryCodeToId = [];
        
        // First pass: Insert/update categories
        foreach ($categories as $category) {
            // Check if category already exists
            $checkStmt = $pdo->prepare("
                SELECT id FROM akeneo_categories 
                WHERE $codeColumn = ?
            ");
            $checkStmt->execute([$category['code']]);
            $existingCategory = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCategory) {
                // Category exists, update name
                if ($nameColumn) {
                    $updateStmt = $pdo->prepare("
                        UPDATE akeneo_categories 
                        SET $nameColumn = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $category['labels']['en_US'] ?? $category['code'],
                        $existingCategory['id']
                    ]);
                    $updated++;
                }
                $categoryCodeToId[$category['code']] = $existingCategory['id'];
            } else {
                // Category doesn't exist, insert it
                // Build dynamic SQL based on available columns
                $fields = [$codeColumn, $nameColumn];
                $placeholders = ['?', '?'];
                $values = [
                    $category['code'],
                    $category['labels']['en_US'] ?? $category['code']
                ];
                
                if ($parentColumn) {
                    $fields[] = $parentColumn;
                    $placeholders[] = "NULL";  // Will update in second pass
                }
                
                $fieldsStr = implode(', ', $fields);
                $placeholdersStr = implode(', ', $placeholders);
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO akeneo_categories ($fieldsStr)
                    VALUES ($placeholdersStr)
                ");
                
                $insertStmt->execute($values);
                $inserted++;
                $categoryCodeToId[$category['code']] = $pdo->lastInsertId();
            }
        }
        
        // Second pass: Update parent references if parent column exists
        if ($parentColumn) {
            foreach ($categories as $category) {
                if (isset($category['parent']) && !empty($category['parent'])) {
                    // Skip if we don't have both the child and parent IDs
                    if (!isset($categoryCodeToId[$category['code']]) || !isset($categoryCodeToId[$category['parent']])) {
                        continue;
                    }
                    
                    $childId = $categoryCodeToId[$category['code']];
                    $parentId = $categoryCodeToId[$category['parent']];
                    
                    // Update parent reference
                    $updateStmt = $pdo->prepare("
                        UPDATE akeneo_categories 
                        SET $parentColumn = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$parentId, $childId]);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "Sync completed: $inserted categories inserted, $updated categories updated.",
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => count($categories)
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Sync error: ' . $e->getMessage()]);
    }
}

/**
 * Get Akeneo API token
 */
function getAkeneoToken($baseUrl, $clientId, $clientSecret, $username, $password) {
    $tokenUrl = "$baseUrl/api/oauth/v1/token";
    
    $data = [
        'grant_type' => 'password',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'username' => $username,
        'password' => $password
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception('Failed to get Akeneo token: ' . json_encode($tokenData));
    }
    
    return $tokenData['access_token'];
}

/**
 * Fetch categories from Akeneo API
 */
function fetchAkeneoCategories($baseUrl, $token) {
    $categories = [];
    $url = "$baseUrl/api/rest/v1/categories?limit=100";
    $hasNextPage = true;
    
    while ($hasNextPage) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!isset($data['_embedded']['items'])) {
            throw new Exception('Invalid Akeneo categories response');
        }
        
        // Add items to our categories array
        $categories = array_merge($categories, $data['_embedded']['items']);
        
        // Check if there's a next page
        if (isset($data['_links']['next']['href'])) {
            $url = $baseUrl . $data['_links']['next']['href'];
        } else {
            $hasNextPage = false;
        }
    }
    
    return $categories;
}