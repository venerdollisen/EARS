<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Backup & Recovery</h1>
                <p class="text-muted mb-0">Configure backup settings and manage data recovery</p>
            </div>
            <div>
                <button type="button" class="btn btn-success me-2" onclick="createBackup()">
                    <i class="bi bi-download me-2"></i>Create Backup
                </button>
                <button type="button" class="btn btn-primary" onclick="saveBackupSettings()" id="saveBtn">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Backup Configuration</h6>
            </div>
            <div class="card-body">
                <form id="backupSettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Automatic Backup</h6>
                            
                            <div class="mb-3">
                                <label for="auto_backup" class="form-label">Enable Auto Backup</label>
                                <select class="form-select" id="auto_backup" name="auto_backup">
                                    <option value="1" <?= getParameterValue($parameters, 'auto_backup') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'auto_backup') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                <select class="form-select" id="backup_frequency" name="backup_frequency">
                                    <option value="daily" <?= getParameterValue($parameters, 'backup_frequency') === 'daily' ? 'selected' : '' ?>>Daily</option>
                                    <option value="weekly" <?= getParameterValue($parameters, 'backup_frequency') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                    <option value="monthly" <?= getParameterValue($parameters, 'backup_frequency') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_time" class="form-label">Backup Time</label>
                                <input type="time" class="form-control" id="backup_time" name="backup_time" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'backup_time', '02:00')) ?>">
                                <small class="form-text text-muted">Time when automatic backup should run</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_retention_days" class="form-label">Retention Period (days)</label>
                                <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'backup_retention_days', '30')) ?>" min="1" max="365">
                                <small class="form-text text-muted">How long to keep backup files</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Backup Content</h6>
                            
                            <div class="mb-3">
                                <label for="backup_database" class="form-label">Include Database</label>
                                <select class="form-select" id="backup_database" name="backup_database">
                                    <option value="1" <?= getParameterValue($parameters, 'backup_database') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'backup_database') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_files" class="form-label">Include Files</label>
                                <select class="form-select" id="backup_files" name="backup_files">
                                    <option value="1" <?= getParameterValue($parameters, 'backup_files') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'backup_files') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_compression" class="form-label">Compression Level</label>
                                <select class="form-select" id="backup_compression" name="backup_compression">
                                    <option value="none" <?= getParameterValue($parameters, 'backup_compression') === 'none' ? 'selected' : '' ?>>None</option>
                                    <option value="low" <?= getParameterValue($parameters, 'backup_compression') === 'low' ? 'selected' : '' ?>>Low (Fast)</option>
                                    <option value="medium" <?= getParameterValue($parameters, 'backup_compression') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="high" <?= getParameterValue($parameters, 'backup_compression') === 'high' ? 'selected' : '' ?>>High (Small)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_encryption" class="form-label">Encrypt Backups</label>
                                <select class="form-select" id="backup_encryption" name="backup_encryption">
                                    <option value="0" <?= getParameterValue($parameters, 'backup_encryption') === '0' ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?= getParameterValue($parameters, 'backup_encryption') === '1' ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Storage Settings</h6>
                            
                            <div class="mb-3">
                                <label for="backup_location" class="form-label">Backup Location</label>
                                <select class="form-select" id="backup_location" name="backup_location">
                                    <option value="local" <?= getParameterValue($parameters, 'backup_location') === 'local' ? 'selected' : '' ?>>Local Server</option>
                                    <option value="ftp" <?= getParameterValue($parameters, 'backup_location') === 'ftp' ? 'selected' : '' ?>>FTP Server</option>
                                    <option value="cloud" <?= getParameterValue($parameters, 'backup_location') === 'cloud' ? 'selected' : '' ?>>Cloud Storage</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_path" class="form-label">Backup Directory</label>
                                <input type="text" class="form-control" id="backup_path" name="backup_path" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'backup_path', '/backups')) ?>">
                                <small class="form-text text-muted">Directory where backups will be stored</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="max_backup_size" class="form-label">Maximum Backup Size (MB)</label>
                                <input type="number" class="form-control" id="max_backup_size" name="max_backup_size" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'max_backup_size', '1000')) ?>" min="100" max="10000">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Notification Settings</h6>
                            
                            <div class="mb-3">
                                <label for="backup_notification_email" class="form-label">Notification Email</label>
                                <input type="email" class="form-control" id="backup_notification_email" name="backup_notification_email" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'backup_notification_email')) ?>">
                                <small class="form-text text-muted">Email to notify about backup status</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_notification_success" class="form-label">Notify on Success</label>
                                <select class="form-select" id="backup_notification_success" name="backup_notification_success">
                                    <option value="1" <?= getParameterValue($parameters, 'backup_notification_success') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'backup_notification_success') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_notification_failure" class="form-label">Notify on Failure</label>
                                <select class="form-select" id="backup_notification_failure" name="backup_notification_failure">
                                    <option value="1" <?= getParameterValue($parameters, 'backup_notification_failure') === '1' ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= getParameterValue($parameters, 'backup_notification_failure') === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="backup_log_level" class="form-label">Log Level</label>
                                <select class="form-select" id="backup_log_level" name="backup_log_level">
                                    <option value="error" <?= getParameterValue($parameters, 'backup_log_level') === 'error' ? 'selected' : '' ?>>Error Only</option>
                                    <option value="warning" <?= getParameterValue($parameters, 'backup_log_level') === 'warning' ? 'selected' : '' ?>>Warning</option>
                                    <option value="info" <?= getParameterValue($parameters, 'backup_log_level') === 'info' ? 'selected' : '' ?>>Info</option>
                                    <option value="debug" <?= getParameterValue($parameters, 'backup_log_level') === 'debug' ? 'selected' : '' ?>>Debug</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recent Backups Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Recent Backups</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="backupsTable">
                        <thead>
                            <tr>
                                <th>Backup Date</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No backups found. Create your first backup to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
function saveBackupSettings() {
    const form = $('#backupSettingsForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Prepare settings array for API
    const settings = [
        { name: 'auto_backup', value: formData.auto_backup },
        { name: 'backup_frequency', value: formData.backup_frequency },
        { name: 'backup_time', value: formData.backup_time },
        { name: 'backup_retention_days', value: formData.backup_retention_days },
        { name: 'backup_database', value: formData.backup_database },
        { name: 'backup_files', value: formData.backup_files },
        { name: 'backup_compression', value: formData.backup_compression },
        { name: 'backup_encryption', value: formData.backup_encryption },
        { name: 'backup_location', value: formData.backup_location },
        { name: 'backup_path', value: formData.backup_path },
        { name: 'max_backup_size', value: formData.max_backup_size },
        { name: 'backup_notification_email', value: formData.backup_notification_email },
        { name: 'backup_notification_success', value: formData.backup_notification_success },
        { name: 'backup_notification_failure', value: formData.backup_notification_failure },
        { name: 'backup_log_level', value: formData.backup_log_level }
    ];
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/save-backup',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ settings: settings }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Backup settings saved successfully!', 'success');
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

function createBackup() {
    // Show loading
    const backupBtn = $('button[onclick="createBackup()"]');
    const originalText = backupBtn.html();
    backupBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/create-backup',
        method: 'POST',
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Backup created successfully!', 'success');
                // Refresh backup list
                loadBackups();
            } else {
                EARS.showAlert(response.error || 'Failed to create backup', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to create backup', 'danger');
        },
        complete: function() {
            backupBtn.prop('disabled', false).html(originalText);
        }
    });
}

function loadBackups() {
    // This would load the list of recent backups
    // For now, we'll just show a placeholder
}

// Auto-save form data
$(document).ready(function() {
    $('#backupSettingsForm').on('change', 'input, select, textarea', function() {
        // Auto-save after 3 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveBackupSettings();
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