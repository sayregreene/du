/**
 * Export module for the attribute comparison
 * Handles exporting attribute and value mappings to CSV/JSON
 */
import state from '../state.js';
import apiClient from '../api-client.js';
import uiUtils from '../ui-utils.js';

// Cache DOM elements
let elements = {};

/**
 * Initialize the export module
 */
function initialize() {
    // Cache DOM elements for better performance
    elements = {
        exportModal: uiUtils.getElement('export-modal'),
        btnExportMappings: uiUtils.getElement('btn-export-mappings'),
        btnExport: uiUtils.getElement('btn-export'),
        btnCloseExportModal: document.querySelectorAll('.btn-close-export-modal'),
        exportFormat: uiUtils.getElement('export-format'),
        exportSelection: uiUtils.getElement('export-selection'),
        btnExportAllMappings: uiUtils.getElement('btn-export-all-mappings')
    };
    
    // Set up event listeners
    setupEventListeners();
}

/**
 * Set up event listeners for export functionality
 */
function setupEventListeners() {
    // Export button in mappings tab
    if (elements.btnExportAllMappings) {
        elements.btnExportAllMappings.addEventListener('click', exportAllMappings);
    }
    
    // Export button in export modal
    if (elements.btnExport) {
        elements.btnExport.addEventListener('click', exportMappings);
    }
    
    // Close buttons for export modal
    if (elements.btnCloseExportModal && elements.btnCloseExportModal.length > 0) {
        elements.btnCloseExportModal.forEach(btn => {
            btn.addEventListener('click', () => {
                hideExportModal();
            });
        });
    }
}

/**
 * Show the export modal
 */
function showExportModal() {
    if (!elements.exportModal) return;
    
    elements.exportModal.classList.remove('d-none');
}

/**
 * Hide the export modal
 */
function hideExportModal() {
    if (!elements.exportModal) return;
    
    elements.exportModal.classList.add('d-none');
}

/**
 * Export all mappings
 * Used when clicking the "Export All Mappings" button in the mappings tab
 */
async function exportAllMappings() {
    uiUtils.showLoading();
    
    try {
        // Fetch all attribute mappings
        const attributeResponse = await apiClient.loadAllMappings({
            type: 'attributes',
            limit: 1000 // Get a large number to ensure we get all
        });
        
        // Fetch all value mappings
        const valueResponse = await apiClient.loadAllMappings({
            type: 'values',
            limit: 1000 // Get a large number to ensure we get all
        });
        
        // Generate CSV and download
        exportAsCSV(attributeResponse.mappings || [], valueResponse.mappings || []);
        
    } catch (error) {
        console.error('Error exporting mappings:', error);
        uiUtils.showAlert('Error exporting mappings. Please try again.');
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Export mappings based on modal selections
 * Used when clicking the "Export" button in the export modal
 */
async function exportMappings() {
    if (!elements.exportFormat || !elements.exportSelection) {
        console.error('Missing export form elements');
        return;
    }
    
    const format = elements.exportFormat.value;
    const selection = elements.exportSelection.value;
    
    uiUtils.showLoading();
    
    try {
        // Prepare query parameters for the API
        const params = {};
        if (selection === 'new') {
            params.type = 'new';
        } else if (selection === 'existing') {
            params.type = 'existing';
        }
        
        // Fetch attribute mappings
        const attributeResponse = await apiClient.loadAllMappings({
            ...params,
            type: 'attributes',
            limit: 1000 // Get a large number to ensure we get all
        });
        
        // Fetch value mappings
        const valueResponse = await apiClient.loadAllMappings({
            ...params,
            type: 'values',
            limit: 1000 // Get a large number to ensure we get all
        });
        
        // Export based on selected format
        if (format === 'csv') {
            exportAsCSV(attributeResponse.mappings || [], valueResponse.mappings || []);
        } else {
            exportAsJSON(attributeResponse.mappings || [], valueResponse.mappings || []);
        }
        
        // Hide the modal
        hideExportModal();
        
    } catch (error) {
        console.error('Error exporting mappings:', error);
        uiUtils.showAlert(`Error exporting mappings: ${error.message}. Please try again.`);
    } finally {
        uiUtils.hideLoading();
    }
}

/**
 * Export mappings as CSV
 * @param {Array} attributeMappings - Attribute mappings
 * @param {Array} valueMappings - Value mappings
 */
function exportAsCSV(attributeMappings, valueMappings) {
    // Create headers for the CSV
    let csv = 'Type,Pivotree Attribute Name,Pivotree Attribute Value,Pivotree UOM,Akeneo Attribute Code,Akeneo Attribute Label,Akeneo Value Code,Akeneo Value Label,Is New Attribute,Is New Value,New Attribute Code,New Attribute Label,New Attribute Type,New Value Code,New Value Label\n';
    
    // Add attribute mappings
    attributeMappings.forEach(mapping => {
        csv += `Attribute,${escapeCsvValue(mapping.pivotree_attribute_name)},,,"${mapping.akeneo_attribute_code || ''}","${mapping.akeneo_attribute_label || ''}",,,${mapping.is_new_attribute || 0},,"${mapping.new_attribute_code || ''}","${mapping.new_attribute_label || ''}","${mapping.new_attribute_type || ''}",,\n`;
    });
    
    // Add value mappings
    valueMappings.forEach(mapping => {
        csv += `Value,${escapeCsvValue(mapping.pivotree_attribute_name)},${escapeCsvValue(mapping.pivotree_attribute_value)},"${mapping.pivotree_uom || ''}","${mapping.akeneo_attribute_code || ''}",,"${mapping.akeneo_value_code || ''}","${mapping.akeneo_value_label || ''}",,${mapping.is_new_value || 0},,,,,"${mapping.new_value_code || ''}","${mapping.new_value_label || ''}"\n`;
    });
    
    // Create a download link
    uiUtils.downloadFile(csv, 'attribute-mappings.csv', 'text/csv');
}

/**
 * Escape a value for CSV
 * @param {String} value - Value to escape
 * @returns {String} - Escaped value
 */
function escapeCsvValue(value) {
    if (!value) return '';
    
    // If value contains commas, quotes, or newlines, wrap it in quotes
    if (value.includes(',') || value.includes('"') || value.includes('\n')) {
        // Double any quotes inside the value
        return `"${value.replace(/"/g, '""')}"`;
    }
    
    return value;
}

/**
 * Export mappings as JSON
 * @param {Array} attributeMappings - Attribute mappings
 * @param {Array} valueMappings - Value mappings
 */
function exportAsJSON(attributeMappings, valueMappings) {
    const data = {
        attributeMappings,
        valueMappings
    };
    
    // Create a download link
    uiUtils.downloadFile(
        JSON.stringify(data, null, 2),
        'attribute-mappings.json',
        'application/json'
    );
}

// Export module functions
export default {
    initialize,
    showExportModal,
    hideExportModal,
    exportAllMappings,
    exportMappings
};