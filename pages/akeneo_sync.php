<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'akeneodata';
$username = 'dm_db';
$password = 'Borealis5609!';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$baseUrl = "https://heilind.cloud.akeneo.com";
$clientId = "7_pi3gmawb57kg0844ow0ksk4gogs80w44wk40k8ck4so4wo08c";
$clientSecret = "1kw80i828c4gc4k000s8kkkw4w4wsgsoggccco4gw4k0w4scoo";
$username = "sbg_rest_5375";
$password = "87d8707e6";

function authenticateAkeneo($baseUrl, $clientId, $clientSecret, $username, $password)
{
    $ch = curl_init("$baseUrl/api/oauth/v1/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'password',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

$endpointBaseURL = "https://heilind.cloud.akeneo.com/api/rest/v1";
$baseUrl = "https://heilind.cloud.akeneo.com";
function fetchAkeneoPaginatedData($endpoint, &$token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, $limit = 100, $maxPages = 1)
{
    $results = [];
    $page = 1;
    $retryCount = 0;
    $endpointBaseURL = "https://heilind.cloud.akeneo.com/api/rest/v1";
    $baseUrl = "https://heilind.cloud.akeneo.com";

    do {
        $url = "$endpointBaseURL/$endpoint?limit=$limit&page=$page";
        file_put_contents('/var/www/html/du/debug.log', "Fetching URL: $url\n", FILE_APPEND);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        file_put_contents('/var/www/html/du/debug.log', "HTTP Status: $httpStatus\nResponse: $response\n", FILE_APPEND);

        if ($httpStatus === 401 && $retryCount < 1) {
            // Token expired, reauthenticate
            $token = authenticateAkeneo($baseUrl, $clientId, $clientSecret, $username, $password);
            if (!$token) {
                throw new Exception("Failed to reauthenticate after token expiration.");
            }
            $retryCount++;
            continue; // Retry with the new token
        } elseif ($httpStatus !== 200) {
            throw new Exception("Failed to fetch data. HTTP Status: $httpStatus");
        }

        $retryCount = 0; // Reset retry count if successful
        $data = json_decode($response, true);
        if (isset($data['items'])) {
            $results = array_merge($results, $data['items']);
        }
        $page++;
        if ($maxPages !== null && $page > $maxPages) {
            break;
        }
    } while (isset($data['_links']['next']));

    return $results;
}


function upsertData($pdo, $table, $data, $uniqueColumn) {
    foreach ($data as $item) {
        $columns = implode(", ", array_keys($item));
        $placeholders = ":" . implode(", :", array_keys($item));
        $updates = implode(", ", array_map(fn($col) => "$col = VALUES($col)", array_keys($item)));

        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)
                  ON DUPLICATE KEY UPDATE $updates";

        file_put_contents('/var/www/html/du/debug.log', "SQL Query: $query\nData: " . json_encode($item) . "\n", FILE_APPEND);

        $stmt = $pdo->prepare($query);
        foreach ($item as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            file_put_contents('/var/www/html/du/debug.log', "SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


function importCategories(PDO $pdo, $baseUrl, $token)
{
    // We'll fetch *all* categories via pagination
    $allCategories = fetchAllPaginated(
        $pdo,
        $baseUrl,
        $token,
        "/api/rest/v1/categories?limit=100"
    );

    // Prepare an insert/update statement.  
    // On DUPLICATE KEY means we match on "category" if it’s unique in your schema
    $stmt = $pdo->prepare("
        INSERT INTO akeneo_categories
            (category, category_name, parent_category_id)
        VALUES
            (:code, :name, :parentId)
        ON DUPLICATE KEY UPDATE
            category_name = VALUES(category_name),
            parent_category_id = VALUES(parent_category_id)
    ");

    // For parent lookup, we might do a second pass or store them temporarily.
    // For simplicity, let's just store parent_category_id as NULL for now,
    // then do a second pass to update. (Because the parent might not exist in DB yet.)
    // Another approach is to store the parent code in a temporary table or column,
    // then handle parent-child after all are inserted.

    foreach ($allCategories as $cat) {
        $code   = $cat['code'];
        // Some categories have localized names in e.g. $cat['labels']['en_US']
        $name   = $cat['labels']['en_US'] ?? $code;
        $parent = $cat['parent'] ?? null;

        // We'll set parent_category_id to null for now
        $stmt->execute([
            ':code'    => $code,
            ':name'    => $name,
            ':parentId'=> null,
        ]);
    }

    // OPTIONAL: do a second pass to update parent_category_id properly
    // using the parent's code to look up its ID
    $updateParentStmt = $pdo->prepare("
        UPDATE akeneo_categories c
        JOIN akeneo_categories cp ON cp.category = :parent_code
        SET c.parent_category_id = cp.id
        WHERE c.category = :child_code
    ");
    foreach ($allCategories as $cat) {
        if (!empty($cat['parent'])) {
            $updateParentStmt->execute([
                ':parent_code' => $cat['parent'],
                ':child_code'  => $cat['code']
            ]);
        }
    }

    return count($allCategories);
}




function syncProducts($pdo, &$token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, $maxPages = null) {
    $products = fetchAkeneoPaginatedData("products", $token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, 100, $maxPages);
    file_put_contents('/var/www/html/du/debug.log', "Fetched products: " . json_encode($products) . "\n", FILE_APPEND);

    foreach ($products as $product) {
        $productData = [
            'identifier' => $product['identifier'],
            'family_id' => $product['family'],
            'category_id' => $product['categories'][0] ?? null,
        ];
        file_put_contents('/var/www/html/du/debug.log', "Product data to upsert: " . json_encode($productData) . "\n", FILE_APPEND);
        upsertData($pdo, 'products', [$productData], 'identifier');
    }
    return count($products);
}


function syncAkeneoData($pdo, $endpointBaseURL, $clientId, $clientSecret, $username, $password, $maxPages = null) {
    $baseUrl = "https://heilind.cloud.akeneo.com";
    $token = authenticateAkeneo($baseUrl, $clientId, $clientSecret, $username, $password);
    if (!$token) {
        throw new Exception("Failed to authenticate with Akeneo API.");
    }

    file_put_contents('/var/www/html/du/debug.log', "Starting sync for families\n", FILE_APPEND);
    $families = fetchAkeneoPaginatedData("families", $token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, 100, $maxPages);
    file_put_contents('/var/www/html/du/debug.log', "Fetched families: " . json_encode($families) . "\n", FILE_APPEND);
    upsertData($pdo, 'families', $families, 'code');

    file_put_contents('/var/www/html/du/debug.log', "Starting sync for categories\n", FILE_APPEND);
    $categories = fetchAkeneoPaginatedData("categories", $token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, 100, $maxPages);
    file_put_contents('/var/www/html/du/debug.log', "Fetched categories: " . json_encode($categories) . "\n", FILE_APPEND);
    upsertData($pdo, 'categories', $categories, 'code');

    file_put_contents('/var/www/html/du/debug.log', "Starting sync for attributes\n", FILE_APPEND);
    $attributes = fetchAkeneoPaginatedData("attributes", $token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, 100, $maxPages);
    file_put_contents('/var/www/html/du/debug.log', "Fetched attributes: " . json_encode($attributes) . "\n", FILE_APPEND);
    upsertData($pdo, 'attributes', $attributes, 'code');

    file_put_contents('/var/www/html/du/debug.log', "Starting sync for products\n", FILE_APPEND);
    $recordsExported = syncProducts($pdo, $token, $endpointBaseURL, $clientId, $clientSecret, $username, $password, $maxPages);
    file_put_contents('/var/www/html/du/debug.log', "Records exported: $recordsExported\n", FILE_APPEND);

    return $recordsExported;
}


function logExportHistory($pdo, $status, $recordsExported = 0)
{
    $stmt = $pdo->prepare("INSERT INTO export_history (status, records_exported) VALUES (:status, :recordsExported)");
    $stmt->execute([
        ':status' => $status,
        ':recordsExported' => $recordsExported
    ]);
}

try {
    logExportHistory($pdo, 'In Progress');
    $recordsExported = syncAkeneoData($pdo, $endpointBaseURL, $clientId, $clientSecret, $username, $password, 2);
    logExportHistory($pdo, 'Completed', $recordsExported);

    echo json_encode(['success' => true, 'message' => "Export completed successfully. Records exported: $recordsExported."]);
} catch (Exception $e) {
    logExportHistory($pdo, 'Failed');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>