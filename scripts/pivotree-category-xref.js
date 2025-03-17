// /var/www/html/du/scripts/pivotree-category-xref.js

// Create a namespace for category xref to avoid global variable conflicts
const CategoryXref = {
    // Configuration
    currentPage: 1,
    totalPages: 1,
    debounceTimeout: null,
    currentFilters: {
        search: '',
        source: 'pivotree', // Default to pivotree view
        mappingStatus: 'all',
        page: 1,
        limit: 50
    },
    selectedCategory: null,

    // Main initialization function
    initializeCategoryXref: function() {
        console.log('Initializing category cross-reference...');
        
        // First, sync categories if needed
        this.syncCategoriesIfNeeded();
        
        this.setupEventListeners();
        this.loadCategories();
    },

    // Check if categories need syncing
    syncCategoriesIfNeeded: async function() {
        try {
            // Check if we have any pivotree categories
            const response = await fetch('/du/api/pivotree/categories.php?action=getPivotreeCategories&limit=1');
            const data = await response.json();
            
            if (!data.success || data.total === 0) {
                console.log('No Pivotree categories found, syncing...');
                await this.syncPivotreeCategories();
                await this.syncAkeneoCategories();
            }
        } catch (error) {
            console.error('Error checking categories:', error);
        }
    },

    // Sync Pivotree categories from database
    syncPivotreeCategories: async function() {
        showLoading('Syncing Pivotree Categories');
        
        try {
            const response = await fetch('/du/api/pivotree/categories.php?action=syncPivotreeCategories');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to sync Pivotree categories');
            }
            
            console.log(data.message);
        } catch (error) {
            console.error('Error syncing Pivotree categories:', error);
            alert('Error syncing Pivotree categories: ' + error.message);
        } finally {
            hideLoading();
        }
    },

    // Sync Akeneo categories from API
    syncAkeneoCategories: async function() {
        showLoading('Syncing Akeneo Categories');
        
        try {
            const response = await fetch('/du/api/pivotree/categories.php?action=syncAkeneoCategories');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to sync Akeneo categories');
            }
            
            console.log(data.message);
        } catch (error) {
            console.error('Error syncing Akeneo categories:', error);
            alert('Error syncing Akeneo categories: ' + error.message);
        } finally {
            hideLoading();
        }
    },

    // Set up event listeners for the page
    setupEventListeners: function() {
        // Search input handler with debounce
        const searchInput = document.getElementById('categorySearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimeout);
                this.debounceTimeout = setTimeout(() => {
                    this.currentFilters.search = e.target.value.trim();
                    this.currentFilters.page = 1; // Reset to first page on new search
                    this.loadCategories();
                }, 300);
            });
        }

        // Source filter handler
        const sourceFilter = document.getElementById('sourceFilter');
        if (sourceFilter) {
            sourceFilter.addEventListener('change', (e) => {
                this.currentFilters.source = e.target.value;
                this.currentFilters.page = 1; // Reset to first page on filter change
                this.loadCategories();
            });
        }

        // Pagination handlers
        const self = this; // Store reference to 'this' for use in event listeners
        document.getElementById('prevPageBtn')?.addEventListener('click', () => {
            if (self.currentFilters.page > 1) {
                self.currentFilters.page--;
                self.loadCategories();
            }
        });

        document.getElementById('nextPageBtn')?.addEventListener('click', () => {
            if (self.currentFilters.page < self.totalPages) {
                self.currentFilters.page++;
                self.loadCategories();
            }
        });
        
        // Add sync buttons to the filter box
        const filtersBox = document.querySelector('.Box.mb-3 .Box-body');
        if (filtersBox) {
            const syncButtonsDiv = document.createElement('div');
            syncButtonsDiv.className = 'd-flex flex-items-start mt-2';
            syncButtonsDiv.innerHTML = `
                <button id="syncPivotreeBtn" class="btn btn-sm mr-2">Refresh Pivotree Categories</button>
                <button id="syncAkeneoBtn" class="btn btn-sm">Refresh Akeneo Categories</button>
            `;
            filtersBox.appendChild(syncButtonsDiv);
            
            // Add event listeners for sync buttons
            document.getElementById('syncPivotreeBtn')?.addEventListener('click', () => {
                self.syncPivotreeCategories().then(() => self.loadCategories());
            });
            
            document.getElementById('syncAkeneoBtn')?.addEventListener('click', () => {
                self.syncAkeneoCategories().then(() => self.loadCategories());
            });
        }
    },

    // Load categories data
    loadCategories: async function() {
        showLoading('Loading Categories');
        
        try {
            // Determine which API endpoint to use based on source filter
            let endpoint;
            if (this.currentFilters.source === 'akeneo') {
                endpoint = '/du/api/pivotree/categories.php?action=getAkeneoCategories';
            } else {
                endpoint = '/du/api/pivotree/categories.php?action=getPivotreeCategories';
            }
            
            // Build query string
            const queryParams = new URLSearchParams({
                page: this.currentFilters.page.toString(),
                limit: this.currentFilters.limit.toString(),
                search: this.currentFilters.search,
                mappingStatus: this.currentFilters.mappingStatus
            });
            
            // Make API request
            const response = await fetch(`${endpoint}&${queryParams.toString()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load categories');
            }
            
            // Update pagination info
            this.totalPages = Math.ceil(data.total / data.limit);
            this.updatePaginationInfo(data.page, this.totalPages, data.total);
            
            // Display categories
            this.displayCategories(data.categories);
            
            // Update category count
            const countElement = document.getElementById('category-showing-count');
            if (countElement) {
                countElement.textContent = data.total;
            }
            
        } catch (error) {
            console.error('Error loading categories:', error);
            document.getElementById('categoriesTableBody').innerHTML = `
                <tr><td colspan="5" class="text-center text-red">
                    Error loading categories: ${error.message}. Please try again.
                </td></tr>
            `;
        } finally {
            hideLoading();
        }
    },

    // Update pagination information
    updatePaginationInfo: function(currentPage, totalPages, totalCategories) {
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            paginationInfo.innerHTML = `
                Page ${currentPage} of ${totalPages} (${totalCategories} total)
            `;
        }

        // Update button states
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    },

    // Display categories in the table
    displayCategories: function(categories) {
        const self = this; // Store reference to this for use in event handlers
        const tbody = document.getElementById('categoriesTableBody');
        if (!tbody) return;
        
        if (!Array.isArray(categories) || categories.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No categories found</td></tr>';
            return;
        }
        
        const isPivotree = this.currentFilters.source !== 'akeneo';
        
        tbody.innerHTML = categories.map(category => `
            <tr class="category-row" data-id="${category.id}" data-source="${isPivotree ? 'pivotree' : 'akeneo'}">
                <td class="px-3 py-2">
                    <span class="Label Label--${isPivotree ? 'blue' : 'green'}">
                        ${isPivotree ? 'pivotree' : 'akeneo'}
                    </span>
                </td>
                <td class="px-3 py-2">${category.id}</td>
                <td class="px-3 py-2">${isPivotree ? category.category_name : category.category_name}</td>
                <td class="px-3 py-2">
                    <span class="Label Label--${category.status === 'mapped' ? 'green' : 'yellow'}">
                        ${category.status}
                    </span>
                </td>
                <td class="px-3 py-2">
                    <button class="btn btn-sm btn-outline view-btn">
                        View
                    </button>
                    ${category.status !== 'mapped' ? 
                        `<button class="btn btn-sm btn-primary ml-2 map-btn">
                            Map
                        </button>` : 
                        `<button class="btn btn-sm btn-danger ml-2 unmap-btn">
                            Unmap
                        </button>`
                    }
                </td>
            </tr>
        `).join('');

        // Add event listeners for buttons
        tbody.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('.category-row');
                if (row) {
                    self.selectCategory(row.dataset.id, row.dataset.source);
                }
            });
        });
        
        tbody.querySelectorAll('.map-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('.category-row');
                if (row) {
                    self.openCategoryMappingForm(row.dataset.id, row.dataset.source);
                }
            });
        });
        
        tbody.querySelectorAll('.unmap-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('.category-row');
                if (row) {
                    self.unmapCategory(row.dataset.id, row.dataset.source);
                }
            });
        });
        
        // Make rows clickable
        tbody.querySelectorAll('.category-row').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', (e) => {
                // Don't trigger if clicking on a button
                if (e.target.tagName !== 'BUTTON') {
                    self.selectCategory(row.dataset.id, row.dataset.source);
                    
                    // Highlight the selected row
                    tbody.querySelectorAll('.category-row').forEach(r => 
                        r.classList.remove('bg-yellow-light')
                    );
                    row.classList.add('bg-yellow-light');
                }
            });
        });
    },

    // Select a category to display details
    selectCategory: async function(id, source) {
        console.log(`Selected category: ${id} (${source})`);
        
        try {
            showLoading('Loading Category Details');
            
            // Determine which API endpoint to use based on source
            let endpoint;
            if (source === 'akeneo') {
                endpoint = `/du/api/pivotree/categories.php?action=getAkeneoCategories&limit=1&id=${id}`;
            } else {
                endpoint = `/du/api/pivotree/categories.php?action=getPivotreeCategories&limit=1&id=${id}`;
            }
            
            // Make API request
            const response = await fetch(endpoint);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load category details');
            }
            
            // Find the category in the response
            const category = data.categories.find(c => c.id == id);
            
            if (!category) {
                throw new Error('Category not found in response');
            }
            
            this.selectedCategory = {
                ...category,
                source: source
            };
            
            this.displayCategoryDetails(this.selectedCategory);
            this.displayCategoryMapping(this.selectedCategory);
            
        } catch (error) {
            console.error('Error loading category details:', error);
            document.getElementById('categoryDetailsContent').innerHTML = `
                <div class="flash flash-error">
                    Error loading category details: ${error.message}
                </div>
            `;
            document.getElementById('categoryMappingContent').innerHTML = `
                <div class="blankslate">
                    <h3 class="blankslate-heading">Error</h3>
                    <p>Could not load category mapping information</p>
                </div>
            `;
        } finally {
            hideLoading();
        }
    },

    // Display category details in the right panel
    displayCategoryDetails: function(category) {
        const detailsContent = document.getElementById('categoryDetailsContent');
        if (!detailsContent) return;
        
        const isPivotree = category.source === 'pivotree';
        
        let html = `
            <div class="Box color-shadow-small">
                <div class="Box-row">
                    <strong>Source:</strong> 
                    <span class="Label Label--${isPivotree ? 'blue' : 'green'} ml-2">
                        ${category.source}
                    </span>
                </div>
                <div class="Box-row">
                    <strong>ID:</strong> ${category.id}
                </div>
                <div class="Box-row">
                    <strong>Name:</strong> ${isPivotree ? category.category_name : category.category_name}
                </div>`;
                
        if (isPivotree) {
            html += `
                <div class="Box-row">
                    <strong>Product Count:</strong> ${category.product_count || 0}
                </div>`;
        } else {
            html += `
                <div class="Box-row">
                    <strong>Code:</strong> ${category.category}
                </div>
                <div class="Box-row">
                    <strong>Path:</strong> ${category.path || 'N/A'}
                </div>`;
        }
                
        html += `
                <div class="Box-row">
                    <strong>Status:</strong>
                    <span class="Label Label--${category.status === 'mapped' ? 'green' : 'yellow'} ml-2">
                        ${category.status}
                    </span>
                </div>
            </div>
        `;
        
        detailsContent.innerHTML = html;
    },

    // Display category mapping in the right panel
    displayCategoryMapping: async function(category) {
        const self = this; // Store reference to this
        const mappingContent = document.getElementById('categoryMappingContent');
        if (!mappingContent) return;
        
        if (category.status === 'mapped' && category.mapping) {
            // Display existing mapping
            const mapping = category.mapping;
            const isPivotree = category.source === 'pivotree';
            
            mappingContent.innerHTML = `
                <div class="Box color-shadow-small">
                    <div class="Box-row">
                        <strong>Mapped to:</strong>
                    </div>
                    <div class="Box-row">
                        <div class="d-flex flex-items-center">
                            <span class="Label Label--${isPivotree ? 'green' : 'blue'} mr-2">
                                ${isPivotree ? 'akeneo' : 'pivotree'}
                            </span>
                            <strong>${isPivotree ? mapping.akeneo_name : mapping.pivotree_name}</strong>
                            <span class="text-small text-gray ml-1">(${isPivotree ? mapping.akeneo_id : mapping.pivotree_id})</span>
                        </div>
                    </div>
                    <div class="Box-row">
                        <button class="btn btn-sm btn-danger" id="removeMapping" data-id="${mapping.id || 0}">
                            Remove Mapping
                        </button>
                    </div>
                </div>
            `;
            
            // Add event listener for removing mapping
            document.getElementById('removeMapping')?.addEventListener('click', (e) => {
                const mappingId = e.target.dataset.id;
                if (mappingId) {
                    self.unmapCategory(mappingId);
                }
            });
        } else {
            try {
                // Display mapping form with target categories
                let targetCategories = [];
                const isPivotree = category.source === 'pivotree';
                
                // Fetch target categories
                if (isPivotree) {
                    // Get Akeneo categories that are not mapped
                    const response = await fetch('/du/api/pivotree/categories.php?action=getAkeneoCategories&mappingStatus=unmapped&limit=100');
                    const data = await response.json();
                    if (data.success) {
                        targetCategories = data.categories.map(c => ({
                            id: c.id,
                            name: c.category_name,
                            code: c.category
                        }));
                    }
                } else {
                    // Get Pivotree categories that are not mapped
                    const response = await fetch('/du/api/pivotree/categories.php?action=getPivotreeCategories&mappingStatus=unmapped&limit=100');
                    const data = await response.json();
                    if (data.success) {
                        targetCategories = data.categories.map(c => ({
                            id: c.id,
                            name: c.category_name
                        }));
                    }
                }
                
                mappingContent.innerHTML = `
                    <div class="Box color-shadow-small">
                        <div class="Box-row">
                            <strong>Create Mapping</strong>
                        </div>
                        <div class="Box-row">
                            <div class="form-group">
                                <label for="targetCategory" class="d-block mb-1">
                                    Select ${isPivotree ? 'Akeneo' : 'Pivotree'} Category:
                                </label>
                                <select id="targetCategory" class="form-select width-full">
                                    <option value="">-- Select Target Category --</option>
                                    ${targetCategories.map(opt => 
                                        `<option value="${opt.id}">${opt.name}${opt.code ? ` (${opt.code})` : ''}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="Box-row">
                            <button class="btn btn-sm btn-primary" id="saveMapping">
                                Save Mapping
                            </button>
                        </div>
                    </div>
                `;
                
                // Add event listener for saving mapping
                document.getElementById('saveMapping')?.addEventListener('click', () => {
                    const targetSelect = document.getElementById('targetCategory');
                    if (targetSelect && targetSelect.value) {
                        self.saveMapping(category.id, category.source, targetSelect.value);
                    } else {
                        alert('Please select a target category');
                    }
                });
            } catch (error) {
                console.error('Error loading target categories:', error);
                mappingContent.innerHTML = `
                    <div class="flash flash-error">
                        Error loading target categories: ${error.message}
                    </div>
                `;
            }
        }
    },

    // Open the category mapping form
    openCategoryMappingForm: function(id, source) {
        this.selectCategory(id, source);
    },

    // Save a new category mapping
    saveMapping: async function(sourceId, sourceSystem, targetId) {
        showLoading('Saving Mapping');
        
        try {
            // Determine pivotree_id and akeneo_id based on source
            let pivotreeId, akeneoId;
            
            if (sourceSystem === 'pivotree') {
                pivotreeId = sourceId;
                akeneoId = targetId;
            } else {
                pivotreeId = targetId;
                akeneoId = sourceId;
            }
            
            // Make API request
            const response = await fetch('/du/api/pivotree/categories.php?action=saveMapping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pivotree_id: pivotreeId,
                    akeneo_id: akeneoId,
                    created_by: 'user'
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to save mapping');
            }
            
            // Reload the current categories to reflect the changes
            this.loadCategories();
            
            // Select the category again to refresh details
            setTimeout(() => {
                this.selectCategory(sourceId, sourceSystem);
            }, 1000);
            
        } catch (error) {
            console.error('Error saving mapping:', error);
            alert('Error saving mapping: ' + error.message);
        } finally {
            hideLoading();
        }
    },

    // Remove a category mapping
    unmapCategory: async function(mappingId) {
        showLoading('Removing Mapping');
        
        try {
            // Make API request
            const response = await fetch(`/du/api/pivotree/categories.php?action=deleteMapping&id=${mappingId}`, {
                method: 'DELETE'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to delete mapping');
            }
            
            // Reload the current categories to reflect the changes
            this.loadCategories();
            
            // If a category is selected, refresh its details
            if (this.selectedCategory) {
                setTimeout(() => {
                    this.selectCategory(this.selectedCategory.id, this.selectedCategory.source);
                }, 1000);
            }
            
        } catch (error) {
            console.error('Error removing mapping:', error);
            alert('Error removing mapping: ' + error.message);
        } finally {
            hideLoading();
        }
    }
}; // End of CategoryXref namespace

// Keep the loading functions outside the namespace since they're global utilities
// Show loading indicator
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

// Hide loading indicator
function hideLoading() {
    const container = document.querySelector('.loading-container');
    
    if (container) {
        container.style.display = 'none';
        // Restore scrolling
        document.body.style.overflow = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('categoryXrefContainer');
    if (container) {
        CategoryXref.initializeCategoryXref();
    }
});

// Also expose the initialization function for the main scripts.js to call
window.initializeCategoryXref = function() {
    CategoryXref.initializeCategoryXref();
};