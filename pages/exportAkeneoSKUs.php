<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akeneo Sync</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Primer/21.1.1/primer.css" rel="stylesheet" />
    <style>
        #progressBar {
            width: 100%;
            background-color: #e1e4e8;
            height: 20px;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        #progressBarFill {
            height: 100%;
            width: 0%;
            background-color: #2ea44f;
            transition: width 0.2s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sync Akeneo Data</h1>
        <button id="startSync" class="btn btn-primary">Start Sync</button>
        <div id="progressBar" style="display: none;">
            <div id="progressBarFill"></div>
        </div>
        <div id="status" class="mt-3"></div>
    </div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- JavaScript Logic -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('startSync').addEventListener('click', function () {
                console.log('Button clicked'); // Debugging click event
                const progressBar = document.getElementById('progressBar');
                const progressBarFill = document.getElementById('progressBarFill');
                const status = document.getElementById('status');
                const startSync = document.getElementById('startSync');

                progressBar.style.display = 'block';
                progressBarFill.style.width = '0%';
                status.innerHTML = "Starting sync...";

                $.ajax({
                    url: 'akeneo_sync_test.php', // Replace with your PHP script
                    type: 'POST',
                    xhr: function () {
                        const xhr = new window.XMLHttpRequest();
                        xhr.addEventListener("progress", function (evt) {
                            if (evt.lengthComputable) {
                                const percentComplete = (evt.loaded / evt.total) * 100;
                                progressBarFill.style.width = percentComplete + '%';
                            }
                        });
                        return xhr;
                    },
                    success: function (response) {
                        progressBarFill.style.width = '100%';
                        status.innerHTML = "Sync completed: " + response.message;
                    },
                    error: function () {
                        status.innerHTML = "An error occurred during the sync.";
                    }
                });
            });
        });
    </script>
</body>
</html>
