<?php
// /var/www/html/du/api/pivotree/process-export.php
// This script runs in the background, processing the export

// Enhanced debug log function
function debug_log($message, $data = null)
{
    $logFile = __DIR__ . '/../../data/export-debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";

    if ($data !== null) {
        // Format data as JSON or limit strings for readability
        if (is_array($data) || is_object($data)) {
            $logMessage .= " - " . json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $logMessage .= " - " . $data;
        }
    }

    $logMessage .= "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Start debug logging
debug_log("==== EXPORT PROCESS STARTED ====");

// Get job ID from command line
$jobId = $argv[1] ?? null;
if (!$jobId) {
    debug_log("ERROR: Job ID is required");
    die("Job ID is required");
}

debug_log("Processing export job", $jobId);

// Include necessary files
require_once __DIR__ . '/../../config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debug_log("Database connection established");

    // Read job data
    $jobsDir = __DIR__ . '/../../data/export-jobs';
    $jobDataFile = "$jobsDir/$jobId.json";

    if (!file_exists($jobDataFile)) {
        debug_log("ERROR: Job data not found", $jobDataFile);
        die("Job data not found");
    }

    $jobData = json_decode(file_get_contents($jobDataFile), true);
    debug_log("Job data loaded", ["format" => $jobData['format'], "exportAll" => $jobData['exportAll']]);

    // Update job status
    $jobData['status'] = 'processing';
    $jobData['startedAt'] = date('Y-m-d H:i:s');
    file_put_contents($jobDataFile, json_encode($jobData));

    // Determine total number of products to process
    if ($jobData['exportAll']) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pivotree_products");
        $jobData['total'] = (int) $stmt->fetchColumn();
        debug_log("Total products to export (all)", $jobData['total']);
    } else {
        $jobData['total'] = count($jobData['productIds']);
        debug_log("Total products to export (selected)", $jobData['total']);
    }

    // Update job data with total
    file_put_contents($jobDataFile, json_encode($jobData));

    // Set up file for export based on format
    $exportDir = __DIR__ . '/../../data/exports';
    if (!file_exists($exportDir)) {
        mkdir($exportDir, 0755, true);
        debug_log("Created export directory", $exportDir);
    }

    $exportFile = "$exportDir/$jobId";
    debug_log("Export file path", $exportFile);

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
        debug_log("Using query for all products", $query);
    } else {
        if (empty($jobData['productIds'])) {
            debug_log("ERROR: No product IDs provided for export");
            throw new Exception("No product IDs provided for export");
        }
        $placeholders = implode(',', array_fill(0, count($jobData['productIds']), '?'));
        $query = "SELECT * FROM pivotree_products WHERE pvt_sku_id IN ($placeholders) ORDER BY pvt_sku_id";
        debug_log("Using query for specific products", $query);
    }

    debug_log("Starting product collection...");

    // First pass: collect all products and their attributes
    while (true) {
        // Check if job has been cancelled
        $updatedJobData = json_decode(file_get_contents($jobDataFile), true);
        if (isset($updatedJobData['status']) && $updatedJobData['status'] === 'cancelled') {
            debug_log("Job cancelled by user");
            throw new Exception("Job cancelled by user");
        }

        // Prepare and execute the query
        $stmt = $pdo->prepare($query);

        if ($jobData['exportAll']) {
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            debug_log("Fetching batch", ["offset" => $offset, "limit" => $batchSize]);
        } else {
            // For specific product IDs, we need to bind all IDs as parameters
            foreach ($jobData['productIds'] as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }
            debug_log("Fetching specific products", count($jobData['productIds']));
        }

        $stmt->execute();

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($products) === 0) {
            debug_log("No more products to process");
            break; // No more products to process
        }

        debug_log("Processing batch of products", count($products));

        // Process each product
        foreach ($products as $product) {
            // Get attributes for this product
            $attrStmt = $pdo->prepare("SELECT * FROM pivotree_product_attributes WHERE pvt_sku_id = ?");
            $attrStmt->execute([$product['pvt_sku_id']]);
            $attributes = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

            debug_log("Product attributes for " . $product['pvt_sku_id'], count($attributes));

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
                        debug_log("Added Akeneo attribute code", $akeneoCode);
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
                debug_log("Updated progress", ["processed" => $processed, "total" => $jobData['total']]);
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
    debug_log("All Akeneo attribute codes", $akeneoAttributeCodes);

    // Sample the first product to see what's happening
    if (count($productsData) > 0) {
        $sampleSku = array_key_first($productsData);
        $sampleProduct = $productsData[$sampleSku];

        debug_log("SAMPLE PRODUCT", $sampleProduct['product']['pvt_sku_id']);

        // Log each attribute with mapping details for this sample product
        foreach ($sampleProduct['attributes'] as $attr) {
            debug_log("SAMPLE ATTRIBUTE", [
                "name" => $attr['attribute_name'],
                "value" => $attr['attribute_value'],
                "uom" => $attr['uom'] ?? null,
                "akeneo_code" => $attr['akeneo_attribute_code'],
                "is_new_attr" => $attr['is_new_attribute'],
                "mapped_value" => $attr['mapped_value'],
                "mapped_label" => $attr['mapped_value_label']
            ]);

            // Specifically look for Accessory Type attributes
            if ($attr['attribute_name'] === 'Accessory Type') {
                debug_log("FOUND ACCESSORY TYPE ATTRIBUTE", [
                    "value" => $attr['attribute_value'],
                    "mapped_value" => $attr['mapped_value'],
                    "export_value" => getExportValue($attr)
                ]);
            }
        }
    }

    // Now generate the export files with Akeneo-formatted layout
    debug_log("Starting export generation in format: " . $jobData['format']);

    switch ($jobData['format']) {
        case 'csv':
            $exportFile .= '.csv';
            debug_log("Generating CSV export", $exportFile);
            // Use the function to generate CSV export
            generateCsvExport($productsData, $akeneoAttributeCodes, $exportFile, $jobDataFile, $jobData);
            break;

        case 'json':
            $exportFile .= '.json';
            debug_log("Generating JSON export", $exportFile);
            // Use the function to generate JSON export
            generateJsonExport($productsData, $exportFile);
            break;

        case 'excel':
            $exportFile .= '.xlsx';
            debug_log("Generating Excel export", $exportFile);

            // Get Excel data using the function
            $excelData = generateExcelExport($productsData, $akeneoAttributeCodes, $exportFile);

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
                    debug_log("Excel file saved with PhpSpreadsheet");
                } else {
                    // Fall back to CSV for Excel format if PhpSpreadsheet isn't available
                    $csvContent = '';
                    foreach ($excelData as $row) {
                        $csvContent .= implode(',', array_map('escapeCsvValue', $row)) . "\n";
                    }
                    file_put_contents($exportFile, $csvContent);
                    debug_log("Excel fallback to CSV (PhpSpreadsheet not available)");
                }
            } else {
                // Fall back to CSV for Excel format if vendor/autoload.php isn't available
                $csvContent = '';
                foreach ($excelData as $row) {
                    $csvContent .= implode(',', array_map('escapeCsvValue', $row)) . "\n";
                }
                file_put_contents($exportFile, $csvContent);
                debug_log("Excel fallback to CSV (vendor/autoload.php not available)");
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
    debug_log("Export completed successfully", ["file" => $exportFile]);

} catch (Exception $e) {
    // Update job status to failed
    $jobData['status'] = 'failed';
    $jobData['error'] = $e->getMessage();
    file_put_contents($jobDataFile, json_encode($jobData));

    // Log error
    debug_log("ERROR: Export failed", $e->getMessage());
    error_log("Export job $jobId failed: " . $e->getMessage());
}

debug_log("==== EXPORT PROCESS ENDED ====");

/**
 * Helper function to determine the export value for an attribute
 * @param array $attr The attribute data
 * @return string The value to export
 */
function getExportValue($attr)
{
    // Check if the attribute value contains semicolons
    if (strpos($attr['attribute_value'], ';') !== false) {
        debug_log("Processing multi-value attribute", [
            "attribute" => $attr['attribute_name'],
            "value" => $attr['attribute_value']
        ]);
        
        // Split values by semicolon and trim whitespace
        $values = array_map('trim', explode(';', $attr['attribute_value']));
        $mappedValues = [];
        
        // Process each individual value
        foreach ($values as $singleValue) {
            // Create a temporary attribute with just this single value
            $singleAttr = [
                'attribute_name' => $attr['attribute_name'],
                'attribute_value' => $singleValue,
                'uom' => $attr['uom'] ?? null,
                'akeneo_attribute_code' => $attr['akeneo_attribute_code'],
                'mapped_value' => null,  // Will be populated by lookupValueMapping
                'mapped_value_label' => null
            ];
            
            // Look up the mapping for this specific value
            lookupValueMapping($GLOBALS['pdo'], $singleAttr);
            
            // Determine export value for this single value using standard logic
            $singleExportValue = $singleAttr['mapped_value'] !== null ?
                $singleAttr['mapped_value'] :
                $singleAttr['attribute_value'];
                
            $mappedValues[] = $singleExportValue;
            
            debug_log("Processed single value within multi-value", [
                "original" => $singleValue,
                "mapped" => $singleAttr['mapped_value'],
                "export_value" => $singleExportValue
            ]);
        }
        
        // Join all mapped values with commas for Akeneo
        $exportValue = implode(',', $mappedValues);
        
        debug_log("Final multi-value export", [
            "attribute" => $attr['attribute_name'],
            "original_values" => $values,
            "export_value" => $exportValue
        ]);
        
        return $exportValue;
    }
    
    // Original logic for single values
    $exportValue = $attr['mapped_value'] !== null ?
        $attr['mapped_value'] :
        $attr['attribute_value'];

    debug_log("Export value selection", [
        "attribute" => $attr['attribute_name'],
        "value" => $attr['attribute_value'],
        "mapped_value" => $attr['mapped_value'],
        "using" => $exportValue
    ]);

    return $exportValue;
}

/**
 * Look up value mapping for a specific attribute value
 * @param PDO $pdo Database connection
 * @param array &$attr Attribute data (passed by reference to modify)
 */
function lookupValueMapping($pdo, &$attr)
{
    $stmt = $pdo->prepare("
        SELECT * FROM attribute_value_mappings 
        WHERE pivotree_attribute_name = ? AND pivotree_attribute_value = ?
        AND (pivotree_uom = ? OR (pivotree_uom IS NULL))
    ");
    
    $stmt->execute([
        $attr['attribute_name'],
        $attr['attribute_value'],
        $attr['uom'] ?? null
    ]);
    
    $valueMapping = $stmt->fetch(PDO::FETCH_ASSOC);
    
    debug_log("Value mapping lookup for specific value", [
        "name" => $attr['attribute_name'],
        "value" => $attr['attribute_value'],
        "result" => ($valueMapping ? "Found mapping" : "No mapping found")
    ]);
    
    // Update the attribute with mapping values if found
    if ($valueMapping) {
        if ($valueMapping['is_new_value']) {
            $attr['mapped_value'] = $valueMapping['new_value_code'];
            $attr['mapped_value_label'] = $valueMapping['new_value_label'];
        } else {
            $attr['mapped_value'] = $valueMapping['akeneo_value_code'];
            $attr['mapped_value_label'] = $valueMapping['akeneo_value_label'];
        }
    }
}


/**
 * Modified getMappedAttributes function to share PDO globally for multi-value processing
 * @param PDO $pdo Database connection
 * @param array $attributes Product attributes
 * @return array Mapped attributes
 */
function getMappedAttributes($pdo, $attributes)
{
    // Store PDO in globals for access in getExportValue/lookupValueMapping
    $GLOBALS['pdo'] = $pdo;
    
    debug_log("Starting getMappedAttributes for " . count($attributes) . " attributes");

    // Fetch all attribute mappings
    $stmt = $pdo->query("SELECT * FROM attribute_mappings");
    $attributeMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mappedAttributes = [];

    foreach ($attributes as $attr) {
        debug_log("Processing attribute", ["name" => $attr['attribute_name'], "value" => $attr['attribute_value']]);

        // 1. Test looking up the specific attribute mapping
        $attrMapping = null;
        foreach ($attributeMappings as $mapping) {
            if ($mapping['pivotree_attribute_name'] === $attr['attribute_name']) {
                $attrMapping = $mapping;
                break;
            }
        }

        if (!$attrMapping) {
            // No mapping for this attribute
            debug_log("No attribute mapping found for: " . $attr['attribute_name']);
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

        debug_log("Found attribute mapping", $attrMapping);

        // 2. Test looking up specific value mapping
        $stmt = $pdo->prepare("
        SELECT * FROM attribute_value_mappings 
        WHERE pivotree_attribute_name = ? AND pivotree_attribute_value = ?
        AND (pivotree_uom = ? OR (pivotree_uom IS NULL))
        ");
        
        $stmt->execute([
            $attr['attribute_name'],
            $attr['attribute_value'],
            $attr['uom'] ?? null
        ]);
        
        // Start output buffering
        ob_start();
        
        // Dump the parameters
        $stmt->debugDumpParams();
        
        // Get the buffered output and clear the buffer
        $output = ob_get_clean();
        
        // Log the output
        debug_log($output);
        
        $valueMapping = $stmt->fetch(PDO::FETCH_ASSOC);

        debug_log("Value mapping search result", $valueMapping ? $valueMapping : "No value mapping found");

        // 3. Test the mapping logic
        $attrCode = $attrMapping['is_new_attribute']
            ? $attrMapping['new_attribute_code']
            : $attrMapping['akeneo_attribute_code'];

        debug_log("Selected attribute code: " . $attrCode);

        // Determine value code based on is_new_value
        $valueCode = null;
        $valueLabel = null;

        if ($valueMapping) {
            if ($valueMapping['is_new_value']) {
                $valueCode = $valueMapping['new_value_code'];
                $valueLabel = $valueMapping['new_value_label'];
                debug_log("Using new value code: " . $valueCode);
            } else {
                $valueCode = $valueMapping['akeneo_value_code'];
                $valueLabel = $valueMapping['akeneo_value_label'];
                debug_log("Using existing value code: " . $valueCode);
            }
        }

        $mappedAttributes[] = array_merge($attr, [
            'mapped' => true,
            'akeneo_attribute_code' => $attrCode,
            'akeneo_attribute_label' => $attrMapping['akeneo_attribute_label'] ?? $attrMapping['new_attribute_label'] ?? null,
            'is_new_attribute' => (bool) ($attrMapping['is_new_attribute']),
            'mapped_value' => $valueCode,
            'mapped_value_label' => $valueLabel
        ]);

        debug_log("Final mapped attribute", [
            "name" => $attr['attribute_name'],
            "value" => $attr['attribute_value'],
            "akeneo_code" => $attrCode,
            "mapped_value" => $valueCode
        ]);
    }

    return $mappedAttributes;
}

/**
 * Generate the CSV data for export - FIXED VERSION
 * @param array $productsData Product data with attributes
 * @param array $akeneoAttributeCodes List of all Akeneo attribute codes
 * @param string $exportFile Path to the export file
 * @param string $jobDataFile Path to the job data file
 * @param array $jobData Job data
 */
function generateCsvExport($productsData, $akeneoAttributeCodes, $exportFile, $jobDataFile, &$jobData)
{
    debug_log("Starting CSV export generation");

    // Generate header row with required Akeneo columns
    $header = "sku";

    // Add mapped attribute columns - use Akeneo attribute codes as headers
    foreach ($akeneoAttributeCodes as $akeneoCode) {
        $header .= ",$akeneoCode";
    }

    $header .= "\n";
    file_put_contents($exportFile, $header);
    debug_log("CSV header written", $header);

    $rowCount = 0;

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

                // Use the helper function to get the export value
                $valueToExport = getExportValue($attr);

                debug_log("Exporting value for $akeneoCode", [
                    "product" => $sku,
                    "original" => $attr['attribute_value'],
                    "mapped" => $attr['mapped_value'],
                    "using" => $valueToExport
                ]);

                $row .= ',' . escapeCsvValue($valueToExport);
            } else {
                // Attribute not present for this product
                $row .= ',';
            }
        }

        $row .= "\n";
        file_put_contents($exportFile, $row, FILE_APPEND);

        $rowCount++;

        // Log occasional rows for debugging
        if ($rowCount <= 5 || $rowCount % 100 === 0) {
            debug_log("Wrote CSV row #$rowCount", $row);
        }

        // Update progress
        $jobData['status'] = 'generating_file';
        $jobData['fileProgress'] = isset($jobData['fileProgress']) ? $jobData['fileProgress'] + 1 : 1;
        $fileProgressPercentage = $jobData['fileProgress'] / count($productsData);
        file_put_contents($jobDataFile, json_encode($jobData));
    }

    debug_log("CSV export completed", ["rows" => $rowCount, "file" => $exportFile]);
}

/**
 * Generate the JSON data for export - FIXED VERSION
 * @param array $productsData Product data with attributes
 * @param string $exportFile Path to the export file
 */
function generateJsonExport($productsData, $exportFile)
{
    debug_log("Starting JSON export generation");

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

                // Use the helper function to get the export value
                $valueToExport = getExportValue($attr);

                debug_log("JSON export value for $akeneoCode", [
                    "product" => $identifier,
                    "original" => $attr['attribute_value'],
                    "mapped" => $attr['mapped_value'],
                    "using" => $valueToExport
                ]);

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
    debug_log("JSON export completed", ["products" => count($jsonData), "file" => $exportFile]);
}

/**
 * Generate the Excel data for export - FIXED VERSION
 * @param array $productsData Product data with attributes
 * @param array $akeneoAttributeCodes List of all Akeneo attribute codes
 * @param string $exportFile Path to the export file
 * @return array Excel data as array of arrays
 */
function generateExcelExport($productsData, $akeneoAttributeCodes, $exportFile)
{
    debug_log("Starting Excel export data generation");

    // Prepare Excel data with Akeneo import format
    $excelData = [];

    // Header row - use Akeneo attribute codes
    $header = ['sku'];

    // Add attribute columns
    foreach ($akeneoAttributeCodes as $akeneoCode) {
        $header[] = $akeneoCode;
    }

    $excelData[] = $header;
    debug_log("Excel header prepared", $header);

    $rowCount = 0;

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

                // Use the helper function to get the export value
                $valueToExport = getExportValue($attr);

                debug_log("Excel export value for $akeneoCode", [
                    "product" => $sku,
                    "original" => $attr['attribute_value'],
                    "mapped" => $attr['mapped_value'],
                    "using" => $valueToExport
                ]);

                $row[] = $valueToExport;
            } else {
                // Attribute not present for this product
                $row[] = '';
            }
        }

        $excelData[] = $row;
        $rowCount++;

        // Log occasional rows for debugging
        if ($rowCount <= 5 || $rowCount % 100 === 0) {
            debug_log("Prepared Excel row #$rowCount", $row);
        }
    }

    debug_log("Excel data generation completed", ["rows" => $rowCount]);
    return $excelData;
}

/**
 * Escape a value for CSV
 * @param string $value Value to escape
 * @return string Escaped value
 */
function escapeCsvValue($value)
{
    if ($value === null || $value === '')
        return '';

    $value = (string) $value;

    // If value contains commas, quotes, or newlines, wrap it in quotes
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        // Double any quotes inside the value
        $value = '"' . str_replace('"', '""', $value) . '"';
    }

    return $value;
}