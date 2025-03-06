<?php
// /var/www/html/du/api/pivotree/attribute-mappings.php
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

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve mappings
        getMappings($pdo);
        break;
    case 'POST':
        // Save a new mapping
        saveMapping($pdo);
        break;
    case 'DELETE':
        // Delete a mapping
        deleteMapping($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

/**
 * Get attribute mappings
 */
function getMappings($pdo) {
    try {
        // Get query parameters
        $attributeName = isset($_GET['attribute_name']) ? $_GET['attribute_name'] : null;
        $type = isset($_GET['type']) ? $_GET['type'] : null; // 'all', 'new', 'existing'
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Validate inputs
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Build query
        $query = "SELECT * FROM attribute_mappings WHERE 1=1";
        $params = [];
        
        if ($attributeName) {
            $query .= " AND pivotree_attribute_name = :attribute_name";
            $params['attribute_name'] = $attributeName;
        }
        
        if ($search) {
            $query .= " AND (pivotree_attribute_name LIKE :search 
                        OR akeneo_attribute_code LIKE :search 
                        OR akeneo_attribute_label LIKE :search
                        OR new_attribute_code LIKE :search
                        OR new_attribute_label LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        if ($type === 'new') {
            $query .= " AND is_new_attribute = 1";
        } elseif ($type === 'existing') {
            $query .= " AND is_new_attribute = 0";
        }
        
        // Count total first
        $countQuery = "SELECT COUNT(*) FROM ($query) as count_table";
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();
        
        // Add order and limits to main query
        $query .= " ORDER BY pivotree_attribute_name LIMIT :limit OFFSET :offset";
        
        // Execute query
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'mappings' => $mappings,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve mappings: ' . $e->getMessage()]);
    }
}

/**
 * Save a new attribute mapping
 */
function saveMapping($pdo) {
    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['pivotree_attribute_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required attribute name']);
            return;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if mapping already exists
        $stmt = $pdo->prepare("
            SELECT id FROM attribute_mappings 
            WHERE pivotree_attribute_name = :name
        ");
        $stmt->bindValue(':name', $data['pivotree_attribute_name']);
        $stmt->execute();
        
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            // Update existing mapping
            $query = "
                UPDATE attribute_mappings SET
                    akeneo_attribute_code = :akeneo_code,
                    akeneo_attribute_label = :akeneo_label,
                    is_new_attribute = :is_new_attribute,
                    new_attribute_code = :new_attribute_code,
                    new_attribute_label = :new_attribute_label,
                    new_attribute_type = :new_attribute_type
                WHERE id = :id
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':id', $existingId);
        } else {
            // Insert new mapping
            $query = "
                INSERT INTO attribute_mappings (
                    pivotree_attribute_name,
                    akeneo_attribute_code,
                    akeneo_attribute_label,
                    is_new_attribute,
                    new_attribute_code,
                    new_attribute_label,
                    new_attribute_type
                ) VALUES (
                    :name,
                    :akeneo_code,
                    :akeneo_label,
                    :is_new_attribute,
                    :new_attribute_code,
                    :new_attribute_label,
                    :new_attribute_type
                )
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':name', $data['pivotree_attribute_name']);
        }
        
        // Bind common parameters
        $stmt->bindValue(':akeneo_code', $data['akeneo_attribute_code'] ?? null);
        $stmt->bindValue(':akeneo_label', $data['akeneo_attribute_label'] ?? null);
        $stmt->bindValue(':is_new_attribute', isset($data['is_new_attribute']) ? 1 : 0);
        $stmt->bindValue(':new_attribute_code', $data['new_attribute_code'] ?? null);
        $stmt->bindValue(':new_attribute_label', $data['new_attribute_label'] ?? null);
        $stmt->bindValue(':new_attribute_type', $data['new_attribute_type'] ?? null);
        
        $stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => $existingId ? 'Attribute mapping updated' : 'Attribute mapping created',
            'id' => $existingId ?: $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save mapping: ' . $e->getMessage()]);
    }
}

/**
 * Delete an attribute mapping
 */
function deleteMapping($pdo) {
    try {
        // Get DELETE data
        parse_str(file_get_contents('php://input'), $data);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required ID']);
            return;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete the mapping
        $stmt = $pdo->prepare("DELETE FROM attribute_mappings WHERE id = :id");
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // No rows affected - mapping not found
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Attribute mapping not found']);
            return;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Attribute mapping deleted'
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete mapping: ' . $e->getMessage()]);
    }
}