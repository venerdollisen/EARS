<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Security Settings</h1>
                <p class="text-muted mb-0">Configure security and authentication settings</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveSecuritySettings()" id="saveBtn">
                <i class="bi bi-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Authentication & Access Control</h6>
            </div>
            <div class="card-body">
                <form id="securitySettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Session Management</h6>
                            
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'session_timeout', '3600')) ?>" min="300" max="86400">
                                <small class="form-text text-muted">Minimum: 5 minutes (300s), Maximum: 24 hours (86400s)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="max_login_attempts" class="form-label">Maximum Login Attempts</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'max_login_attempts', '5')) ?>" min="3" max="10">
                                <small class="form-text text-muted">Number of failed attempts before account lockout</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="lockout_duration" class="form-label">Lockout Duration (minutes)</label>
                                <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'lockout_duration', '30')) ?>" min="5" max="1440">
                                <small class="form-text text-muted">How long to lock account after max attempts</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_expiry_days" class="form-label">Password Expiry (days)</label>
                                <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'password_expiry_days', '90')) ?>" min="0" max="365">
                                <small class="form-text text-muted">0 = Never expire</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Password Policy</h6>
                            
                            <div class="mb-3">
                                <label for="min_password_length" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="min_password_length" name="min_password_length" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'min_password_length', '8')) ?>" min="6" max="20">
                            </div>
                            
                            <div class="mb-3">
                                <label for="require_uppercase" class="form-label">Require Uppercase Letters</label>
                                <select class="form-select" id="require_uppercase" name="require_uppercase">
                                    <option value="1" <?= getParameterValue($parameters, 'require_uppercase') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'require_uppercase') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="require_numbers" class="form-label">Require Numbers</label>
                                <select class="form-select" id="require_numbers" name="require_numbers">
                                    <option value="1" <?= getParameterValue($parameters, 'require_numbers') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'require_numbers') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="require_special_chars" class="form-label">Require Special Characters</label>
                                <select class="form-select" id="require_special_chars" name="require_special_chars">
                                    <option value="1" <?= getParameterValue($parameters, 'require_special_chars') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'require_special_chars') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Access Control</h6>
                            
                            <div class="mb-3">
                                <label for="enable_audit_log" class="form-label">Enable Audit Logging</label>
                                <select class="form-select" id="enable_audit_log" name="enable_audit_log">
                                    <option value="1" <?= getParameterValue($parameters, 'enable_audit_log') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'enable_audit_log') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                                <small class="form-text text-muted">Log all user actions for security monitoring</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ip_whitelist" class="form-label">IP Whitelist</label>
                                <textarea class="form-control" id="ip_whitelist" name="ip_whitelist" rows="3" placeholder="Enter IP addresses, one per line"><?= htmlspecialchars(getParameterValue($parameters, 'ip_whitelist')) ?></textarea>
                                <small class="form-text text-muted">Leave empty to allow all IPs</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                                <select class="form-select" id="maintenance_mode" name="maintenance_mode">
                                    <option value="0" <?= getParameterValue($parameters, 'maintenance_mode') === '0' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="1" <?= getParameterValue($parameters, 'maintenance_mode') === '1' ? 'selected' : '' ?>>Enabled</option>
                                </select>
                                <small class="form-text text-muted">Restrict access during system maintenance</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Two-Factor Authentication</h6>
                            
                            <div class="mb-3">
                                <label for="enable_2fa" class="form-label">Enable 2FA</label>
                                <select class="form-select" id="enable_2fa" name="enable_2fa">
                                    <option value="0" <?= getParameterValue($parameters, 'enable_2fa') === '0' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="1" <?= getParameterValue($parameters, 'enable_2fa') === '1' ? 'selected' : '' ?>>Optional</option>
                                    <option value="2" <?= getParameterValue($parameters, 'enable_2fa') === '2' ? 'selected' : '' ?>>Required</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="2fa_method" class="form-label">2FA Method</label>
                                <select class="form-select" id="2fa_method" name="2fa_method">
                                    <option value="email" <?= getParameterValue($parameters, '2fa_method') === 'email' ? 'selected' : '' ?>>Email</option>
                                    <option value="sms" <?= getParameterValue($parameters, '2fa_method') === 'sms' ? 'selected' : '' ?>>SMS</option>
                                    <option value="authenticator" <?= getParameterValue($parameters, '2fa_method') === 'authenticator' ? 'selected' : '' ?>>Authenticator App</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_codes_count" class="form-label">Backup Codes Count</label>
                                <input type="number" class="form-control" id="backup_codes_count" name="backup_codes_count" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'backup_codes_count', '10')) ?>" min="5" max="20">
                                <small class="form-text text-muted">Number of backup codes to generate</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
function saveSecuritySettings() {
    const form = $('#securitySettingsForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Prepare settings array for API
    const settings = [
        { name: 'session_timeout', value: formData.session_timeout },
        { name: 'max_login_attempts', value: formData.max_login_attempts },
        { name: 'lockout_duration', value: formData.lockout_duration },
        { name: 'password_expiry_days', value: formData.password_expiry_days },
        { name: 'min_password_length', value: formData.min_password_length },
        { name: 'require_uppercase', value: formData.require_uppercase },
        { name: 'require_numbers', value: formData.require_numbers },
        { name: 'require_special_chars', value: formData.require_special_chars },
        { name: 'enable_audit_log', value: formData.enable_audit_log },
        { name: 'ip_whitelist', value: formData.ip_whitelist },
        { name: 'maintenance_mode', value: formData.maintenance_mode },
        { name: 'enable_2fa', value: formData.enable_2fa },
        { name: '2fa_method', value: formData['2fa_method'] },
        { name: 'backup_codes_count', value: formData.backup_codes_count }
    ];
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/save-security',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ settings: settings }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Security settings saved successfully!', 'success');
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

// Auto-save form data
$(document).ready(function() {
    $('#securitySettingsForm').on('change', 'input, select, textarea', function() {
        // Auto-save after 3 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveSecuritySettings();
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