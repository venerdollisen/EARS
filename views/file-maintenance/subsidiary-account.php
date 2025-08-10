<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Subsidiary Accounts (Suppliers)</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
                <i class="bi bi-plus-circle me-2"></i>Add Supplier
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Supplier List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="suppliersTable">
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Account</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$supplier['supplier_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$supplier['contact_person']) ?></td>
                                    <td><?= htmlspecialchars((string)$supplier['phone']) ?></td>
                                    <td><?= htmlspecialchars((string)$supplier['email']) ?></td>
                                    <td>
                                        <?php 
                                        if ($supplier['account_code'] && $supplier['account_name']) {
                                            echo htmlspecialchars((string)$supplier['account_code'] . ' - ' . (string)$supplier['account_name']);
                                        } else {
                                            echo '<span class="text-muted">No account linked</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $supplier['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($supplier['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editSupplier(<?= htmlspecialchars(json_encode($supplier)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteSupplier(<?= $supplier['id'] ?>)">
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

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="supplierForm">
                    <input type="hidden" id="supplier_id" name="id">
                    <input type="hidden" id="action" name="action" value="create">
                    <input type="hidden" name="table" value="suppliers">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Linked Account *</label>
                        <select class="form-select" id="account_id" name="account_id" required>
                            <option value="">Select Account</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= htmlspecialchars((string)$account['account_code'] . ' - ' . (string)$account['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#suppliersTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
});

function editSupplier(supplier) {
    $('#modalTitle').text('Edit Supplier');
    $('#action').val('update');
    $('#supplier_id').val(supplier.id);
    $('#supplier_name').val(supplier.supplier_name);
    $('#contact_person').val(supplier.contact_person);
    $('#phone').val(supplier.phone);
    $('#email').val(supplier.email);
    $('#address').val(supplier.address);
    $('#account_id').val(supplier.account_id);
    
    $('#supplierModal').modal('show');
}

function deleteSupplier(supplierId) {
    if (confirm('Are you sure you want to delete this supplier?')) {
        fetch(APP_URL + '/api/file-maintenance/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                table: 'suppliers',
                id: supplierId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Supplier deleted successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert(data.error || 'Failed to delete supplier', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to delete supplier', 'danger');
        });
    }
}

function saveSupplier() {
    const form = document.getElementById('supplierForm');
    
    // Basic validation
    const requiredFields = ['supplier_name', 'account_id'];
    for (let field of requiredFields) {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            showAlert('Please fill in all required fields.', 'warning');
            input.focus();
            return;
        }
    }
    
    const formData = {};
    const formElements = form.elements;
    for (let element of formElements) {
        if (element.name) {
            formData[element.name] = element.value;
        }
    }
    
    const saveBtn = document.querySelector('button[onclick="saveSupplier()"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(APP_URL + '/api/file-maintenance/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Supplier saved successfully!', 'success');
            $('#supplierModal').modal('hide');
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.error || 'Failed to save supplier', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to save supplier', 'danger');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Reset modal when closed
$('#supplierModal').on('hidden.bs.modal', function() {
    document.getElementById('supplierForm').reset();
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('action').value = 'create';
    document.getElementById('supplier_id').value = '';
});
</script> 