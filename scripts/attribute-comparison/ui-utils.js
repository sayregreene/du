/**
 * UI utilities for attribute comparison module
 */

let loadingMessageElement = null;
let loadingOverlayElement = null;

/**
 * Initialize the loading indicator
 * Creates the loading overlay and message elements if they don't exist
 */
function initializeLoadingIndicator() {
    // Only initialize once
    if (loadingOverlayElement && loadingMessageElement) {
        return;
    }
    
    // Create loading overlay
    loadingOverlayElement = document.createElement('div');
    loadingOverlayElement.className = 'loading-overlay';
    loadingOverlayElement.style.position = 'fixed';
    loadingOverlayElement.style.top = '0';
    loadingOverlayElement.style.left = '0';
    loadingOverlayElement.style.width = '100%';
    loadingOverlayElement.style.height = '100%';
    loadingOverlayElement.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    loadingOverlayElement.style.zIndex = '9999';
    loadingOverlayElement.style.display = 'none';
    
    // Create loading message
    loadingMessageElement = document.createElement('div');
    loadingMessageElement.className = 'loading-message';
    loadingMessageElement.style.position = 'absolute';
    loadingMessageElement.style.top = '50%';
    loadingMessageElement.style.left = '50%';
    loadingMessageElement.style.transform = 'translate(-50%, -50%)';
    loadingMessageElement.style.backgroundColor = 'white';
    loadingMessageElement.style.padding = '20px';
    loadingMessageElement.style.borderRadius = '5px';
    loadingMessageElement.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.2)';
    
    // Add a spinner
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.style.width = '30px';
    spinner.style.height = '30px';
    spinner.style.border = '3px solid #f3f3f3';
    spinner.style.borderTop = '3px solid #3498db';
    spinner.style.borderRadius = '50%';
    spinner.style.animation = 'spin 1s linear infinite';
    spinner.style.margin = '0 auto 10px auto';
    
    // Add spinner animation
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(styleElement);
    
    // Assemble the loading message
    loadingMessageElement.appendChild(spinner);
    loadingMessageElement.appendChild(document.createElement('div'));
    
    // Add to the overlay
    loadingOverlayElement.appendChild(loadingMessageElement);
    
    // Add to the document
    document.body.appendChild(loadingOverlayElement);
}

/**
 * Show loading indicator with a message
 * @param {String} message - Message to display (optional)
 */
function showLoading(message = 'Loading...') {
    initializeLoadingIndicator();
    
    // Update the message
    loadingMessageElement.querySelector('div').textContent = message;
    
    // Show the overlay
    loadingOverlayElement.style.display = 'block';
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    if (loadingOverlayElement) {
        loadingOverlayElement.style.display = 'none';
    }
}

/**
 * Safely get a DOM element, log warning if not found
 * @param {String} id - Element ID
 * @returns {HTMLElement|null} - The element or null if not found
 */
function getElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        console.warn(`Element with ID "${id}" not found`);
    }
    return element;
}

/**
 * Format an attribute name as a code (lowercase, underscores)
 * @param {String} name - Attribute name
 * @returns {String} - Formatted code
 */
function formatAttributeCode(name) {
    if (!name) return '';

    // First, convert to lowercase
    let code = name.toLowerCase();

    // Replace spaces and non-alphanumeric characters with underscores
    code = code.replace(/\s+/g, '_')
        .replace(/[^a-z0-9_]/g, '_');

    // Remove consecutive underscores
    code = code.replace(/_+/g, '_');

    // Remove leading and trailing underscores
    code = code.replace(/^_+|_+$/g, '');

    // Ensure the code starts with a letter (Akeneo requirement)
    if (!/^[a-z]/.test(code)) {
        code = 'attr_' + code;
    }

    // Akeneo typically limits attribute codes to 255 characters but shorter is better
    if (code.length > 50) {
        code = code.substring(0, 50);
    }

    return code;
}

/**
 * Format an attribute name as a label (Title Case)
 * @param {String} name - Attribute name
 * @returns {String} - Formatted label
 */
function formatAttributeLabel(name) {
    if (!name) return '';

    return name
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Format an attribute value as a code (lowercase, underscores)
 * @param {String} value - Attribute value
 * @returns {String} - Formatted code
 */
function formatValueCode(value) {
    if (!value) return '';

    // If value is numeric, prefix with 'val_' - Removed
    // if (!isNaN(value) && value !== '') {
    //     return 'val_' + value.toString().replace(/\./g, '_');
    // }

    return value
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_]/g, '')
        .replace(/_+/g, '_');
}

/**
 * Format an attribute value as a label (Title Case)
 * @param {String} value - Attribute value
 * @returns {String} - Formatted label
 */
function formatValueLabel(value) {
    if (!value) return '';

    return value
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Show a confirm dialog
 * @param {String} message - Message to display
 * @returns {Boolean} - True if confirmed, false if canceled
 */
function confirmDialog(message) {
    return window.confirm(message);
}

/**
 * Show an alert message
 * @param {String} message - Message to display
 */
function showAlert(message) {
    window.alert(message);
}

/**
 * Download a file
 * @param {String} content - File content
 * @param {String} fileName - File name
 * @param {String} contentType - MIME type
 */
function downloadFile(content, fileName, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();

    setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }, 0);
}

// Export utility functions
export default {
    showLoading,
    hideLoading,
    getElement,
    formatAttributeCode,
    formatAttributeLabel,
    formatValueCode,
    formatValueLabel,
    confirmDialog,
    showAlert,
    downloadFile,
    addTableStyles,
    applyTableStyles  // Add these new functions
};

/**
 * Adds table styling for fixed headers and full-width columns
 */
function addTableStyles() {
    // Check if styles are already added
    if (document.getElementById('attribute-comparison-table-styles')) {
        return; // Already added
    }
    
    // Create style element
    const styleElement = document.createElement('style');
    styleElement.id = 'attribute-comparison-table-styles';
    
    // Add the CSS
    styleElement.textContent = `
    /* Sticky headers and full-width tables */
    .attribute-comparison-tables thead {
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
        box-shadow: 0 1px 0 rgba(27, 31, 35, 0.1);
    }

    .attribute-comparison-tables table {
        width: 100%;
        table-layout: fixed;
    }

    .attribute-comparison-tables th, 
    .attribute-comparison-tables td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Column width distribution */
    .attribute-comparison-tables .col-name {
        width: 40%;
    }

    .attribute-comparison-tables .col-count {
        width: 15%;
    }

    .attribute-comparison-tables .col-status {
        width: 25%;
    }

    .attribute-comparison-tables .col-actions {
        width: 20%;
    }

    /* Improved scrollable table areas */
    .table-container {
        max-height: 600px;
        overflow-y: auto;
        border: 1px solid var(--color-border-default);
        border-radius: 6px;
    }

    .table-container table {
        margin-bottom: 0;
        border: none;
    }
    `;
    
    // Add to document head
    document.head.appendChild(styleElement);
}

/**
 * Apply table styles to the appropriate elements
 */
function applyTableStyles() {
    // First ensure styles are added
    addTableStyles();
    
    // Add classes to table containers
    document.querySelectorAll('#attributes-table, #attribute-values-list').forEach(table => {
        const container = table.closest('.overflow-auto');
        if (container) {
            container.classList.add('attribute-comparison-tables');
            container.classList.add('table-container');
        }
    });
    
    // Update column classes if needed
    document.querySelectorAll('#attributes-table th, #attribute-values-list th').forEach((th, index) => {
        if (index === 0) th.classList.add('col-name');
        if (index === 1) th.classList.add('col-count');
        if (index === 2) th.classList.add('col-status');
        if (index === 3) th.classList.add('col-actions');
    });
}