<style>
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container-lg px-3 py-4">
        <div class="d-flex flex-justify-between flex-items-center mb-4">
            <h1 class="h2">Akeneo Categories</h1>
            <button id="refreshBtn" class="btn btn-primary" onclick="refreshCategories()">
                Refresh Categories
            </button>
        </div>
        
        <div id="message" class="flash mb-4" style="display: none;"></div>
        
        <div id="categoriesContainer" class="Box">
            <!-- Categories will be loaded here -->
        </div>
    </div>

    <script>
    function refreshCategories() {
        const btn = document.getElementById('refreshBtn');
        const msg = document.getElementById('message');
        const container = document.getElementById('categoriesContainer');
        
        btn.classList.add('loading');
        msg.style.display = 'none';
        container.classList.add('loading');
        
        fetch('refresh_categories.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            msg.className = `flash flash-${data.success ? 'success' : 'error'}`;
            msg.textContent = data.message;
            msg.style.display = 'block';
            
            if (data.success) {
                loadCategories();
            }
        })
        .catch(error => {
            msg.className = 'flash flash-error';
            msg.textContent = 'An error occurred while refreshing categories';
            msg.style.display = 'block';
        })
        .finally(() => {
            btn.classList.remove('loading');
            container.classList.remove('loading');
        });
    }

    function loadCategories() {
        const container = document.getElementById('categoriesContainer');
        
        fetch('get_categories.php')
            .then(response => response.json())
            .then(categories => {
                container.innerHTML = renderCategoryTree(categories);
            })
            .catch(error => {
                container.innerHTML = '<div class="flash flash-error m-3">Failed to load categories</div>';
            });
    }

    function renderCategoryTree(categories, parentId = null, level = 0) {
        const items = categories.filter(c => c.parent_category_id === parentId);
        if (!items.length) return '';
        
        return items.map(category => `
            <div class="Box-row" style="padding-left: ${level * 24}px">
                <div class="d-flex flex-items-center">
                    <span class="text-bold">${category.category_name}</span>
                    <span class="Label ml-2">${category.category}</span>
                </div>
                ${renderCategoryTree(categories, category.id, level + 1)}
            </div>
        `).join('');
    }

    // Load categories when page loads
    document.addEventListener('DOMContentLoaded', loadCategories);
    </script>