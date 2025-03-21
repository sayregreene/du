/**
 * Values module for the attribute comparison
 * Handles attribute value listing, filtering, and mapping
 */
import state from '../state.js';
import apiClient from '../api-client.js';
import uiUtils from '../ui-utils.js';

// Cache DOM elements
let elements = {};

/**
 * Initialize the values module
 */
function initialize() {
    // Cache DOM elements for better performance
    elements = {
        attributeSelector: uiUtils.getElement('attribute-selector'),
        loadAttributeValuesBtn: uiUtils.getElement('load-attribute-values'),
        valueMappingStatusFilter: uiUtils.getElement('value-mapping-status-filter'),
        valuesMappingContainer: uiUtils.getElement('values-mapping-container'),
        selectedAttributeName: uiUtils.getElement('selected-attribute-name'),
        attributeValuesList: uiUtils.getElement('attribute-values-list'),
        
        // Value mapping form
        valueMappingForm: uiUtils.getElement('value-mapping-form'),
        selectedPivotreeValue: uiUtils.getElement('selected-pivotree-value'),
        selectedPivotreeUom: uiUtils.getElement('selected-pivotree-uom'),
        akeneoValue: uiUtils.getElement('akeneo-value'), // Hidden input to store selected value
        akeneoValueSearch: uiUtils.getElement('akeneo-value-search'), // Search input
        akeneoValueList: uiUtils.getElement('akeneo-value-list'), // List container
        newValueSection: uiUtils.getElement('new-value-section'),
        btnNewValue: uiUtils.getElement('btn-new-value'),
        btnCancelValueMapping: uiUtils.getElement('btn-cancel-value-mapping'),
        btnSaveValueMapping: uiUtils.getElement('btn-save-value-mapping'),
        
        // New value form
        newValueCode: uiUtils.getElement('new-value-code'),
        newValueLabel: uiUtils.getElement('new-value-label')
    };
    
    uiUtils.applyTableStyles();

    // Subscribe to state changes
    state.subscribe((newState, path) => {
        if (path.startsWith('valuesTab')) {
            if (path === 'valuesTab.attributeValues' || path === 'valuesTab.mappingStatus') {
                renderAttributeValues();
            }
        }
    });
    
    // Initialize event listeners
    setupEventListeners();
    
    // Load mapped attributes for the dropdown
    loadMappedAttributes();
}

/**
 * Set up event listeners for values tab elements
 */
/**
 * Set up event listeners for values tab elements
 */
function setupEventListeners() {
    // Load values button
    if (elements.loadAttributeValuesBtn && elements.attributeSelector) {
        elements.loadAttributeValuesBtn.addEventListener('click', () => {
            const selectedAttrName = elements.attributeSelector.value;
            if (!selectedAttrName) {
                uiUtils.showAlert('Please select an attribute');
                return;
            }
            
            // Update state with selected attribute
            state.updateState('valuesTab.selectedAttributeName', selectedAttrName);
            
            // Find the Akeneo attribute code for this attribute
            const currentState = state.getState();
            const attributeMapping = currentState.mappings.attributes.find(
                mapping => mapping.pivotree_attribute_name === selectedAttrName
            );
            
            if (attributeMapping) {
                const attributeCode = attributeMapping.akeneo_attribute_code ||
                    attributeMapping.new_attribute_code;
                state.updateState('valuesTab.selectedAttributeCode', attributeCode);
            }
            
            // Load the attribute values
            loadAttributeValues(selectedAttrName);
        });
    }
    
    // Value mapping status filter
    if (elements.valueMappingStatusFilter) {
        elements.valueMappingStatusFilter.addEventListener('change', () => {
            state.updateState('valuesTab.mappingStatus', elements.valueMappingStatusFilter.value);
            filterAttributeValues();
        });
    }
    
    // Value search
    if (elements.akeneoValueSearch) {
        elements.akeneoValueSearch.addEventListener('input', () => {
            filterAttributeOptions(elements.akeneoValueSearch.value);
        });
    }
    
    // New value button - FIXED VERSION
    if (elements.btnNewValue) {
        elements.btnNewValue.addEventListener('click', () => {
            console.log('New value button clicked');
            
            // Toggle visibility of the new value section
            if (elements.newValueSection) {
                elements.newValueSection.classList.toggle('d-none');
                
                // Only auto-generate when showing the section
                if (!elements.newValueSection.classList.contains('d-none')) {
                    // Auto-generate value code and label when showing the section
                    generateNewValueCode();
                    
                    // Focus the code field for editing
                    if (elements.newValueCode) {
                        elements.newValueCode.focus();
                    }
                }
            } else {
                console.error('newValueSection element not found');
            }
        });
    }
    
    // Cancel value mapping button
    if (elements.btnCancelValueMapping && elements.valueMappingForm) {
        elements.btnCancelValueMapping.addEventListener('click', () => {
            elements.valueMappingForm.classList.add('d-none');
            state.updateState('valuesTab.selectedValue', null);
        });
    }
    
    // Save value mapping button
    if (elements.btnSaveValueMapping) {
        elements.btnSaveValueMapping.addEventListener('click', saveValueMapping);
    }
}


/**
 * Calculate similarity between two strings (simple implementation)
 * @param {string} a - First string
 * @param {string} b - Second string
 * @returns {number} - Similarity score (0-1 where 1 is identical)
 */
function calculateSimilarity(a, b) {
    // Convert to lowercase for case-insensitive comparison
    const strA = a.toLowerCase();
    const strB = b.toLowerCase();
    
    // Direct match
    if (strA === strB) return 1;
    
    // Check if one contains the other
    if (strA.includes(strB) || strB.includes(strA)) {
        return 0.8;
    }
    
    // Check for word matches
    const wordsA = strA.split(/\s+/);
    const wordsB = strB.split(/\s+/);
    
    // Count matching words
    const commonWords = wordsA.filter(word => wordsB.includes(word)).length;
    if (commonWords > 0) {
        return 0.5 + (0.3 * commonWords / Math.max(wordsA.length, wordsB.length));
    }
    
    // Simple character-based similarity
    let matches = 0;
    const maxLength = Math.max(strA.length, strB.length);
    const minLength = Math.min(strA.length, strB.length);
    
    for (let i = 0; i < minLength; i++) {
        if (strA[i] === strB[i]) matches++;
    }
    
    return matches / maxLength;
}




/**
 * Load mapped attributes for the Values tab
 */
// async function loadMappedAttributes() {
//     if (!elements.attributeSelector) return;
    
//     // Clear the dropdown first
//     elements.attributeSelector.innerHTML = '<option value="">-- Select Attribute --</option>';
    
//     const currentState = state.getState();
    
//     // Filter attributes that have been mapped
//     const mappedAttributes = currentState.mappings.attributes.filter(mapping =>
//         mapping.akeneo_attribute_code || mapping.new_attribute_code
//     );
    
//     if (mappedAttributes.length === 0) {
//         const option = document.createElement('option');
//         option.disabled = true;
//         option.textContent = 'No mapped attributes found. Map attributes first.';
//         elements.attributeSelector.appendChild(option);
//         return;
//     }
    
//     // Add mapped attributes to the dropdown
//     mappedAttributes.forEach(attr => {
//         const option = document.createElement('option');
//         option.value = attr.pivotree_attribute_name;
//         option.textContent = `${attr.pivotree_attribute_name} → ${attr.akeneo_attribute_code || attr.new_attribute_code}`;
//         elements.attributeSelector.appendChild(option);
//     });
// }

async function loadMappedAttributes() {
    if (!elements.attributeSelector) return;
    
    // Show loading indicator
    uiUtils.showLoading('Loading attributes...');

    try {
        // Clear the dropdown first
        elements.attributeSelector.innerHTML = '<option value="">-- Select Attribute --</option>';
        
        const currentState = state.getState();
        
        // Filter attributes that have been mapped
        const mappedAttributes = currentState.mappings.attributes.filter(mapping =>
            mapping.akeneo_attribute_code || mapping.new_attribute_code
        );
        
        if (mappedAttributes.length === 0) {
            const option = document.createElement('option');
            option.disabled = true;
            option.textContent = 'No mapped attributes found. Map attributes first.';
            elements.attributeSelector.appendChild(option);
            return;
        }
        
        // Keep track of promises to load unmapped counts
        const countPromises = [];
        
        // Prepare the dropdown items
        const dropdownItems = mappedAttributes.map(attr => {
            // Start a promise to get the unmapped count
            const countPromise = getUnmappedValuesCount(attr.pivotree_attribute_name);
            countPromises.push(countPromise);
            
            return {
                attr,
                countPromise
            };
        });
        
        // Wait for all count promises to resolve
        const counts = await Promise.all(countPromises);
        
        // Add mapped attributes to the dropdown with counts
        dropdownItems.forEach((item, index) => {
            const attr = item.attr;
            const unmappedCount = counts[index];
            
            const option = document.createElement('option');
            option.value = attr.pivotree_attribute_name;
            
            // Format the label with the unmapped count
            let optionText = `${attr.pivotree_attribute_name} → ${attr.akeneo_attribute_code || attr.new_attribute_code}`;
            
            if (unmappedCount !== null) {
                // Add the unmapped count if available
                optionText += ` (${unmappedCount} unmapped values)`;
            }
            
            option.textContent = optionText;
            
            // If there are unmapped values, add a data attribute for styling
            if (unmappedCount > 0) {
                option.dataset.hasUnmapped = 'true';
                option.style.fontWeight = 'bold';
                option.style.color = '#bf3989'; // Use a highlight color for attributes with unmapped values
            }
            
            elements.attributeSelector.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading mapped attributes:', error);
        
        // Add a fallback option
        const option = document.createElement('option');
        option.disabled = true;
        option.textContent = 'Error loading attributes. Please try again.';
        elements.attributeSelector.appendChild(option);
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Get the count of unmapped values for a specific attribute
 * @param {String} attributeName - The attribute name to check
 * @returns {Promise<Number>} - Promise resolving to the count of unmapped values
 */
async function getUnmappedValuesCount(attributeName) {
    try {
        // Get all values for this attribute
        const valuesResponse = await apiClient.loadAttributeValues(attributeName);
        
        // Process values - handle semicolon-separated values
        const allValues = [];
        
        // Process each value
        (valuesResponse.values || []).forEach(value => {
            const originalValue = value.attribute_value;
            
            // Check if the value contains semicolons
            if (originalValue && originalValue.includes(';')) {
                // Split by semicolon and create separate entries
                const splitValues = originalValue.split(';')
                    .map(v => v.trim())
                    .filter(v => v);
                
                splitValues.forEach(splitValue => {
                    allValues.push({
                        attribute_value: splitValue,
                        uom: value.uom
                    });
                });
            } else {
                // Just add the value as is
                allValues.push({
                    attribute_value: originalValue,
                    uom: value.uom
                });
            }
        });
        
        // Get mappings for this attribute
        const mappingsResponse = await apiClient.loadValueMappings(attributeName);
        const mappings = mappingsResponse.mappings || [];
        
        // Count unmapped values by checking each value against existing mappings
        let unmappedCount = 0;
        
        allValues.forEach(value => {
            const isMapped = mappings.some(
                mapping =>
                    mapping.pivotree_attribute_value === value.attribute_value &&
                    ((!mapping.pivotree_uom && !value.uom) ||
                     (mapping.pivotree_uom === value.uom))
            );
            
            if (!isMapped) {
                unmappedCount++;
            }
        });
        
        return unmappedCount;
    } catch (error) {
        console.error('Error getting unmapped values count:', error);
        return null; // Return null on error to indicate count is unavailable
    }
}

/**
 * Update the attributes selector after mapping a value
 * Call this function after successfully saving a value mapping
 */
function updateAttributeSelectorAfterMapping() {
    // Only update if we've already saved at least one mapping
    const currentState = state.getState();
    const attributeName = currentState.valuesTab.selectedAttributeName;
    
    if (!attributeName) return;
    
    // Find the option in the dropdown
    const option = Array.from(elements.attributeSelector.options).find(
        opt => opt.value === attributeName
    );
    
    if (!option) return;
    
    // Update the count asynchronously
    getUnmappedValuesCount(attributeName)
        .then(unmappedCount => {
            if (unmappedCount === null) return;
            
            // Get the base text without the count
            let baseText = option.textContent;
            const countIndex = baseText.lastIndexOf(' (');
            if (countIndex > -1) {
                baseText = baseText.substring(0, countIndex);
            }
            
            // Update the text with the new count
            option.textContent = `${baseText} (${unmappedCount} unmapped values)`;
            
            // Update the styling
            if (unmappedCount > 0) {
                option.dataset.hasUnmapped = 'true';
                option.style.fontWeight = 'bold';
                option.style.color = '#bf3989';
            } else {
                option.dataset.hasUnmapped = 'false';
                option.style.fontWeight = 'normal';
                option.style.color = '';
            }
        })
        .catch(error => {
            console.error('Error updating unmapped count:', error);
        });
}

/**
 * Load attribute values for a specific attribute
 * @param {String} attributeName - Name of the attribute to load values for
 */
async function loadAttributeValues(attributeName) {
    uiUtils.showLoading('Loading Attribute Values');
    
    try {
        const response = await apiClient.loadAttributeValues(attributeName);
        
        // Process values - handle semicolon-separated values
        const processedValues = [];
        
        // Process each value
        (response.values || []).forEach(value => {
            const originalValue = value.attribute_value;
            
            // Check if the value contains semicolons
            if (originalValue && originalValue.includes(';')) {
                // Split by semicolon and create separate entries
                const splitValues = originalValue.split(';').map(v => v.trim()).filter(v => v);
                
                splitValues.forEach(splitValue => {
                    processedValues.push({
                        ...value,
                        attribute_value: splitValue,
                        original_value: originalValue // Keep track of the original value
                    });
                });
            } else {
                // Just add the value as is
                processedValues.push({
                    ...value,
                    original_value: originalValue
                });
            }
        });
        
        // Store both the processed values and original response
        state.updateState('valuesTab.attributeValues', processedValues);
        state.updateState('valuesTab.originalValues', processedValues);
        
        // Load mappings for this attribute
        await loadValueMappings(attributeName);
        
        // Get Akeneo attribute options if we have a selected attribute code
        const currentState = state.getState();
        if (currentState.valuesTab.selectedAttributeCode) {
            await loadAkeneoAttributeValues(currentState.valuesTab.selectedAttributeCode);
        }
        
        // Update UI
        if (elements.selectedAttributeName) {
            elements.selectedAttributeName.textContent = attributeName;
        }
        if (elements.valuesMappingContainer) {
            elements.valuesMappingContainer.classList.remove('d-none');
        }
        
        // Filter values based on current status filter
        filterAttributeValues();
        
    } catch (error) {
        console.error('Error loading attribute values:', error);
        
        if (elements.attributeValuesList) {
            elements.attributeValuesList.innerHTML = `
                <tr><td colspan="5" class="text-center text-red">
                    Error loading attribute values: ${error.message}. Please try again.
                </td></tr>
            `;
        }
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Load attribute value mappings for a specific attribute
 * @param {String} attributeName - Name of the attribute to load mappings for
 */
async function loadValueMappings(attributeName) {
    try {
        const response = await apiClient.loadValueMappings(attributeName);
        
        // Get current mappings
        const currentState = state.getState();
        
        // Update the mappings in state
        const attributeMappings = currentState.mappings.values.filter(
            mapping => mapping.pivotree_attribute_name !== attributeName
        );
        
        // Combine with the new mappings for this attribute
        const updatedMappings = [
            ...attributeMappings,
            ...(response.mappings || [])
        ];
        
        state.updateState('mappings.values', updatedMappings);
        
    } catch (error) {
        console.error('Error loading value mappings:', error);
    }
}

/**
 * Load Akeneo attribute values for a specific attribute
 * @param {String} attributeCode - Akeneo attribute code
 */
async function loadAkeneoAttributeValues(attributeCode) {
    if (!elements.akeneoValueList) return;
    
    // Clear the value list
    elements.akeneoValueList.innerHTML = '<div class="blankslate p-3"><p>Loading options...</p></div>';
    
    // Clear hidden input and search
    if (elements.akeneoValue) elements.akeneoValue.value = '';
    if (elements.akeneoValueSearch) elements.akeneoValueSearch.value = '';
    
    try {
        const response = await apiClient.loadAkeneoAttributeValues(attributeCode);
        
        // Store attribute values in state
        state.updateState('akeneo.attributeValues', response.options || []);
        
        // Render the options list
        renderAttributeOptions();
        
    } catch (error) {
        console.error('Error loading Akeneo attribute values:', error);
        
        if (elements.akeneoValueList) {
            elements.akeneoValueList.innerHTML = `<div class="blankslate p-3"><p>Error loading options: ${error.message}</p></div>`;
        }
    }
}

/**
 * Filter attribute values based on mapping status
 */
function filterAttributeValues() {
    const currentState = state.getState();
    const status = currentState.valuesTab.mappingStatus;
    
    let filteredValues;
    
    if (status === 'all') {
        // Use all values without filtering
        filteredValues = currentState.valuesTab.originalValues;
    } else {
        // Filter based on mapped/unmapped status
        filteredValues = currentState.valuesTab.originalValues.filter(value => {
            const isMapped = currentState.mappings.values.some(
                mapping =>
                    mapping.pivotree_attribute_name === currentState.valuesTab.selectedAttributeName &&
                    mapping.pivotree_attribute_value === value.attribute_value &&
                    // Also compare UOM, treating null/undefined/empty string as equivalent
                    ((!mapping.pivotree_uom && !value.uom) ||
                        (mapping.pivotree_uom === value.uom))
            );
            
            return (status === 'mapped' && isMapped) || (status === 'unmapped' && !isMapped);
        });
    }
    
    // Update filtered values in state
    state.updateState('valuesTab.attributeValues', filteredValues);
}

/**
 * Render attribute values table
 */
function renderAttributeValues() {
    if (!elements.attributeValuesList) return;
    
    const currentState = state.getState();
    const values = currentState.valuesTab.attributeValues;
    
    // Clear the table
    elements.attributeValuesList.innerHTML = '';
    
    if (!values || values.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="5" class="text-center p-3">No values found for this attribute</td>
        `;
        elements.attributeValuesList.appendChild(row);
        return;
    }
    
    // Add each value to the table
    values.forEach(value => {
        const row = document.createElement('tr');
        
        // Check if this value has been mapped - consider UOM in the comparison
        const valueMapping = currentState.mappings.values.find(
            mapping =>
                mapping.pivotree_attribute_name === currentState.valuesTab.selectedAttributeName &&
                mapping.pivotree_attribute_value === value.attribute_value &&
                // Also compare UOM, treating null/undefined/empty string as equivalent
                ((!mapping.pivotree_uom && !value.uom) ||
                    (mapping.pivotree_uom === value.uom))
        );
        
        const mappingStatus = valueMapping ?
            { label: 'Mapped', color: 'green' } :
            { label: 'Not Mapped', color: 'red' };
        
        // Use data attributes to store attribute_value and uom for later use
        row.innerHTML = `
            <td class="px-3 py-2">${value.attribute_value}</td>
            <td class="px-3 py-2">${value.uom || 'N/A'}</td>
            <td class="px-3 py-2">${valueMapping ? (valueMapping.akeneo_value_code || valueMapping.new_value_code) : ''}</td>
            <td class="px-3 py-2">
                <span class="Label Label--${mappingStatus.color}">${mappingStatus.label}</span>
            </td>
            <td class="px-3 py-2">
                <button class="btn btn-sm btn-outline btn-map-value" 
                  data-value="${value.attribute_value}"
                  data-uom="${value.uom || ''}">
                    Map
                </button>
            </td>
        `;
        
        elements.attributeValuesList.appendChild(row);
    });
    
    // Add event listeners to the map buttons
    document.querySelectorAll('.btn-map-value').forEach(btn => {
        btn.addEventListener('click', function() {
            const attributeValue = this.getAttribute('data-value');
            const uom = this.getAttribute('data-uom');
            selectAttributeValue(attributeValue, uom);
        });
    });
}

/**
 * Render attribute options as a selectable list
 * @param {String} searchTerm - Optional search term to filter options
 */
function renderAttributeOptions(searchTerm = '') {
    if (!elements.akeneoValueList) return;
    
    // Clear the list
    elements.akeneoValueList.innerHTML = '';
    
    const currentState = state.getState();
    const attributeValues = currentState.akeneo.attributeValues;
    
    // Get the selected value to compare with
    const selectedValue = currentState.valuesTab.selectedValue?.attribute_value || '';
    
    // Filter options based on search term
    let filteredOptions = searchTerm ?
        attributeValues.filter(option =>
            option.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
            option.code.toLowerCase().includes(searchTerm.toLowerCase())
        ) :
        attributeValues;
    
    if (filteredOptions.length === 0) {
        elements.akeneoValueList.innerHTML = '<div class="blankslate p-3"><p>No options matching your search.</p></div>';
        return;
    }
    
    // Sort options based on similarity to the selected value
    filteredOptions = filteredOptions.map(option => {
        // Calculate similarity scores for both label and code
        const labelSimilarity = calculateSimilarity(option.label, selectedValue);
        const codeSimilarity = calculateSimilarity(option.code, selectedValue);
        
        // Use the higher score
        const similarity = Math.max(labelSimilarity, codeSimilarity);
        
        return {
            ...option,
            similarity
        };
    });
    
    // Sort by similarity (highest first) with alphabetical sorting as secondary criteria
    filteredOptions.sort((a, b) => {
        // If similarity difference is significant, use it for sorting
        if (Math.abs(b.similarity - a.similarity) > 0.1) {
            return b.similarity - a.similarity;
        }
        // Otherwise, fall back to alphabetical order
        return a.label.localeCompare(b.label);
    });
    
    // Create selectable list
    const list = document.createElement('ul');
    list.className = 'list-style-none';
    
    // Add the top 10 similar options visually separated if not searching
    if (!searchTerm && selectedValue) {
        const topOptions = filteredOptions.slice(0, 10).filter(opt => opt.similarity > 0.3);
        const remainingOptions = filteredOptions.filter(opt => !topOptions.includes(opt));
        
        // Sort remaining options alphabetically
        remainingOptions.sort((a, b) => a.label.localeCompare(b.label));
        
        // Add top options with special highlighting
        topOptions.forEach(option => {
            addOptionToList(option, list, true);
        });
        
        // Add a separator if we have any top options
        if (topOptions.length > 0) {
            const separator = document.createElement('li');
            separator.className = 'Box-row bg-gray-light';
            separator.innerHTML = '<div class="text-center text-gray">Other options</div>';
            list.appendChild(separator);
        }
        
        // Add remaining options
        remainingOptions.forEach(option => {
            addOptionToList(option, list, false);
        });
    } else {
        // Just add all options normally
        filteredOptions.forEach(option => {
            addOptionToList(option, list, false);
        });
    }
    
    elements.akeneoValueList.appendChild(list);
}

/**
 * Helper function to add an option to the list
 * @param {Object} option - The option to add
 * @param {HTMLElement} list - The list element to add to
 * @param {Boolean} isRecommended - Whether this is a recommended/top match
 */
function addOptionToList(option, list, isRecommended) {
    const item = document.createElement('li');
    item.className = 'Box-row d-flex flex-items-center option-item';
    if (isRecommended) {
        item.classList.add('bg-yellow-light');
    }
    
    item.setAttribute('data-value', option.code);
    item.setAttribute('data-label', option.label);
    
    // Check if this option is currently selected
    if (elements.akeneoValue && elements.akeneoValue.value === option.code) {
        item.classList.add('selected', 'bg-blue-light');
    }
    
    item.innerHTML = `
        <div class="flex-auto">
            <span class="d-block text-bold">${option.label}</span>
            <span class="d-block text-small text-gray">${option.code}</span>
            ${isRecommended ? '<span class="Label Label--yellow ml-1">Match</span>' : ''}
        </div>
    `;
    
    // Add click handler
    item.addEventListener('click', function() {
        selectAttributeOption(this);
    });
    
    list.appendChild(item);
}

/**
 * Filter attribute options based on search input
 * @param {String} searchTerm - Search term to filter options
 */
function filterAttributeOptions(searchTerm) {
    renderAttributeOptions(searchTerm);
}

/**
 * Select an attribute option from the list
 * @param {HTMLElement} itemElement - Selected list item
 */
function selectAttributeOption(itemElement) {
    if (!elements.akeneoValue || !elements.akeneoValueList) return;
    
    // Remove selected class from all items
    const items = elements.akeneoValueList.querySelectorAll('.option-item');
    items.forEach(item => item.classList.remove('selected', 'bg-blue-light'));
    
    // Add selected class to clicked item
    itemElement.classList.add('selected', 'bg-blue-light');
    
    // Update hidden input with selected value
    const value = itemElement.getAttribute('data-value');
    elements.akeneoValue.value = value;
}

/**
 * Select an attribute value to map
 * @param {String} attributeValue - Attribute value to select
 * @param {String} uom - Unit of measure for the value
 */
function selectAttributeValue(attributeValue, uom) {
    if (!elements.valueMappingForm || !elements.selectedPivotreeValue) {
        console.error('Missing DOM elements for value selection');
        return;
    }
    
    console.log(`Selecting value: ${attributeValue} with UOM: ${uom}`);
    
    const currentState = state.getState();
    
    // Find the value in our state, considering both value and UOM
    const value = currentState.valuesTab.attributeValues.find(
        val => val.attribute_value === attributeValue && 
              ((val.uom || '') === (uom || ''))
    );
    
    if (!value) {
        console.error(`Attribute value not found: ${attributeValue} with UOM: ${uom}`);
        return;
    }
    
    // Update state
    state.updateState('valuesTab.selectedValue', value);
    
    // Show the value mapping form
    elements.valueMappingForm.classList.remove('d-none');
    
    // Populate form with value details
    elements.selectedPivotreeValue.textContent = attributeValue + (uom ? ` (${uom})` : '');
    if (elements.selectedPivotreeUom) {
        elements.selectedPivotreeUom.textContent = uom || 'N/A';
    }
    
    // Clear form fields
    if (elements.akeneoValue) {
        elements.akeneoValue.value = '';
    }
    if (elements.akeneoValueSearch) {
        elements.akeneoValueSearch.value = '';
    }
    
    // Always hide the new value section by default
    if (elements.newValueSection) {
        elements.newValueSection.classList.add('d-none');
    }
    
    // Auto-populate fields for new value
    generateNewValueCode();
    
    // Re-render the attribute options to clear any previous selection
    renderAttributeOptions();
    
    // Check if we have a value mapping - must match both value AND UOM
    const valueMapping = currentState.mappings.values.find(
        mapping =>
            mapping.pivotree_attribute_name === currentState.valuesTab.selectedAttributeName &&
            mapping.pivotree_attribute_value === attributeValue &&
            ((mapping.pivotree_uom || '') === (uom || ''))
    );
    
    if (valueMapping) {
        // If this is an existing mapping
        if (valueMapping.is_new_value) {
            // If it was mapped to a new value, show the new value section
            if (elements.newValueSection) {
                elements.newValueSection.classList.remove('d-none');
                if (elements.newValueCode) {
                    elements.newValueCode.value = valueMapping.new_value_code || '';
                }
                if (elements.newValueLabel) {
                    elements.newValueLabel.value = valueMapping.new_value_label || '';
                }
            }
        } else {
            // If it was mapped to an existing value, set the hidden input and highlight the option
            if (elements.akeneoValue) {
                elements.akeneoValue.value = valueMapping.akeneo_value_code || '';
                
                // Highlight the selected option in the list
                setTimeout(() => {
                    const selectedItem = elements.akeneoValueList?.querySelector(`[data-value="${elements.akeneoValue.value}"]`);
                    if (selectedItem) {
                        selectedItem.classList.add('selected', 'bg-blue-light');
                        // Scroll to the selected item
                        selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }, 100);
            }
        }
    }
}

/**
 * Generate code and label for a new value option
 */
function generateNewValueCode() {
    const currentState = state.getState();
    
    if (!elements.newValueCode || !elements.newValueLabel || !currentState.valuesTab.selectedValue) {
        return;
    }
    
    const attributeValue = currentState.valuesTab.selectedValue.attribute_value;
    const uom = currentState.valuesTab.selectedValue.uom;
    
    // Get the attribute code to use as prefix
    let attributePrefix = '';
    
    // Use the selected attribute code from the values tab
    if (currentState.valuesTab.selectedAttributeCode) {
        attributePrefix = currentState.valuesTab.selectedAttributeCode + '_';
    } else {
        // Fallback to a formatted version of the attribute name
        const attributeName = currentState.valuesTab.selectedAttributeName || '';
        attributePrefix = uiUtils.formatAttributeCode(attributeName) + '_';
    }
    
    // Generate value code with attribute prefix
    let valueCode = uiUtils.formatValueCode(attributeValue);
    
    // For UOM values, include the UOM in the code
    if (uom) {
        valueCode += '_' + uom.toLowerCase().replace(/[^a-z0-9]/g, '');
    }
    
    // Apply the prefix to the value code
    elements.newValueCode.value = attributePrefix + valueCode;
    
    // Set the label
    elements.newValueLabel.value = uiUtils.formatValueLabel(attributeValue) + (uom ? ` ${uom}` : '');
}

/**
 * Save value mapping
 */
async function saveValueMapping() {
    const currentState = state.getState();
    
    if (!currentState.valuesTab.selectedValue) {
        uiUtils.showAlert('Please select a value to map first.');
        return;
    }
    
    // Show loading spinner
    uiUtils.showLoading('Saving value mapping...');
    
    try {
        // Create value mapping object with basic properties
        const valueMapping = {
            pivotree_attribute_name: currentState.valuesTab.selectedAttributeName,
            pivotree_attribute_value: currentState.valuesTab.selectedValue.attribute_value,
            pivotree_uom: currentState.valuesTab.selectedValue.uom || null,
            akeneo_attribute_code: currentState.valuesTab.selectedAttributeCode
        };
        
        // Determine if we're mapping to an existing option or creating a new one
        if (elements.akeneoValue && elements.akeneoValue.value) {
            // CASE 1: Mapping to an existing Akeneo option
            valueMapping.akeneo_value_code = elements.akeneoValue.value;
            valueMapping.is_new_value = 0; // EXPLICITLY set to 0 for existing values
            
            // Find the selected option to get the label
            const selectedOption = currentState.akeneo.attributeValues.find(
                option => option.code === elements.akeneoValue.value
            );
            
            valueMapping.akeneo_value_label = selectedOption ? selectedOption.label : '';
            
            // Make sure new value fields are null
            valueMapping.new_value_code = null;
            valueMapping.new_value_label = null;
            
        } else if (elements.newValueSection && !elements.newValueSection.classList.contains('d-none') &&
            elements.newValueCode && elements.newValueLabel && elements.newValueCode.value && elements.newValueLabel.value) {
            // CASE 2: Creating a new option
            valueMapping.is_new_value = 1;
            valueMapping.new_value_code = elements.newValueCode.value;
            valueMapping.new_value_label = elements.newValueLabel.value;
            
            // Make sure Akeneo value fields are null
            valueMapping.akeneo_value_code = null;
            valueMapping.akeneo_value_label = null;
        } else {
            uiUtils.showAlert('Please select an Akeneo option or create a new one.');
            return;
        }
        
        // Save value mapping
        const response = await apiClient.saveValueMapping(valueMapping);
        
        // Show success message
        uiUtils.showAlert('Value mapping saved successfully!');
        
        
        // Hide the value mapping form
        if (elements.valueMappingForm) {
            elements.valueMappingForm.classList.add('d-none');
        }
        
        // Update state.mappings.values with the new mapping
        const currentMappings = [...currentState.mappings.values];
        const mappingIndex = currentMappings.findIndex(
            mapping => 
                mapping.pivotree_attribute_name === valueMapping.pivotree_attribute_name &&
                mapping.pivotree_attribute_value === valueMapping.pivotree_attribute_value &&
                ((!mapping.pivotree_uom && !valueMapping.pivotree_uom) || 
                 (mapping.pivotree_uom === valueMapping.pivotree_uom))
        );
        
        if (mappingIndex >= 0) {
            // Update existing mapping
            currentMappings[mappingIndex] = {
                ...currentMappings[mappingIndex],
                ...valueMapping,
                id: response.id || currentMappings[mappingIndex].id
            };
        } else {
            // Add new mapping
            currentMappings.push({
                ...valueMapping,
                id: response.id
            });
        }
        
        // Update state
        state.updateState('mappings.values', currentMappings);
        state.updateState('valuesTab.selectedValue', null);
        
        // Update the UI for just this value without reloading everything
        updateValueInTable(valueMapping);
        updateAttributeSelectorAfterMapping();
        
    } catch (error) {
        console.error('Error saving value mapping:', error);
        uiUtils.showAlert('Error saving value mapping: ' + error.message);
    } finally {
        // Hide loading spinner
        uiUtils.hideLoading();
    }
}

/**
 * Update a value row in the table after mapping is updated
 * @param {Object} valueMapping - Value mapping that was updated
 */
function updateValueInTable(valueMapping) {
    if (!elements.attributeValuesList) return;
    
    // Find all rows for this attribute value (there might be multiple with different UOMs)
    const rows = elements.attributeValuesList.querySelectorAll('tr');
    
    rows.forEach(row => {
        const valueBtn = row.querySelector('.btn-map-value');
        if (!valueBtn) return;
        
        const rowValue = valueBtn.getAttribute('data-value');
        const rowUom = valueBtn.getAttribute('data-uom') || '';
        
        // Check if this is the row we need to update
        if (rowValue === valueMapping.pivotree_attribute_value &&
            (rowUom === (valueMapping.pivotree_uom || ''))) {
            
            // Get the status cell (4th column) and value cell (3rd column)
            const valueCell = row.cells[2];
            const statusCell = row.cells[3];
            
            if (!statusCell || !valueCell) return;
            
            // Update the value cell with the mapped value
            valueCell.textContent = valueMapping.akeneo_value_code || valueMapping.new_value_code || '';
            
            // Update the status cell
            statusCell.innerHTML = `<span class="Label Label--green">Mapped</span>`;
        }
    });
    
    // Also update the filtered values based on current filter
    filterAttributeValues();
}

// Export module functions
export default {
    initialize,
    loadAttributeValues,
    selectAttributeValue
};