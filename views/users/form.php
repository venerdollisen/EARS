<?php
$isEdit = ($mode ?? '') === 'edit';
$rec = $record ?? [
  'username' => '', 'full_name' => '', 'email' => '', 'role' => 'user', 'status' => 'active'
];
?>
<div class="row">
  <div class="col-12 col-lg-8">
    <h1 class="h4 mb-3"><?= $isEdit ? 'Edit User' : 'Create User' ?></h1>
    <div class="card">
      <div class="card-body">
        <form id="userForm">
          <div class="mb-3">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($rec['username']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep)</small>' : '*' ?></label>
            <div class="input-group">
              <input type="password" class="form-control" name="password" id="password" <?= $isEdit ? '' : 'required' ?>>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="password-strength mt-2">
              <div class="progress" style="height: 5px;">
                <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
              </div>
              <small class="form-text text-muted" id="passwordStrengthText">Minimum 8 characters</small>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($rec['full_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($rec['email'] ?? '') ?>">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                  <option value="admin" <?= ($rec['role']==='admin'?'selected':'') ?>>Finance Manager</option>
                  <option value="manager" <?= ($rec['role']==='manager'?'selected':'') ?>>BIR</option>
                  <option value="user" <?= ($rec['role']==='user'?'selected':'') ?>>Bookkeper</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                  <option value="active" <?= ($rec['status']==='active'?'selected':'') ?>>Active</option>
                  <option value="inactive" <?= ($rec['status']==='inactive'?'selected':'') ?>>Inactive</option>
                </select>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="submitUser()">Save</button>
            <a href="<?= APP_URL ?>/users" class="btn btn-secondary">Back</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) {
        strength += 25;
        feedback.push('Length ✓');
    } else {
        feedback.push('At least 8 characters');
    }
    
    if (/[a-z]/.test(password)) {
        strength += 25;
        feedback.push('Lowercase ✓');
    } else {
        feedback.push('Lowercase letter');
    }
    
    if (/[A-Z]/.test(password)) {
        strength += 25;
        feedback.push('Uppercase ✓');
    } else {
        feedback.push('Uppercase letter');
    }
    
    if (/[0-9]/.test(password)) {
        strength += 25;
        feedback.push('Number ✓');
    } else {
        feedback.push('Number');
    }
    
    return { strength, feedback };
}

// Update password strength indicator
function updatePasswordStrength() {
    const password = $('#password').val();
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    
    // Hide strength indicator if password is empty in edit mode
    if (isEdit && (!password || password.trim() === '')) {
        $('#passwordStrengthBar').css('width', '0%');
        $('#passwordStrengthText').text('Leave blank to keep current password').removeClass('text-success text-warning text-info text-danger').addClass('text-muted');
        return;
    }
    
    const { strength, feedback } = checkPasswordStrength(password);
    
    const bar = $('#passwordStrengthBar');
    const text = $('#passwordStrengthText');
    
    bar.css('width', strength + '%');
    
    if (strength <= 25) {
        bar.removeClass('bg-success bg-warning bg-info').addClass('bg-danger');
        text.removeClass('text-success text-warning text-info text-muted').addClass('text-danger');
    } else if (strength <= 50) {
        bar.removeClass('bg-success bg-danger bg-info').addClass('bg-warning');
        text.removeClass('text-success text-danger text-info text-muted').addClass('text-warning');
    } else if (strength <= 75) {
        bar.removeClass('bg-danger bg-warning bg-success').addClass('bg-info');
        text.removeClass('text-danger text-warning text-success text-muted').addClass('text-info');
    } else {
        bar.removeClass('bg-danger bg-warning bg-info').addClass('bg-success');
        text.removeClass('text-danger text-warning text-info text-muted').addClass('text-success');
    }
    
    text.text(feedback.join(', '));
}

function submitUser() {
  const form = $('#userForm');
  const data = Object.fromEntries(new FormData(form[0]).entries());
  const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  
  // Validate password for new users
  if (!isEdit && (!data.password || data.password.trim() === '')) {
    EARS.showAlert('Password is required for new users', 'danger', '#globalAlertContainer');
    return;
  }
  
  // Validate password strength for new users or when password is provided in edit mode
  if ((!isEdit || (isEdit && data.password && data.password.trim() !== '')) && data.password) {
    if (data.password.length < 8) {
      EARS.showAlert('Password must be at least 8 characters long', 'danger', '#globalAlertContainer');
      return;
    }
    
    if (!/[a-z]/.test(data.password)) {
      EARS.showAlert('Password must contain at least one lowercase letter', 'danger', '#globalAlertContainer');
      return;
    }
    
    if (!/[A-Z]/.test(data.password)) {
      EARS.showAlert('Password must contain at least one uppercase letter', 'danger', '#globalAlertContainer');
      return;
    }
    
    if (!/[0-9]/.test(data.password)) {
      EARS.showAlert('Password must contain at least one number', 'danger', '#globalAlertContainer');
      return;
    }
  }
  
  const url = isEdit ? (APP_URL + '/api/users/update/<?= (int)($rec['id'] ?? 0) ?>') : (APP_URL + '/api/users/create');
  $.ajax({
    url: url,
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(data),
    success: function(resp){
      if (resp && resp.success) {
        EARS.showAlert('User saved successfully', 'success', '#globalAlertContainer');
        setTimeout(()=>{ window.location.href = APP_URL + '/users'; }, 800);
      } else {
        EARS.showAlert((resp && resp.error) || 'Failed to save', 'danger', '#globalAlertContainer');
      }
    },
    error: function(xhr){
      const r = xhr.responseJSON; EARS.showAlert((r && r.error) || 'Failed to save', 'danger', '#globalAlertContainer');
    }
  });
}

// Initialize password functionality
$(document).ready(function() {
    // Password strength validation
    $('#password').on('input', function() {
        updatePasswordStrength();
    });
    
    // Password toggle functionality
    $('#togglePassword').on('click', function() {
        const input = $('#password');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
});
</script>

