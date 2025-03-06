<?php
// /var/www/html/du/api/pivotree/attribute-value-mappings.php
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
 * Get attribute value mappings
 */
function getMappings($pdo) {
    try {
        // Get query parameters
        $attributeName = isset($_GET['attribute_name']) ? $_GET['attribute_name'] : null;
        $attributeValue = isset($_GET['attribute_value']) ? $_GET['attribute_value'] : null;
        $akeneoCode = isset($_GET['akeneo_code']) ? $_GET['akeneo_code'] : null;
        $type = isset($_GET['type']) ? $_GET['type'] : null; // 'all', 'new', 'existing'
        
        // Build query
        $query = "SELECT * FROM attribute_value_mappings WHERE 1=1";
        $params = [];
        
        if ($attributeName) {
            $query .= " AND pivotree_attribute_name = :attribute_name";
            $params['attribute_name'] = $attributeName;
        }
        
        if ($attributeValue) {
            $query .= " AND pivotree_attribute_value = :attribute_value";
            $params['attribute_value'] = $attributeValue;
        }
        
        if ($akeneoCode) {
            $query .= " AND akeneo_attribute_code = :akeneo_code";
            $params['akeneo_code'] = $akeneoCode;
        }
        
        if ($type === 'new') {
            $query .= " AND is_new_value = 1";
        } elseif ($type === 'existing') {
            $query .= " AND is_new_value = 0";
        }
        
        $query .= " ORDER BY pivotree_attribute_name, pivotree_attribute_value";
        
        // Execute query
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        
        $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['mappings' => $mappings]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve value mappings: ' . $e->getMessage()]);
    }
}

/**
 * Save a new attribute value mapping
 */
function saveMapping($pdo) {
    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['pivotree_attribute_name']) || !isset($data['pivotree_attribute_value'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        // Log incoming data for debugging
        error_log('Incoming value mapping data: ' . json_encode($data));
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // First check if the attribute mapping exists
        $stmt = $pdo->prepare("
            SELECT id FROM attribute_mappings 
            WHERE pivotree_attribute_name = :name
        ");
        $stmt->bindValue(':name', $data['pivotree_attribute_name']);
        $stmt->execute();
        
        $attributeMappingId = $stmt->fetchColumn();
        
        if (!$attributeMappingId) {
            // The attribute hasn't been mapped yet
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Attribute must be mapped before mapping its values']);
            return;
        }
        
        // Check if value mapping already exists - considering UOM for uniqueness
        $stmt = null;
        $existingId = null;
        
        if (isset($data['pivotree_uom']) && $data['pivotree_uom'] !== '') {
            // If UOM is provided, use it in the query
            $stmt = $pdo->prepare("
                SELECT id FROM attribute_value_mappings 
                WHERE pivotree_attribute_name = :name 
                AND pivotree_attribute_value = :value 
                AND pivotree_uom = :uom
            ");
            $stmt->bindValue(':name', $data['pivotree_attribute_name']);
            $stmt->bindValue(':value', $data['pivotree_attribute_value']);
            $stmt->bindValue(':uom', $data['pivotree_uom']);
        } else {
            // If no UOM, check for entries with null/empty UOM
            $stmt = $pdo->prepare("
                SELECT id FROM attribute_value_mappings 
                WHERE pivotree_attribute_name = :name 
                AND pivotree_attribute_value = :value 
                AND (pivotree_uom IS NULL OR pivotree_uom = '')
            ");
            $stmt->bindValue(':name', $data['pivotree_attribute_name']);
            $stmt->bindValue(':value', $data['pivotree_attribute_value']);
        }
        
        $stmt->execute();
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            // Update existing mapping
            $query = "
                UPDATE attribute_value_mappings SET
                    pivotree_uom = :uom,
                    akeneo_attribute_code = :akeneo_code,
                    akeneo_value_code = :value_code,
                    akeneo_value_label = :value_label,
                    is_new_value = :is_new_value,
                    new_value_code = :new_value_code,
                    new_value_label = :new_value_label
                WHERE id = :id
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':id', $existingId);
        } else {
            // Insert new mapping
            $query = "
                INSERT INTO attribute_value_mappings (
                    pivotree_attribute_name,
                    pivotree_attribute_value,
                    pivotree_uom,
                    akeneo_attribute_code,
                    akeneo_value_code,
                    akeneo_value_label,
                    is_new_value,
                    new_value_code,
                    new_value_label
                ) VALUES (
                    :name,
                    :value,
                    :uom,
                    :akeneo_code,
                    :value_code,
                    :value_label,
                    :is_new_value,
                    :new_value_code,
                    :new_value_label
                )
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':name', $data['pivotree_attribute_name']);
            $stmt->bindValue(':value', $data['pivotree_attribute_value']);
        }
        
        // Explicitly determine if this is a new value
        $isNewValue = 0; // Default to existing value
        
        if (isset($data['is_new_value'])) {
            // If explicitly provided, use that value
            $isNewValue = (int)$data['is_new_value'];
        } else {
            // Otherwise determine based on which fields are present
            if (!empty($data['new_value_code']) && empty($data['akeneo_value_code'])) {
                $isNewValue = 1; // New value
            } elseif (!empty($data['akeneo_value_code']) && empty($data['new_value_code'])) {
                $isNewValue = 0; // Existing value
            } else {
                // If both or neither are provided, log warning
                error_log('Warning: Ambiguous value mapping, cannot determine if new or existing: ' . json_encode($data));
            }
        }
        
        // Bind common parameters
        $stmt->bindValue(':uom', $data['pivotree_uom'] ?? null);
        $stmt->bindValue(':akeneo_code', $data['akeneo_attribute_code'] ?? null);
        
        // For existing values, ensure new_value fields are NULL
        if ($isNewValue == 0) {
            $stmt->bindValue(':value_code', $data['akeneo_value_code'] ?? null);
            $stmt->bindValue(':value_label', $data['akeneo_value_label'] ?? null);
            $stmt->bindValue(':is_new_value', 0);
            $stmt->bindValue(':new_value_code', null);
            $stmt->bindValue(':new_value_label', null);
        } 
        // For new values, ensure akeneo_value fields are NULL
        else {
            $stmt->bindValue(':value_code', null);
            $stmt->bindValue(':value_label', null);
            $stmt->bindValue(':is_new_value', 1);
            $stmt->bindValue(':new_value_code', $data['new_value_code'] ?? null);
            $stmt->bindValue(':new_value_label', $data['new_value_label'] ?? null);
        }
        
        $stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => $existingId ? 'Attribute value mapping updated' : 'Attribute value mapping created',
            'id' => $existingId ?: $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save value mapping: ' . $e->getMessage()]);
    }
}

/**
 * Delete an attribute value mapping
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
        $stmt = $pdo->prepare("DELETE FROM attribute_value_mappings WHERE id = :id");
        $stmt->bindValue(':id', $data['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // No rows affected - mapping not found
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Attribute value mapping not found']);
            return;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Attribute value mapping deleted'
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete value mapping: ' . $e->getMessage()]);
    }
}