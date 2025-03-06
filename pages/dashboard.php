
<?php
?>
<div class="container-lg">
    <div class="d-flex flex-items-center flex-justify-between">
        <h1 class="h2 mb-3">Dashboard</h1>
        <button class="btn btn-primary">Create New</button>
    </div>
    
    <div class="Box">
        <div class="Box-header">
            <h3 class="Box-title">Recent Activity</h3>
        </div>
        <div class="Box-body">
            <div class="d-flex flex-column">
                <div class="d-flex flex-items-center py-2">
                    <div class="color-fg-success">●</div>
                    <div class="flex-auto ml-2">
                        <strong>Project A</strong> deployment successful
                        <div class="color-fg-muted">2 hours ago</div>
                    </div>
                </div>
                <div class="d-flex flex-items-center py-2">
                    <div class="color-fg-attention">●</div>
                    <div class="flex-auto ml-2">
                        <strong>Server Warning</strong> high CPU usage
                        <div class="color-fg-muted">5 hours ago</div>
                    </div>
                </div>
                <div class="d-flex flex-items-center py-2">
                    <div class="color-fg-danger">●</div>
                    <div class="flex-auto ml-2">
                        <strong>Database Backup</strong> failed
                        <div class="color-fg-muted">1 day ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="Box mt-4">
        <div class="Box-header">
            <h3 class="Box-title">System Status</h3>
        </div>
        <div class="Box-body">
            <div class="d-flex flex-column">
                <div class="d-flex flex-items-center py-2">
                    <div class="flex-auto">
                        <strong>CPU Usage</strong>
                        <div class="Progress mt-1">
                            <div class="bg-success" style="width: 45%"></div>
                        </div>
                    </div>
                    <span class="ml-3">45%</span>
                </div>
                <div class="d-flex flex-items-center py-2">
                    <div class="flex-auto">
                        <strong>Memory Usage</strong>
                        <div class="Progress mt-1">
                            <div class="bg-attention" style="width: 78%"></div>
                        </div>
                    </div>
                    <span class="ml-3">78%</span>
                </div>
                <div class="d-flex flex-items-center py-2">
                    <div class="flex-auto">
                        <strong>Disk Space</strong>
                        <div class="Progress mt-1">
                            <div class="bg-danger" style="width: 92%"></div>
                        </div>
                    </div>
                    <span class="ml-3">92%</span>
                </div>
            </div>
        </div>
    </div>
</div>