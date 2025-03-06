
<?php
?>
<div class="container-lg">
    <div class="d-flex flex-items-center flex-justify-between">
        <h1 class="h2 mb-3">Files</h1>
        <div class="d-flex">
            <div class="input-group mr-2">
                <input type="text" class="form-control" placeholder="Search files..." aria-label="Search files">
                <span class="input-group-button">
                    <button class="btn" type="button" aria-label="Search files">
                        <svg class="octicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                            <path d="M10.68 11.74a6 6 0 0 1-7.922-8.982 6 6 0 0 1 8.982 7.922l3.04 3.04a.75.75 0 0 1-1.06 1.06l-3.04-3.04ZM11.5 7a4.499 4.499 0 1 0-8.997 0A4.499 4.499 0 0 0 11.5 7Z"></path>
                        </svg>
                    </button>
                </span>
            </div>
            <button class="btn btn-primary">
                <svg class="octicon mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path d="M7.75 2a.75.75 0 0 1 .75.75V7h4.25a.75.75 0 0 1 0 1.5H8.5v4.25a.75.75 0 0 1-1.5 0V8.5H2.75a.75.75 0 0 1 0-1.5H7V2.75A.75.75 0 0 1 7.75 2Z"></path>
                </svg>
                Upload
            </button>
        </div>
    </div>
    
    <div class="Box">
        <div class="Box-header d-flex flex-items-center">
            <div class="flex-auto">Name</div>
            <div class="flex-shrink-0 mr-3" style="width: 100px;">Size</div>
            <div class="flex-shrink-0 mr-3" style="width: 150px;">Modified</div>
            <div class="flex-shrink-0" style="width: 40px;"></div>
        </div>

        <div class="Box-row d-flex flex-items-center">
            <div class="flex-auto">
                <svg class="octicon mr-2 color-fg-accent" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path d="M2 1.75C2 .784 2.784 0 3.75 0h6.586c.464 0 .909.184 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v9.586A1.75 1.75 0 0 1 13.25 16h-9.5A1.75 1.75 0 0 1 2 14.25Zm1.75-.25a.25.25 0 0 0-.25.25v12.5c0 .138.112.25.25.25h9.5a.25.25 0 0 0 .25-.25V6h-2.75A1.75 1.75 0 0 1 9 4.25V1.5Z"></path>
                </svg>
                <strong>project_report.pdf</strong>
            </div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 100px;">2.4 MB</div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 150px;">2 hours ago</div>
            <div class="flex-shrink-0" style="width: 40px;">
                <details class="details-reset details-overlay">
                    <summary class="btn-octicon" aria-haspopup="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                            <path d="M8 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM1.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm13 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"></path>
                        </svg>
                    </summary>
                    <div class="SelectMenu">
                        <div class="SelectMenu-modal">
                            <div class="SelectMenu-list">
                                <button class="SelectMenu-item" role="menuitem">Download</button>
                                <button class="SelectMenu-item" role="menuitem">Share</button>
                                <button class="SelectMenu-item" role="menuitem">Rename</button>
                                <button class="SelectMenu-item color-fg-danger" role="menuitem">Delete</button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="Box-row d-flex flex-items-center">
            <div class="flex-auto">
                <svg class="octicon mr-2 color-fg-success" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path d="M2 1.75C2 .784 2.784 0 3.75 0h6.586c.464 0 .909.184 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v9.586A1.75 1.75 0 0 1 13.25 16h-9.5A1.75 1.75 0 0 1 2 14.25Zm1.75-.25a.25.25 0 0 0-.25.25v12.5c0 .138.112.25.25.25h9.5a.25.25 0 0 0 .25-.25V6h-2.75A1.75 1.75 0 0 1 9 4.25V1.5Z"></path>
                </svg>
                <strong>data_analysis.xlsx</strong>
            </div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 100px;">856 KB</div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 150px;">1 day ago</div>
            <div class="flex-shrink-0" style="width: 40px;">
                <details class="details-reset details-overlay">
                    <summary class="btn-octicon" aria-haspopup="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                            <path d="M8 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM1.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm13 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"></path>
                        </svg>
                    </summary>
                    <div class="SelectMenu">
                        <div class="SelectMenu-modal">
                            <div class="SelectMenu-list">
                                <button class="SelectMenu-item" role="menuitem">Download</button>
                                <button class="SelectMenu-item" role="menuitem">Share</button>
                                <button class="SelectMenu-item" role="menuitem">Rename</button>
                                <button class="SelectMenu-item color-fg-danger" role="menuitem">Delete</button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="Box-row d-flex flex-items-center">
            <div class="flex-auto">
                <svg class="octicon mr-2 color-fg-attention" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path d="M2 1.75C2 .784 2.784 0 3.75 0h6.586c.464 0 .909.184 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v9.586A1.75 1.75 0 0 1 13.25 16h-9.5A1.75 1.75 0 0 1 2 14.25Zm1.75-.25a.25.25 0 0 0-.25.25v12.5c0 .138.112.25.25.25h9.5a.25.25 0 0 0 .25-.25V6h-2.75A1.75 1.75 0 0 1 9 4.25V1.5Z"></path>
                </svg>
                <strong>presentation.pptx</strong>
            </div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 100px;">4.2 MB</div>
            <div class="flex-shrink-0 mr-3 color-fg-muted" style="width: 150px;">3 days ago</div>
            <div class="flex-shrink-0" style="width: 40px;">
                <details class="details-reset details-overlay">
                    <summary class="btn-octicon" aria-haspopup="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                            <path d="M8 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM1.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm13 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"></path>
                        </svg>
                    </summary>
                    <div class="SelectMenu">
                        <div class="SelectMenu-modal">
                            <div class="SelectMenu-list">
                                <button class="SelectMenu-item" role="menuitem">Download</button>
                                <button class="SelectMenu-item" role="menuitem">Share</button>
                                <button class="SelectMenu-item" role="menuitem">Rename</button>
                                <button class="SelectMenu-item color-fg-danger" role="menuitem">Delete</button>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <nav class="paginate-container my-4" aria-label="Pagination">
        <div class="pagination d-flex">
            <span class="previous_page disabled">Previous</span>
            <em class="current" aria-current="true">1</em>
            <a rel="next" href="#" aria-label="Page 2">2</a>
            <a href="#" aria-label="Page 3">3</a>
            <span class="gap">…</span>
            <a href="#" aria-label="Page 8">8</a>
            <a class="next_page" rel="next" href="#" aria-label="Next Page">Next</a>
        </div>
    </nav>
</div>