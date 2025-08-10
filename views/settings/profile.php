<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">My Profile</h1>
                <p class="text-muted mb-0">Manage your personal information and account settings</p>
            </div>
        </div>
    </div>
</div>

<?php
// Split full_name into first and last name for form display
$fullName = $user['full_name'] ?? '';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($firstName) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($lastName) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                                <small class="form-text text-muted">Username cannot be changed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" onclick="saveProfile()" id="saveProfileBtn">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
            </div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-warning" onclick="changePassword()" id="changePasswordBtn">
                            <i class="bi bi-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Profile Summary -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Profile Summary</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="avatar-placeholder bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?>
                    </div>
                </div>
                
                <h5 class="mb-1"><?= htmlspecialchars($user['full_name'] ?? '') ?></h5>
                <p class="text-muted mb-2"><?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?></p>
                
                <hr>
                
                <div class="text-start">
                    <div class="mb-2">
                        <small class="text-muted">Email:</small><br>
                        <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Username:</small><br>
                        <span><?= htmlspecialchars($user['username'] ?? '') ?></span>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Member Since:</small><br>
                        <span><?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                    </div>
                    
                    <div class="mb-0">
                        <small class="text-muted">Last Updated:</small><br>
                        <span><?= date('M d, Y', strtotime($user['updated_at'] ?? 'now')) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Security -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Account Security</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Account Status</span>
                        <span class="badge bg-<?= ($user['status'] ?? 'active') === 'active' ? 'success' : 'danger' ?>">
                            <?= ucfirst($user['status'] ?? 'active') ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>User Role</span>
                        <span class="badge bg-info"><?= ucfirst($user['role'] ?? 'user') ?></span>
                    </div>
                </div>
                
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Last Login</span>
                        <small class="text-muted"><?= ($user['last_login'] ?? null) ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
function saveProfile() {
    const form = $('#profileForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Show loading
    const saveBtn = $('#saveProfileBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/save-profile',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ profile: formData }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Profile updated successfully!', 'success');
                // Refresh page to update profile summary
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                EARS.showAlert(response.error || 'Failed to update profile', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to update profile', 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

function changePassword() {
    const form = $('#passwordForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    // Validate passwords match
    if (formData.new_password !== formData.confirm_password) {
        EARS.showAlert('New passwords do not match', 'danger');
        return;
    }
    
    // Show loading
    const changeBtn = $('#changePasswordBtn');
    const originalText = changeBtn.html();
    changeBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Changing...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/settings/change-password',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Password changed successfully!', 'success');
                // Clear password form
                form[0].reset();
            } else {
                EARS.showAlert(response.error || 'Failed to change password', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to change password', 'danger');
        },
        complete: function() {
            changeBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Auto-save profile form data
$(document).ready(function() {
    $('#profileForm').on('change', 'input, select, textarea', function() {
        // Auto-save after 3 seconds of inactivity
        clearTimeout(window.autoSaveTimer);
        window.autoSaveTimer = setTimeout(function() {
            saveProfile();
        }, 3000);
    });
});
</script> 