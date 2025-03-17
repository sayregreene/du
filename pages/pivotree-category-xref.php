<!-- Category Cross-Reference Interface -->
<div class="container-fluid p-3" id="categoryXrefContainer">
    <div class="Subhead">
        <div class="Subhead-heading">Category Cross Reference</div>
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
                                   id="categorySearchInput" 
                                   class="form-control input-sm width-full" 
                                   placeholder="Search category name"
                                   aria-label="Search categories">
                        </div>
                        <!-- Source Filter -->
                        <div class="flex-auto">
                            <select id="sourceFilter" class="form-select">
                                <option value="all">All Sources</option>
                                <option value="pivotree">Pivotree</option>
                                <option value="akeneo">Akeneo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="Box">
                <div class="Box-header d-flex flex-items-center">
                    <h3 class="Box-title flex-auto">Categories</h3>
                    <div class="text-small text-gray">
                        <span id="category-showing-count">0</span> categories
                    </div>
                </div>
                <div class="Box-body p-0">
                    <div class="overflow-auto" style="max-height: 600px;">
                        <table class="table table-sm" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2">Source</th>
                                    <th class="px-3 py-2">ID</th>
                                    <th class="px-3 py-2">Name</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="Box-footer">
                    <div class="d-flex flex-items-center">
                        <div class="flex-auto">
                            <span id="paginationInfo"></span>
                        </div>
                        <div class="BtnGroup">
                            <button class="BtnGroup-item btn btn-sm" id="prevPageBtn" disabled>
                                Previous
                            </button>
                            <button class="BtnGroup-item btn btn-sm" id="nextPageBtn">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Category Details & Mapping -->
        <div class="col-4">
            <div id="categoryDetails" class="Box mb-3">
                <div class="Box-header">
                    <h3 class="Box-title">Category Details</h3>
                </div>
                <div class="Box-body" id="categoryDetailsContent">
                    <div class="blankslate">
                        <h3 class="blankslate-heading">No Category Selected</h3>
                        <p>Select a category from the table to view details</p>
                    </div>
                </div>
            </div>
            
            <div id="categoryMapping" class="Box">
                <div class="Box-header">
                    <h3 class="Box-title">Category Mapping</h3>
                </div>
                <div class="Box-body" id="categoryMappingContent">
                    <div class="blankslate">
                        <h3 class="blankslate-heading">No Category Selected</h3>
                        <p>Select a category to create or view mappings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>