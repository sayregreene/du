<?php
// /var/www/html/du/api/pivotree/process-export.php
// This script runs in the background, processing the export

// Get job ID from command line
$jobId = $argv[1] ?? null;
if (!$jobId) {
    die("Job ID is required");
}

// Include necessary files
require_once __DIR__ . '/../../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read job data
    $jobsDir = __DIR__ . '/../../data/export-jobs';
    $jobDataFile = "$jobsDir/$jobId.json";

    if (!file_exists($jobDataFile)) {
        die("Job data not found");
    }

    $jobData = json_decode(file_get_contents($jobDataFile), true);

    // Update job status
    $jobData['status'] = 'processing';
    $jobData['startedAt'] = date('Y-m-d H:i:s');
    file_put_contents($jobDataFile, json_encode($jobData));

    // Determine total number of products to process
    if ($jobData['exportAll']) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pivotree_products");
        $jobData['total'] = (int)$stmt->fetchColumn();
    } else {
        $jobData['total'] = count($jobData['productIds']);
    }
    
    // Update job data with total
    file_put_contents($jobDataFile, json_encode($jobData));
    
    // Set up file for export based on format
    $exportDir = __DIR__ . '/../../data/exports';
    if (!file_exists($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    $exportFile = "$exportDir/$jobId";
    
    // Process products in small batches
    $batchSize = 100;
    $processed = 0;
    $offset = 0;

    // Collection of all unique Akeneo attribute codes to build headers
    $akeneoAttributeCodes = [];

    // Prepare storage for product data in memory
    $productsData = [];

    // Build query based on export type
    if ($jobData['exportAll']) {
        $query = "SELECT * FROM pivotree_products ORDER BY pvt_sku_id LIMIT :limit OFFSET :offset";
    } else {
        if (empty($jobData['productIds'])) {
            throw new Exception("No product IDs provided for export");
        }
        $placeholders = implode(',', array_fill(0, count($jobData['productIds']), '?'));
        $query = "SELECT * FROM pivotree_products WHERE pvt_sku_id IN ($placeholders) ORDER BY pvt_sku_id";
    }

    // First pass: collect all products and their attributes
    while (true) {
        // Check if job has been cancelled
        $updatedJobData = json_decode(file_get_contents($jobDataFile), true);
        if (isset($updatedJobData['status']) && $updatedJobData['status'] === 'cancelled') {
            throw new Exception("Job cancelled by user");
        }
        
        // Prepare and execute the query
        $stmt = $pdo->prepare($query);
        
        if ($jobData['exportAll']) {
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            // For specific product IDs, we need to bind all IDs as parameters
            foreach ($jobData['productIds'] as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }
        }
        
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($products) === 0) {
            break; // No more products to process
        }
        
        // Process each product
        foreach ($products as $product) {
            // Get attributes for this product
            $attrStmt = $pdo->prepare("SELECT * FROM pivotree_product_attributes WHERE pvt_sku_id = ?");
            $attrStmt->execute([$product['pvt_sku_id']]);
            $attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get mappings for attributes
            $mappedAttributes = getMappedAttributes($pdo, $attributes);
            
            // Store for later use
            $productsData[$product['pvt_sku_id']] = [
                'product' => $product,
                'attributes' => $mappedAttributes
            ];
            
            // Collect all unique Akeneo attribute codes
            foreach ($mappedAttributes as $attr) {
                // Only include attributes that have an Akeneo attribute code mapping
                if (!empty($attr['akeneo_attribute_code'])) {
                    $akeneoCode = $attr['akeneo_attribute_code'];
                    if (!in_array($akeneoCode, $akeneoAttributeCodes)) {
                        $akeneoAttributeCodes[] = $akeneoCode;
                    }
                }
            }
            
            $processed++;
            
            // Update progress regularly
            if ($processed % 10 === 0) {
                $jobData['processed'] = $processed;
                $jobData['progress'] = $jobData['total'] > 0 ? $processed / $jobData['total'] : 0;
                $jobData['status'] = 'collecting_data';
                file_put_contents($jobDataFile, json_encode($jobData));
            }
        }
        
        if ($jobData['exportAll']) {
            $offset += $batchSize;
        } else {
            break; // For specific IDs, we only need one query
        }
    }

    // Sort attribute codes for consistent output
    sort($akeneoAttributeCodes);

    // Now generate the export files with Akeneo-formatted layout
    switch ($jobData['format']) {
        case 'csv':
            $exportFile .= '.csv';
            
            // Generate header row with required Akeneo columns
            $header = "sku";
            
            // Add mapped attribute columns - use Akeneo attribute codes as headers
            foreach ($akeneoAttributeCodes as $akeneoCode) {
                $header .= ",$akeneoCode";
            }
            
            $header .= "\n";
            file_put_contents($exportFile, $header);
            
            // Generate data rows
            foreach ($productsData as $pvtSkuId => $data) {
                $product = $data['product'];
                $attributes = $data['attributes'];
                
                // Create a lookup for quick access to attributes by Akeneo code
                $attrByAkeneoCode = [];
                foreach ($attributes as $attr) {
                    if (!empty($attr['akeneo_attribute_code'])) {
                        $attrByAkeneoCode[$attr['akeneo_attribute_code']] = $attr;
                    }
                }
                
                // Use sku_id as the identifier
                $sku = !empty($product['sku_id']) ? 
                       $product['sku_id'] : 
                       $product['pvt_sku_id'];
                
                // Start with SKU
                $row = escapeCsvValue($sku);
                
                // Add each attribute value in order of attribute codes
                foreach ($akeneoAttributeCodes as $akeneoCode) {
                    if (isset($attrByAkeneoCode[$akeneoCode])) {
                        $attr = $attrByAkeneoCode[$akeneoCode];
                        // Use mapped value if available, otherwise use original value
                        $valueToExport = !empty($attr['mapped_value']) ? 
                                         $attr['mapped_value'] : 
                                         $attr['attribute_value'];
                        $row .= ',' . escapeCsvValue($valueToExport);
                    } else {
                        // Attribute not present for this product
                        $row .= ',';
                    }
                }
                
                $row .= "\n";
                file_put_contents($exportFile, $row, FILE_APPEND);
                
                // Update progress
                $jobData['status'] = 'generating_file';
                $jobData['fileProgress'] = isset($jobData['fileProgress']) ? $jobData['fileProgress'] + 1 : 1;
                $fileProgressPercentage = $jobData['fileProgress'] / count($productsData);
                file_put_contents($jobDataFile, json_encode($jobData));
            }
            break;
            
        case 'json':
            $exportFile .= '.json';
            
            // Build JSON with Akeneo import structure
            $jsonData = [];
            
            foreach ($productsData as $pvtSkuId => $data) {
                $product = $data['product'];
                $attributes = $data['attributes'];
                
                // Use sku_id as the identifier
                $identifier = !empty($product['sku_id']) ? 
                              $product['sku_id'] : 
                              $product['pvt_sku_id'];
                
                $productJson = [
                    'identifier' => $identifier,
                    'values' => []
                ];
                
                // Add mapped attribute values
                foreach ($attributes as $attr) {
                    if (!empty($attr['akeneo_attribute_code'])) {
                        $akeneoCode = $attr['akeneo_attribute_code'];
                        // Use mapped value if available, otherwise use original value
                        $valueToExport = !empty($attr['mapped_value']) ? 
                                         $attr['mapped_value'] : 
                                         $attr['attribute_value'];
                        
                        // Values in Akeneo JSON format are arrays with scope and locale
                        $productJson['values'][$akeneoCode] = [
                            [
                                'locale' => null,
                                'scope' => null,
                                'data' => $valueToExport
                            ]
                        ];
                    }
                }
                
                $jsonData[] = $productJson;
            }
            
            // Write JSON to file
            file_put_contents($exportFile, json_encode($jsonData, JSON_PRETTY_PRINT));
            break;
            
        case 'excel':
            $exportFile .= '.xlsx';
            
            // Prepare Excel data with Akeneo import format
            $excelData = [];
            
            // Header row - use Akeneo attribute codes
            $header = ['sku'];
            
            // Add attribute columns
            foreach ($akeneoAttributeCodes as $akeneoCode) {
                $header[] = $akeneoCode;
            }
            
            $excelData[] = $header;
            
            // Add product rows
            foreach ($productsData as $pvtSkuId => $data) {
                $product = $data['product'];
                $attributes = $data['attributes'];
                
                // Create a lookup for quick access to attributes by Akeneo code
                $attrByAkeneoCode = [];
                foreach ($attributes as $attr) {
                    if (!empty($attr['akeneo_attribute_code'])) {
                        $attrByAkeneoCode[$attr['akeneo_attribute_code']] = $attr;
                    }
                }
                
                // Use sku_id as the identifier
                $sku = !empty($product['sku_id']) ? 
                       $product['sku_id'] : 
                       $product['pvt_sku_id'];
                
                // Start with SKU
                $row = [$sku];
                
                // Add each attribute value in order of attribute codes
                foreach ($akeneoAttributeCodes as $akeneoCode) {
                    if (isset($attrByAkeneoCode[$akeneoCode])) {
                        $attr = $attrByAkeneoCode[$akeneoCode];
                        // Use mapped value if available, otherwise use original value
                        $valueToExport = !empty($attr['mapped_value']) ? 
                                         $attr['mapped_value'] : 
                                         $attr['attribute_value'];
                        $row[] = $valueToExport;
                    } else {
                        // Attribute not present for this product
                        $row[] = '';
                    }
                }
                
                $excelData[] = $row;
            }
            
            // Generate Excel file
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    
                    // Add data to sheet
                    $sheet->fromArray($excelData, null, 'A1');
                    
                    // Auto-size columns
                    foreach (range('A', 'Z') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    
                    // Save Excel file
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save($exportFile);
                } else {
                    // Fall back to CSV for Excel format if PhpSpreadsheet isn't available
                    $csvContent = '';
                    foreach ($excelData as $row) {
                        $csvContent .= implode(',', array_map('escapeCsvValue', $row)) . "\n";
                    }
                    file_put_contents($exportFile, $csvContent);
                }
            } else {
                // Fall back to CSV for Excel format if vendor/autoload.php isn't available
                $csvContent = '';
                foreach ($excelData as $row) {
                    $csvContent .= implode(',', array_map('escapeCsvValue', $row)) . "\n";
                }
                file_put_contents($exportFile, $csvContent);
            }
            break;
    }

    // Update job to completed status
    $jobData['status'] = 'completed';
    $jobData['completedAt'] = date('Y-m-d H:i:s');
    $jobData['progress'] = 1;
    $jobData['processed'] = $jobData['total'];
    $jobData['exportFile'] = $exportFile;
    file_put_contents($jobDataFile, json_encode($jobData));
    
} catch (Exception $e) {
    // Update job status to failed
    $jobData['status'] = 'failed';
    $jobData['error'] = $e->getMessage();
    file_put_contents($jobDataFile, json_encode($jobData));
    
    // Log error
    error_log("Export job $jobId failed: " . $e->getMessage());
}

/**
 * Get mappings for attributes
 * @param PDO $pdo Database connection
 * @param array $attributes Product attributes
 * @return array Mapped attributes
 */
function getMappedAttributes($pdo, $attributes) {
    // Fetch all attribute mappings
    $stmt = $pdo->query("SELECT * FROM attribute_mappings");
    $attributeMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mappedAttributes = [];
    
    foreach ($attributes as $attr) {
        // Find attribute mapping
        $attrMapping = null;
        foreach ($attributeMappings as $mapping) {
            if ($mapping['pivotree_attribute_name'] === $attr['attribute_name']) {
                $attrMapping = $mapping;
                break;
            }
        }
        
        if (!$attrMapping) {
            // No mapping for this attribute
            $mappedAttributes[] = array_merge($attr, [
                'mapped' => false,
                'akeneo_attribute_code' => null,
                'akeneo_attribute_label' => null,
                'is_new_attribute' => false,
                'mapped_value' => null,
                'mapped_value_label' => null
            ]);
            continue;
        }
        
        // Attribute is mapped, now check value mapping
        $stmt = $pdo->prepare("
            SELECT * FROM attribute_value_mappings 
            WHERE pivotree_attribute_name = ? AND pivotree_attribute_value = ?
            AND (pivotree_uom = ? OR (pivotree_uom IS NULL AND ? IS NULL))
        ");
        
        $stmt->execute([
            $attr['attribute_name'], 
            $attr['attribute_value'],
            $attr['uom'] ?? null,
            $attr['uom'] ?? null
        ]);
        
        $valueMapping = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $mappedAttributes[] = array_merge($attr, [
            'mapped' => true,
            'akeneo_attribute_code' => $attrMapping['akeneo_attribute_code'] ?? $attrMapping['new_attribute_code'] ?? null,
            'akeneo_attribute_label' => $attrMapping['akeneo_attribute_label'] ?? $attrMapping['new_attribute_label'] ?? null,
            'is_new_attribute' => (bool)($attrMapping['is_new_attribute'] ?? false),
            'mapped_value' => $valueMapping ? ($valueMapping['akeneo_value_code'] ?? $valueMapping['new_value_code'] ?? null) : null,
            'mapped_value_label' => $valueMapping ? ($valueMapping['akeneo_value_label'] ?? $valueMapping['new_value_label'] ?? null) : null
        ]);
    }
    
    return $mappedAttributes;
}

/**
 * Escape a value for CSV
 * @param string $value Value to escape
 * @return string Escaped value
 */
function escapeCsvValue($value) {
    if ($value === null || $value === '') return '';
    
    $value = (string)$value;
    
    // If value contains commas, quotes, or newlines, wrap it in quotes
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        // Double any quotes inside the value
        $value = '"' . str_replace('"', '""', $value) . '"';
    }
    
    return $value;
}