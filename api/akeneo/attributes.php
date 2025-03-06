<?php
// /var/www/html/du/api/akeneo/attributes.php
header('Content-Type: application/json');

// Include the configuration file
require_once '../../config.php';

// Get query parameters
$attributeCode = isset($_GET['attribute_code']) ? $_GET['attribute_code'] : null;
$getAllOptions = isset($_GET['all_options']) && $_GET['all_options'] === 'true';
$getAllAttributes = isset($_GET['all_attributes']) && $_GET['all_attributes'] === 'true';

// Initialize response arrays
$response = [
    'attributes' => [],
    'options' => []
];



// Use actual Akeneo API
try {
    // Get Akeneo token
    $token = getAkeneoToken($baseUrl, $clientId, $clientSecret, $username, $password);

    if ($attributeCode) {
        // Get options for a specific attribute
        $response['options'] = getAkeneoAttributeOptions($baseUrl, $token, $attributeCode, $getAllOptions);
    } else {
        // Get all attributes
        $response['attributes'] = getAkeneoAttributes($baseUrl, $token, $getAllAttributes);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch from Akeneo API: ' . $e->getMessage()]);
    exit;
}


// Return response
echo json_encode($response);

/**
 * Get authentication token from Akeneo
 */
function getAkeneoToken($baseUrl, $clientId, $clientSecret, $username, $password)
{
    $url = "$baseUrl/api/oauth/v1/token";

    $data = [
        'grant_type' => 'password',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'username' => $username,
        'password' => $password
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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
 * Get attributes from Akeneo
 */
function getAkeneoAttributes($baseUrl, $token, $getAllAttributes = false)
{
    // If requesting all attributes, we need to paginate to get everything
    $limit = $getAllAttributes ? 100 : 100; // Default to 100, we can paginate if needed
    $attributes = [];
    $page = 1;
    $hasMore = true;

    while ($hasMore) {
        $url = "$baseUrl/api/rest/v1/attributes?limit=$limit&page=$page&with_count=true";

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
            throw new Exception('Invalid Akeneo attributes response');
        }

        // Format the response to match our needs
        foreach ($data['_embedded']['items'] as $item) {
            $attributes[] = [
                'code' => $item['code'],
                'type' => $item['type'],
                'group' => $item['group'],
                'label' => $item['labels']['en_US'] ?? $item['code'],
                'localizable' => $item['localizable'],
                'scopable' => $item['scopable']
            ];
        }

        // Check if we need to fetch more pages
        if ($getAllAttributes) {
            // Check if there are more pages
            if (isset($data['_links']['next'])) {
                $page++;
            } else {
                $hasMore = false;
            }
        } else {
            // Only fetch one page if not requesting all
            $hasMore = false;
        }
    }

    return $attributes;
}

/**
 * Get attribute options from Akeneo
 */
function getAkeneoAttributeOptions($baseUrl, $token, $attributeCode, $getAllOptions = false)
{
    // If requesting all options, we need to paginate to get everything
    $limit = $getAllOptions ? 100 : 100; // Default to 100, we can paginate if needed
    $options = [];
    $page = 1;
    $hasMore = true;

    while ($hasMore) {
        $url = "$baseUrl/api/rest/v1/attributes/$attributeCode/options?limit=$limit&page=$page&with_count=true";

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

        // Some attributes might not have options (e.g., text fields)
        if (!isset($data['_embedded']['items'])) {
            return [];
        }

        // Format the response to match our needs
        foreach ($data['_embedded']['items'] as $item) {
            $options[] = [
                'code' => $item['code'],
                'label' => $item['labels']['en_US'] ?? $item['code'],
                'attributeCode' => $attributeCode
            ];
        }

        // Check if we need to fetch more pages
        if ($getAllOptions) {
            // Check if there are more pages
            if (isset($data['_links']['next'])) {
                $page++;
            } else {
                $hasMore = false;
            }
        } else {
            // Only fetch one page if not requesting all
            $hasMore = false;
        }
    }

    return $options;
}

/**
 * Get mock data for testing
 */
function getMockData($attributeCode, $getAllOptions = false, $getAllAttributes = false)
{
    // Mock attributes - create a more extensive list if $getAllAttributes is true
    $attributes = [
        [
            'code' => 'color',
            'type' => 'pim_catalog_simpleselect',
            'group' => 'general',
            'label' => 'Color',
            'localizable' => false,
            'scopable' => false
        ],
        [
            'code' => 'size',
            'type' => 'pim_catalog_simpleselect',
            'group' => 'general',
            'label' => 'Size',
            'localizable' => false,
            'scopable' => false
        ],
        [
            'code' => 'weight',
            'type' => 'pim_catalog_metric',
            'group' => 'technical',
            'label' => 'Weight',
            'localizable' => false,
            'scopable' => false
        ],
        [
            'code' => 'description',
            'type' => 'pim_catalog_textarea',
            'group' => 'marketing',
            'label' => 'Description',
            'localizable' => true,
            'scopable' => true
        ],
        [
            'code' => 'brand',
            'type' => 'pim_catalog_simpleselect',
            'group' => 'general',
            'label' => 'Brand',
            'localizable' => false,
            'scopable' => false
        ],
        [
            'code' => 'material',
            'type' => 'pim_catalog_simpleselect',
            'group' => 'technical',
            'label' => 'Material',
            'localizable' => false,
            'scopable' => false
        ]
    ];

    // Add more attributes if requested
    if ($getAllAttributes) {
        for ($i = 1; $i <= 50; $i++) {
            $attributes[] = [
                'code' => 'attribute_' . $i,
                'type' => 'pim_catalog_text',
                'group' => 'other',
                'label' => 'Additional Attribute ' . $i,
                'localizable' => false,
                'scopable' => false
            ];
        }
    }

    // Mock options
    $options = [
        'color' => generateColorOptions($getAllOptions),
        'size' => generateSizeOptions($getAllOptions),
        'brand' => [
            ['code' => 'acme', 'label' => 'ACME', 'attributeCode' => 'brand'],
            ['code' => 'globex', 'label' => 'Globex', 'attributeCode' => 'brand'],
            ['code' => 'initech', 'label' => 'Initech', 'attributeCode' => 'brand'],
            ['code' => 'umbrella', 'label' => 'Umbrella', 'attributeCode' => 'brand']
        ],
        'material' => [
            ['code' => 'cotton', 'label' => 'Cotton', 'attributeCode' => 'material'],
            ['code' => 'leather', 'label' => 'Leather', 'attributeCode' => 'material'],
            ['code' => 'silk', 'label' => 'Silk', 'attributeCode' => 'material'],
            ['code' => 'wool', 'label' => 'Wool', 'attributeCode' => 'material'],
            ['code' => 'polyester', 'label' => 'Polyester', 'attributeCode' => 'material']
        ]
    ];

    // Prepare the response
    $result = [
        'attributes' => $attributes,
        'options' => isset($attributeCode) && isset($options[$attributeCode]) ? $options[$attributeCode] : []
    ];

    return $result;
}

/**
 * Generate a list of color options for mock data
 */
function generateColorOptions($getAllOptions)
{
    $basicColors = [
        ['code' => 'red', 'label' => 'Red', 'attributeCode' => 'color'],
        ['code' => 'blue', 'label' => 'Blue', 'attributeCode' => 'color'],
        ['code' => 'green', 'label' => 'Green', 'attributeCode' => 'color'],
        ['code' => 'yellow', 'label' => 'Yellow', 'attributeCode' => 'color'],
        ['code' => 'black', 'label' => 'Black', 'attributeCode' => 'color'],
        ['code' => 'white', 'label' => 'White', 'attributeCode' => 'color']
    ];

    if (!$getAllOptions) {
        return $basicColors;
    }

    // Generate more colors for a complete list
    $extendedColors = $basicColors;

    $colorVariants = [
        'light_blue',
        'dark_blue',
        'navy_blue',
        'sky_blue',
        'turquoise',
        'dark_red',
        'light_red',
        'crimson',
        'maroon',
        'burgundy',
        'light_green',
        'dark_green',
        'olive',
        'lime',
        'forest_green',
        'orange',
        'pink',
        'purple',
        'lavender',
        'indigo',
        'brown',
        'tan',
        'beige',
        'khaki',
        'cream',
        'gray',
        'silver',
        'gold',
        'bronze',
        'copper'
    ];

    foreach ($colorVariants as $colorCode) {
        $label = str_replace('_', ' ', ucwords($colorCode, '_'));
        $extendedColors[] = [
            'code' => $colorCode,
            'label' => $label,
            'attributeCode' => 'color'
        ];
    }

    return $extendedColors;
}

/**
 * Generate a list of size options for mock data
 */
function generateSizeOptions($getAllOptions)
{
    $basicSizes = [
        ['code' => 'xs', 'label' => 'Extra Small', 'attributeCode' => 'size'],
        ['code' => 's', 'label' => 'Small', 'attributeCode' => 'size'],
        ['code' => 'm', 'label' => 'Medium', 'attributeCode' => 'size'],
        ['code' => 'l', 'label' => 'Large', 'attributeCode' => 'size'],
        ['code' => 'xl', 'label' => 'Extra Large', 'attributeCode' => 'size'],
        ['code' => 'xxl', 'label' => 'Double Extra Large', 'attributeCode' => 'size']
    ];

    if (!$getAllOptions) {
        return $basicSizes;
    }

    // Generate more sizes for a complete list
    $extendedSizes = $basicSizes;

    $numericSizes = ['0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20'];
    $euSizes = ['34', '36', '38', '40', '42', '44', '46', '48', '50', '52'];
    $footwearSizes = ['5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '12', '13'];

    foreach ($numericSizes as $size) {
        $extendedSizes[] = [
            'code' => 'us_' . $size,
            'label' => 'US ' . $size,
            'attributeCode' => 'size'
        ];
    }

    foreach ($euSizes as $size) {
        $extendedSizes[] = [
            'code' => 'eu_' . $size,
            'label' => 'EU ' . $size,
            'attributeCode' => 'size'
        ];
    }

    foreach ($footwearSizes as $size) {
        $extendedSizes[] = [
            'code' => 'shoe_' . str_replace('.', '_', $size),
            'label' => 'Shoe ' . $size,
            'attributeCode' => 'size'
        ];
    }

    return $extendedSizes;
}