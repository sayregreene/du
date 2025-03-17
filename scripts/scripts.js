// /var/www/html/du/scripts/scripts.js

// DOM Elements
const mainContent = document.querySelector('.main-content');
const primaryNavLinks = document.querySelectorAll('.nav-primary a');

// Navigation configuration
const navigation = {
    dashboard: {
        mainPage: 'dashboard',
        submenu: [
            {
                label: 'Overview',
                page: 'dashboard',
                icon: '<i class="pi pi-chart-bar" style="font-size: 1rem"></i>'
            },
            {
                label: 'Analytics',
                page: 'dashboard-analytics',
                icon: null
            },
            {
                label: 'Reports',
                page: 'dashboard-reports',
                icon: null
            }
        ]
    },
    akeneo: {
        mainPage: 'akeneo',
        submenu: [
            {
                label: 'Products',
                page: 'akeneo-products',
                icon: '<i class="pi pi-barcode"></i>'
            },
            {
                label: 'Categories',
                page: 'akeneo-categories',
                icon: ''
            },
            {
                label: 'Sync History',
                page: 'akeneo-sync',
                icon: '<i class="pi pi-sync"></i>'
            },
            {
                label: 'Imports',
                page: 'akeneo-imports',
                icon: '<i class="pi pi-sync"></i>'
            }
        ]
    },
    erp: {
        mainPage: 'erp',
        submenu: [
            {
                label: 'Products',
                page: 'erp-products',
                icon: '<i class="pi pi-barcode"></i>'
            }
        ]
    },
    pivotree: {
        mainPage: 'pivotree',
        submenu: [
            {
                label: 'Product Data',
                page: 'pivotree-product-data',
                icon: ''
            },
            {
                label: 'Attribute Data',
                page: 'attribute-comparison',
                icon: ''
            },
            {
                label: 'Category Cross Reference',
                page: 'pivotree-category-xref',
                icon: '<i class="pi pi-sitemap"></i>'
            },
            {
                label: 'Normalization',
                page: 'normalization',
                icon: ''
            }
        ]
    },
    files: {
        mainPage: 'files',
        submenu: [
            {
                label: 'All Files',
                page: 'files-all',
                icon: '<i class="pi pi-file" style="font-size: 1rem"></i>'
            },
            {
                label: 'Recent',
                page: 'files-recent',
                icon: '<i class="pi pi-calendar" style="font-size: 1rem"></i>'
            },
            {
                label: 'Shared',
                page: 'files-shared',
                icon: null
            }
        ]
    },
    settings: {
        mainPage: 'settings',
        submenu: [
            {
                label: 'General',
                page: 'settings-general',
                icon: null
            },
            {
                label: 'Security',
                page: 'settings-security',
                icon: null
            },
            {
                label: 'Integrations',
                page: 'settings-integrations',
                icon: null
            }
        ]
    }
};



// === Navigation Functions ===

// Initialize navigation state
function initializeNavigation(page) {
    const section = page.split('-')[0];

    // Set active state in primary nav
    document.querySelector('.nav-primary a.active')?.classList.remove('active');
    const activeLink = document.querySelector(`.nav-primary a[data-page="${section}"]`);
    activeLink?.classList.add('active');

    // Update submenu for the section
    updateSubmenu(section);
}


// Create expand button for collapsed submenu
function createExpandButton() {
    const expandButton = document.createElement('button');
    expandButton.className = 'expand-button';
    expandButton.innerHTML = `
        <i class="pi pi-arrow-circle-right" style="font-size: 1rem"></i>
    `;
    expandButton.addEventListener('click', () => {
        document.querySelector('.nav-secondary').classList.remove('collapsed');
        document.querySelector('.main-content').classList.remove('nav-collapsed');
    });
    return expandButton;
}

// Update submenu content and visibility
function updateSubmenu(section) {
    const sectionConfig = navigation[section];
    const submenuItems = sectionConfig?.submenu || [];
    const navSecondary = document.querySelector('.nav-secondary');

    if (!navSecondary) {
        // Create nav-secondary if it doesn't exist
        const newNavSecondary = document.createElement('nav');
        newNavSecondary.className = 'nav-secondary';

        // Create expand button if it doesn't exist
        if (!document.querySelector('.expand-button')) {
            const expandButton = createExpandButton();
            document.querySelector('.layout-container').appendChild(expandButton);
        }

        document.querySelector('.layout-container').insertBefore(
            newNavSecondary,
            document.querySelector('.main-content')
        );
    }

    // Ensure submenu is expanded when updating content
    const existingNavSecondary = document.querySelector('.nav-secondary');
    if (existingNavSecondary) {
        existingNavSecondary.classList.remove('collapsed');
    }

    const navSecondaryContent = `
        <div class="nav-secondary-header">
            <h2 class="h4">${section.charAt(0).toUpperCase() + section.slice(1)}</h2>
            <button class="btn-octicon" id="collapseNav">
                <i class="pi pi-arrow-circle-left" style="font-size: 1rem"></i>
            </button>
        </div>
        <div class="nav-secondary-content">
            ${submenuItems.map(item => `
                <a href="#" class="menu-item" data-page="${item.page}">
                    <div class="menu-item-content">
                        ${item.icon ? item.icon : ''}
                        <span class="menu-item-label">${item.label}</span>
                    </div>
                </a>
            `).join('')}
        </div>
    `;

    document.querySelector('.nav-secondary').innerHTML = navSecondaryContent;
    document.querySelector('.main-content').classList.remove('nav-collapsed');

    // Add event listeners for submenu
    document.querySelector('#collapseNav')?.addEventListener('click', () => {
        document.querySelector('.nav-secondary').classList.toggle('collapsed');
        document.querySelector('.main-content').classList.toggle('nav-collapsed');
    });
}

// === Page Loading Functions ===


// Load page content
async function loadPage(page, updateHistory = true, isSubmenuClick = false) {
    try {
        showLoading('Loading page...'); // Add loading indicator if available
        
        // Handle navigation UI updates
        if (!isSubmenuClick) {
            const section = page.split('-')[0];
            document.querySelector('.nav-primary a.active')?.classList.remove('active');
            const activeLink = document.querySelector(`.nav-primary a[data-page="${section}"]`);
            activeLink?.classList.add('active');
            updateSubmenu(section);
        }

        // Fetch page content
        const mainResponse = await fetch(`pages/${page}.php`);
        if (!mainResponse.ok) throw new Error(`Failed to load main content for ${page}`);
        const mainHtml = await mainResponse.text();
        
        // INSERT THE CONTENT
        mainContent.innerHTML = mainHtml;
        
        // Update browser history
        if (updateHistory) {
            history.pushState({ page }, '', `?page=${page}`);
        }

        // Initialize page-specific functionality AFTER content is in the DOM
        if (page === 'attribute-comparison') {
            console.log('Initializing attribute comparison after content load');
            if (typeof initializeAttributeComparison === 'function') {
                initializeAttributeComparison();
            }
        } else if (page === 'pivotree-product-data') {
            if (typeof initializePivotreeProducts === 'function') {
                initializePivotreeProducts();
            }
        } else if (page === 'pivotree-category-xref') {
            // Add our new page initialization
            if (typeof initializeCategoryXref === 'function') {
                initializeCategoryXref();
            } else {
                // Dynamically load the script if not already loaded
                const script = document.createElement('script');
                script.src = 'scripts/pivotree-category-xref.js';
                script.onload = () => {
                    if (typeof initializeCategoryXref === 'function') {
                        initializeCategoryXref();
                    }
                };
                document.head.appendChild(script);
            }
        }

    } catch (error) {
        console.error('Error loading page:', error);
        mainContent.innerHTML = `<p style="color:red;">Error loading page: ${error.message}</p>`;
    } finally {
        hideLoading(); // Hide loading indicator
    }
}

// === Sync Functions ===

async function handleStartSync() {
    console.log('Starting sync process');
    const progressBar = document.getElementById('progressBar');
    const progressBarFill = document.getElementById('progressBarFill');
    const status = document.getElementById('status');

    if (!progressBar || !progressBarFill || !status) {
        console.error('Required elements for syncing not found.');
        return;
    }

    progressBar.style.display = 'block';
    progressBarFill.style.width = '0%';
    status.innerHTML = "Starting sync...";

    try {
        const response = await fetch('pages/akeneo_sync.php', { method: 'POST' });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const text = await response.text();
        if (!text) {
            throw new Error('Empty response from server');
        }
        const data = JSON.parse(text);
        progressBarFill.style.width = '100%';
        status.innerHTML = `Sync completed: ${data.message}`;
    } catch (error) {
        console.error('Error during sync:', error);
        status.innerHTML = "An error occurred during the sync.";
    }
}

async function syncData(action) {
    const resultDiv = document.getElementById('syncResult');
    resultDiv.style.display = 'block';
    resultDiv.textContent = 'Syncing ' + action + '...';

    try {
        const formData = new FormData();
        formData.append('action', action);

        const resp = await fetch('sync.php', {
            method: 'POST',
            body: formData
        });
        const data = await resp.json();

        resultDiv.textContent = data.message;
        resultDiv.className = `flash flash-${data.status === 'success' ? 'success' : 'error'}`;

        // Refresh the history table after sync
        await loadHistory();
    } catch (err) {
        resultDiv.textContent = err.toString();
        resultDiv.className = 'flash flash-error';
    }
}

async function loadHistory() {
    try {
        const resp = await fetch('/du/pages/history.php');
        const data = await resp.json();

        const tbody = document.querySelector('#historyTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.id}</td>
                <td>${row.entity_type}</td>
                <td class="${row.status === 'success' ? 'status-success' : 'status-error'}">
                    ${row.status}
                </td>
                <td>${row.message || ''}</td>
                <td>${row.sync_date}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Error loading history:', error);
    }
}

// === Event Listeners ===

// Primary navigation clicks
primaryNavLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const section = link.dataset.page || 'dashboard';
        const mainPage = navigation[section]?.mainPage || section;
        loadPage(mainPage, true, false);
    });
});

// Main content click delegation
mainContent.addEventListener('click', (e) => {
    // Your existing click handlers
    const syncButton = e.target.closest('button#startSync');
    if (syncButton) {
        handleStartSync();
    }

    // Add file type radio button change handling
    const fileTypeRadio = e.target.closest('input[name="file-type"]');
    if (fileTypeRadio) {
        handleFileTypeChange(fileTypeRadio);
    }
});

// Submenu click delegation
document.addEventListener('click', (e) => {
    // Handle submenu links
    const submenuLink = e.target.closest('.nav-secondary .menu-item');
    if (submenuLink) {
        e.preventDefault();
        const page = submenuLink.dataset.page;
        if (page) {
            // Update active state in submenu
            document.querySelectorAll('.nav-secondary .menu-item').forEach(item => {
                item.classList.remove('active');
            });
            submenuLink.classList.add('active');

            loadPage(page, true, true);
        }
    }
});

// Other

// === File Import Functions ===
function toggleDocOptions(value) {
    document.getElementById('docOptions').style.display = (value === 'documents' || value === 'images') ? 'block' : 'none';
}

let globalJobId = null;
let pollInterval = null;

/**
 * Called when the user clicks "Process File"
 */
function startImport() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files.length) {
        appendLog("No file selected.");
        return;
    }

    const fileType = document.querySelector('input[name="fileType"]:checked').value;
    const replaceMode = document.querySelector('input[name="replaceMode"]:checked').value;

    let docSource = null;
    if (fileType === 'documents' || fileType === 'images') {
        const sourceRadios = document.getElementsByName('docSource');
        for (let r of sourceRadios) {
            if (r.checked) {
                docSource = r.value;
            }
        }
        if (!docSource) {
            appendLog("Please select a document source (AWS or External).");
            return;
        }
    }

    appendLog("Uploading file...");

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('fileType', fileType);
    formData.append('docSource', docSource || '');
    formData.append('replaceMode', replaceMode);

    // Clear existing progress
    updateProgressBar(0);
    const logDiv = document.getElementById('log');
    logDiv.innerHTML = '';

    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                appendLog("Upload error: " + data.message);
                return;
            }
            globalJobId = data.jobId;
            appendLog("File uploaded. Job ID: " + globalJobId);

            // Start processing in background
            return fetch('start_process.php?jobId=' + encodeURIComponent(globalJobId));
        })
        .then(res => {
            if (!res) return;
            return res.json();
        })
        .then(data => {
            if (!data) return;
            if (!data.success) {
                appendLog("Error starting process: " + data.message);
                return;
            }
            appendLog("Processing started...");

            // Poll for progress every 2 seconds
            if (pollInterval) {
                clearInterval(pollInterval);
            }
            pollInterval = setInterval(checkProgress, 2000);
        })
        .catch(err => {
            appendLog("Error: " + err.message);
        });
}

function checkProgress() {
    if (!globalJobId) return;
    fetch('progress.php?jobId=' + encodeURIComponent(globalJobId))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                appendLog("Progress error: " + data.message);
                clearInterval(pollInterval);
                return;
            }
            // Update progress bar
            updateProgressBar(data.progress);

            // Append any new log lines
            data.log.forEach(msg => appendLog(msg));

            if (data.finished) {
                clearInterval(pollInterval);
                pollInterval = null;
                appendLog("Processing complete!");
                updateProgressBar(100);
            }
        })
        .catch(err => {
            appendLog("Progress fetch error: " + err);
        });
}

function updateProgressBar(percent) {
    const bar = document.getElementById('progress-bar');
    bar.style.width = percent + "%";
}

function appendLog(message) {
    const logDiv = document.getElementById('log');
    logDiv.innerHTML += message + "<br>";
    logDiv.scrollTop = logDiv.scrollHeight;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial page based on URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page') || 'dashboard';

    // Initialize navigation state
    initializeNavigation(initialPage);

    // Load the page content
    loadPage(initialPage, false);

    // Load history if on sync page
    if (initialPage === 'akeneo-sync') {
        loadHistory();
    }

    // Initialize file import functionality
    // initializeFileImport();
});

// Handle browser back/forward navigation
window.addEventListener('popstate', (event) => {
    if (event.state?.page) {
        loadPage(event.state.page, false);
    }
});