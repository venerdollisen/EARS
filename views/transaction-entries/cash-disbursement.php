<?php $isStatusLocked = isset($user['role']) && in_array($user['role'], ['user','assistant']); ?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Cash Disbursement Entry</h1>
            <div>
                <button type="button" class="btn btn-success" onclick="saveTransaction()">
                    <i class="bi bi-save me-2"></i>Save Transaction
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Recent Transactions section intentionally moved below the form -->

<div class="row">
    <!-- Transaction Form -->
    <div class="col-lg-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Cash Disbursement Details</h6>
            </div>
            <div class="card-body">
                <form id="transactionForm" data-autosave="false">
                    <input type="hidden" name="transaction_type" value="cash_disbursement">
                    
                    <!-- Header Information -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="voucher_number" class="form-label">Cash Voucher Number *</label>
                                <input type="text" class="form-control" id="voucher_number" name="voucher_number" 
                                       value="CV-<?= date('Ymd') ?>-<?= str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="transaction_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                                <small class="form-text text-muted">Current date: <?= date('Y-m-d') ?></small>
                            </div>
                        </div>
   
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_number" class="form-label">Cash Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number" 
                                       placeholder="Enter check number">
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_date" class="form-label">Cash Date</label>
                                <input type="date" class="form-control" id="check_date" name="check_date" 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="payee_name" class="form-label">Payee/Cash Name *</label>
                                <input type="text" class="form-control" id="payee_name" name="payee_name" 
                                       placeholder="Enter payee name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="po_number" class="form-label">P.O. Number</label>
                                <input type="text" class="form-control" id="po_number" name="po_number" 
                                       placeholder="Enter P.O. number">
                            </div>
                        </div>
                        <div class="col-md-3" style="display: none;">
                            <div class="mb-3">
                                <label for="cwo_number" class="form-label">CWO Number</label>
                                <input type="text" class="form-control" id="cwo_number" name="cwo_number" 
                                       placeholder="Enter CWO number">
                            </div>
                        </div>
                        <div class="col-md-3" style="display: none;">
                            <div class="mb-3">
                                <label for="ebr_number" class="form-label">EBR Number</label>
                                <input type="text" class="form-control" id="ebr_number" name="ebr_number" 
                                       placeholder="Enter EBR number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="particulars" class="form-label">Particulars *</label>
                                <textarea class="form-control" id="particulars" name="particulars" rows="3" 
                                        placeholder="Enter transaction particulars..." required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_payment_status" class="form-label">Payment Status</label>
                                <select class="form-select" id="check_payment_status" name="check_payment_status" <?= $isStatusLocked ? 'disabled' : '' ?> >
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="On Hold">On Hold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3" style="display: none;">
                            <div class="mb-3">
                                <label for="cv_status" class="form-label">CV Status</label>
                                <select class="form-select" id="cv_status" name="cv_status" <?= $isStatusLocked ? 'disabled' : '' ?> >
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3" style="display: none;">
                            <div class="mb-3">
                                <label for="cv_checked" class="form-label">CV Checked</label>
                                <select class="form-select" id="cv_checked" name="cv_checked" <?= $isStatusLocked ? 'disabled' : '' ?> >
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Checked">Checked</option>
                                    <option value="Unchecked">Unchecked</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3" style="display: none;">
                            <div class="mb-3">
                                <label for="return_reason" class="form-label">Reason of Return</label>
                                <input type="text" class="form-control" id="return_reason" name="return_reason" 
                                       placeholder="Enter return reason (max 120 characters)" maxlength="120">
                                <small class="form-text text-muted">Limit to 120 characters</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Account Distribution Table -->
                    <h6 class="mb-3">Account Distribution</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="accountTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Account Title</th>
                                    <th width="15%">Project</th>
                                    <th width="15%">Department</th>
                                    <th width="20%">Subsidiary Account</th>
                                    <th width="12%">Debit</th>
                                    <th width="12%">Credit</th>
                                    <th width="3%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="accountTableBody">
                                <!-- Account rows will be added here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>
                                            <span id="balanceLabel" class="text-danger">UNBALANCED</span>:
                                        </strong>
                                    </td>
                                    <td class="text-end"><strong id="totalDebit">₱0.00</strong></td>
                                    <td class="text-end"><strong id="totalCredit">₱0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-primary" onclick="addAccountRow()" id="addAccountBtn">
                            <i class="bi bi-plus-circle me-2"></i>Add Account Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div>
        <!-- Recent Transactions -->
        <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Cash Disbursements</h6>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3 g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="cdFilterDate" class="form-label">Date Range</label>
                            <input type="date" class="form-control" id="cdFilterDate" placeholder="Filter by date">
                        </div>
                        <div class="col-md-3">
                            <label for="cdFilterReference" class="form-label">Voucher Number</label>
                            <input type="text" class="form-control" id="cdFilterReference" placeholder="Filter by voucher number">
                        </div>
                        <div class="col-md-3">
                            <label for="cdFilterPaymentForm" class="form-label">Payment Form</label>
                            <select class="form-select" id="cdFilterPaymentForm">
                                <option value="">All Payment Forms</option>
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit_card">Credit Card</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex justify-content-start">
                            <button type="button" class="btn btn-outline-secondary mt-3 mt-md-0" onclick="clearCDFilters()">
                                <i class="bi bi-x-circle me-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="cdTransactionsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Cash Voucher Number</th>
                                    <th>Total Amount</th>
                                    <th>Particulars</th>
                                    <th>Payee</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="recentTransactions"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Selection Modal -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="accountSearch" placeholder="Search accounts...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="accountSelectionTable">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Title</th>
                                <th>Account Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="accountSelectionBody">
                            <!-- Account options will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionDetails">
                    <!-- Transaction details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCDTransaction()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
let currentRowIndex = 0;
let selectedAccountData = null;

// Initialize the page
$(document).ready(function() {
    addAccountRow();

    // Initialize table first, then load data to avoid race conditions
    const table = $('#cdTransactionsTable');
    if (table.length > 0) {
        initializeCDTransactionsTable();
    }
    loadRecentTransactions();

    // Auto-save form data
    setInterval(autoSaveForm, 30000); // Auto-save every 30 seconds

    // Initialize filters
    initializeCDFilters();
});

function addAccountRow() {
    const tbody = document.getElementById('accountTableBody');
    const row = document.createElement('tr');
    row.id = `accountRow_${currentRowIndex}`;

    row.innerHTML = `
        <td>
            <select class="form-select account-select" name="accounts[${currentRowIndex}][account_id]" required onchange="calculateTotals()">
                <option value="">Select Account</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= $account['id'] ?>">
                        <?= htmlspecialchars((string)$account['account_code'] . ' - ' . (string)$account['account_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="form-select project-select" name="accounts[${currentRowIndex}][project_id]">
                <option value="">Select Project</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>">
                        <?= htmlspecialchars((string)$project['project_code'] . ' - ' . (string)$project['project_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="form-select department-select" name="accounts[${currentRowIndex}][department_id]">
                <option value="">Select Department</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= $department['id'] ?>">
                        <?= htmlspecialchars((string)$department['department_code'] . ' - ' . (string)$department['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select class="form-select subsidiary-select" name="accounts[${currentRowIndex}][subsidiary_id]">
                <option value="">No Subsidiary</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>">
                        <?= htmlspecialchars((string)$supplier['supplier_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="text" class="form-control debit-amount" name="accounts[${currentRowIndex}][debit]" placeholder="0.00" onchange="calculateTotals()" onblur="validateAmount(this)" onkeypress="validateNumericInput(event)">
        </td>
        <td>
            <input type="text" class="form-control credit-amount" name="accounts[${currentRowIndex}][credit]" placeholder="0.00" onchange="calculateTotals()" onblur="validateAmount(this)" onkeypress="validateNumericInput(event)">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAccountRow(${currentRowIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);
    currentRowIndex++;
}

function removeAccountRow(index) {
    const row = document.getElementById(`accountRow_${index}`);
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function openAccountModal(rowIndex) {
    selectedAccountData = { rowIndex: rowIndex };
    loadAccounts();
    $('#accountModal').modal('show');
}

function loadAccounts() {
    $.ajax({
        url: APP_URL + '/api/accounts/list',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const tbody = document.getElementById('accountSelectionBody');
                tbody.innerHTML = '';
                
                response.accounts.forEach(account => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${account.account_code}</td>
                        <td>${account.account_title}</td>
                        <td>${account.account_type}</td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" onclick="selectAccount('${account.id}', '${account.account_code}', '${account.account_title}')">
                                Select
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        },
        error: function() {
            EARS.showAlert('Failed to load accounts', 'danger');
        }
    });
}

function selectAccount(accountId, accountCode, accountTitle) {
    if (selectedAccountData) {
        const row = document.getElementById(`accountRow_${selectedAccountData.rowIndex}`);
        row.querySelector('.account-id').value = accountId;
        row.querySelector('.account-code').value = accountCode;
        row.querySelector('.account-title').value = accountTitle;
    }
    $('#accountModal').modal('hide');
}

function openSubsidiaryModal(rowIndex) {
    // Similar to account modal but for subsidiaries
    // This would load subsidiary accounts
    EARS.showAlert('Subsidiary selection feature coming soon', 'info');
}

function calculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;
    
    document.querySelectorAll('#accountTableBody tr').forEach(row => {
        const debit = parseFloat(row.querySelector('.debit-amount')?.value || 0) || 0;
        const credit = parseFloat(row.querySelector('.credit-amount')?.value || 0) || 0;
        totalDebit += debit;
        totalCredit += credit;
    });

    // Fix floating point precision issues by rounding to 2 decimal places
    totalDebit = Math.round(totalDebit * 100) / 100;
    totalCredit = Math.round(totalCredit * 100) / 100;

    // Update totals
    document.getElementById('totalDebit').textContent = '₱' + totalDebit.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('totalCredit').textContent = '₱' + totalCredit.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // Update balance label
    const difference = Math.abs(totalDebit - totalCredit);
    const balanceLabel = document.getElementById('balanceLabel');
    if (difference <= 0.001) {
        balanceLabel.classList.remove('text-danger');
        balanceLabel.classList.add('text-success');
        balanceLabel.textContent = 'BALANCED';
    } else {
        balanceLabel.classList.remove('text-success');
        balanceLabel.classList.add('text-danger');
        balanceLabel.textContent = 'UNBALANCED';
    }
}

function validateAmount(input) {
    // Ensure only 2 decimal places and proper rounding
    let value = parseFloat(input.value) || 0;
    value = Math.round(value * 100) / 100; // Round to 2 decimal places
    input.value = value.toFixed(2);
    calculateTotals();
}

function validateNumericInput(event) {
    const char = String.fromCharCode(event.which);
    const input = event.target;
    const value = input.value;
    
    // Allow: backspace, delete, tab, escape, enter, and navigation keys
    if (event.keyCode === 8 || event.keyCode === 9 || event.keyCode === 27 || event.keyCode === 13 || 
        (event.keyCode >= 35 && event.keyCode <= 40)) {
        return;
    }
    
    // Allow numbers
    if (/\d/.test(char)) {
        return;
    }
    
    // Allow decimal point only if it's not already present
    if (char === '.' && value.indexOf('.') === -1) {
        return;
    }
    
    // Prevent any other characters
    event.preventDefault();
}

function checkBalance() {
    calculateTotals();
    const totalDebit = parseFloat((document.getElementById('totalDebit').textContent || '0').replace(/[^0-9.]/g, '')) || 0;
    const totalCredit = parseFloat((document.getElementById('totalCredit').textContent || '0').replace(/[^0-9.]/g, '')) || 0;
    
    // Fix floating point precision issues
    const roundedDebit = Math.round(totalDebit * 100) / 100;
    const roundedCredit = Math.round(totalCredit * 100) / 100;
    const difference = Math.abs(roundedDebit - roundedCredit);
    
    if (difference < 0.01) {
        EARS.showAlert('✅ Transaction is balanced!', 'success', '#globalAlertContainer');
    } else {
        EARS.showAlert('❌ Transaction is not balanced. Difference: ₱' + difference.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }), 'danger', '#globalAlertContainer');
    }
}

function saveTransaction() {
    const form = document.getElementById('transactionForm');
    const formData = new FormData(form);
    
    // Validate required fields
    if (!formData.get('voucher_number') || !formData.get('payee_name') || !formData.get('particulars')) {
        EARS.showAlert('Please fill in all required fields', 'danger');
        return;
    }
    
    // Check if at least one account line is filled
    let hasAccountData = false;
    document.querySelectorAll('#accountTableBody tr').forEach(row => {
        const accountId = row.querySelector('.account-select')?.value || '';
        const debit = parseFloat(row.querySelector('.debit-amount')?.value || 0) || 0;
        const credit = parseFloat(row.querySelector('.credit-amount')?.value || 0) || 0;
        if (accountId && (debit > 0 || credit > 0)) {
            hasAccountData = true;
        }
    });
    
    if (!hasAccountData) {
        EARS.showAlert('Please add at least one account line with amounts', 'danger');
        return;
    }
    
    // Check balance
    calculateTotals();
    const totalDebit = parseFloat(document.getElementById('totalDebit').textContent.replace('₱', '')) || 0;
    const totalCredit = parseFloat(document.getElementById('totalCredit').textContent.replace('₱', '')) || 0;
    
    // Fix floating point precision issues
    const roundedDebit = Math.round(totalDebit * 100) / 100;
    const roundedCredit = Math.round(totalCredit * 100) / 100;
    
    if (Math.abs(roundedDebit - roundedCredit) > 0.001) {
        EARS.showAlert('Transaction is not balanced. Please check your entries.', 'warning');
        return;
    }
    
    // Show loading
    const saveBtn = document.querySelector('button[onclick="saveTransaction()"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    // Prepare data
    const data = {
        voucher_number: formData.get('voucher_number'),
        transaction_date: formData.get('transaction_date'),
        check_number: formData.get('check_number'),
        check_date: formData.get('check_date'),
        payee_name: formData.get('payee_name'),
        po_number: formData.get('po_number'),
        cwo_number: formData.get('cwo_number'),
        ebr_number: formData.get('ebr_number'),
        particulars: formData.get('particulars'),
        cv_status: formData.get('cv_status'),
        cv_checked: formData.get('cv_checked'),
        check_payment_status: formData.get('check_payment_status'),
        return_reason: formData.get('return_reason'),
        account_distribution: []
    };
    
    // Collect account data
    document.querySelectorAll('#accountTableBody tr').forEach(row => {
        const accountId = row.querySelector('.account-select')?.value;
        const projectId = row.querySelector('.project-select')?.value;
        const departmentId = row.querySelector('.department-select')?.value;
        const subsidiaryId = row.querySelector('.subsidiary-select')?.value;
        const debit = parseFloat(row.querySelector('.debit-amount').value) || 0;
        const credit = parseFloat(row.querySelector('.credit-amount').value) || 0;

        if (accountId && (debit > 0 || credit > 0)) {
            data.account_distribution.push({
                account_id: parseInt(accountId),
                project_id: projectId ? parseInt(projectId) : null,
                department_id: departmentId ? parseInt(departmentId) : null,
                subsidiary_id: subsidiaryId ? parseInt(subsidiaryId) : null,
                debit: debit,
                credit: credit
            });
        }
    });
    
    // Save transaction
    $.ajax({
        url: APP_URL + '/api/cash-disbursement/save',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Cash disbursement saved successfully!', 'success');
                // Reset form
                form.reset();
                document.getElementById('accountTableBody').innerHTML = '';
                addAccountRow();
                calculateTotals();
                loadRecentTransactions();
                
                // Update status (only if elements exist)
                const statusEl = document.getElementById('transactionStatus');
                if (statusEl) {
                    statusEl.textContent = 'New';
                }
                const lastModifiedEl = document.getElementById('lastModified');
                if (lastModifiedEl) {
                    lastModifiedEl.textContent = new Date().toLocaleString();
                }
                const createdByEl = document.getElementById('createdBy');
                if (createdByEl) {
                    createdByEl.textContent = 'Current User';
                }
            } else {
                EARS.showAlert(response.error || 'Failed to save transaction', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save transaction', 'danger');
        },
        complete: function() {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    });
}

function loadRecentTransactions() {
    $.ajax({
        url: APP_URL + '/api/cash-disbursement/recent',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
            } catch (e) {}

            if (response && (response.success === true || response.success === 'true') && (response.data || response.transactions)) {
                const items = (response.data || response.transactions || []).filter(Boolean);
                const tbody = document.getElementById('recentTransactions');
                if (!tbody) return;

                // Always rebuild tbody from API data
                let html = '';
                items.forEach(t => {
                    const amt = (t.amount != null ? t.amount : (t.total_amount != null ? t.total_amount : 0));
                    const dateStr = t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }) : '-';
                    html += `
                        <tr>
                            <td>${dateStr}</td>
                            <td><span class="badge bg-primary">${t.reference_no || t.voucher_number || '-'}</span></td>
                            <td class="text-end">₱${parseFloat(amt || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
                            <td>${t.particulars || t.description || '-'}</td>
                            <td>${t.payee_name || '-'}</td>
                            <td><span class="badge bg-success">Posted</span></td>
                            <td>
                              <button type="button" class="btn btn-sm btn-outline-primary cd-view-transaction-btn" data-transaction-id="${t.id}"><i class="bi bi-eye"></i> View</button>
                            </td>
                        </tr>`;
                });
                tbody.innerHTML = html || `<tr><td colspan="7" class="text-center text-muted">No recent cash disbursements</td></tr>`;

                // Also feed rows directly to DataTable to guarantee visibility
                const rowsForDT = items.map(t => {
                    const amt = (t.amount != null ? t.amount : (t.total_amount != null ? t.total_amount : 0));
                    const dateStr = t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }) : '-';
                    return [
                        dateStr,
                        `<span class="badge bg-primary">${t.reference_no || t.voucher_number || '-'}</span>`,
                        `₱${parseFloat(amt || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}`,
                        (t.particulars || t.description || '-'),
                        (t.payee_name || '-'),
                        '<span class="badge bg-success">Posted</span>',
                        `<button type="button" class="btn btn-sm btn-outline-primary cd-view-transaction-btn" data-transaction-id="${t.id}"><i class="bi bi-eye"></i> View</button>`
                    ];
                });

                if ($.fn.DataTable) {
                    let dt;
                    if ($.fn.DataTable.isDataTable('#cdTransactionsTable')) {
                        dt = $('#cdTransactionsTable').DataTable();
                        dt.clear();
                        if (rowsForDT.length) dt.rows.add(rowsForDT);
                        dt.draw();
                    } else {
                        initializeCDTransactionsTable();
                        dt = $('#cdTransactionsTable').DataTable();
                        if (rowsForDT.length) dt.rows.add(rowsForDT);
                        dt.draw();
                    }
                }
                
                console.log('Recent disbursements loaded:', items.length);
                
                // Delegate click for dynamically added View buttons
                $('#cdTransactionsTable tbody').off('click', '.cd-view-transaction-btn').on('click', '.cd-view-transaction-btn', function() {
                    const transactionId = $(this).data('transaction-id');
                    viewCDTransactionDetails(transactionId);
                });
            } else {
                console.log('Recent disbursements: unexpected response', response);
            }
        }
    });
}

// DataTable and Filtering Functions (Cash Disbursement)
let cdTransactionsTable;

function initializeCDTransactionsTable() {
    const table = $('#cdTransactionsTable');
    if (table.length === 0) return;
    if ($.fn.DataTable.isDataTable('#cdTransactionsTable')) {
        try { $('#cdTransactionsTable').DataTable().destroy(); } catch (e) {}
    }
    // Clear any global DataTables search filters that may be lingering from other pages
    if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.search) {
        $.fn.dataTable.ext.search = [];
    }
    cdTransactionsTable = $('#cdTransactionsTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: 2, type: 'num-fmt', className: 'text-end' },
            { targets: -1, orderable: false, searchable: false }
        ],
        // Use standard layout to avoid DOM rendering issues
        dom: 'lfrtip'
    });
}

function initializeCDFilters() {
    $('#cdFilterDate').on('change', function() { filterCDTransactions(); });
    $('#cdFilterReference').on('keyup', function() { filterCDTransactions(); });
    $('#cdFilterPaymentForm').on('change', function() { filterCDTransactions(); });
}

function filterCDTransactions() {
    const dateFilter = $('#cdFilterDate').val();
    const refFilter = ($('#cdFilterReference').val() || '').toLowerCase();
    const pfFilter = ($('#cdFilterPaymentForm').val() || '').toLowerCase();

    // Remove existing custom filtering
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search || [];
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(() => false);

    $.fn.dataTable.ext.search.push(function(settings, data) {
        if (settings.nTable.id !== 'cdTransactionsTable') return true;
        const rowDate = data[0];
        const rowRef = (data[1] || '').toLowerCase();
        const rowPF = (data[4] || '').toLowerCase();

        if (dateFilter) {
            const rowDateObj = new Date(rowDate);
            const filterDateObj = new Date(dateFilter);
            if (rowDateObj.toDateString() !== filterDateObj.toDateString()) return false;
        }
        if (refFilter && !rowRef.includes(refFilter)) return false;
        if (pfFilter && rowPF !== pfFilter) return false;
        return true;
    });
    if (cdTransactionsTable) cdTransactionsTable.draw();
}

function clearCDFilters() {
    $('#cdFilterDate').val('');
    $('#cdFilterReference').val('');
    $('#cdFilterPaymentForm').val('');
    // Clear search filters
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search || [];
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(() => false);
    if (cdTransactionsTable) { cdTransactionsTable.search(''); cdTransactionsTable.draw(); }
}

// View transaction details for Cash Disbursement
function viewCDTransactionDetails(transactionId) {
    // Show loading in modal
    $('#transactionDetails').html(`
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading transaction details...</p>
        </div>
    `);
    $('#transactionModal').modal('show');

    $.ajax({
        url: APP_URL + '/api/cash-disbursement/get/' + transactionId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                displayCDTransactionDetails(response.data);
            } else {
                $('#transactionDetails').html('<div class="alert alert-danger">Failed to load transaction details.</div>');
            }
        },
        error: function() {
            $('#transactionDetails').html('<div class="alert alert-danger">Failed to load transaction details.</div>');
        }
    });
}

// Render a full details view similar to cash receipt, with distribution breakdown
function displayCDTransactionDetails(transaction) {
    const modalContent = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Transaction Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Cash Voucher Number:</strong></td>
                        <td><span class="badge bg-primary">${transaction.reference_no || '-'}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>${transaction.transaction_date ? new Date(transaction.transaction_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }) : '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Form:</strong></td>
                        <td>${transaction.payment_form || 'cash'}</td>
                    </tr>
                    <tr>
                        <td><strong>Check Number:</strong></td>
                        <td>${transaction.check_number || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Bank:</strong></td>
                        <td>${transaction.bank || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Billing Number:</strong></td>
                        <td>${transaction.billing_number || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Payee:</strong></td>
                        <td>${transaction.payee_name || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td>${transaction.description || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Amount:</strong></td>
                        <td class="fw-bold">₱${parseFloat(transaction.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Account Distribution</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th>Project</th>
                                <th>Department</th>
                                <th>Subsidiary</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${generateCDAccountRows(transaction.child_transactions || [])}
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>₱${calculateCDTotalDebit(transaction.child_transactions || []).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
                                <td class="text-end"><strong>₱${calculateCDTotalCredit(transaction.child_transactions || []).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>`;

    $('#transactionDetails').html(modalContent);
}

function generateCDAccountRows(childTransactions) {
    if (!childTransactions || childTransactions.length === 0) {
        return `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="bi bi-info-circle me-2"></i>
                    This transaction was created before the enhanced account distribution system was implemented.
                    <br><small class="text-muted">New transactions will show detailed account distribution.</small>
                </td>
            </tr>
        `;
    }

    return childTransactions.map(t => {
        let type = t.transaction_type;
        if (!type || type === '') {
            if (t.reference_no && t.reference_no.includes('-D')) type = 'debit';
            else if (t.reference_no && t.reference_no.includes('-C')) type = 'credit';
        }
        const debitCol = type === 'debit' ? '₱' + parseFloat(t.amount).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-';
        const creditCol = type === 'credit' ? '₱' + parseFloat(t.amount).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-';
        return `
            <tr>
                <td>${t.account_name || 'N/A'}</td>
                <td>${t.project_name || '-'}</td>
                <td>${t.department_name || '-'}</td>
                <td>${t.supplier_name || '-'}</td>
                <td class="text-end">${debitCol}</td>
                <td class="text-end">${creditCol}</td>
            </tr>
        `;
    }).join('');
}

function calculateCDTotalDebit(childTransactions) {
    return (childTransactions || [])
        .filter(t => {
            let type = t.transaction_type;
            if (!type || type === '') {
                if (t.reference_no && t.reference_no.includes('-D')) type = 'debit';
            }
            return type === 'debit';
        })
        .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
}

function calculateCDTotalCredit(childTransactions) {
    return (childTransactions || [])
        .filter(t => {
            let type = t.transaction_type;
            if (!type || type === '') {
                if (t.reference_no && t.reference_no.includes('-C')) type = 'credit';
            }
            return type === 'credit';
        })
        .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
}

// Print the currently displayed cash disbursement details
function printCDTransaction() {
    const printWindow = window.open('', '_blank');
    const modalContent = document.getElementById('transactionDetails')?.innerHTML || '';
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cash Disbursement Details</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .text-end { text-align: right; }
                .fw-bold { font-weight: bold; }
                .badge { background-color: #0d6efd; color: white; padding: 4px 8px; border-radius: 4px; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <h2>Cash Disbursement Details</h2>
            ${modalContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function autoSaveForm() {
    // Auto-save form data to localStorage
    const formData = new FormData(document.getElementById('transactionForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Save account table data (new selectors)
    data.account_distribution = [];
    document.querySelectorAll('#accountTableBody tr').forEach(row => {
        const accountId = row.querySelector('.account-select')?.value || '';
        const projectId = row.querySelector('.project-select')?.value || '';
        const departmentId = row.querySelector('.department-select')?.value || '';
        const subsidiaryId = row.querySelector('.subsidiary-select')?.value || '';
        const debit = row.querySelector('.debit-amount')?.value || '';
        const credit = row.querySelector('.credit-amount')?.value || '';
        
        if (accountId || projectId || departmentId || subsidiaryId || debit || credit) {
            data.account_distribution.push({
                account_id: accountId,
                project_id: projectId,
                department_id: departmentId,
                subsidiary_id: subsidiaryId,
                debit: debit,
                credit: credit
            });
        }
    });
    
    localStorage.setItem('cash_disbursement_draft', JSON.stringify(data));
}

// Load draft data on page load
$(document).ready(function() {
    const draft = localStorage.getItem('cash_disbursement_draft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            
            // Fill form fields
            Object.keys(data).forEach(key => {
                if (key !== 'accounts' && key !== 'account_distribution') {
                    const field = document.getElementById(key);
                    if (field) {
                        field.value = data[key];
                    }
                }
            });
            
            // Fill account table (supports both old 'accounts' and new 'account_distribution')
            const rows = (data.account_distribution && data.account_distribution.length > 0) ? data.account_distribution : (data.accounts || []);
            if (rows.length > 0) {
                document.getElementById('accountTableBody').innerHTML = '';
                rows.forEach((account, index) => {
                    addAccountRow();
                    const row = document.getElementById(`accountRow_${index}`);
                    if (row) {
                        const accSel = row.querySelector('.account-select'); if (accSel) accSel.value = account.account_id || '';
                        const projSel = row.querySelector('.project-select'); if (projSel) projSel.value = account.project_id || account.project || '';
                        const deptSel = row.querySelector('.department-select'); if (deptSel) deptSel.value = account.department_id || account.department || '';
                        const subSel = row.querySelector('.subsidiary-select'); if (subSel) subSel.value = account.subsidiary_id || '';
                        row.querySelector('.debit-amount').value = account.debit || '';
                        row.querySelector('.credit-amount').value = account.credit || '';
                    }
                });
                currentRowIndex = rows.length;
            }
            
            calculateTotals();
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
});

// Clear draft when form is successfully saved
function clearDraft() {
    localStorage.removeItem('cash_disbursement_draft');
}
</script> 