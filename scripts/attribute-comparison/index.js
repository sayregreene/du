/**
 * Main entry point for the attribute comparison module
 * Initializes the module and handles tab switching
 */
import state from './state.js';
import uiUtils from './ui-utils.js';
import attributesModule from './modules/attributes.js';
import valuesModule from './modules/values.js';
import mappingsModule from './modules/mappings.js';
import exportModule from './modules/export.js';

// Tab elements
let tabElements = {
    attributesTab: null,
    valuesTab: null,
    mappingsTab: null,
    attributesContent: null,
    valuesContent: null,
    mappingsContent: null
};

// Active tab
let activeTab = 'attributes';

/**
 * Initialize the attribute comparison module
 * This function is called when the page loads
 */
function initialize() {
    console.log('Initializing attribute comparison module...');
    
    // Cache tab elements
    tabElements = {
        attributesTab: document.getElementById('tab-attributes'),
        valuesTab: document.getElementById('tab-attribute-values'),
        mappingsTab: document.getElementById('tab-view-mappings'),
        attributesContent: document.getElementById('content-attributes'),
        valuesContent: document.getElementById('content-attribute-values'),
        mappingsContent: document.getElementById('content-view-mappings')
    };
    
    // Check if critical elements are available
    if (!tabElements.attributesTab || !tabElements.valuesTab || !tabElements.attributesContent ||
        !tabElements.valuesContent) {
        console.error('Critical DOM elements for attribute mapping are missing');
        return; // Exit initialization if critical elements are missing
    }
    
    // Set up tab navigation
    setupTabNavigation();
    
    // Initialize the attributes module (default tab)
    attributesModule.initialize();
    
    // Hook up export button if present
    const exportBtn = document.getElementById('btn-export-mappings');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            if (exportModule && typeof exportModule.showExportModal === 'function') {
                exportModule.showExportModal();
            } else {
                console.warn('Export module not loaded or missing showExportModal function');
            }
        });
    }
    
    // Initialize export dialog
    if (exportModule && typeof exportModule.initialize === 'function') {
        exportModule.initialize();
    }
}

/**
 * Set up tab navigation
 */
function setupTabNavigation() {
    // Attributes tab
    tabElements.attributesTab.addEventListener('click', function(e) {
        e.preventDefault();
        showTab('attributes');
    });
    
    // Values tab
    tabElements.valuesTab.addEventListener('click', function(e) {
        e.preventDefault();
        showTab('values');
    });
    
    // Mappings tab
    if (tabElements.mappingsTab) {
        tabElements.mappingsTab.addEventListener('click', function(e) {
            e.preventDefault();
            showTab('mappings');
        });
    }
}

/**
 * Show a specific tab and initialize its module if needed
 * @param {String} tabName - Name of the tab to show
 */
function showTab(tabName) {
    console.log('Showing tab:', tabName);
    
    // Only initialize modules when they're first accessed
    let moduleNeedsInitialization = false;
    
    // Update tab selection
    if (tabName === 'attributes') {
        // Update UI
        tabElements.attributesTab.classList.add('selected');
        tabElements.valuesTab.classList.remove('selected');
        if (tabElements.mappingsTab) tabElements.mappingsTab.classList.remove('selected');
        tabElements.attributesContent.classList.remove('d-none');
        tabElements.valuesContent.classList.add('d-none');
        if (tabElements.mappingsContent) tabElements.mappingsContent.classList.add('d-none');
        
        // No need to initialize - it's done by default
    } else if (tabName === 'values') {
        // Update UI
        tabElements.attributesTab.classList.remove('selected');
        tabElements.valuesTab.classList.add('selected');
        if (tabElements.mappingsTab) tabElements.mappingsTab.classList.remove('selected');
        tabElements.attributesContent.classList.add('d-none');
        tabElements.valuesContent.classList.remove('d-none');
        if (tabElements.mappingsContent) tabElements.mappingsContent.classList.add('d-none');
        
        // Initialize values module if this is the first time showing it
        if (activeTab !== 'values') {
            moduleNeedsInitialization = true;
        }
    } else if (tabName === 'mappings') {
        // Update UI
        tabElements.attributesTab.classList.remove('selected');
        tabElements.valuesTab.classList.remove('selected');
        if (tabElements.mappingsTab) tabElements.mappingsTab.classList.add('selected');
        tabElements.attributesContent.classList.add('d-none');
        tabElements.valuesContent.classList.add('d-none');
        if (tabElements.mappingsContent) tabElements.mappingsContent.classList.remove('d-none');
        
        // Initialize mappings module if this is the first time showing it
        if (activeTab !== 'mappings') {
            moduleNeedsInitialization = true;
        }
    }
    
    // Track the active tab
    activeTab = tabName;
    
    // Initialize the appropriate module if needed
    if (moduleNeedsInitialization) {
        if (tabName === 'values') {
            if (valuesModule && typeof valuesModule.initialize === 'function') {
                valuesModule.initialize();
            }
        } else if (tabName === 'mappings') {
            if (mappingsModule && typeof mappingsModule.initialize === 'function') {
                mappingsModule.initialize();
            }
        }
    }
}

// Expose function for global usage
window.initializeAttributeComparison = initialize;

// Export the module
export default {
    initialize,
    showTab
};