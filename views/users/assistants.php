<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Assistant Accounts</h1>
      <a href="<?= APP_URL ?>/users/create" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Assistant</a>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover" id="assistantsTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
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
                  <td><span class="badge <?= ($u['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($u['status'] ?? 'active') ?></span></td>
                  <td><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
                  <td>
                    <a href="<?= APP_URL ?>/users/edit/<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
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
    $('#assistantsTable').DataTable({ pageLength: 25, order: [[4, 'desc']], columnDefs:[{targets:-1, orderable:false, searchable:false}] });
  }
});

function deleteUser(id){
  if(!confirm('Delete this assistant?')) return;
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

