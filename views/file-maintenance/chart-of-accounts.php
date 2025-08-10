<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Chart of Accounts</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal" id="addAccountBtn">
                <i class="bi bi-plus-circle me-2"></i>Add Account
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Account List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="accountsTable">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th>Group</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$account['account_code']) ?></td>
                                    <td><?= htmlspecialchars((string)$account['account_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getAccountTypeColor($account['type_name']) ?>">
                                            <?= htmlspecialchars((string)$account['type_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars((string)$account['group_name']) ?></td>
                                    <td class="text-end">₱<?= number_format($account['balance'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $account['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($account['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-account" 
                                                onclick="editAccount(<?= htmlspecialchars(json_encode($account)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteAccount(<?= $account['id'] ?>)">
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

<!-- Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="accountForm">
                    <input type="hidden" id="account_id" name="id">
                    <input type="hidden" id="action" name="action" value="create">
                    <input type="hidden" name="table" value="chart_of_accounts">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_code" class="form-label">Account Code *</label>
                                <input type="text" class="form-control" id="account_code" name="account_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_name" class="form-label">Account Name *</label>
                                <input type="text" class="form-control" id="account_name" name="account_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_type_id" class="form-label">Account Type *</label>
                                <select class="form-select" id="account_type_id" name="account_type_id" required>
                                    <option value="">Select Account Type</option>
                                    <?php foreach ($accountTypes as $type): ?>
                                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars((string)$type['type_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="group_id" class="form-label">Account Group *</label>
                                <select class="form-select" id="group_id" name="group_id" required>
                                    <option value="">Select Account Group</option>
                                    <?php foreach ($accountGroups as $group): ?>
                                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars((string)$group['group_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAccount()">Save Account</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#accountsTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
    
    // Disable auto-save for account form to prevent caching
    $('#accountForm').off('input change');
    
    // Clear form when Add Account button is clicked
    $('#addAccountBtn').on('click', function() {
        $('#accountForm')[0].reset();
        $('#modalTitle').text('Add Account');
        $('#action').val('create');
        $('#account_id').val('');
        $('#accountForm .is-invalid').removeClass('is-invalid');
        // Clear localStorage
        localStorage.removeItem('accountForm');
    });
    
    // Add flag to track edit mode
    window.isEditMode = false;
});

function editAccount(account) {
    // Set edit mode flag
    window.isEditMode = true;
    
    // Set modal title and action
    $('#modalTitle').text('Edit Account');
    $('#action').val('update');
    
    // Fill form with account data
    $('#account_id').val(account.id);
    $('#account_code').val(account.account_code);
    $('#account_name').val(account.account_name);
    $('#account_type_id').val(account.account_type_id);
    $('#group_id').val(account.group_id);
    $('#description').val(account.description);
    
    // Clear validation classes
    $('#accountForm .is-invalid').removeClass('is-invalid');
    
    // Show modal after a small delay to ensure data is populated
    setTimeout(function() {
        $('#accountModal').modal('show');
    }, 10);
}

function deleteAccount(accountId) {
    EARS.confirm('Are you sure you want to delete this account?', function() {
        $.ajax({
            url: APP_URL + '/api/file-maintenance/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                table: 'chart_of_accounts',
                id: accountId
            }),
            success: function(response) {
                if (response.success) {
                    EARS.showAlert('Account deleted successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    EARS.showAlert(response.error || 'Failed to delete account', 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                EARS.showAlert(response?.error || 'Failed to delete account', 'danger');
            }
        });
    });
}

function saveAccount() {
    const form = $('#accountForm');
    
    if (!EARS.validateForm('#accountForm')) {
        EARS.showAlert('Please fill in all required fields.', 'warning');
        return;
    }
    
    const formData = {};
    form.serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });
    
    const saveBtn = $('button[onclick="saveAccount()"]');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    $.ajax({
        url: APP_URL + '/api/file-maintenance/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Account saved successfully!', 'success');
                $('#accountModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                EARS.showAlert(response.error || 'Failed to save account', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save account', 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Reset modal when closed
$('#accountModal').on('hidden.bs.modal', function() {
    $('#accountForm')[0].reset();
    $('#modalTitle').text('Add Account');
    $('#action').val('create');
    $('#account_id').val('');
    // Clear any validation classes
    $('#accountForm .is-invalid').removeClass('is-invalid');
});

// Clear form when opening modal for new account
$('#accountModal').on('show.bs.modal', function(e) {
    // Only clear if it's a new account (not edit)
    if (!window.isEditMode) {
        $('#accountForm')[0].reset();
        $('#modalTitle').text('Add Account');
        $('#action').val('create');
        $('#account_id').val('');
        // Clear any validation classes
        $('#accountForm .is-invalid').removeClass('is-invalid');
        // Clear localStorage for this form
        localStorage.removeItem('accountForm');
    }
    // Reset edit mode flag
    window.isEditMode = false;
});

</script>

<?php
function getAccountTypeColor($typeName) {
    switch (strtolower($typeName)) {
        case 'asset':
            return 'success';
        case 'liability':
            return 'danger';
        case 'equity':
            return 'primary';
        case 'revenue':
            return 'info';
        case 'expense':
            return 'warning';
        default:
            return 'secondary';
    }
}
?> 