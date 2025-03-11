//  /var/www/html/du/scripts/pivotree-products.js

// Global variables
let currentPvtSkuId = null;
let debounceTimeout = null;
let currentPage = 1;
let totalPages = 1;
let currentFilters = {
    search: '',
    brand: '',
    node: '',
    page: 1,
    limit: 50
};

// Export functionality variables
let exportInProgress = false;
let exportCancelled = false;
let currentExportProgress = 0;
let exportTotalProducts = 0;

// Main initialization function
async function initializePivotreeProducts() {
    console.log('Initializing product analysis...');
    addExportUI(); // Add export UI elements
    await loadFilterOptions();
    setupProductEventListeners();
    await loadPivotreeProducts();
}

async function loadFilterOptions() {
    try {
        const response = await fetch('/du/api/pivotree/products.php?action=getFilterOptions');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        if (!result || !result.brands || !result.nodes) {
            throw new Error('Invalid filter options data received');
        }
        
        // Populate brand filter
        const brandFilter = document.getElementById('brandFilter');
        if (brandFilter) {
            brandFilter.innerHTML = '<option value="">All Brands</option>' +
                result.brands.map(brand => 
                    `<option value="${brand}">${brand}</option>`
                ).join('');
        }

        // Populate node filter
        const nodeFilter = document.getElementById('nodeFilter');
        if (nodeFilter) {
            nodeFilter.innerHTML = '<option value="">All Categories</option>' +
                result.nodes.map(node => 
                    `<option value="${node}">${node}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading filter options:', error);
        // Continue execution even if filters fail to load
    }
}

function setupProductEventListeners() {
    // Search input handler with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                currentFilters.search = e.target.value.trim();
                currentFilters.page = 1; // Reset to first page on new search
                loadPivotreeProducts();
            }, 300);
        });
    }

    // Filter handlers
    ['brandFilter', 'nodeFilter'].forEach(id => {
        const filter = document.getElementById(id);
        if (filter) {
            filter.addEventListener('change', (e) => {
                currentFilters[id === 'brandFilter' ? 'brand' : 'node'] = e.target.value;
                currentFilters.page = 1; // Reset to first page on filter change
                loadPivotreeProducts();
            });
        }
    });

    // Pagination handlers
    document.getElementById('prevPage')?.addEventListener('click', () => {
        if (currentFilters.page > 1) {
            currentFilters.page--;
            loadPivotreeProducts();
        }
    });

    document.getElementById('nextPage')?.addEventListener('click', () => {
        if (currentFilters.page < totalPages) {
            currentFilters.page++;
            loadPivotreeProducts();
        }
    });
}

async function loadPivotreeProducts() {
    showLoading('Loading Pivotree Product Data');
    try {
        // Build query string from filters
        const queryParams = new URLSearchParams({
            action: 'getProducts',
            page: currentFilters.page.toString(),
            limit: currentFilters.limit.toString(),
            search: currentFilters.search,
            brand: currentFilters.brand,
            node: currentFilters.node
        });

        console.log('Loading products with params:', queryParams.toString());

        const response = await fetch(`/du/api/pivotree/products.php?${queryParams}`);
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to load products');
        }
        
        // Update pagination info
        totalPages = Math.ceil(result.total / result.limit);
        updatePaginationUI(result.page, totalPages, result.total);
        
        // Display products
        displayProducts(result.data);
        
    } catch (error) {
        console.error('Error loading products:', error);
        document.getElementById('productsTableBody').innerHTML = 
            `<tr><td colspan="4" class="text-center text-red">
                Error loading products: ${error.message}. Please try again.
            </td></tr>`;
    }
    hideLoading();
}

function updatePaginationUI(currentPage, totalPages, totalRecords) {
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        paginationInfo.innerHTML = `
            Page ${currentPage} of ${totalPages} (${totalRecords} total records)
        `;
    }

    // Update button states
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
}

function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    
    if (!Array.isArray(products) || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr class="product-row" data-pvt-sku-id="${product.pvt_sku_id}">
            <td class="px-3 py-2">${product.brand || ''}</td>
            <td class="px-3 py-2">${product.sku_id || ''}</td>
            <td class="px-3 py-2">${product.manufacturer_part_number || ''}</td>
            <td class="px-3 py-2">${product.short_description || ''}</td>
        </tr>
    `).join('');

    // Add click handlers
    tbody.querySelectorAll('.product-row').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            selectProduct(row.dataset.pvtSkuId);
            // Highlight selected row
            tbody.querySelectorAll('.product-row').forEach(r => r.classList.remove('bg-yellow-light'));
            row.classList.add('bg-yellow-light');
        });
    });
}

async function selectProduct(pvtSkuId) {
    if (!pvtSkuId) return;
    
    try {
        const response = await fetch(`/du/api/pivotree/products.php?action=getAttributes&pvt_sku_id=${encodeURIComponent(pvtSkuId)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to load attributes');
        }
        
        displayProductDetails(pvtSkuId, result.data);
    } catch (error) {
        console.error('Error loading product details:', error);
        document.getElementById('productDetailsContent').innerHTML = `
            <div class="flash flash-error">Error loading product details. Please try again.</div>
        `;
    }
}

function showLoading(message = 'Loading...') {
    const container = document.querySelector('.loading-container');
    const text = document.querySelector('.spinner-text');
    
    if (text) {
      text.textContent = message;
    }
    
    if (container) {
      container.style.display = 'block';
      // Prevent background scrolling while loading
      document.body.style.overflow = 'hidden';
    }
}
  
function hideLoading() {
    const container = document.querySelector('.loading-container');
    
    if (container) {
      container.style.display = 'none';
      // Restore scrolling
      document.body.style.overflow = '';
    }
}

function displayProductDetails(pvtSkuId, attributes) {
    const detailsContent = document.getElementById('productDetailsContent');
    if (!detailsContent) return;

    // Find the selected product from the table
    const selectedProduct = document.querySelector(`tr[data-pvt-sku-id="${pvtSkuId}"]`);
    const productData = selectedProduct ? {
        brand: selectedProduct.cells[0].textContent,
        sku_id: selectedProduct.cells[1].textContent,
        manufacturer_part_number: selectedProduct.cells[2].textContent,
        short_description: selectedProduct.cells[3].textContent
    } : null;

    let html = `
        <div class="mb-4">
            <div class="h4 mb-2">Basic Information</div>
            <div class="Box color-shadow-small">
                <div class="Box-row">
                    <strong>PVT SKU ID:</strong> ${pvtSkuId}
                </div>
                ${productData ? `
                    <div class="Box-row">
                        <strong>Brand:</strong> ${productData.brand}
                    </div>
                    <div class="Box-row">
                        <strong>SKU ID:</strong> ${productData.sku_id}
                    </div>
                    <div class="Box-row">
                        <strong>Mfg Part Number:</strong> ${productData.manufacturer_part_number}
                    </div>
                    <div class="Box-row">
                        <strong>Description:</strong> ${productData.short_description}
                    </div>
                ` : ''}
            </div>
        </div>
    `;

    // Add attributes section
    if (Array.isArray(attributes) && attributes.length > 0) {
        html += `
            <div>
                <div class="h4 mb-2">Attributes</div>
                <div class="Box color-shadow-small">
                    ${attributes.map(attr => `
                        <div class="Box-row">
                            <strong>${attr.attribute_name}:</strong> 
                            ${attr.attribute_value}
                            ${attr.uom ? `(${attr.uom})` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } else {
        html += `
            <div>
                <div class="h4 mb-2">Attributes</div>
                <div class="Box color-shadow-small">
                    <div class="Box-row">No attributes found</div>
                </div>
            </div>
        `;
    }

    detailsContent.innerHTML = html;
}

/**
 * EXPORT FUNCTIONALITY STARTS HERE
 */

/**
 * Adds export UI elements to the product data page
 */
function addExportUI() {
    console.log('Adding export UI to product data page');
    
    // Find the filters container
    const filtersBox = document.querySelector('.Box.mb-3');
    if (!filtersBox) {
        console.error('Filters container not found');
        return;
    }
    
    // Create export button section
    const exportSection = document.createElement('div');
    exportSection.className = 'd-flex flex-items-center mt-2';
    exportSection.innerHTML = `
        <div class="flex-auto mr-3">
            <select id="exportFormat" class="form-select">
                <option value="csv">CSV Export</option>
                <option value="json">JSON Export</option>
                <option value="excel">Excel Export</option>
            </select>
        </div>
        <div>
            <button id="exportCurrentBtn" class="btn btn-sm mr-2">Export Current View</button>
            <button id="exportAllBtn" class="btn btn-sm btn-primary">Export All Products</button>
        </div>
    `;
    
    // Append to filters box
    filtersBox.appendChild(exportSection);
    
    // Create export progress modal
    const exportModal = document.createElement('div');
    exportModal.className = 'Box Box-overlay d-none';
    exportModal.id = 'exportProgressModal';
    exportModal.innerHTML = `
        <div class="Box-overlay-header">
            <h3 class="Box-title">Exporting Products</h3>
            <button class="Box-btn-octicon" id="cancelExportBtn" type="button">
                <svg class="octicon octicon-x" viewBox="0 0 16 16" width="16" height="16">
                    <path fill-rule="evenodd" d="M3.72 3.72a.75.75 0 011.06 0L8 6.94l3.22-3.22a.75.75 0 111.06 1.06L9.06 8l3.22 3.22a.75.75 0 11-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 01-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 010-1.06z"></path>
                </svg>
            </button>
        </div>
        <div class="Box-body">
            <p id="exportStatusText">Preparing to export products...</p>
            <div class="Progress my-3">
                <div class="bg-blue" id="exportProgressBar" style="width: 0%"></div>
            </div>
            <div id="exportDetails" class="text-small color-fg-muted">
                Processed: <span id="exportProcessed">0</span> of <span id="exportTotal">0</span> products
            </div>
        </div>
    `;
    
    // Append to body
    document.body.appendChild(exportModal);
    
    // Set up event listeners for export buttons
    document.getElementById('exportCurrentBtn').addEventListener('click', () => {
        exportProducts(false); // Export current view
    });
    
    document.getElementById('exportAllBtn').addEventListener('click', () => {
        exportProducts(true); // Export all products
    });
    
    // Set up cancel button
    document.getElementById('cancelExportBtn').addEventListener('click', () => {
        cancelExport();
    });
}

/**
 * Shows the export progress modal
 */
function showExportProgress() {
    const modal = document.getElementById('exportProgressModal');
    if (modal) {
        modal.classList.remove('d-none');
    }
    
    // Reset progress
    currentExportProgress = 0;
    exportCancelled = false;
    updateExportProgress(0, 0);
}

/**
 * Hides the export progress modal
 */
function hideExportProgress() {
    const modal = document.getElementById('exportProgressModal');
    if (modal) {
        modal.classList.add('d-none');
    }
}

/**
 * Updates the export progress display
 * @param {Number} processed - Number of processed products
 * @param {Number} total - Total number of products to process
 */
function updateExportProgress(processed, total) {
    const progressBar = document.getElementById('exportProgressBar');
    const processedSpan = document.getElementById('exportProcessed');
    const totalSpan = document.getElementById('exportTotal');
    const statusText = document.getElementById('exportStatusText');
    
    if (progressBar && processedSpan && totalSpan && statusText) {
        const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        
        progressBar.style.width = `${percentage}%`;
        processedSpan.textContent = processed;
        totalSpan.textContent = total;
        
        if (exportCancelled) {
            statusText.textContent = 'Export cancelled.';
        } else if (processed === total && total > 0) {
            statusText.textContent = 'Export complete! Preparing download...';
        } else {
            statusText.textContent = `Exporting products (${percentage}%)...`;
        }
    }
}

/**
 * Cancels the current export operation
 */
function cancelExport() {
    if (exportInProgress) {
        exportCancelled = true;
        document.getElementById('exportStatusText').textContent = 'Cancelling export...';
    } else {
        hideExportProgress();
    }
}

/**
 * Modified export function that delegates to the server
 * @param {Boolean} exportAll - Whether to export all products or just the current view
 */
async function exportProducts(exportAll = false) {
    if (exportInProgress) {
        alert('An export is already in progress');
        return;
    }
    
    try {
        exportInProgress = true;
        showExportProgress();
        
        // Get export format
        const format = document.getElementById('exportFormat').value;
        
        // If exporting current view, get the product IDs
        let productIds = [];
        if (!exportAll) {
            const currentProducts = getCurrentProducts();
            productIds = currentProducts.map(product => product.pvt_sku_id);
        }
        
        // Start the export job on the server
        const response = await fetch('/du/api/pivotree/export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                exportAll: exportAll,
                format: format,
                productIds: productIds
            })
        });
        
        if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to start export');
        }
        
        // Get the job ID for status monitoring
        const jobId = result.jobId;
        
        // Start polling for status updates
        await monitorExportProgress(jobId);
        
    } catch (error) {
        console.error('Error during export:', error);
        document.getElementById('exportStatusText').textContent = `Error: ${error.message}`;
        setTimeout(hideExportProgress, 3000);
    } finally {
        exportInProgress = false;
    }
}

async function monitorExportProgress(jobId) {
    let completed = false;
    let cancelled = false;
    
    // Create a "toast" notification that will remain visible when navigating away
    const toast = createPersistentToast('Export in progress', 'Your export is being processed in the background.');
    
    // Begin polling
    while (!completed && !cancelled) {
        try {
            // Check if user cancelled the export
            if (exportCancelled) {
                // Send cancellation request to server
                await fetch(`/du/api/pivotree/export-status.php?jobId=${jobId}&cancel=true`);
                cancelled = true;
                toast.update('Export cancelled', 'The export job was cancelled.');
                break;
            }
            
            // Poll server for status
            const response = await fetch(`/du/api/pivotree/export-status.php?jobId=${jobId}`);
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            
            const status = await response.json();
            
            if (!status.success) {
                throw new Error(status.message || 'Failed to get export status');
            }
            
            // Update progress in UI
            const { progress, total, processed, state, message } = status;
            updateExportProgress(processed, total);
            document.getElementById('exportStatusText').textContent = message || `Exporting products (${Math.round(progress * 100)}%)...`;
            
            if (state === 'completed') {
                // Export is completed
                completed = true;
                document.getElementById('exportStatusText').textContent = 'Export completed successfully!';
                
                // Update toast with download link
                toast.update(
                    'Export completed', 
                    `<a href="/du/api/pivotree/export-download.php?jobId=${jobId}" class="btn btn-sm btn-primary mt-2">Download Export</a>`,
                    true
                );
                
                // Add download button to the export modal too
                const exportActions = document.createElement('div');
                exportActions.className = 'mt-3';
                exportActions.innerHTML = `
                    <a href="/du/api/pivotree/export-download.php?jobId=${jobId}" class="btn btn-primary">
                        Download Export
                    </a>
                    <button class="btn ml-2" onclick="hideExportProgress()">Close</button>
                `;
                
                const modalBody = document.querySelector('#exportProgressModal .Box-body');
                if (modalBody) {
                    modalBody.appendChild(exportActions);
                }
                
                break;
            } else if (state === 'failed') {
                // Export failed
                document.getElementById('exportStatusText').textContent = `Export failed: ${message}`;
                toast.update('Export failed', message || 'The export job failed to complete.', false, 'flash-error');
                break;
            }
            
            // Update toast with current progress
            toast.updateProgress(progress);
            
            // Wait before next poll
            await new Promise(resolve => setTimeout(resolve, 1000));
            
        } catch (error) {
            console.error('Error monitoring export:', error);
            document.getElementById('exportStatusText').textContent = `Error: ${error.message}`;
            toast.update('Export error', error.message, false, 'flash-error');
            break;
        }
    }
}

/**
 * Creates a persistent toast notification that remains visible
 * even when navigating away from the page
 * @param {String} title - Toast title
 * @param {String} message - Toast message
 * @returns {Object} - Toast control object
 */
function createPersistentToast(title, message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('persistent-toasts');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'persistent-toasts';
        toastContainer.className = 'position-fixed bottom-0 right-0 p-3';
        toastContainer.style.zIndex = '1000';
        document.body.appendChild(toastContainer);
        
        // Add CSS for toasts
        const toastStyle = document.createElement('style');
        toastStyle.textContent = `
            .persistent-toast {
                background-color: white;
                border: 1px solid #e1e4e8;
                border-radius: 6px;
                box-shadow: 0 8px 24px rgba(149, 157, 165, 0.2);
                width: 350px;
                margin-top: 10px;
                overflow: hidden;
            }
            .toast-progress {
                height: 4px;
                background-color: #2ea44f;
                width: 0%;
                transition: width 0.3s ease;
            }
        `;
        document.head.appendChild(toastStyle);
    }
    
    // Create the toast element
    const toastId = `toast-${Date.now()}`;
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = 'persistent-toast';
    toast.innerHTML = `
        <div class="toast-progress" style="width: 0%"></div>
        <div class="Box p-3">
            <div class="d-flex flex-items-center">
                <div class="flex-auto">
                    <strong class="toast-title">${title}</strong>
                </div>
                <button class="btn-octicon toast-close" type="button">
                    <svg class="octicon octicon-x" viewBox="0 0 16 16" width="16" height="16">
                        <path fill-rule="evenodd" d="M3.72 3.72a.75.75 0 011.06 0L8 6.94l3.22-3.22a.75.75 0 111.06 1.06L9.06 8l3.22 3.22a.75.75 0 11-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 01-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 010-1.06z"></path>
                    </svg>
                </button>
            </div>
            <div class="toast-message mt-1">${message}</div>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Add close button handler
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.remove();
    });
    
    // Return control object
    return {
        element: toast,
        updateProgress: (progress) => {
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                progressBar.style.width = `${progress * 100}%`;
            }
        },
        update: (newTitle, newMessage, keepOpen = false, flashClass = null) => {
            const titleEl = toast.querySelector('.toast-title');
            const messageEl = toast.querySelector('.toast-message');
            
            if (titleEl) titleEl.textContent = newTitle;
            if (messageEl) messageEl.innerHTML = newMessage;
            
            // Apply flash styling if specified
            if (flashClass) {
                toast.className = `persistent-toast flash ${flashClass}`;
            }
            
            // Auto close after 8 seconds if not keepOpen
            if (!keepOpen) {
                setTimeout(() => {
                    toast.remove();
                }, 8000);
            }
        },
        remove: () => {
            toast.remove();
        }
    };
}

/**
 * Fetches all products from the API
 * @returns {Promise<Array>} - Promise resolving to array of products
 */
async function fetchAllProducts() {
    try {
        document.getElementById('exportStatusText').textContent = 'Fetching all products...';
        
        // We'll need to fetch the total count first to know how many pages to request
        const initialResponse = await fetch('/du/api/pivotree/products.php?action=getProducts&page=1&limit=1');
        if (!initialResponse.ok) {
            throw new Error(`HTTP error! status: ${initialResponse.status}`);
        }
        
        const initialResult = await initialResponse.json();
        if (!initialResult.success) {
            throw new Error(initialResult.message || 'Failed to load products');
        }
        
        const totalProducts = initialResult.total;
        const pageSize = 100; // Use a larger page size for export
        const totalPages = Math.ceil(totalProducts / pageSize);
        
        document.getElementById('exportStatusText').textContent = `Fetching ${totalProducts} products across ${totalPages} pages...`;
        
        // Create an array of page fetch promises
        const pageFetches = [];
        for (let page = 1; page <= totalPages; page++) {
            if (exportCancelled) break;
            
            const pagePromise = fetch(`/du/api/pivotree/products.php?action=getProducts&page=${page}&limit=${pageSize}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    if (!result.success) {
                        throw new Error(result.message || 'Failed to load products');
                    }
                    return result.data;
                });
            
            pageFetches.push(pagePromise);
            
            // Allow cancellation to be detected between page requests
            await new Promise(resolve => setTimeout(resolve, 0));
        }
        
        // Wait for all pages to load
        const pageResults = await Promise.all(pageFetches);
        
        // Combine all pages
        return pageResults.flat();
        
    } catch (error) {
        console.error('Error fetching all products:', error);
        throw error;
    }
}

/**
 * Gets the current products displayed in the table
 * @returns {Array} - Array of currently displayed products
 */
function getCurrentProducts() {
    const products = [];
    
    // Get product rows from the table
    const rows = document.querySelectorAll('#productsTableBody .product-row');
    rows.forEach(row => {
        const pvtSkuId = row.dataset.pvtSkuId;
        const cells = row.cells;
        
        if (pvtSkuId && cells.length >= 4) {
            products.push({
                pvt_sku_id: pvtSkuId,
                brand: cells[0].textContent.trim(),
                sku_id: cells[1].textContent.trim(),
                manufacturer_part_number: cells[2].textContent.trim(),
                short_description: cells[3].textContent.trim()
            });
        }
    });
    
    return products;
}

/**
 * Fetches attributes for a specific product
 * @param {String} pvtSkuId - Product SKU ID
 * @returns {Promise<Array>} - Promise resolving to array of attributes
 */
async function fetchProductAttributes(pvtSkuId) {
    try {
        const response = await fetch(`/du/api/pivotree/products.php?action=getAttributes&pvt_sku_id=${encodeURIComponent(pvtSkuId)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to load attributes');
        }
        
        return result.data;
    } catch (error) {
        console.error(`Error fetching attributes for product ${pvtSkuId}:`, error);
        // Return empty array instead of throwing to continue with other products
        return [];
    }
}

/**
 * Gets mapping information for attributes
 * @param {Array} attributes - Array of product attributes
 * @returns {Promise<Array>} - Promise resolving to array of mapped attributes
 */
async function getMappedAttributes(attributes) {
    try {
        // Fetch all attribute mappings
        const response = await fetch('/du/api/pivotree/attribute-mappings.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        const attributeMappings = result.mappings || [];
        
        // Fetch value mappings for each attribute
        const mappedAttributes = [];
        
        for (const attr of attributes) {
            // Find attribute mapping
            const attrMapping = attributeMappings.find(mapping => 
                mapping.pivotree_attribute_name === attr.attribute_name);
            
            if (!attrMapping) {
                // No mapping for this attribute
                mappedAttributes.push({
                    ...attr,
                    mapped: false,
                    akeneo_attribute_code: null,
                    akeneo_attribute_label: null,
                    is_new_attribute: false,
                    mapped_value: null,
                    mapped_value_label: null
                });
                continue;
            }
            
            // Attribute is mapped, now check value mapping
            const valueMappingsResponse = await fetch(`/du/api/pivotree/attribute-value-mappings.php?attribute_name=${encodeURIComponent(attr.attribute_name)}`);
            if (!valueMappingsResponse.ok) {
                throw new Error(`HTTP error! status: ${valueMappingsResponse.status}`);
            }
            
            const valueResult = await valueMappingsResponse.json();
            const valueMappings = valueResult.mappings || [];
            
            // Find value mapping
            const valueMapping = valueMappings.find(mapping => 
                mapping.pivotree_attribute_name === attr.attribute_name && 
                mapping.pivotree_attribute_value === attr.attribute_value &&
                (
                    (mapping.pivotree_uom === attr.uom) || 
                    (!mapping.pivotree_uom && !attr.uom)
                )
            );
            
            mappedAttributes.push({
                ...attr,
                mapped: true,
                akeneo_attribute_code: attrMapping.akeneo_attribute_code || attrMapping.new_attribute_code,
                akeneo_attribute_label: attrMapping.akeneo_attribute_label || attrMapping.new_attribute_label,
                is_new_attribute: !!attrMapping.is_new_attribute,
                mapped_value: valueMapping ? (valueMapping.akeneo_value_code || valueMapping.new_value_code) : null,
                mapped_value_label: valueMapping ? (valueMapping.akeneo_value_label || valueMapping.new_value_label) : null
            });
        }
        
        return mappedAttributes;
    } catch (error) {
        console.error('Error getting mapped attributes:', error);
        // Return original attributes with default mapping data
        return attributes.map(attr => ({
            ...attr,
            mapped: false,
            akeneo_attribute_code: null,
            akeneo_attribute_label: null,
            is_new_attribute: false,
            mapped_value: null,
            mapped_value_label: null
        }));
    }
}

/**
 * Generates and downloads the export file
 * @param {Array} data - Array of product data with mappings
 * @param {String} format - Export format (csv, json, excel)
 */
function generateExportFile(data, format) {
    if (data.length === 0) {
        alert('No data to export');
        return;
    }
    
    document.getElementById('exportStatusText').textContent = 'Generating export file...';
    
    try {
        let content, filename, mimeType;
        
        switch (format) {
            case 'json':
                content = generateJsonExport(data);
                filename = 'pivotree-products-export.json';
                mimeType = 'application/json';
                break;
                
            case 'excel':
                content = generateExcelExport(data);
                filename = 'pivotree-products-export.xlsx';
                mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
                
            case 'csv':
            default:
                content = generateCsvExport(data);
                filename = 'pivotree-products-export.csv';
                mimeType = 'text/csv';
                break;
        }
        
        // Create download link
        document.getElementById('exportStatusText').textContent = 'Download ready!';
        
        // If it's an Excel export (binary format), handle differently
        if (format === 'excel') {
            downloadExcelFile(content, filename);
        } else {
            // Text-based formats (CSV, JSON)
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            
            setTimeout(() => {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 0);
        }
        
    } catch (error) {
        console.error('Error generating export file:', error);
        alert('Error generating export file: ' + error.message);
    }
}

/**
 * Generates CSV export content
 * @param {Array} data - Export data
 * @returns {String} - CSV content
 */
function generateCsvExport(data) {
    // CSV header
    let csv = 'PVT_SKU_ID,Brand,SKU_ID,Manufacturer_Part_Number,Short_Description,Attribute_Name,Attribute_Value,UOM,Mapped_Attribute_Code,Mapped_Attribute_Label,Is_New_Attribute,Mapped_Value_Code,Mapped_Value_Label\n';
    
    // Add data rows
    data.forEach(item => {
        const product = item.product;
        const mappedAttributes = item.mappedAttributes || [];
        
        if (mappedAttributes.length === 0) {
            // Product with no attributes
            csv += `${escapeCsvValue(product.pvt_sku_id)},${escapeCsvValue(product.brand)},${escapeCsvValue(product.sku_id)},${escapeCsvValue(product.manufacturer_part_number)},${escapeCsvValue(product.short_description)},,,,,,,,\n`;
        } else {
            // Product with attributes
            mappedAttributes.forEach(attr => {
                csv += `${escapeCsvValue(product.pvt_sku_id)},${escapeCsvValue(product.brand)},${escapeCsvValue(product.sku_id)},${escapeCsvValue(product.manufacturer_part_number)},${escapeCsvValue(product.short_description)},`;
                csv += `${escapeCsvValue(attr.attribute_name)},${escapeCsvValue(attr.attribute_value)},${escapeCsvValue(attr.uom || '')},`;
                csv += `${escapeCsvValue(attr.akeneo_attribute_code || '')},${escapeCsvValue(attr.akeneo_attribute_label || '')},${attr.is_new_attribute ? 'Yes' : 'No'},`;
                csv += `${escapeCsvValue(attr.mapped_value || '')},${escapeCsvValue(attr.mapped_value_label || '')}\n`;
            });
        }
    });
    
    return csv;
}

/**
 * Escape a value for CSV
 * @param {String} value - Value to escape
 * @returns {String} - Escaped value
 */
function escapeCsvValue(value) {
    if (value === null || value === undefined) return '';
    
    value = String(value);
    
    // If value contains commas, quotes, or newlines, wrap it in quotes
    if (value.includes(',') || value.includes('"') || value.includes('\n')) {
        // Double any quotes inside the value
        return `"${value.replace(/"/g, '""')}"`;
    }
    
    return value;
}

/**
 * Generates JSON export content
 * @param {Array} data - Export data
 * @returns {String} - JSON content
 */
function generateJsonExport(data) {
    // Transform data to a more JSON-friendly structure
    const jsonData = data.map(item => {
        const product = item.product;
        const mappedAttributes = item.mappedAttributes || [];
        
        return {
            product: {
                pvt_sku_id: product.pvt_sku_id,
                brand: product.brand,
                sku_id: product.sku_id,
                manufacturer_part_number: product.manufacturer_part_number,
                short_description: product.short_description
            },
            attributes: mappedAttributes.map(attr => ({
                name: attr.attribute_name,
                value: attr.attribute_value,
                uom: attr.uom || null,
                mapping: {
                    mapped: attr.mapped,
                    akeneo_attribute: {
                        code: attr.akeneo_attribute_code,
                        label: attr.akeneo_attribute_label,
                        is_new: attr.is_new_attribute
                    },
                    akeneo_value: {
                        code: attr.mapped_value,
                        label: attr.mapped_value_label
                    }
                }
            }))
        };
    });
    
    return JSON.stringify(jsonData, null, 2);
}

/**
 * Generates Excel export content
 * @param {Array} data - Export data
 * @returns {Blob} - Excel file as Blob
 */
function generateExcelExport(data) {
    // Load XLSX from CDN if not already available
    if (typeof XLSX === 'undefined') {
        // This is a placeholder - in a real implementation, you'd need to ensure 
        // XLSX.js is loaded or use a dynamic import
        throw new Error('XLSX library not available. Please include sheet.js in your project.');
    }
    
    // Prepare data for Excel
    const excelData = [];
    
    // Add headers
    excelData.push([
        'PVT_SKU_ID', 'Brand', 'SKU_ID', 'Manufacturer_Part_Number', 'Short_Description',
        'Attribute_Name', 'Attribute_Value', 'UOM',
        'Mapped_Attribute_Code', 'Mapped_Attribute_Label', 'Is_New_Attribute',
        'Mapped_Value_Code', 'Mapped_Value_Label'
    ]);
    
    // Add data rows
    data.forEach(item => {
        const product = item.product;
        const mappedAttributes = item.mappedAttributes || [];
        
        if (mappedAttributes.length === 0) {
            // Product with no attributes
            excelData.push([
                product.pvt_sku_id, product.brand, product.sku_id, 
                product.manufacturer_part_number, product.short_description,
                '', '', '', '', '', '', '', ''
            ]);
        } else {
            // Product with attributes
            mappedAttributes.forEach(attr => {
                excelData.push([
                    product.pvt_sku_id, product.brand, product.sku_id, 
                    product.manufacturer_part_number, product.short_description,
                    attr.attribute_name, attr.attribute_value, attr.uom || '',
                    attr.akeneo_attribute_code || '', attr.akeneo_attribute_label || '', 
                    attr.is_new_attribute ? 'Yes' : 'No',
                    attr.mapped_value || '', attr.mapped_value_label || ''
                ]);
            });
        }
    });
    
    // Create workbook
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    XLSX.utils.book_append_sheet(wb, ws, 'Pivotree Products');
    
    // Generate Excel file
    const excelBuffer = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    return new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
}

/**
 * Downloads an Excel file
 * @param {Blob} blob - Excel file as Blob
 * @param {String} filename - File name
 */
function downloadExcelFile(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    
    setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }, 0);
}

// Initialize when the script loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePivotreeProducts);
} else {
    initializePivotreeProducts();
}