// In attribute-comparison-bridge.js
window.initializeAttributeComparison = function() {
    console.log('Initializing attribute comparison (bridge function called)');
    
    // Check if the DOM has the necessary elements
    const container = document.getElementById('attributeComparisonContainer');
    if (!container) {
        console.error('Missing attributeComparisonContainer element');
        return;
    }
    
    // If the module is already loaded, use it
    if (window.attributeComparisonModule && window.attributeComparisonModule.initialize) {
        console.log('Module already loaded, calling initialize...');
        try {
            window.attributeComparisonModule.initialize();
        } catch (error) {
            console.error('Error in initialize function:', error);
        }
        return;
    }
    
    // If module not loaded yet, load it
    console.log('Loading attribute comparison module dynamically');
    const script = document.createElement('script');
    script.type = 'module';
    script.src = 'scripts/attribute-comparison/index.js';
    script.onload = () => {
        console.log('Attribute comparison module loaded, initializing...');
        if (window.attributeComparisonModule && window.attributeComparisonModule.initialize) {
            window.attributeComparisonModule.initialize();
        }
    };
    document.head.appendChild(script);
};