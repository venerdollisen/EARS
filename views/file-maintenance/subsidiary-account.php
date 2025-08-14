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
                                <th>Contact Info</th>
                                <th>VAT Details</th>
                                <th>Account</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string)$supplier['supplier_name']) ?></strong>
                                        <?php if ($supplier['tin']): ?>
                                            <br><small class="text-muted">TIN: <?= htmlspecialchars((string)$supplier['tin']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($supplier['contact_person']): ?>
                                            <div><i class="bi bi-person me-1"></i><?= htmlspecialchars((string)$supplier['contact_person']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($supplier['phone']): ?>
                                            <div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$supplier['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($supplier['email']): ?>
                                            <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string)$supplier['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $supplier['vat_subject'] === 'VAT' ? 'primary' : ($supplier['vat_subject'] === 'Non-VAT' ? 'warning' : 'info') ?>">
                                            <?= htmlspecialchars((string)$supplier['vat_subject']) ?>
                                        </span>
                                        <?php if ($supplier['vat_rate'] && $supplier['vat_rate'] > 0): ?>
                                            <br><small class="text-muted">Rate: <?= number_format($supplier['vat_rate'], 0) ?>%</small>
                                        <?php endif; ?>
                                        <?php if ($supplier['vat_account_code']): ?>
                                            <br><small class="text-muted">VAT: <?= htmlspecialchars((string)$supplier['vat_account_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($supplier['account_code'] && $supplier['account_name']): ?>
                                            <div><strong><?= htmlspecialchars((string)$supplier['account_code']) ?></strong></div>
                                            <small class="text-muted"><?= htmlspecialchars((string)$supplier['account_name']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">No account linked</span>
                                        <?php endif; ?>
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
                    
                    <!-- Basic Information Section -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                        </div>
                        <div class="card-body">
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
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- VAT Information Section -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>VAT Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="vat_subject" class="form-label">VAT Subject</label>
                                        <select class="form-select" id="vat_subject" name="vat_subject">
                                            <option value="VAT">VAT</option>
                                            <option value="Non-VAT">Non-VAT</option>
                                            <option value="Zero-Rated">Zero-Rated</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="vat_rate" class="form-label">VAT Rate (%)</label>
                                        <input type="number" class="form-control" id="vat_rate" name="vat_rate" step="0.01" min="0" max="100" value="12.00" placeholder="12.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tin" class="form-label">TIN</label>
                                        <input type="text" class="form-control" id="tin" name="tin" placeholder="123-456-789-000">
                                    </div>
                                </div>
                            </div>
                            

                        </div>
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
    $('#vat_subject').val(supplier.vat_subject);
    $('#tin').val(supplier.tin);
    $('#vat_rate').val(supplier.vat_rate);
    
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
    const requiredFields = ['supplier_name'];
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

// Use EARS.showAlert instead of local showAlert function
function showAlert(message, type) {
    if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert(message, type);
    } else {
        // Fallback for when EARS is not available
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alertHtml;
        
        // Auto-remove after 2.5 seconds to match EARS.showAlert
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 2500);
    }
}

// Reset modal when closed
$('#supplierModal').on('hidden.bs.modal', function() {
    document.getElementById('supplierForm').reset();
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('action').value = 'create';
    document.getElementById('supplier_id').value = '';
});
</script> 