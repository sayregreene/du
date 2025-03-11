/**
 * Mappings module for the attribute comparison
 * Handles viewing, editing, and deleting mappings
 */
import state from '../state.js';
import apiClient from '../api-client.js';
import uiUtils from '../ui-utils.js';

// Cache DOM elements
let elements = {};

/**
 * Initialize the mappings module
 */
function initialize() {
    // Cache DOM elements for better performance
    elements = {
        // Mapping type selector
        mappingTypeFilter: uiUtils.getElement('mapping-type-filter'),
        
        // Search
        mappingsSearchInput: uiUtils.getElement('mappings-search-input'),
        mappingsSearchButton: uiUtils.getElement('mappings-search-button'),
        
        // Tables
        attributeMappingsTable: uiUtils.getElement('attribute-mappings-table'),
        valueMappingsTable: uiUtils.getElement('value-mappings-table'),
        attributeMappingsList: uiUtils.getElement('attribute-mappings-list'),
        valueMappingsList: uiUtils.getElement('value-mappings-list'),
        
        // Pagination
        mappingsPrevPageBtn: uiUtils.getElement('mappings-prev-page'),
        mappingsNextPageBtn: uiUtils.getElement('mappings-next-page'),
        mappingsCurrentPageSpan: uiUtils.getElement('mappings-current-page'),
        mappingsTotalPagesSpan: uiUtils.getElement('mappings-total-pages'),
        mappingsShowingStartSpan: uiUtils.getElement('mappings-showing-start'),
        mappingsShowingEndSpan: uiUtils.getElement('mappings-showing-end'),
        totalMappingsSpan: uiUtils.getElement('total-mappings'),
        
        // Buttons
        btnDeleteSelected: uiUtils.getElement('btn-delete-selected')
    };
    
    
    // Initialize mappings state if needed
    initializeMappingsTab();
   
    // Subscribe to state changes
    state.subscribe((newState, path) => {
        if (path.startsWith('mappingsTab')) {
            if (path === 'mappingsTab.mappings') {
                renderMappings();
            }
            if (path === 'mappingsTab.page' || path === 'mappingsTab.total') {
                updateMappingsPagination();
            }
        }
    });
    
    // Set up event listeners
    setupEventListeners();
    
    // Load the mappings
    loadAllMappings();
}

/**
 * Initialize mappings tab state if needed
 */
function initializeMappingsTab() {
    const currentState = state.getState();
    
    if (!currentState.mappingsTab) {
        state.updateState('mappingsTab', {
            type: 'attributes',  // Default to attributes view
            page: 1,
            limit: 50,
            search: '',
            total: 0,
            mappings: [],
            selectedMappings: []
        });
    }
    
    // Toggle mappings tables based on selected type
    toggleMappingsTables();
}

/**
 * Set up event listeners for mappings tab elements
 */
function setupEventListeners() {
    // Mapping type filter
    if (elements.mappingTypeFilter) {
        elements.mappingTypeFilter.addEventListener('change', () => {
            state.updateState('mappingsTab.type', elements.mappingTypeFilter.value);
            state.updateState('mappingsTab.page', 1); // Reset to page 1
            toggleMappingsTables();
            loadAllMappings();
        });
    }
    
    // Search functionality
    if (elements.mappingsSearchButton && elements.mappingsSearchInput) {
        elements.mappingsSearchButton.addEventListener('click', () => {
            state.updateState('mappingsTab.search', elements.mappingsSearchInput.value);
            state.updateState('mappingsTab.page', 1); // Reset to page 1
            loadAllMappings();
        });
        
        elements.mappingsSearchInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                state.updateState('mappingsTab.search', elements.mappingsSearchInput.value);
                state.updateState('mappingsTab.page', 1); // Reset to page 1
                loadAllMappings();
            }
        });
    }
    
    // Pagination
    if (elements.mappingsPrevPageBtn) {
        elements.mappingsPrevPageBtn.addEventListener('click', () => {
            const currentState = state.getState();
            if (currentState.mappingsTab.page > 1) {
                state.updateState('mappingsTab.page', currentState.mappingsTab.page - 1);
                loadAllMappings();
            }
        });
    }
    
    if (elements.mappingsNextPageBtn) {
        elements.mappingsNextPageBtn.addEventListener('click', () => {
            const currentState = state.getState();
            const totalPages = Math.ceil(currentState.mappingsTab.total / currentState.mappingsTab.limit);
            
            if (currentState.mappingsTab.page < totalPages) {
                state.updateState('mappingsTab.page', currentState.mappingsTab.page + 1);
                loadAllMappings();
            }
        });
    }
    
    // Delete selected button
    if (elements.btnDeleteSelected) {
        elements.btnDeleteSelected.addEventListener('click', () => {
            const currentState = state.getState();
            if (currentState.mappingsTab.selectedMappings.length === 0) {
                uiUtils.showAlert('Please select mappings to delete first.');
                return;
            }
            
            if (uiUtils.confirmDialog(`Are you sure you want to delete ${currentState.mappingsTab.selectedMappings.length} selected mappings?`)) {
                deleteSelectedMappings();
            }
        });
    }
}

/**
 * Toggle visibility of mappings tables based on the selected type
 */
function toggleMappingsTables() {
    if (!elements.attributeMappingsTable || !elements.valueMappingsTable) return;
    
    const currentState = state.getState();
    console.log('Toggling mapping tables, current type:', currentState.mappingsTab.type);
    
    if (currentState.mappingsTab.type === 'attributes') {
        elements.attributeMappingsTable.classList.remove('d-none');
        elements.valueMappingsTable.classList.add('d-none');
    } else {
        elements.attributeMappingsTable.classList.add('d-none');
        elements.valueMappingsTable.classList.remove('d-none');
    }
}

/**
 * Load all mappings for viewing
 */
async function loadAllMappings() {
    uiUtils.showLoading();
    
    try {
        const currentState = state.getState();
        
        // Clear selected mappings
        state.updateState('mappingsTab.selectedMappings', []);
        
        // Prepare parameters
        const params = {
            type: currentState.mappingsTab.type,
            page: currentState.mappingsTab.page,
            limit: currentState.mappingsTab.limit,
            search: currentState.mappingsTab.search
        };
        
        // Load mappings
        const response = await apiClient.loadAllMappings(params);
        
        // Update state
        state.updateState('mappingsTab.mappings', response.mappings || []);
        state.updateState('mappingsTab.total', response.total || state.mappingsTab.mappings.length || 0);
        
        // If we got page info from the server, use it
        if (response.page) state.updateState('mappingsTab.page', response.page);
        if (response.limit) state.updateState('mappingsTab.limit', response.limit);
        
        // Render mappings and update pagination
        renderMappings();
        updateMappingsPagination();
        
    } catch (error) {
        console.error('Error loading mappings:', error);
        
        // Show error message in the appropriate list
        const errorMessage = `Error loading mappings: ${error.message}`;
        
        if (elements.attributeMappingsList && state.getState().mappingsTab.type === 'attributes') {
            elements.attributeMappingsList.innerHTML = `
                <tr><td colspan="5" class="text-center text-red py-3">${errorMessage}</td></tr>
            `;
        } else if (elements.valueMappingsList) {
            elements.valueMappingsList.innerHTML = `
                <tr><td colspan="6" class="text-center text-red py-3">${errorMessage}</td></tr>
            `;
        }
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Render the mappings in the appropriate table
 */
function renderMappings() {
    const currentState = state.getState();
    
    if (currentState.mappingsTab.type === 'attributes') {
        renderAttributeMappings();
    } else {
        renderValueMappings();
    }
}

/**
 * Render attribute mappings
 */
function renderAttributeMappings() {
    if (!elements.attributeMappingsList) return;
    
    const currentState = state.getState();
    const mappings = currentState.mappingsTab.mappings;
    
    // Clear the table
    elements.attributeMappingsList.innerHTML = '';
    
    if (!mappings || mappings.length === 0) {
        elements.attributeMappingsList.innerHTML = `
            <tr><td colspan="5" class="text-center py-3">No attribute mappings found</td></tr>
        `;
        return;
    }
    
    // Add each mapping to the table
    mappings.forEach(mapping => {
        const row = document.createElement('tr');
        
        // Determine the attribute code and type to display
        const akeneoAttribute = mapping.is_new_attribute
            ? mapping.new_attribute_code
            : mapping.akeneo_attribute_code;
        
        const attributeType = mapping.is_new_attribute
            ? mapping.new_attribute_type || 'new attribute'
            : 'existing';
        
        const statusClass = mapping.is_new_attribute
            ? 'Label--yellow'
            : 'Label--green';
        
        const statusText = mapping.is_new_attribute
            ? 'New'
            : 'Mapped';
        
        row.innerHTML = `
            <td class="px-3 py-2">
                <input type="checkbox" class="mapping-checkbox mr-2" data-id="${mapping.id}" data-type="attribute">
                ${mapping.pivotree_attribute_name}
            </td>
            <td class="px-3 py-2">${akeneoAttribute || ''}</td>
            <td class="px-3 py-2">${attributeType}</td>
            <td class="px-3 py-2">
                <span class="Label ${statusClass}">${statusText}</span>
            </td>
            <td class="px-3 py-2">
                <button class="btn btn-sm btn-outline btn-edit-mapping" 
                  data-id="${mapping.id}" data-type="attribute">
                    Edit
                </button>
                <button class="btn btn-sm btn-danger btn-delete-mapping" 
                  data-id="${mapping.id}" data-type="attribute">
                    Delete
                </button>
            </td>
        `;
        
        elements.attributeMappingsList.appendChild(row);
    });
    
    // Add event listeners for checkboxes
    document.querySelectorAll('.mapping-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const mappingId = this.getAttribute('data-id');
            const selectedMappings = [...state.getState().mappingsTab.selectedMappings];
            
            if (this.checked) {
                if (!selectedMappings.includes(mappingId)) {
                    selectedMappings.push(mappingId);
                }
            } else {
                const index = selectedMappings.indexOf(mappingId);
                if (index !== -1) {
                    selectedMappings.splice(index, 1);
                }
            }
            
            state.updateState('mappingsTab.selectedMappings', selectedMappings);
        });
    });
    
    // Add event listeners for edit buttons
    document.querySelectorAll('.btn-edit-mapping').forEach(btn => {
        btn.addEventListener('click', function() {
            const mappingId = this.getAttribute('data-id');
            const mappingType = this.getAttribute('data-type');
            
            editMapping(mappingId, mappingType);
        });
    });
    
    // Add event listeners for delete buttons
    document.querySelectorAll('.btn-delete-mapping').forEach(btn => {
        btn.addEventListener('click', function() {
            const mappingId = this.getAttribute('data-id');
            const mappingType = this.getAttribute('data-type');
            
            if (uiUtils.confirmDialog('Are you sure you want to delete this mapping?')) {
                deleteMapping(mappingId, mappingType);
            }
        });
    });
}

/**
 * Render value mappings
 */
function renderValueMappings() {
    if (!elements.valueMappingsList) return;
    
    const currentState = state.getState();
    const mappings = currentState.mappingsTab.mappings;
    
    // Clear the table
    elements.valueMappingsList.innerHTML = '';
    
    if (mappings.length === 0) {
        elements.valueMappingsList.innerHTML = `
            <tr><td colspan="6" class="text-center py-3">No value mappings found</td></tr>
        `;
        return;
    }
    
    // Add each mapping to the table
    mappings.forEach(mapping => {
        const row = document.createElement('tr');
        
        // Determine the value code to display
        const akeneoValue = mapping.is_new_value
            ? mapping.new_value_code
            : mapping.akeneo_value_code;
        
        const statusClass = mapping.is_new_value
            ? 'Label--yellow'
            : 'Label--green';
        
        const statusText = mapping.is_new_value
            ? 'New'
            : 'Mapped';
        
        row.innerHTML = `
            <td class="px-3 py-2">${mapping.pivotree_attribute_name}</td>
            <td class="px-3 py-2">${mapping.pivotree_attribute_value}${mapping.pivotree_uom ? ` (${mapping.pivotree_uom})` : ''}</td>
            <td class="px-3 py-2">${mapping.akeneo_attribute_code || ''}</td>
            <td class="px-3 py-2">${akeneoValue || ''}</td>
            <td class="px-3 py-2">
                <span class="Label ${statusClass}">${statusText}</span>
            </td>
            <td class="px-3 py-2">
                <div class="BtnGroup">
                    <button class="BtnGroup-item btn btn-sm btn-edit-mapping" 
                      data-id="${mapping.id}" data-type="value">
                        Edit
                    </button>
                    <button class="BtnGroup-item btn btn-sm btn-danger btn-delete-mapping" 
                      data-id="${mapping.id}" data-type="value">
                        Delete
                    </button>
                </div>
            </td>
        `;
        
        elements.valueMappingsList.appendChild(row);
    });
    
    // Add event listeners for edit buttons
    document.querySelectorAll('.btn-edit-mapping').forEach(btn => {
        btn.addEventListener('click', function() {
            const mappingId = this.getAttribute('data-id');
            const mappingType = this.getAttribute('data-type');
            
            editMapping(mappingId, mappingType);
        });
    });
    
    // Add event listeners for delete buttons
    document.querySelectorAll('.btn-delete-mapping').forEach(btn => {
        btn.addEventListener('click', function() {
            const mappingId = this.getAttribute('data-id');
            const mappingType = this.getAttribute('data-type');
            
            if (uiUtils.confirmDialog('Are you sure you want to delete this mapping?')) {
                deleteMapping(mappingId, mappingType);
            }
        });
    });
}

/**
 * Update mappings pagination information
 */
function updateMappingsPagination() {
    if (!elements.mappingsCurrentPageSpan || !elements.mappingsTotalPagesSpan || !elements.mappingsShowingStartSpan ||
        !elements.mappingsShowingEndSpan || !elements.totalMappingsSpan || !elements.mappingsPrevPageBtn || !elements.mappingsNextPageBtn) {
        return;
    }
    
    const currentState = state.getState();
    
    // Ensure we have valid values
    const limit = Math.max(1, currentState.mappingsTab.limit || 50);
    const total = Math.max(0, currentState.mappingsTab.total || 0);
    const page = Math.max(1, currentState.mappingsTab.page || 1);
    
    // Calculate pagination safely
    const totalPages = Math.max(1, Math.ceil(total / limit) || 1);
    
    let showingStart = 0;
    let showingEnd = 0;
    
    if (total > 0) {
        showingStart = (page - 1) * limit + 1;
        showingEnd = Math.min(page * limit, total);
    }
    
    // Update UI
    elements.mappingsCurrentPageSpan.textContent = page;
    elements.mappingsTotalPagesSpan.textContent = totalPages;
    elements.mappingsShowingStartSpan.textContent = showingStart;
    elements.mappingsShowingEndSpan.textContent = showingEnd;
    elements.totalMappingsSpan.textContent = total;
    
    // Update button states
    elements.mappingsPrevPageBtn.disabled = page <= 1;
    elements.mappingsNextPageBtn.disabled = page >= totalPages;
    
    // Debug log
    console.log('Mappings pagination updated:', {
        page: page,
        limit: limit,
        total: total,
        totalPages: totalPages,
        showing: `${showingStart}-${showingEnd}`
    });
}

/**
 * Edit a mapping
 * @param {String} mappingId - ID of the mapping to edit
 * @param {String} mappingType - Type of mapping ('attribute' or 'value')
 */
function editMapping(mappingId, mappingType) {
    const currentState = state.getState();
    
    // Get the main entry point to handle tab switching
    const mainModule = window.attributeComparisonModule;
    
    if (mappingType === 'attribute') {
        // Find the mapping in the state
        const mapping = currentState.mappingsTab.mappings.find(m => m.id == mappingId);
        
        if (mapping) {
            // Switch to the attribute mapping tab
            if (mainModule && typeof mainModule.showTab === 'function') {
                mainModule.showTab('attributes');
            }
            
            // Import the attributes module dynamically
            import('./attributes.js').then(attributesModule => {
                // Select the attribute to edit
                attributesModule.default.selectAttribute(mapping.pivotree_attribute_name, 0);
            });
        }
    } else if (mappingType === 'value') {
        // Find the mapping in the state
        const mapping = currentState.mappingsTab.mappings.find(m => m.id == mappingId);
        
        if (mapping) {
            // Switch to the value mapping tab
            if (mainModule && typeof mainModule.showTab === 'function') {
                mainModule.showTab('values');
            }
            
            // Import the values module dynamically
            import('./values.js').then(valuesModule => {
                // Update state with the attribute name
                state.updateState('valuesTab.selectedAttributeName', mapping.pivotree_attribute_name);
                
                // Load attribute values and then select the value to edit
                valuesModule.default.loadAttributeValues(mapping.pivotree_attribute_name).then(() => {
                    valuesModule.default.selectAttributeValue(
                        mapping.pivotree_attribute_value, 
                        mapping.pivotree_uom || ''
                    );
                });
            });
        }
    }
}

/**
 * Delete a mapping
 * @param {String} mappingId - ID of the mapping to delete
 * @param {String} mappingType - Type of mapping ('attribute' or 'value')
 */
async function deleteMapping(mappingId, mappingType) {
    uiUtils.showLoading();
    
    try {
        if (mappingType === 'attribute') {
            await apiClient.deleteAttributeMapping(mappingId);
        } else {
            await apiClient.deleteValueMapping(mappingId);
        }
        
        // Show success message
        uiUtils.showAlert('Mapping deleted successfully!');
        
        // Reload mappings to update the UI
        loadAllMappings();
        
    } catch (error) {
        console.error('Error deleting mapping:', error);
        uiUtils.showAlert('Error deleting mapping. Please try again.');
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Delete selected mappings
 */
async function deleteSelectedMappings() {
    const currentState = state.getState();
    if (currentState.mappingsTab.selectedMappings.length === 0) {
        return;
    }
    
    uiUtils.showLoading();
    
    try {
        // Create promises for all delete operations
        const deletePromises = currentState.mappingsTab.selectedMappings.map(mappingId => {
            if (currentState.mappingsTab.type === 'attributes') {
                return apiClient.deleteAttributeMapping(mappingId);
            } else {
                return apiClient.deleteValueMapping(mappingId);
            }
        });
        
        // Execute all delete operations
        await Promise.all(deletePromises);
        
        // Show success message
        uiUtils.showAlert(`Successfully deleted ${deletePromises.length} mappings.`);
        
        // Clear selected mappings
        state.updateState('mappingsTab.selectedMappings', []);
        
        // Reload mappings to update the UI
        loadAllMappings();
        
    } catch (error) {
        console.error('Error deleting mappings:', error);
        uiUtils.showAlert(`Error deleting mappings: ${error.message}`);
    } finally {
        uiUtils.hideLoading();
    }
}

// Export module functions
export default {
    initialize,
    loadAllMappings,
    editMapping,
    deleteMapping
};