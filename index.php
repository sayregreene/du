<!DOCTYPE html>
<html lang="en">
<!-- /var/www/html/du/index.php -->

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>ADM</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Primer/21.1.1/primer.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/primeicons@7.0.0/primeicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <link href="css/styles.css" rel="stylesheet" />
    <!-- IMPORTANT: Bridge script must be a regular script (not a module) and load BEFORE scripts.js -->
    <!-- <script src="scripts/attribute-comparison/attribute-comparison-bridge.js"></script> -->
    <!-- Other scripts -->
    <script src="scripts/scripts.js" defer></script>
    <script src="scripts/pivotree-products.js"></script>

</head>

<body>
    <div class="layout-container">
        <!-- Primary Navigation -->
        <nav class="nav-primary">
            <a href="#" class="active" data-page="dashboard">
                <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">
                    <path
                        d="M11.03 2.59a1.501 1.501 0 0 1 1.94 0l7.5 6.363a1.5 1.5 0 0 1 .53 1.144V19.5a1.5 1.5 0 0 1-1.5 1.5h-5.75a.75.75 0 0 1-.75-.75V14h-2v6.25a.75.75 0 0 1-.75.75H4.5A1.5 1.5 0 0 1 3 19.5v-9.403c0-.44.194-.859.53-1.144ZM12 3.734l-7.5 6.363V19.5h5v-6.25a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 .75.75v6.25h5v-9.403Z">
                    </path>
                </svg> -->
                Home
            </a>
            <a href="#" data-page="erp">
                ERP
            </a>
            <a href="#" data-page="akeneo">
                Akeneo
            </a>
            <a href="#" data-page="pivotree">
                Pivotree
            </a>
            <a href="#" data-page="files">
                Files
            </a>


            <a href="#" data-page="settings">
                Settings
            </a>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <h1>Loading...</h1>
        </main>
    </div>

    <div class="loading-container" style="display: none;">
        <div class="loading-overlay"></div>
        <div class="loading-spinner">
            <div class="spinner-icon"></div>
            <div class="spinner-text">Loading...</div>
        </div>
    </div>

</body>

</html>