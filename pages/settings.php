
<?php
?>
<div class="container-lg">
    <h1 class="h2 mb-3">Settings</h1>
    
    <div class="Box mb-4">
        <div class="Box-header">
            <h3 class="Box-title">Profile Settings</h3>
        </div>
        <div class="Box-body">
            <form>
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="avatar" class="d-block">Profile Picture</label>
                    </div>
                    <div class="form-group-body d-flex flex-items-center">
                        <img src="https://github.com/github.png" alt="Profile picture" class="avatar mr-3" style="width: 100px; height: 100px;">
                        <div>
                            <button type="button" class="btn mr-2">Upload new picture</button>
                            <button type="button" class="btn-danger btn">Remove</button>
                            <div class="color-fg-muted mt-1">JPG, GIF or PNG. Max size of 800K</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-group-header">
                        <label for="name">Display Name</label>
                    </div>
                    <div class="form-group-body">
                        <input class="form-control" type="text" id="name" value="John Doe">
                        <div class="note">This is your public display name.</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="email">Email Address</label>
                    </div>
                    <div class="form-group-body">
                        <input class="form-control" type="email" id="email" value="john@example.com">
                        <div class="note">Your email address is used for notifications and password resets.</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="bio">Bio</label>
                    </div>
                    <div class="form-group-body">
                        <textarea class="form-control" id="bio" rows="3"></textarea>
                        <div class="note">A brief description about yourself.</div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="Box mb-4">
        <div class="Box-header">
            <h3 class="Box-title">Preferences</h3>
        </div>
        <div class="Box-body">
            <form>
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="timezone">Timezone</label>
                    </div>
                    <div class="form-group-body">
                        <select class="form-select" id="timezone">
                            <option>UTC</option>
                            <option>Eastern Time (US & Canada)</option>
                            <option>Central Time (US & Canada)</option>
                            <option>Pacific Time (US & Canada)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-group-header">
                        <label for="language">Language</label>
                    </div>
                    <div class="form-group-body">
                        <select class="form-select" id="language">
                            <option>English</option>
                            <option>Spanish</option>
                            <option>French</option>
                            <option>German</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-group-header">
                        <label>Email Notifications</label>
                    </div>
                    <div class="form-group-body">
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox" checked>
                                Receive system notifications
                            </label>
                        </div>
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox" checked>
                                Receive security alerts
                            </label>
                        </div>
                        <div class="form-checkbox">
                            <label>
                                <input type="checkbox">
                                Receive marketing emails
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="Box mb-4">
        <div class="Box-header">
            <h3 class="Box-title">Security</h3>
        </div>
        <div class="Box-body">
            <form>
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="current-password">Change Password</label>
                    </div>
                    <div class="form-group-body">
                        <input class="form-control mb-2" type="password" id="current-password" placeholder="Current password">
                        <input class="form-control mb-2" type="password" id="new-password" placeholder="New password">
                        <input class="form-control" type="password" id="confirm-password" placeholder="Confirm new password">
                        <div class="note mt-2">Password must be at least 8 characters long and include a number and special character.</div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-group-header">
                        <label>Two-Factor Authentication</label>
                    </div>
                    <div class="form-group-body">
                        <div class="flash">
                            Two-factor authentication is not enabled yet.
                            <a href="#" class="btn btn-sm ml-2">Enable 2FA</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-items-center flex-justify-end">
        <button type="button" class="btn mr-2">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</div>