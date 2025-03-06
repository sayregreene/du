<!-- Product Analysis Interface -->
<div class="container-fluid p-3">
    <div class="Subhead">
        <div class="Subhead-heading">Product Analysis</div>
    </div>

    <div class="d-flex">
        <!-- Left side - Filters and Table -->
        <div class="col-8 pr-3">
            <!-- Filters -->
            <div class="Box mb-3">
                <div class="Box-header">
                    <h3 class="Box-title">Filters</h3>
                </div>
                <div class="Box-body">
                    <div class="d-flex flex-items-start mb-2">
                        <!-- Search -->
                        <div class="flex-auto mr-3">
                            <input type="text" 
                                   id="searchInput" 
                                   class="form-control input-sm width-full" 
                                   placeholder="Search SKU ID or Mfg Part Number"
                                   aria-label="Search products">
                        </div>
                        <!-- Brand Filter -->
                        <div class="flex-auto mr-3">
                            <select id="brandFilter" class="form-select">
                                <option value="">All Brands</option>
                            </select>
                        </div>
                        <!-- Terminal Node Filter -->
                        <div class="flex-auto">
                            <select id="nodeFilter" class="form-select">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="Box">
                <div class="Box-body p-0">
                    <div class="overflow-auto">
                        <table class="table table-sm" id="productsTable">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2">Brand</th>
                                    <th class="px-3 py-2">SKU ID</th>
                                    <th class="px-3 py-2">Mfg Part Number</th>
                                    <th class="px-3 py-2">Short Description</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Product Details -->
        <div class="col-4">
            <div id="productDetails" class="Box height-full">
                <div class="Box-header">
                    <h3 class="Box-title">Product Details</h3>
                </div>
                <div class="Box-body" id="productDetailsContent">
                    <div class="blankslate">
                        <h3 class="blankslate-heading">No Product Selected</h3>
                        <p>Select a product from the table to view details</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="Box-footer">
    <div class="d-flex flex-items-center">
        <div class="flex-auto">
            <span id="paginationInfo"></span>
        </div>
        <div class="BtnGroup">
            <button class="BtnGroup-item btn btn-sm" id="prevPage" disabled>
                Previous
            </button>
            <button class="BtnGroup-item btn btn-sm" id="nextPage">
                Next
            </button>
        </div>
    </div>
</div>
