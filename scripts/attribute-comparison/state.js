/**
 * State management for the attribute comparison module
 * Centralizes state storage and provides methods to update it
 */

// Private variable to hold the state
let _state = {
    // Attributes tab state
    attributesTab: {
        page: 1,
        limit: 50,
        total: 0,
        search: '',
        mappingStatus: 'all', // 'all', 'mapped', 'unmapped'
        attributes: [],
        filteredAttributes: [], // For client-side filtering
        selectedAttribute: null
    },
    
    // Values tab state
    valuesTab: {
        selectedAttributeName: '',
        selectedAttributeCode: '',
        attributeValues: [],
        originalValues: [], // Store the original values before filtering
        mappingStatus: 'all', // 'all', 'mapped', 'unmapped'
        selectedValue: null
    },
    
    // Mappings tab state
    mappingsTab: {
        type: 'attributes',  // 'attributes' or 'values'
        page: 1,
        limit: 50,
        search: '',
        total: 0,
        mappings: [],
        selectedMappings: []
    },
    
    // Akeneo data (attributes and values)
    akeneo: {
        attributes: [],
        attributeValues: []
    },
    
    // Mappings data (attributes and values)
    mappings: {
        attributes: [],
        values: []
    }
};

// List of subscribers for state updates
const subscribers = [];

/**
 * Subscribe to state changes
 * @param {Function} callback - Function to call when state changes
 * @returns {Function} Unsubscribe function
 */
function subscribe(callback) {
    subscribers.push(callback);
    
    // Return unsubscribe function
    return () => {
        const index = subscribers.indexOf(callback);
        if (index !== -1) {
            subscribers.splice(index, 1);
        }
    };
}

/**
 * Notify all subscribers of state changes
 * @param {Object} updatedState - The updated state
 * @param {String} path - The path that was updated
 */
function notifySubscribers(updatedState, path) {
    subscribers.forEach(callback => callback(updatedState, path));
}

/**
 * Get the current state
 * @returns {Object} The current state
 */
function getState() {
    // Return a clone to prevent direct mutation
    return JSON.parse(JSON.stringify(_state));
}

/**
 * Update the state at a specific path
 * @param {String} path - Dot notation path to update (e.g., 'attributesTab.page')
 * @param {*} value - New value
 */
function updateState(path, value) {
    const pathParts = path.split('.');
    let current = _state;
    
    // Navigate to the nested property
    for (let i = 0; i < pathParts.length - 1; i++) {
        if (current[pathParts[i]] === undefined) {
            current[pathParts[i]] = {};
        }
        current = current[pathParts[i]];
    }
    
    // Update the value
    const lastPart = pathParts[pathParts.length - 1];
    current[lastPart] = value;
    
    // Notify subscribers
    notifySubscribers(getState(), path);
}

/**
 * Reset the state for a specific tab
 * @param {String} tab - Tab name ('attributesTab', 'valuesTab', 'mappingsTab')
 */
function resetTabState(tab) {
    if (!_state[tab]) return;
    
    const initialState = {
        attributesTab: {
            page: 1,
            limit: 50,
            total: 0,
            search: '',
            mappingStatus: 'all',
            attributes: [],
            filteredAttributes: [],
            selectedAttribute: null
        },
        valuesTab: {
            selectedAttributeName: '',
            selectedAttributeCode: '',
            attributeValues: [],
            originalValues: [],
            mappingStatus: 'all',
            selectedValue: null
        },
        mappingsTab: {
            type: 'attributes',
            page: 1,
            limit: 50,
            search: '',
            total: 0,
            mappings: [],
            selectedMappings: []
        }
    };
    
    _state[tab] = { ..._state[tab], ...initialState[tab] };
    notifySubscribers(getState(), tab);
}

// Public API
export default {
    getState,
    updateState,
    resetTabState,
    subscribe
};