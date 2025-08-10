<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Users</h1>
      <button type="button" class="btn btn-primary" onclick="openCreateUser()"><i class="bi bi-plus-lg me-2"></i>Add User</button>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover" id="usersTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['full_name'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($u['username']) ?></td>
                  <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
                  <td><span class="badge <?= ($u['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($u['status'] ?? 'active') ?></span></td>
                  <td><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
                  <td>
                    <button type="button" class="btn btn-sm btn-outline-primary" 
                      onclick="openEditUser(this)"
                      data-id="<?= (int)$u['id'] ?>"
                      data-username="<?= htmlspecialchars($u['username']) ?>"
                      data-full_name="<?= htmlspecialchars($u['full_name'] ?? '') ?>"
                      data-email="<?= htmlspecialchars($u['email'] ?? '') ?>"
                      data-role="<?= htmlspecialchars($u['role'] ?? 'user') ?>"
                      data-status="<?= htmlspecialchars($u['status'] ?? 'active') ?>">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= (int)$u['id'] ?>)"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (window.jQuery && $.fn.DataTable) {
    $('#usersTable').DataTable({ pageLength: 25, order: [[5, 'desc']], columnDefs:[{targets:-1, orderable:false, searchable:false}] });
  }
});

function deleteUser(id){
  if(!confirm('Delete this user?')) return;
  $.ajax({
    url: APP_URL + '/api/users/delete/' + id,
    method: 'POST',
    success: function(resp){
      if(resp && resp.success){ location.reload(); }
      else { EARS.showAlert((resp && resp.error) || 'Failed to delete', 'danger', '#globalAlertContainer'); }
    },
    error: function(xhr){ const r = xhr.responseJSON; EARS.showAlert((r && r.error) || 'Failed to delete', 'danger', '#globalAlertContainer'); }
  });
}
</script>

<!-- Create/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Create User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="userCreateForm">
          <input type="hidden" name="id" value="">
          <div class="mb-3">
            <label class="form-label">Username *</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label" id="passwordLabel">Password *</label>
            <input type="password" class="form-control" name="password" id="passwordInput" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                  <option value="admin">Admin</option>
                  <option value="manager">Manager</option>
                  <option value="user" selected>Assistant</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>
        </form>
        <div id="userModalAlert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="saveUser()">Save</button>
      </div>
    </div>
  </div>
 </div>

<script>
function openCreateUser(){
  const form = $('#userCreateForm')[0];
  form.reset();
  form.id.value = '';
  $('#userModalTitle').text('Create User');
  $('#passwordLabel').text('Password *');
  $('#passwordInput').prop('required', true).val('');
  const modal = new bootstrap.Modal(document.getElementById('userModal'));
  modal.show();
}

function openEditUser(btn){
  const d = btn.dataset;
  const form = $('#userCreateForm')[0];
  form.reset();
  form.id.value = d.id || '';
  form.username.value = d.username || '';
  form.full_name.value = d.full_name || '';
  form.email.value = d.email || '';
  form.role.value = d.role || 'user';
  form.status.value = d.status || 'active';
  $('#userModalTitle').text('Edit User');
  $('#passwordLabel').text('Password (leave blank to keep)');
  $('#passwordInput').prop('required', false).val('');
  const modal = new bootstrap.Modal(document.getElementById('userModal'));
  modal.show();
}

function saveUser(){
  const form = $('#userCreateForm');
  const data = Object.fromEntries(new FormData(form[0]).entries());
  const isEdit = !!data.id;
  const url = isEdit ? (APP_URL + '/api/users/update/' + data.id) : (APP_URL + '/api/users/create');
  $.ajax({
    url: url,
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(data),
    success: function(resp){
      if(resp && resp.success){ location.reload(); }
      else { $('#userModalAlert').html('<div class="alert alert-danger">'+(resp.error||'Failed to save')+'</div>'); }
    },
    error: function(xhr){ const r = xhr.responseJSON; $('#userModalAlert').html('<div class="alert alert-danger">'+((r&&r.error)||'Failed to save')+'</div>'); }
  });
}
</script>

