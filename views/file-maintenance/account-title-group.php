<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Account Title Groups</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#groupModal">
                <i class="bi bi-plus-circle me-2"></i>Add Group
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Group List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="groupsTable">
                        <thead>
                            <tr>
                                <th>Group Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$group['group_name']) ?></td>
                                    <td><?= htmlspecialchars((string)($group['description'] ?? '')) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $group['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($group['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($group['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editGroup(<?= htmlspecialchars(json_encode($group)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteGroup(<?= $group['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- Group Modal -->
<div class="modal fade" id="groupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="groupForm">
                    <input type="hidden" id="group_id" name="id">
                    <input type="hidden" id="action" name="action" value="create">
                    <input type="hidden" name="table" value="account_title_groups">
                    
                    <div class="mb-3">
                        <label for="group_name" class="form-label">Group Name *</label>
                        <input type="text" class="form-control" id="group_name" name="group_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveGroup()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentGroup = null;

function editGroup(group) {
    currentGroup = group;
    document.getElementById('modalTitle').textContent = 'Edit Group';
    document.getElementById('action').value = 'update';
    document.getElementById('group_id').value = group.id;
    document.getElementById('group_name').value = group.group_name;
    document.getElementById('description').value = group.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('groupModal'));
    modal.show();
}

function deleteGroup(groupId) {
    if (confirm('Are you sure you want to delete this group?')) {
        fetch(APP_URL + '/api/file-maintenance/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                table: 'account_title_groups',
                id: groupId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Group deleted successfully', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('Failed to delete group: ' + data.error, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while deleting the group', 'danger');
        });
    }
}

function saveGroup() {
    const form = document.getElementById('groupForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Validate required fields
    if (!data.group_name.trim()) {
        showAlert('Group name is required', 'danger');
        return;
    }
    
    fetch(APP_URL + '/api/file-maintenance/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Group saved successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('Failed to save group: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while saving the group', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.row');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Reset modal when opened for adding new group
document.getElementById('groupModal').addEventListener('show.bs.modal', function (event) {
    if (!currentGroup) {
        document.getElementById('modalTitle').textContent = 'Add Group';
        document.getElementById('action').value = 'create';
        document.getElementById('groupForm').reset();
        document.getElementById('group_id').value = '';
    }
});

// Reset currentGroup when modal is hidden
document.getElementById('groupModal').addEventListener('hidden.bs.modal', function (event) {
    currentGroup = null;
});

// Initialize DataTable
$(document).ready(function() {
    $('#groupsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search groups:",
            "lengthMenu": "Show _MENU_ groups per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ groups"
        }
    });
});
</script> 