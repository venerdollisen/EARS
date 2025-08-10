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
            <input type="password" class="form-control" name="password" <?= $isEdit ? '' : 'required' ?>>
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
                  <option value="admin" <?= ($rec['role']==='admin'?'selected':'') ?>>Admin</option>
                  <option value="manager" <?= ($rec['role']==='manager'?'selected':'') ?>>Manager</option>
                  <option value="user" <?= ($rec['role']==='user'?'selected':'') ?>>Assistant</option>
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
function submitUser() {
  const form = $('#userForm');
  const data = Object.fromEntries(new FormData(form[0]).entries());
  const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
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
</script>

