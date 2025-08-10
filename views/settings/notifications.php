<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Notification Settings</h1>
                <p class="text-muted mb-0">Configure notification preferences and alerts</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()" id="saveBtn">
                <i class="bi bi-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Email Notifications</h6>
            </div>
            <div class="card-body">
                <form id="notificationSettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Email Configuration</h6>
                            
                            <div class="mb-3">
                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'smtp_host')) ?>" placeholder="smtp.gmail.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'smtp_port', '587')) ?>" min="1" max="65535">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'smtp_username')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'smtp_password')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_encryption" class="form-label">Encryption</label>
                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                    <option value="tls" <?= getParameterValue($parameters, 'smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                    <option value="ssl" <?= getParameterValue($parameters, 'smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    <option value="none" <?= getParameterValue($parameters, 'smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Email Preferences</h6>
                            
                            <div class="mb-3">
                                <label for="from_email" class="form-label">From Email</label>
                                <input type="email" class="form-control" id="from_email" name="from_email" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'from_email')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="from_name" class="form-label">From Name</label>
                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'from_name')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="reply_to_email" class="form-label">Reply-To Email</label>
                                <input type="email" class="form-control" id="reply_to_email" name="reply_to_email" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'reply_to_email')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_signature" class="form-label">Email Signature</label>
                                <textarea class="form-control" id="email_signature" name="email_signature" rows="3"><?= htmlspecialchars(getParameterValue($parameters, 'email_signature')) ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">System Notifications</h6>
                            
                            <div class="mb-3">
                                <label for="notify_system_errors" class="form-label">System Errors</label>
                                <select class="form-select" id="notify_system_errors" name="notify_system_errors">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_system_errors') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_system_errors') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notify_login_attempts" class="form-label">Failed Login Attempts</label>
                                <select class="form-select" id="notify_login_attempts" name="notify_login_attempts">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_login_attempts') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_login_attempts') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notify_backup_status" class="form-label">Backup Status</label>
                                <select class="form-select" id="notify_backup_status" name="notify_backup_status">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_backup_status') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_backup_status') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notify_disk_space" class="form-label">Low Disk Space</label>
                                <select class="form-select" id="notify_disk_space" name="notify_disk_space">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_disk_space') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_disk_space') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Business Notifications</h6>
                            
                            <div class="mb-3">
                                <label for="notify_new_transactions" class="form-label">New Transactions</label>
                                <select class="form-select" id="notify_new_transactions" name="notify_new_transactions">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_new_transactions') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_new_transactions') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notify_large_transactions" class="form-label">Large Transactions</label>
                                <select class="form-select" id="notify_large_transactions" name="notify_large_transactions">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_large_transactions') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_large_transactions') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="large_transaction_threshold" class="form-label">Large Transaction Threshold</label>
                                <input type="number" class="form-control" id="large_transaction_threshold" name="large_transaction_threshold" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'large_transaction_threshold', '10000')) ?>" min="1000" step="1000">
                                <small class="form-text text-muted">Amount in PHP to trigger large transaction notification</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notify_monthly_reports" class="form-label">Monthly Reports</label>
                                <select class="form-select" id="notify_monthly_reports" name="notify_monthly_reports">
                                    <option value="1" <?= getParameterValue($parameters, 'notify_monthly_reports') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notify_monthly_reports') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">In-App Notifications</h6>
                            
                            <div class="mb-3">
                                <label for="enable_in_app_notifications" class="form-label">Enable In-App Notifications</label>
                                <select class="form-select" id="enable_in_app_notifications" name="enable_in_app_notifications">
                                    <option value="1" <?= getParameterValue($parameters, 'enable_in_app_notifications') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'enable_in_app_notifications') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notification_sound" class="form-label">Notification Sound</label>
                                <select class="form-select" id="notification_sound" name="notification_sound">
                                    <option value="1" <?= getParameterValue($parameters, 'notification_sound') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'notification_sound') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notification_position" class="form-label">Notification Position</label>
                                <select class="form-select" id="notification_position" name="notification_position">
                                    <option value="top-right" <?= getParameterValue($parameters, 'notification_position') === 'top-right' ? 'selected' : '' ?>>Top Right</option>
                                    <option value="top-left" <?= getParameterValue($parameters, 'notification_position') === 'top-left' ? 'selected' : '' ?>>Top Left</option>
                                    <option value="bottom-right" <?= getParameterValue($parameters, 'notification_position') === 'bottom-right' ? 'selected' : '' ?>>Bottom Right</option>
                                    <option value="bottom-left" <?= getParameterValue($parameters, 'notification_position') === 'bottom-left' ? 'selected' : '' ?>>Bottom Left</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notification_duration" class="form-label">Notification Duration (seconds)</label>
                                <input type="number" class="form-control" id="notification_duration" name="notification_duration" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'notification_duration', '5')) ?>" min="1" max="30">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">SMS Notifications</h6>
                            
                            <div class="mb-3">
                                <label for="enable_sms_notifications" class="form-label">Enable SMS Notifications</label>
                                <select class="form-select" id="enable_sms_notifications" name="enable_sms_notifications">
                                    <option value="0" <?= getParameterValue($parameters, 'enable_sms_notifications') === '0' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="1" <?= getParameterValue($parameters, 'enable_sms_notifications') === '1' ? 'selected' : '' ?>>Enabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sms_provider" class="form-label">SMS Provider</label>
                                <select class="form-select" id="sms_provider" name="sms_provider">
                                    <option value="twilio" <?= getParameterValue($parameters, 'sms_provider') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                                    <option value="plivo" <?= getParameterValue($parameters, 'sms_provider') === 'plivo' ? 'selected' : '' ?>>Plivo</option>
                                    <option value="nexmo" <?= getParameterValue($parameters, 'sms_provider') === 'nexmo' ? 'selected' : '' ?>>Nexmo</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sms_api_key" class="form-label">SMS API Key</label>
                                <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'sms_api_key')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="sms_api_secret" class="form-label">SMS API Secret</label>
                                <input type="password" class="form-control" id="sms_api_secret" name="sms_api_secret" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'sms_api_secret')) ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Test Notifications Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Test Notifications</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="testEmailNotification()">
                            <i class="bi bi-envelope me-2"></i>Test Email
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="testSMSNotification()">
                            <i class="bi bi-phone me-2"></i>Test SMS
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="testInAppNotification()">
                            <i class="bi bi-bell me-2"></i>Test In-App
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
function saveNotificationSettings() {
    const form = $('#notificationSettingsForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Prepare settings array for API
    const settings = [
        { name: 'smtp_host', value: formData.smtp_host },
        { name: 'smtp_port', value: formData.smtp_port },
        { name: 'smtp_username', value: formData.smtp_username },
        { name: 'smtp_password', value: formData.smtp_password },
        { name: 'smtp_encryption', value: formData.smtp_encryption },
        { name: 'from_email', value: formData.from_email },
        { name: 'from_name', value: formData.from_name },
        { name: 'reply_to_email', value: formData.reply_to_email },
        { name: 'email_signature', value: formData.email_signature },
        { name: 'notify_system_errors', value: formData.notify_system_errors },
        { name: 'notify_login_attempts', value: formData.notify_login_attempts },
        { name: 'notify_backup_status', value: formData.notify_backup_status },
        { name: 'notify_disk_space', value: formData.notify_disk_space },
        { name: 'notify_new_transactions', value: formData.notify_new_transactions },
        { name: 'notify_large_transactions', value: formData.notify_large_transactions },
        { name: 'large_transaction_threshold', value: formData.large_transaction_threshold },
        { name: 'notify_monthly_reports', value: formData.notify_monthly_reports },
        { name: 'enable_in_app_notifications', value: formData.enable_in_app_notifications },
        { name: 'notification_sound', value: formData.notification_sound },
        { name: 'notification_position', value: formData.notification_position },
        { name: 'notification_duration', value: formData.notification_duration },
        { name: 'enable_sms_notifications', value: formData.enable_sms_notifications },
        { name: 'sms_provider', value: formData.sms_provider },
        { name: 'sms_api_key', value: formData.sms_api_key },
        { name: 'sms_api_secret', value: formData.sms_api_secret }
    ];
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/save-notifications',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ settings: settings }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Notification settings saved successfully!', 'success');
            } else {
                EARS.showAlert(response.error || 'Failed to save settings', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save settings', 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

function testEmailNotification() {
    $.ajax({
        url: APP_URL + '/api/settings/test-email',
        method: 'POST',
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Test email sent successfully!', 'success');
            } else {
                EARS.showAlert(response.error || 'Failed to send test email', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to send test email', 'danger');
        }
    });
}

function testSMSNotification() {
    $.ajax({
        url: APP_URL + '/api/settings/test-sms',
        method: 'POST',
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Test SMS sent successfully!', 'success');
            } else {
                EARS.showAlert(response.error || 'Failed to send test SMS', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to send test SMS', 'danger');
        }
    });
}

function testInAppNotification() {
    EARS.showAlert('This is a test in-app notification!', 'info');
}

// Auto-save form data
$(document).ready(function() {
    $('#notificationSettingsForm').on('change', 'input, select, textarea', function() {
        // Auto-save after 3 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveNotificationSettings();
        }, 3000);
    });
});
</script>

<?php
function getParameterValue($parameters, $name, $default = '') {
    foreach ($parameters as $param) {
        if ($param['parameter_name'] === $name) {
            return $param['parameter_value'];
        }
    }
    return $default;
}
?> 