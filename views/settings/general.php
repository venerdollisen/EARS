<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">General Settings</h1>
                <p class="text-muted mb-0">Configure general system settings and preferences</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveGeneralSettings()" id="saveBtn">
                <i class="bi bi-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">System Configuration</h6>
            </div>
            <div class="card-body">
                <form id="generalSettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Company Information</h6>
                            
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'company_name')) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Company Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3"><?= htmlspecialchars(getParameterValue($parameters, 'company_address')) ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="tel" class="form-control" id="company_phone" name="company_phone" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'company_phone')) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'company_email')) ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">System Preferences</h6>
                            
                            <div class="mb-3">
                                <label for="default_currency" class="form-label">Default Currency</label>
                                <select class="form-select" id="default_currency" name="default_currency">
                                    <option value="PHP" <?= getParameterValue($parameters, 'default_currency') === 'PHP' ? 'selected' : '' ?>>Philippine Peso (PHP)</option>
                                    <option value="USD" <?= getParameterValue($parameters, 'default_currency') === 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                    <option value="EUR" <?= getParameterValue($parameters, 'default_currency') === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="decimal_places" class="form-label">Decimal Places</label>
                                <select class="form-select" id="decimal_places" name="decimal_places">
                                    <option value="0" <?= getParameterValue($parameters, 'decimal_places') === '0' ? 'selected' : '' ?>>0 (Whole numbers)</option>
                                    <option value="2" <?= getParameterValue($parameters, 'decimal_places') === '2' ? 'selected' : '' ?>>2 (Standard)</option>
                                    <option value="4" <?= getParameterValue($parameters, 'decimal_places') === '4' ? 'selected' : '' ?>>4 (Precise)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select" id="date_format" name="date_format">
                                    <option value="Y-m-d" <?= getParameterValue($parameters, 'date_format') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?= getParameterValue($parameters, 'date_format') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?= getParameterValue($parameters, 'date_format') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Asia/Manila" <?= getParameterValue($parameters, 'timezone') === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila (GMT+8)</option>
                                    <option value="UTC" <?= getParameterValue($parameters, 'timezone') === 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
                                    <option value="America/New_York" <?= getParameterValue($parameters, 'timezone') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (GMT-5)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Fiscal Year Settings</h6>
                            
                            <div class="mb-3">
                                <label for="fiscal_year_start" class="form-label">Fiscal Year Start Date</label>
                                <input type="date" class="form-control" id="fiscal_year_start" name="fiscal_year_start" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'fiscal_year_start')) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fiscal_year_end" class="form-label">Fiscal Year End Date</label>
                                <input type="date" class="form-control" id="fiscal_year_end" name="fiscal_year_end" 
                                       value="<?= htmlspecialchars(getParameterValue($parameters, 'fiscal_year_end')) ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Display Settings</h6>
                            
                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light" <?= getParameterValue($parameters, 'theme') === 'light' ? 'selected' : '' ?>>Light</option>
                                    <option value="dark" <?= getParameterValue($parameters, 'theme') === 'dark' ? 'selected' : '' ?>>Dark</option>
                                    <option value="auto" <?= getParameterValue($parameters, 'theme') === 'auto' ? 'selected' : '' ?>>Auto (System)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="language" class="form-label">Language</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" <?= getParameterValue($parameters, 'language') === 'en' ? 'selected' : '' ?>>English</option>
                                    <option value="tl" <?= getParameterValue($parameters, 'language') === 'tl' ? 'selected' : '' ?>>Tagalog</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="records_per_page" class="form-label">Records per Page</label>
                                <select class="form-select" id="records_per_page" name="records_per_page">
                                    <option value="10" <?= getParameterValue($parameters, 'records_per_page') === '10' ? 'selected' : '' ?>>10</option>
                                    <option value="25" <?= getParameterValue($parameters, 'records_per_page') === '25' ? 'selected' : '' ?>>25</option>
                                    <option value="50" <?= getParameterValue($parameters, 'records_per_page') === '50' ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= getParameterValue($parameters, 'records_per_page') === '100' ? 'selected' : '' ?>>100</option>
                                </select>
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
function saveGeneralSettings() {
    const form = $('#generalSettingsForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Prepare settings array for API
    const settings = [
        { name: 'company_name', value: formData.company_name },
        { name: 'company_address', value: formData.company_address },
        { name: 'company_phone', value: formData.company_phone },
        { name: 'company_email', value: formData.company_email },
        { name: 'default_currency', value: formData.default_currency },
        { name: 'decimal_places', value: formData.decimal_places },
        { name: 'date_format', value: formData.date_format },
        { name: 'timezone', value: formData.timezone },
        { name: 'fiscal_year_start', value: formData.fiscal_year_start },
        { name: 'fiscal_year_end', value: formData.fiscal_year_end },
        { name: 'theme', value: formData.theme },
        { name: 'language', value: formData.language },
        { name: 'records_per_page', value: formData.records_per_page }
    ];
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/save-general',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ settings: settings }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('General settings saved successfully!', 'success');
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
    $('#generalSettingsForm').on('change', 'input, select, textarea', function() {
        // Auto-save after 3 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveGeneralSettings();
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