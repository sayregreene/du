<?php
require_once '../../config.php';

// Set headers and error reporting
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request details
error_log("Request received: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
error_log("GET params: " . print_r($_GET, true));

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle API requests
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'getFilterOptions':
            // Get unique brands
            $brands = $pdo->query("SELECT DISTINCT brand FROM pivotree_products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")
                         ->fetchAll(PDO::FETCH_COLUMN);

            // Get unique terminal nodes
            $nodes = $pdo->query("SELECT DISTINCT terminal_node FROM pivotree_products WHERE terminal_node IS NOT NULL AND terminal_node != '' ORDER BY terminal_node")
                        ->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'brands' => $brands,
                'nodes' => $nodes
            ]);
            break;

        case 'getProducts':
            // Sanitize and validate input parameters
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
            $offset = ($page - 1) * $limit;
            
            // Log pagination parameters
            error_log("Pagination: page=$page, limit=$limit, offset=$offset");
            
            $search = trim($_GET['search'] ?? '');
            $brand = trim($_GET['brand'] ?? '');
            $node = trim($_GET['node'] ?? '');
            
            // Build query conditions
            $conditions = ["1=1"]; // Always true condition to simplify query building
            $params = [];
            
            if ($search !== '') {
                $conditions[] = "(sku_id LIKE ? OR manufacturer_part_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($brand !== '') {
                $conditions[] = "brand = ?";
                $params[] = $brand;
            }
            
            if ($node !== '') {
                $conditions[] = "terminal_node = ?";
                $params[] = $node;
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM pivotree_products WHERE $whereClause";
            $stmt = $pdo->prepare($countQuery);
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated data
            $query = "SELECT 
                        brand, 
                        sku_id, 
                        manufacturer_part_number, 
                        short_description, 
                        pvt_sku_id, 
                        terminal_node 
                     FROM pivotree_products 
                     WHERE $whereClause 
                     ORDER BY sku_id 
                     LIMIT $limit OFFSET $offset";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'data' => $products
            ]);
            break;

        case 'getAttributes':
            $pvtSkuId = trim($_GET['pvt_sku_id'] ?? '');
            
            if (empty($pvtSkuId)) {
                throw new Exception('PVT SKU ID is required');
            }
            
            $query = "SELECT * FROM pivotree_product_attributes WHERE pvt_sku_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$pvtSkuId]);
            
            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Application error occurred',
        'message' => $e->getMessage()
    ]);
}