/**
 * Attributes module for the attribute comparison
 * Handles attribute listing, filtering, and mapping
 */
import state from '../state.js';
import apiClient from '../api-client.js';
import uiUtils from '../ui-utils.js';

// Cache DOM elements
let elements = {};

/**
 * Initialize the attributes module
 */
function initialize() {
    // Cache DOM elements for better performance
    elements = {
        attributesList: uiUtils.getElement('attributes-list'),
        searchInput: uiUtils.getElement('search-input'),
        searchButton: uiUtils.getElement('search-button'),
        mappingStatusFilter: uiUtils.getElement('mapping-status-filter'),
        prevPageBtn: uiUtils.getElement('prev-page'),
        nextPageBtn: uiUtils.getElement('next-page'),
        currentPageSpan: uiUtils.getElement('current-page'),
        totalPagesSpan: uiUtils.getElement('total-pages'),
        showingStartSpan: uiUtils.getElement('showing-start'),
        showingEndSpan: uiUtils.getElement('showing-end'),
        totalAttributesSpan: uiUtils.getElement('total-attributes'),
        
        // Attribute mapping form elements
        attributeMappingForm: document.querySelector('.attribute-mapping-form'),
        selectAttributeMessage: document.querySelector('.select-attribute-message'),
        pivotreeAttributeName: uiUtils.getElement('pivotree-attribute-name'),
        pivotreeValueCount: uiUtils.getElement('pivotree-value-count'),
        akeneoAttribute: uiUtils.getElement('akeneo-attribute'),
        newAttributeSection: uiUtils.getElement('new-attribute-section'),
        btnNewAttribute: uiUtils.getElement('btn-new-attribute'),
        btnSaveAttributeMapping: uiUtils.getElement('btn-save-attribute-mapping'),
        
        // New attribute form elements
        newAttributeCode: uiUtils.getElement('new-attribute-code'),
        newAttributeLabel: uiUtils.getElement('new-attribute-label'),
        newAttributeType: uiUtils.getElement('new-attribute-type'),
        
        // Recent mappings
        recentMappings: uiUtils.getElement('recent-mappings')
    };
    
    // Subscribe to state changes to update UI when needed
    state.subscribe((newState, path) => {
        if (path.startsWith('attributesTab')) {
            if (path === 'attributesTab.attributes') {
                renderAttributes();
            }
            if (path === 'attributesTab.page' || path === 'attributesTab.total') {
                updatePagination();
            }
        }
    });
    
    // Initialize event listeners
    setupEventListeners();
    
    // Load Akeneo attributes for the dropdown
    loadAkeneoAttributes();
    
    // Load attributes
    loadAttributes();
}

/**
 * Set up event listeners for attribute tab elements
 */
function setupEventListeners() {
    // Search functionality
    if (elements.searchButton && elements.searchInput) {
        elements.searchButton.addEventListener('click', handleSearch);
        elements.searchInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
    }
    
    // Filter by mapping status
    if (elements.mappingStatusFilter) {
        elements.mappingStatusFilter.addEventListener('change', handleMappingStatusChange);
    }
    
    // Pagination
    if (elements.prevPageBtn) {
        elements.prevPageBtn.addEventListener('click', () => {
            const currentState = state.getState();
            if (currentState.attributesTab.page > 1) {
                state.updateState('attributesTab.page', currentState.attributesTab.page - 1);
                loadAttributes();
            }
        });
    }
    
    if (elements.nextPageBtn) {
        elements.nextPageBtn.addEventListener('click', () => {
            const currentState = state.getState();
            const totalPages = Math.ceil(currentState.attributesTab.total / currentState.attributesTab.limit);
            
            if (currentState.attributesTab.page < totalPages) {
                state.updateState('attributesTab.page', currentState.attributesTab.page + 1);
                loadAttributes();
            }
        });
    }
    
    // New attribute button
    if (elements.btnNewAttribute) {
        elements.btnNewAttribute.addEventListener('click', handleNewAttributeClick);
    }
    
    // Save attribute mapping button
    if (elements.btnSaveAttributeMapping) {
        elements.btnSaveAttributeMapping.addEventListener('click', saveAttributeMapping);
    }
}

/**
 * Handle search input
 */
function handleSearch() {
    if (!elements.searchInput) return;
    
    const searchValue = elements.searchInput.value;
    state.updateState('attributesTab.search', searchValue);
    state.updateState('attributesTab.page', 1); // Reset to page 1
    loadAttributes();
}

/**
 * Handle mapping status filter change
 */
function handleMappingStatusChange() {
    if (!elements.mappingStatusFilter) return;
    
    const filterValue = elements.mappingStatusFilter.value;
    state.updateState('attributesTab.mappingStatus', filterValue);
    state.updateState('attributesTab.page', 1); // Reset to page 1
    loadAttributes();
}

/**
 * Load attributes from the API
 */
async function loadAttributes() {
    uiUtils.showLoading('Loading attributes...');
    
    try {
        const currentState = state.getState();
        
        const params = {
            page: currentState.attributesTab.page,
            limit: currentState.attributesTab.limit,
            search: currentState.attributesTab.search,
            mappingStatus: currentState.attributesTab.mappingStatus,
            getAllAttributes: !currentState.akeneo.attributes?.length
        };
        
        const response = await apiClient.loadAttributes(params);
        
        // Update state with response data
        state.updateState('attributesTab.attributes', response.attributes || []);
        state.updateState('attributesTab.total', response.total || 0);
        
        // Only update Akeneo attributes if we received them and they're not already loaded
        if (response.akeneo?.attributes && response.akeneo.attributes.length > 0) {
            state.updateState('akeneo.attributes', response.akeneo.attributes);
            populateAkeneoAttributeDropdown();
        }
        
        // Update mappings
        if (response.mappings) {
            state.updateState('mappings.attributes', response.mappings.attributes || []);
            state.updateState('mappings.values', response.mappings.values || []);
        }
        
        // Render attributes
        renderAttributes();
        updatePagination();
        
    } catch (error) {
        console.error('Error loading attributes:', error);
        
        if (elements.attributesList) {
            elements.attributesList.innerHTML = `
                <tr><td colspan="4" class="text-center text-red">
                    Error loading attributes: ${error.message}. Please try again.
                </td></tr>
            `;
        }
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Load Akeneo attributes for the dropdown
 */
async function loadAkeneoAttributes() {
    console.log('Loading Akeneo attributes...');
    
    try {
        uiUtils.showLoading();
        
        const response = await apiClient.loadAkeneoAttributes();
        
        if (response.attributes && Array.isArray(response.attributes)) {
            state.updateState('akeneo.attributes', response.attributes);
            populateAkeneoAttributeDropdown();
        } else {
            console.error('Unexpected Akeneo attributes data format:', response);
        }
    } catch (error) {
        console.error('Error loading Akeneo attributes:', error);
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Populate the Akeneo attribute dropdown
 */
function populateAkeneoAttributeDropdown() {
    if (!elements.akeneoAttribute) {
        console.error('akeneoAttribute element not found');
        return;
    }
    
    const currentState = state.getState();
    const akeneoAttributes = currentState.akeneo.attributes;
    
    console.log('Populating Akeneo attribute dropdown with', akeneoAttributes.length, 'attributes');
    
    // Clear existing options except the default one
    elements.akeneoAttribute.innerHTML = '<option value="">-- Select Existing Attribute --</option>';
    
    if (!akeneoAttributes || !Array.isArray(akeneoAttributes) || akeneoAttributes.length === 0) {
        // Add a disabled option indicating no attributes are available
        const option = document.createElement('option');
        option.disabled = true;
        option.textContent = 'No Akeneo attributes available';
        elements.akeneoAttribute.appendChild(option);
        return;
    }
    
    // Add options for each Akeneo attribute
    akeneoAttributes.forEach(attr => {
        const option = document.createElement('option');
        option.value = attr.code;
        option.textContent = `${attr.label || attr.code} (${attr.code})`;
        elements.akeneoAttribute.appendChild(option);
    });
}

/**
 * Render attributes table
 */
function renderAttributes() {
    if (!elements.attributesList) {
        console.error('Missing attributesList element');
        return;
    }
    
    const currentState = state.getState();
    const attributes = currentState.attributesTab.attributes;
    
    // Clear the table
    elements.attributesList.innerHTML = '';
    
    // Check if we have any attributes
    if (!attributes || attributes.length === 0) {
        const emptyMessage = currentState.attributesTab.mappingStatus !== 'all' ?
            `No ${currentState.attributesTab.mappingStatus} attributes found` :
            'No attributes found';
            
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="4" class="text-center p-3">${emptyMessage}</td>
        `;
        elements.attributesList.appendChild(row);
        return;
    }
    
    // Add each attribute to the table
    attributes.forEach(attr => {
        const row = document.createElement('tr');
        
        // Check if this attribute exists in Akeneo
        const akeneoStatus = checkAkeneoStatus(attr.attribute_name);
        
        row.innerHTML = `
            <td class="px-3 py-2">${attr.attribute_name}</td>
            <td class="px-3 py-2 text-right">${attr.value_count || 0}</td>
            <td class="px-3 py-2">
                <span class="Label Label--${akeneoStatus.color}">${akeneoStatus.label}</span>
            </td>
            <td class="px-3 py-2">
                <button class="btn btn-sm btn-outline btn-select-attr" 
                  data-name="${attr.attribute_name}"
                  data-count="${attr.value_count || 0}">
                    Map
                </button>
            </td>
        `;
        
        elements.attributesList.appendChild(row);
    });
    
    // Add event listeners to the select buttons
    document.querySelectorAll('.btn-select-attr').forEach(btn => {
        btn.addEventListener('click', function() {
            const attributeName = this.getAttribute('data-name');
            const valueCount = this.getAttribute('data-count');
            selectAttribute(attributeName, valueCount);
        });
    });
}

/**
 * Check if an attribute exists in Akeneo or is already mapped
 * @param {String} attributeName - Name of the attribute to check
 * @returns {Object} Status object with label and color
 */
function checkAkeneoStatus(attributeName) {
    const currentState = state.getState();
    
    // Check if the attribute has been mapped
    const attributeMapping = currentState.mappings.attributes.find(
        mapping => mapping.pivotree_attribute_name === attributeName
    );
    
    if (attributeMapping) {
        // Attribute has been mapped
        return { label: 'Mapped', color: 'green' };
    }
    
    // Does the attribute exist in Akeneo?
    const attributeCode = uiUtils.formatAttributeCode(attributeName);
    const attributeExists = currentState.akeneo.attributes.some(
        akeneoAttr => akeneoAttr.code === attributeCode
    );
    
    if (attributeExists) {
        return { label: 'Exists in Akeneo', color: 'yellow' };
    }
    
    // Not mapped and doesn't exist in Akeneo
    return { label: 'Not Mapped', color: 'red' };
}

/**
 * Update pagination information
 */
function updatePagination() {
    if (!elements.currentPageSpan || !elements.totalPagesSpan || !elements.showingStartSpan ||
        !elements.showingEndSpan || !elements.totalAttributesSpan || !elements.prevPageBtn || !elements.nextPageBtn) {
        console.error('Missing pagination DOM elements');
        return;
    }
    
    const currentState = state.getState();
    
    // Calculate pagination values
    const totalPages = Math.ceil(currentState.attributesTab.total / currentState.attributesTab.limit);
    const showingStart = currentState.attributesTab.total > 0 ? 
        (currentState.attributesTab.page - 1) * currentState.attributesTab.limit + 1 : 0;
    const showingEnd = Math.min(
        currentState.attributesTab.page * currentState.attributesTab.limit, 
        currentState.attributesTab.total
    );
    
    // Update the UI
    elements.currentPageSpan.textContent = currentState.attributesTab.page;
    elements.totalPagesSpan.textContent = totalPages;
    elements.showingStartSpan.textContent = showingStart;
    elements.showingEndSpan.textContent = showingEnd;
    elements.totalAttributesSpan.textContent = currentState.attributesTab.total;
    
    // Update button states
    elements.prevPageBtn.disabled = currentState.attributesTab.page <= 1;
    elements.nextPageBtn.disabled = currentState.attributesTab.page >= totalPages;
}

/**
 * Select an attribute to view details
 * @param {String} attributeName - Name of the attribute to select
 * @param {Number} valueCount - Number of values for this attribute
 */
function selectAttribute(attributeName, valueCount) {
    if (!elements.attributeMappingForm || !elements.selectAttributeMessage || !elements.pivotreeAttributeName) {
        console.error('Missing DOM elements for attribute selection');
        return;
    }
    
    const currentState = state.getState();
    
    // Find the attribute in our state
    const attribute = currentState.attributesTab.attributes.find(
        attr => attr.attribute_name === attributeName
    );
    
    if (!attribute) {
        console.error('Attribute not found:', attributeName);
        return;
    }
    
    // Update state
    state.updateState('attributesTab.selectedAttribute', attribute);
    
    // Show the attribute mapping form
    elements.selectAttributeMessage.classList.add('d-none');
    elements.attributeMappingForm.classList.remove('d-none');
    
    // Populate form with attribute details
    elements.pivotreeAttributeName.textContent = attributeName;
    if (elements.pivotreeValueCount) {
        elements.pivotreeValueCount.textContent = valueCount;
    }
    
    // Clear form fields
    if (elements.akeneoAttribute) {
        elements.akeneoAttribute.value = '';
    }
    
    // Reset sections
    if (elements.newAttributeSection) {
        elements.newAttributeSection.classList.add('d-none');
    }
    
    // Auto-populate fields for new attribute with improved code generation
    if (elements.newAttributeCode && elements.newAttributeLabel) {
        // Generate proper code for Akeneo
        elements.newAttributeCode.value = uiUtils.formatAttributeCode(attributeName);
        
        // Format label in a more user-friendly way
        elements.newAttributeLabel.value = uiUtils.formatAttributeLabel(attributeName);
        
        // If we have a newAttributeType dropdown, try to guess a sensible default
        if (elements.newAttributeType) {
            // Default to text for most attributes
            let guessedType = 'pim_catalog_text';
            
            // Try to guess the type based on attribute name
            const lowerName = attributeName.toLowerCase();
            
            if (lowerName.includes('color') || lowerName.includes('colour')) {
                guessedType = 'pim_catalog_simpleselect';
            } else if (lowerName.includes('size') || lowerName.includes('dimension')) {
                guessedType = 'pim_catalog_simpleselect';
            } else if (lowerName.includes('description') || lowerName.includes('spec')) {
                guessedType = 'pim_catalog_textarea';
            } else if (lowerName.includes('price') || lowerName.includes('cost')) {
                guessedType = 'pim_catalog_price';
            } else if (lowerName.includes('weight') || lowerName.includes('height') ||
                lowerName.includes('width') || lowerName.includes('length')) {
                guessedType = 'pim_catalog_metric';
            } else if (lowerName.includes('date') || lowerName.includes('time')) {
                guessedType = 'pim_catalog_date';
            } else if (lowerName.includes('image') || lowerName.includes('photo')) {
                guessedType = 'pim_catalog_image';
            } else if (lowerName.includes('file') || lowerName.includes('document')) {
                guessedType = 'pim_catalog_file';
            } else if (lowerName.includes('yes') || lowerName.includes('no') ||
                lowerName.includes('true') || lowerName.includes('false')) {
                guessedType = 'pim_catalog_boolean';
            }
            
            elements.newAttributeType.value = guessedType;
        }
    }
    
    // Check if we have an attribute mapping
    const attributeMapping = currentState.mappings.attributes.find(
        mapping => mapping.pivotree_attribute_name === attributeName
    );
    
    if (attributeMapping && elements.akeneoAttribute) {
        // Pre-select the existing mapping
        elements.akeneoAttribute.value = attributeMapping.akeneo_attribute_code || '';
        
        // If it's a new attribute, show the new attribute section
        if (attributeMapping.is_new_attribute && elements.newAttributeSection) {
            elements.newAttributeSection.classList.remove('d-none');
            if (elements.newAttributeCode) {
                elements.newAttributeCode.value = attributeMapping.new_attribute_code || '';
            }
            if (elements.newAttributeLabel) {
                elements.newAttributeLabel.value = attributeMapping.new_attribute_label || '';
            }
            if (elements.newAttributeType) {
                elements.newAttributeType.value = attributeMapping.new_attribute_type || 'pim_catalog_text';
            }
        }
    }
}

/**
 * Handle new attribute button click
 */
function handleNewAttributeClick() {
    if (!elements.newAttributeSection) {
        return;
    }
    
    const currentState = state.getState();
    if (!currentState.attributesTab.selectedAttribute) {
        return;
    }
    
    elements.newAttributeSection.classList.toggle('d-none');
    
    if (!elements.newAttributeSection.classList.contains('d-none')) {
        // When the section becomes visible, generate proper codes
        const attributeName = currentState.attributesTab.selectedAttribute.attribute_name;
        elements.newAttributeCode.value = uiUtils.formatAttributeCode(attributeName);
        elements.newAttributeLabel.value = uiUtils.formatAttributeLabel(attributeName);
        
        // Focus the code field for editing
        elements.newAttributeCode.focus();
        
        // Make sure the dropdown is cleared to avoid confusion
        if (elements.akeneoAttribute) {
            elements.akeneoAttribute.value = '';
        }
    }
}

/**
 * Save attribute mapping
 */
async function saveAttributeMapping() {
    const currentState = state.getState();
    
    if (!currentState.attributesTab.selectedAttribute) {
        uiUtils.showAlert('Please select an attribute to map first.');
        return;
    }
    
    if (!elements.akeneoAttribute) {
        console.error('Missing akeneoAttribute element');
        return;
    }
    
    // Show loading spinner
    uiUtils.showLoading();
    
    try {
        // Create attribute mapping object
        const attributeMapping = {
            pivotree_attribute_name: currentState.attributesTab.selectedAttribute.attribute_name
        };
        
        // Handle existing attribute mapping
        if (elements.akeneoAttribute.value) {
            attributeMapping.akeneo_attribute_code = elements.akeneoAttribute.value;
            attributeMapping.akeneo_attribute_label = elements.akeneoAttribute.options[elements.akeneoAttribute.selectedIndex].text;
            attributeMapping.is_new_attribute = 0;
        } else if (elements.newAttributeSection && !elements.newAttributeSection.classList.contains('d-none') &&
            elements.newAttributeCode && elements.newAttributeLabel && elements.newAttributeCode.value && elements.newAttributeLabel.value) {
            // Handle new attribute creation
            attributeMapping.is_new_attribute = 1;
            attributeMapping.new_attribute_code = elements.newAttributeCode.value;
            attributeMapping.new_attribute_label = elements.newAttributeLabel.value;
            attributeMapping.new_attribute_type = elements.newAttributeType ? elements.newAttributeType.value : 'pim_catalog_text';
        } else {
            uiUtils.showAlert('Please select an Akeneo attribute or create a new one.');
            return;
        }
        
        // Save attribute mapping
        const response = await apiClient.saveAttributeMapping(attributeMapping);
        
        // Show success message
        uiUtils.showAlert('Attribute mapping saved successfully!');
        
        // Reset the form
        if (elements.attributeMappingForm) elements.attributeMappingForm.classList.add('d-none');
        if (elements.selectAttributeMessage) elements.selectAttributeMessage.classList.remove('d-none');
        
        // Update the mapping in state
        const currentMappings = [...currentState.mappings.attributes];
        const mappingIndex = currentMappings.findIndex(
            mapping => mapping.pivotree_attribute_name === attributeMapping.pivotree_attribute_name
        );
        
        if (mappingIndex >= 0) {
            // Update existing mapping
            currentMappings[mappingIndex] = {
                ...currentMappings[mappingIndex],
                ...attributeMapping,
                id: response.id || currentMappings[mappingIndex].id
            };
        } else {
            // Add new mapping
            currentMappings.push({
                ...attributeMapping,
                id: response.id
            });
        }
        
        // Update state with new mappings
        state.updateState('mappings.attributes', currentMappings);
        
        // Update the UI without reloading everything
        updateAttributeInTable(attributeMapping.pivotree_attribute_name);
        updateRecentMappings(attributeMapping);
        
    } catch (error) {
        console.error('Error saving attribute mapping:', error);
        uiUtils.showAlert('Error saving attribute mapping. Please try again.');
    } finally {
        // Hide loading spinner
        uiUtils.hideLoading();
    }
}

/**
 * Update a single attribute's status in the table
 * @param {String} attributeName - Name of the attribute to update
 */
function updateAttributeInTable(attributeName) {
    if (!elements.attributesList) return;
    
    // Find the row for this attribute
    const row = elements.attributesList.querySelector(`tr button[data-name="${attributeName}"]`)?.closest('tr');
    if (!row) return;
    
    // Get the status cell (3rd column)
    const statusCell = row.cells[2];
    if (!statusCell) return;
    
    // Check the new status
    const akeneoStatus = checkAkeneoStatus(attributeName);
    
    // Update the status cell
    statusCell.innerHTML = `<span class="Label Label--${akeneoStatus.color}">${akeneoStatus.label}</span>`;
}

/**
 * Update the recent mappings list
 * @param {Object} mapping - Attribute mapping object
 */
function updateRecentMappings(mapping) {
    if (!elements.recentMappings) return;
    
    // Remove the "no mappings" message if it exists
    const noMappingsItem = elements.recentMappings.querySelector('.text-gray');
    if (noMappingsItem) {
        elements.recentMappings.innerHTML = '';
    }
    
    // Get label to display
    let mappingLabel = mapping.akeneo_attribute_label || mapping.akeneo_attribute_code;
    if (mapping.is_new_attribute) {
        mappingLabel = mapping.new_attribute_label || mapping.new_attribute_code;
    }
    
    // Create a new list item for this mapping
    const listItem = document.createElement('li');
    listItem.className = 'Box-row';
    listItem.innerHTML = `
    <div class="d-flex flex-items-center">
        <div class="flex-auto">
            <strong>${mapping.pivotree_attribute_name}</strong> → 
            <span>${mappingLabel}</span>
        </div>
        <span class="Label Label--${mapping.is_new_attribute ? 'yellow' : 'green'}">
            ${mapping.is_new_attribute ? 'New' : 'Existing'}
        </span>
    </div>
    `;
    
    // Add to the beginning of the list
    elements.recentMappings.insertBefore(listItem, elements.recentMappings.firstChild);
    
    // Limit to 5 recent mappings
    while (elements.recentMappings.children.length > 5) {
        elements.recentMappings.removeChild(elements.recentMappings.lastChild);
    }
}

// Export module functions
export default {
    initialize,
    loadAttributes,
    selectAttribute
};