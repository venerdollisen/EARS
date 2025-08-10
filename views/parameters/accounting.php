<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Accounting Parameters</h1>
            <button type="button" class="btn btn-primary" onclick="saveParameters()" id="saveBtn">
                <i class="bi bi-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">System Parameters</h6>
            </div>
            <div class="card-body">
                <form id="parametersForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Company Information</h6>
                            
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'company_name')) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="default_currency" class="form-label">Default Currency</label>
                                <select class="form-select" id="default_currency" name="default_currency">
                                    <option value="PHP" <?= getParameterValue($parameters, 'default_currency') === 'PHP' ? 'selected' : '' ?>>Philippine Peso (PHP)</option>
                                    <option value="USD" <?= getParameterValue($parameters, 'default_currency') === 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                    <option value="EUR" <?= getParameterValue($parameters, 'default_currency') === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Year Settings</h6>
                            
                            <div class="mb-3">
                                <label for="fiscal_year_start" class="form-label">Year Start Date</label>
                                <input type="date" class="form-control" id="fiscal_year_start" name="fiscal_year_start" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'fiscal_year_start')) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fiscal_year_end" class="form-label">Year End Date</label>
                                <input type="date" class="form-control" id="fiscal_year_end" name="fiscal_year_end" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'fiscal_year_end')) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row" style="display: none;"> 
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Display Settings</h6>
                            
                            <div class="mb-3">
                                <label for="decimal_places" class="form-label">Decimal Places</label>
                                <select class="form-select" id="decimal_places" name="decimal_places">
                                    <option value="0" <?= getParameterValue($parameters, 'decimal_places') === '0' ? 'selected' : '' ?>>0 (Whole numbers)</option>
                                    <option value="2" <?= getParameterValue($parameters, 'decimal_places') === '2' ? 'selected' : '' ?>>2 (Standard)</option>
                                    <option value="4" <?= getParameterValue($parameters, 'decimal_places') === '4' ? 'selected' : '' ?>>4 (Precise)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">System Settings</h6>
                            
                            <div class="mb-3">
                                <label for="auto_backup" class="form-label">Auto Backup</label>
                                <select class="form-select" id="auto_backup" name="auto_backup">
                                    <option value="1" <?= getParameterValue($parameters, 'auto_backup') === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= getParameterValue($parameters, 'auto_backup') === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'session_timeout', '3600')) ?>" min="300" max="86400">
                                <small class="form-text text-muted">Minimum: 5 minutes (300s), Maximum: 24 hours (86400s)</small>
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
function saveParameters() {
    const form = $('#parametersForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Prepare parameters array for API
    const parameters = [
        { id: 1, value: formData.company_name },
        { id: 2, value: formData.fiscal_year_start },
        { id: 3, value: formData.fiscal_year_end },
        { id: 4, value: formData.default_currency },
        { id: 5, value: formData.decimal_places },
        { id: 6, value: formData.auto_backup },
        { id: 7, value: formData.session_timeout }
    ];
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/parameters/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ parameters: parameters }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Parameters saved successfully!', 'success');
            } else {
                EARS.showAlert(response.error || 'Failed to save parameters', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save parameters', 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Auto-save form data
$(document).ready(function() {
    $('#parametersForm').on('change', 'input, select', function() {
        // Auto-save after 2 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveParameters();
        }, 2000);
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