<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">COA Account Types</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#typeModal">
                <i class="bi bi-plus-circle me-2"></i>Add Account Type
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Account Type List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="typesTable">
                        <thead>
                            <tr>
                                <th>Type Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$type['type_name']) ?></td>
                                    <td><?= htmlspecialchars((string)($type['description'] ?? '')) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $type['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($type['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($type['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editType(<?= htmlspecialchars(json_encode($type)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteType(<?= $type['id'] ?>)">
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

<!-- Account Type Modal -->
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Account Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="typeForm">
                    <input type="hidden" id="type_id" name="id">
                    <input type="hidden" id="action" name="action" value="create">
                    <input type="hidden" name="table" value="coa_account_types">
                    
                    <div class="mb-3">
                        <label for="type_name" class="form-label">Type Name *</label>
                        <input type="text" class="form-control" id="type_name" name="type_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveType()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentType = null;

function editType(type) {
    currentType = type;
    document.getElementById('modalTitle').textContent = 'Edit Account Type';
    document.getElementById('action').value = 'update';
    document.getElementById('type_id').value = type.id;
    document.getElementById('type_name').value = type.type_name;
    document.getElementById('description').value = type.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('typeModal'));
    modal.show();
}

function deleteType(typeId) {
    if (confirm('Are you sure you want to delete this account type?')) {
        fetch(APP_URL + '/api/file-maintenance/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                table: 'coa_account_types',
                id: typeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Account type deleted successfully', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('Failed to delete account type: ' + data.error, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while deleting the account type', 'danger');
        });
    }
}

function saveType() {
    const form = document.getElementById('typeForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Validate required fields
    if (!data.type_name.trim()) {
        showAlert('Type name is required', 'danger');
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
            showAlert('Account type saved successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('Failed to save account type: ' + data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while saving the account type', 'danger');
    });
}

// Use EARS.showAlert instead of local showAlert function
function showAlert(message, type) {
    if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert(message, type);
    } else {
        // Fallback for when EARS is not available
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.row');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove after 2.5 seconds to match EARS.showAlert
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 2500);
    }
}

// Reset modal when opened for adding new type
document.getElementById('typeModal').addEventListener('show.bs.modal', function (event) {
    if (!currentType) {
        document.getElementById('modalTitle').textContent = 'Add Account Type';
        document.getElementById('action').value = 'create';
        document.getElementById('typeForm').reset();
        document.getElementById('type_id').value = '';
    }
});

// Reset currentType when modal is hidden
document.getElementById('typeModal').addEventListener('hidden.bs.modal', function (event) {
    currentType = null;
});

// Initialize DataTable
$(document).ready(function() {
    $('#typesTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search types:",
            "lengthMenu": "Show _MENU_ types per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ types"
        }
    });
});
</script> 