/**
 * API client for attribute comparison module
 * Handles all API requests and responses
 */

// API base paths
const API_PATHS = {
    PIVOTREE: '/du/api/pivotree',
    AKENEO: '/du/api/akeneo'
};

/**
 * Generic fetch function with error handling
 * @param {String} url - URL to fetch
 * @param {Object} options - Fetch options
 * @returns {Promise} - Promise with the response data
 */
async function fetchWithErrorHandling(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`API Error (${response.status}): ${errorText}`);
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Check for API-level errors
        if (data.error) {
            console.error(`API Error: ${data.error}`);
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

/**
 * Load attributes with optional filtering
 * @param {Object} params - Query parameters 
 * @returns {Promise} - Promise with the attributes data
 */
async function loadAttributes(params = {}) {
    const url = new URL(`${API_PATHS.PIVOTREE}/attribute-comparison.php`, window.location.origin);
    
    // Add default parameters
    url.searchParams.append('page', params.page || 1);
    url.searchParams.append('limit', params.limit || 50);
    url.searchParams.append('unique_attributes', 'true');
    
    // Add optional parameters
    if (params.search) {
        url.searchParams.append('search', params.search);
    }
    
    if (params.mappingStatus && params.mappingStatus !== 'all') {
        url.searchParams.append('mapping_status', params.mappingStatus);
    }
    
    if (params.getAllAttributes) {
        url.searchParams.append('all_attributes', 'true');
    }
    
    console.log('Loading attributes with URL:', url.toString());
    return fetchWithErrorHandling(url.toString());
}

/**
 * Load attribute values for a specific attribute
 * @param {String} attributeName - Attribute name
 * @returns {Promise} - Promise with the attribute values data
 */
async function loadAttributeValues(attributeName) {
    if (!attributeName) {
        throw new Error('Attribute name is required');
    }
    
    const url = new URL(`${API_PATHS.PIVOTREE}/attribute-values.php`, window.location.origin);
    url.searchParams.append('attribute_name', attributeName);
    
    return fetchWithErrorHandling(url.toString());
}

/**
 * Load attribute value mappings for a specific attribute
 * @param {String} attributeName - Attribute name
 * @returns {Promise} - Promise with the attribute value mappings data
 */
async function loadValueMappings(attributeName) {
    if (!attributeName) {
        throw new Error('Attribute name is required');
    }
    
    const url = new URL(`${API_PATHS.PIVOTREE}/attribute-value-mappings.php`, window.location.origin);
    url.searchParams.append('attribute_name', attributeName);
    
    return fetchWithErrorHandling(url.toString());
}

/**
 * Save attribute mapping
 * @param {Object} mapping - Attribute mapping data
 * @returns {Promise} - Promise with the save result
 */
async function saveAttributeMapping(mapping) {
    if (!mapping.pivotree_attribute_name) {
        throw new Error('Pivotree attribute name is required');
    }
    
    return fetchWithErrorHandling(`${API_PATHS.PIVOTREE}/attribute-mappings.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(mapping)
    });
}

/**
 * Save attribute value mapping
 * @param {Object} mapping - Attribute value mapping data
 * @returns {Promise} - Promise with the save result
 */
async function saveValueMapping(mapping) {
    if (!mapping.pivotree_attribute_name || !mapping.pivotree_attribute_value) {
        throw new Error('Pivotree attribute name and value are required');
    }
    
    return fetchWithErrorHandling(`${API_PATHS.PIVOTREE}/attribute-value-mappings.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(mapping)
    });
}

/**
 * Load Akeneo attributes
 * @returns {Promise} - Promise with the Akeneo attributes data
 */
async function loadAkeneoAttributes() {
    const url = new URL(`${API_PATHS.AKENEO}/attributes.php`, window.location.origin);
    url.searchParams.append('all_attributes', 'true');
    
    return fetchWithErrorHandling(url.toString());
}

/**
 * Load Akeneo attribute values for a specific attribute
 * @param {String} attributeCode - Akeneo attribute code
 * @returns {Promise} - Promise with the Akeneo attribute options data
 */
async function loadAkeneoAttributeValues(attributeCode) {
    if (!attributeCode) {
        throw new Error('Attribute code is required');
    }
    
    const url = new URL(`${API_PATHS.AKENEO}/attributes.php`, window.location.origin);
    url.searchParams.append('attribute_code', attributeCode);
    url.searchParams.append('all_options', 'true');
    
    return fetchWithErrorHandling(url.toString());
}

/**
 * Delete attribute mapping
 * @param {Number} id - Mapping ID
 * @returns {Promise} - Promise with the delete result
 */
async function deleteAttributeMapping(id) {
    return fetchWithErrorHandling(`${API_PATHS.PIVOTREE}/attribute-mappings.php`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}`
    });
}

/**
 * Delete attribute value mapping
 * @param {Number} id - Mapping ID
 * @returns {Promise} - Promise with the delete result
 */
async function deleteValueMapping(id) {
    return fetchWithErrorHandling(`${API_PATHS.PIVOTREE}/attribute-value-mappings.php`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${id}`
    });
}

/**
 * Load mappings for the mappings tab
 * @param {Object} params - Query parameters
 * @returns {Promise} - Promise with the mappings data
 */
async function loadAllMappings(params = {}) {
    const type = params.type || 'attributes';
    const endpoint = type === 'attributes' ? 'attribute-mappings.php' : 'attribute-value-mappings.php';
    
    const url = new URL(`${API_PATHS.PIVOTREE}/${endpoint}`, window.location.origin);
    
    // Add parameters
    url.searchParams.append('page', params.page || 1);
    url.searchParams.append('limit', params.limit || 50);
    
    if (params.search) {
        url.searchParams.append('search', params.search);
    }
    
    return fetchWithErrorHandling(url.toString());
}

// Export API functions
export default {
    loadAttributes,
    loadAttributeValues,
    loadValueMappings,
    saveAttributeMapping,
    saveValueMapping,
    loadAkeneoAttributes,
    loadAkeneoAttributeValues,
    deleteAttributeMapping,
    deleteValueMapping,
    loadAllMappings
};