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
                            <h6 class="text-primary mb-3">Year Configuration</h6>
                            
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
        { name: 'fiscal_year_start', value: formData.fiscal_year_start },
        { name: 'fiscal_year_end', value: formData.fiscal_year_end }
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