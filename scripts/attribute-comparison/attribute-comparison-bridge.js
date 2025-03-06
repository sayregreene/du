// /var/www/html/du/scripts/attribute-comparison-bridge.js

/**
 * This file serves as a bridge between the modular attribute comparison code
 * and the global initializeAttributeComparison function expected by scripts.js
 */

// Add this to attribute-comparison-bridge.js
console.log('Bridge script loaded, checking imports...');

// Check if module loading is working
setTimeout(() => {
    console.log('Status check: attributeComparisonModule exists:', !!window.attributeComparisonModule);
}, 2000);

// Add this to attribute-comparison-bridge.js
window.initializeAttributeComparison = function() {
    console.log('Initializing attribute comparison (placeholder)');
    
    // If the module is already loaded, call its initialize function
    if (window.attributeComparisonModule && window.attributeComparisonModule.initialize) {
        console.log('Module already loaded, calling initialize...');
        try {
            window.attributeComparisonModule.initialize();
            console.log('Initialize function completed successfully');
        } catch (error) {
            console.error('Error in initialize function:', error);
        }
        return;
    }
    
    console.log('Module not yet loaded - will initialize when ready');
    
    // // Attempt to initialize the attribute comparison container
    // const container = document.getElementById('attributeComparisonContainer');
    // if (container) {
    //     // Add a loading indicator to show something is happening
    //     container.innerHTML = '<div class="blankslate"><h3>Loading attribute comparison module...</h3></div>';
    // }
};

// Load the actual module
document.addEventListener('DOMContentLoaded', () => {
    const script = document.createElement('script');
    script.type = 'module';
    script.src = 'scripts/attribute-comparison/index.js';
    
    // When the module loads, it will update the placeholder function
    script.onload = () => {
        console.log('Attribute comparison module loaded');
        
        // If the page is already showing attribute comparison, initialize it now
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page');
        if (currentPage === 'attribute-comparison' && window.attributeComparisonModule) {
            console.log('Auto-initializing attribute comparison');
            window.attributeComparisonModule.initialize();
        }
    };
    
    document.head.appendChild(script);
    console.log('Attribute comparison module script added to page');
});