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

// Main initialization function
async function initializePivotreeProducts() {
    console.log('Initializing product analysis...');
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

// Initialize when the script loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePivotreeProducts);
} else {
    initializePivotreeProducts();
}