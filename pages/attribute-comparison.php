<!-- /var/www/html/du/pages/attribute-comparison.php -->

<div class="container-fluid p-3" id="attributeComparisonContainer">
    <div class="Subhead">
        <div class="Subhead-heading">Attribute Comparison: Pivotree to Akeneo</div>
    </div>

    <!-- Tab Navigation -->
    <div class="UnderlineNav mb-3">
        <div class="UnderlineNav-body">
            <a class="UnderlineNav-item selected" id="tab-attributes" href="#">
                <span>Map Attributes</span>
            </a>
            <a class="UnderlineNav-item" id="tab-attribute-values" href="#">
                <span>Map Values</span>
            </a>
            <a class="UnderlineNav-item" id="tab-view-mappings" href="#">
                <span>View Mappings</span>
            </a>

        </div>
    </div>

    <!-- Map Attributes Tab Content -->
    <div id="content-attributes">
        <div class="Box mb-3">
            <div class="Box-header">
                <h3 class="Box-title">Filters</h3>
            </div>
            <div class="Box-body">
                <div class="d-flex flex-items-start mb-2">
                    <!-- Search -->
                    <div class="flex-auto mr-3">
                        <input type="text" id="search-input" class="form-control input-sm width-full"
                            placeholder="Search attributes" aria-label="Search attributes">
                    </div>
                    <!-- Mapping Status Filter -->
                    <div class="mr-3">
                        <select id="mapping-status-filter" class="form-select">
                            <option value="all">All Attributes</option>
                            <option value="mapped">Mapped Only</option>
                            <option value="unmapped">Unmapped Only</option>
                        </select>
                    </div>
                    <!-- Search Button -->
                    <div>
                        <button class="btn" id="search-button">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex">
            <!-- Left side - Attributes List -->
            <div class="col-7 pr-3">
                <div class="Box">
                    <div class="Box-header d-flex flex-items-center">
                        <h3 class="Box-title flex-auto">Pivotree Attributes</h3>
                        <div class="text-small text-gray">
                            Showing <span id="showing-start">0</span>-<span id="showing-end">0</span> of <span
                                id="total-attributes">0</span>
                        </div>
                    </div>
                    <div class="Box-body p-0">
                        <div class="overflow-auto" style="max-height: 600px;">
                            <table class="table table-sm" id="attributes-table">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2">Attribute Name</th>
                                        <th class="px-3 py-2 text-right">Value Count</th>
                                        <th class="px-3 py-2">Akeneo Status</th>
                                        <th class="px-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="attributes-list">
                                    <!-- Attributes will be inserted here by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="Box-footer">
                        <div class="d-flex flex-items-center">
                            <div class="flex-auto">
                                Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                            </div>
                            <div class="BtnGroup">
                                <button class="BtnGroup-item btn btn-sm" id="prev-page" disabled>
                                    Previous
                                </button>
                                <button class="BtnGroup-item btn btn-sm" id="next-page">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Attribute Mapping -->
            <div class="col-5">
                <div class="Box mb-3">
                    <div class="Box-header">
                        <h3 class="Box-title">Attribute Mapping</h3>
                    </div>
                    <div class="Box-body" id="attribute-details">
                        <div class="select-attribute-message">
                            <div class="blankslate">
                                <h3 class="blankslate-heading">No Attribute Selected</h3>
                                <p>Select an attribute from the table to map it to Akeneo</p>
                            </div>
                        </div>

                        <!-- This will be populated when an attribute is selected -->
                        <div class="attribute-mapping-form d-none">
                            <h4 class="border-bottom pb-2 mb-3">Pivotree Attribute Details</h4>
                            <div class="mb-2">
                                <div class="d-flex flex-items-center mb-1">
                                    <div class="text-bold col-3">Name:</div>
                                    <div class="col-9" id="pivotree-attribute-name"></div>
                                </div>
                                <div class="d-flex flex-items-center mb-1">
                                    <div class="text-bold col-3">Values:</div>
                                    <div class="col-9" id="pivotree-value-count"></div>
                                </div>
                            </div>

                            <h4 class="border-bottom pb-2 mb-3 mt-4">Akeneo Mapping</h4>

                            <div class="form-group">
                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="akeneo-attribute">Attribute:</label>
                                    </div>
                                    <div class="col-9">
                                        <select class="form-select width-full" id="akeneo-attribute">
                                            <option value="">-- Select Existing Attribute --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="new-attribute-section" class="d-none">
                                <h4 class="border-bottom pb-2 mb-3 mt-4">Create New Attribute</h4>

                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="new-attribute-code">Code:</label>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" class="form-control width-full" id="new-attribute-code">
                                    </div>
                                </div>

                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="new-attribute-label">Label:</label>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" class="form-control width-full" id="new-attribute-label">
                                    </div>
                                </div>

                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="new-attribute-type">Type:</label>
                                    </div>
                                    <div class="col-9">
                                        <select class="form-select width-full" id="new-attribute-type">
                                            <option value="pim_catalog_text">Text</option>
                                            <option value="pim_catalog_textarea">Textarea</option>
                                            <option value="pim_catalog_simpleselect">Simple Select</option>
                                            <option value="pim_catalog_multiselect">Multi Select</option>
                                            <option value="pim_catalog_number">Number</option>
                                            <option value="pim_catalog_metric">Metric</option>
                                            <option value="pim_catalog_boolean">Yes/No</option>
                                            <option value="pim_catalog_date">Date</option>
                                            <option value="pim_catalog_file">File</option>
                                            <option value="pim_catalog_image">Image</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-items-center mt-4">
                                <div class="flex-auto">
                                    <button class="btn btn-sm" id="btn-new-attribute">Create New Attribute</button>
                                </div>
                                <div>
                                    <button class="btn btn-primary" id="btn-save-attribute-mapping">Save
                                        Mapping</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recently Mapped Attributes -->
                <div class="Box">
                    <div class="Box-header">
                        <h3 class="Box-title">Recently Mapped Attributes</h3>
                    </div>
                    <div class="Box-body p-0">
                        <ul class="list-style-none" id="recent-mappings">
                            <!-- Recent mappings will be added here -->
                            <li class="Box-row text-center text-gray">No recent mappings</li>
                        </ul>
                    </div>
                    <div class="Box-footer text-right">
                        <button class="btn btn-sm" id="btn-export-mappings">
                            Export Mappings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Values Tab Content -->
    <div id="content-attribute-values" class="d-none">
        <div class="Box mb-3">
            <div class="Box-header">
                <h3 class="Box-title">Attribute Selection</h3>
            </div>
            <div class="Box-body">
                <div class="d-flex flex-items-start mb-2">
                    <div class="flex-auto mr-3">
                        <label for="attribute-selector" class="d-block mb-1">Select Mapped Attribute:</label>
                        <select id="attribute-selector" class="form-select width-full">
                            <option value="">-- Select Attribute --</option>
                            <!-- Mapped attributes will be inserted here -->
                        </select>
                    </div>
                    <div>
                        <label class="d-block" style="visibility: hidden;">Load</label>
                        <button class="btn" id="load-attribute-values">Load Values</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="values-mapping-container" class="d-none">
            <div class="d-flex">
                <!-- Left side - Values List -->
                <div class="col-7 pr-3">
                    <div class="Box mb-3">
                        <div class="Box-header d-flex flex-items-center">
                            <h3 class="Box-title flex-auto">Values for <span id="selected-attribute-name"></span></h3>
                            <div class="d-flex flex-items-center">
                                <label for="value-mapping-status-filter" class="mr-2">Filter:</label>
                                <select id="value-mapping-status-filter" class="form-select">
                                    <option value="all">All Values</option>
                                    <option value="mapped">Mapped Only</option>
                                    <option value="unmapped">Unmapped Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="Box-body p-0">
                            <div class="overflow-auto" style="max-height: 600px;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2">Pivotree Value</th>
                                            <th class="px-3 py-2">UOM</th>
                                            <th class="px-3 py-2">Akeneo Option</th>
                                            <th class="px-3 py-2">Status</th>
                                            <th class="px-3 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attribute-values-list">
                                        <!-- Values will be inserted here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - Value Mapping Form -->
                <div class="col-5">
                    <div id="value-mapping-form" class="Box mb-3 d-none">
                        <div class="Box-header">
                            <h3 class="Box-title">Map Value</h3>
                        </div>
                        <div class="Box-body">
                            <div class="mb-3">
                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">Pivotree Value:</div>
                                    <div class="col-9" id="selected-pivotree-value"></div>
                                </div>
                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">UOM:</div>
                                    <div class="col-9" id="selected-pivotree-uom">N/A</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="akeneo-value-search">Akeneo Option:</label>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" class="form-control width-full mb-2" id="akeneo-value-search"
                                            placeholder="Search options...">
                                        <div class="Box overflow-auto" style="max-height: 250px;">
                                            <div id="akeneo-value-list" class="Box-body p-0">
                                                <div class="blankslate p-3">
                                                    <p>No options available or matching your search.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="akeneo-value">
                                    </div>
                                </div>
                            </div>

                            <div id="new-value-section" class="d-none">
                                <h4 class="border-bottom pb-2 mb-3 mt-4">Create New Option</h4>

                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="new-value-code">Code:</label>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" class="form-control width-full" id="new-value-code">
                                    </div>
                                </div>

                                <div class="d-flex flex-items-center mb-2">
                                    <div class="text-bold col-3">
                                        <label for="new-value-label">Label:</label>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" class="form-control width-full" id="new-value-label">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-items-center mt-4">
                                <div class="flex-auto">
                                    <button class="btn btn-sm" id="btn-new-value">Create New Option</button>
                                </div>
                                <div>
                                    <button class="btn" id="btn-cancel-value-mapping">Cancel</button>
                                    <button class="btn btn-primary ml-2" id="btn-save-value-mapping">Save Value
                                        Mapping</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="content-view-mappings" class="d-none">
    <div class="Box mb-3">
        <div class="Box-header">
            <h3 class="Box-title">Mapping Overview</h3>
        </div>
        <div class="Box-body">
            <div class="d-flex flex-items-start mb-2">
                <!-- Search -->
                <div class="flex-auto mr-3">
                    <input type="text" id="mappings-search-input" class="form-control input-sm width-full"
                        placeholder="Search mappings" aria-label="Search mappings">
                </div>
                <!-- Filter Type -->
                <div class="mr-3">
                    <select id="mapping-type-filter" class="form-select">
                        <option value="attributes">Attribute Mappings</option>
                        <option value="values">Value Mappings</option>
                    </select>
                </div>
                <!-- Search Button -->
                <div>
                    <button class="btn" id="mappings-search-button">Search</button>
                </div>
            </div>
        </div>
    </div>

    <div class="Box mb-3">
        <div class="Box-header d-flex flex-items-center">
            <h3 class="Box-title flex-auto">Existing Mappings</h3>
            <div class="text-small text-gray">
                Showing <span id="mappings-showing-start">0</span>-<span id="mappings-showing-end">0</span> of <span
                    id="total-mappings">0</span>
            </div>
        </div>
        <div class="Box-body p-0">
            <div class="overflow-auto" style="max-height: 600px;">
                <!-- Attribute Mappings Table (initially visible) -->
                <table class="table table-sm" id="attribute-mappings-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-2">Pivotree Attribute</th>
                            <th class="px-3 py-2">Akeneo Attribute</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attribute-mappings-list">
                        <!-- Attribute mappings will be inserted here -->
                    </tbody>
                </table>

                <!-- Value Mappings Table (initially hidden) -->
                <table class="table table-sm d-none" id="value-mappings-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-2">Pivotree Attribute</th>
                            <th class="px-3 py-2">Pivotree Value</th>
                            <th class="px-3 py-2">Akeneo Attribute</th>
                            <th class="px-3 py-2">Akeneo Value</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="value-mappings-list">
                        <!-- Value mappings will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="Box-footer">
            <div class="d-flex flex-items-center">
                <div class="flex-auto">
                    Page <span id="mappings-current-page">1</span> of <span id="mappings-total-pages">1</span>
                </div>
                <div class="BtnGroup">
                    <button class="BtnGroup-item btn btn-sm" id="mappings-prev-page" disabled>
                        Previous
                    </button>
                    <button class="BtnGroup-item btn btn-sm" id="mappings-next-page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="Box-footer text-right">
        <button class="btn btn-sm mr-2" id="btn-delete-selected">Delete Selected</button>
        <button class="btn btn-sm" id="btn-export-all-mappings">Export All Mappings</button>
    </div>
</div>
</div>

<!-- Export Modal -->
<div class="Box Box-overlay d-none" id="export-modal">
    <div class="Box-overlay-header">
        <h3 class="Box-title">Export Attribute Mappings</h3>
        <button class="Box-btn-octicon btn-close-export-modal" type="button">
            <svg class="octicon octicon-x" viewBox="0 0 16 16" width="16" height="16">
                <path fill-rule="evenodd"
                    d="M3.72 3.72a.75.75 0 011.06 0L8 6.94l3.22-3.22a.75.75 0 111.06 1.06L9.06 8l3.22 3.22a.75.75 0 11-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 01-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 010-1.06z">
                </path>
            </svg>
        </button>
    </div>
    <div class="Box-body">
        <div class="form-group">
            <label for="export-format">Export Format:</label>
            <select class="form-select width-full mb-2" id="export-format">
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select>
        </div>
        <div class="form-group">
            <label for="export-selection">Export:</label>
            <select class="form-select width-full" id="export-selection">
                <option value="all">All Mapped Attributes</option>
                <option value="new">New Attributes Only</option>
                <option value="existing">Existing Attribute Mappings Only</option>
            </select>
        </div>
    </div>
    <div class="Box-footer d-flex flex-justify-end">
        <button type="button" class="btn mr-2 btn-close-export-modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btn-export">Export</button>
    </div>
</div>
