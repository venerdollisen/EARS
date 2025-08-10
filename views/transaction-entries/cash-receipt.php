<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Cash Receipt Entry</h1>
            <div>
                <button type="button" class="btn btn-success" onclick="saveTransaction()">
                    <i class="bi bi-save me-2"></i>Save Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Transaction Form -->
    <div class="col-lg-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Cash Receipt Details</h6>
            </div>
            <div class="card-body">
                <form id="transactionForm" data-autosave="false">
                    <input type="hidden" name="transaction_type" value="cash_receipt">
                    
                    <!-- Header Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Official Receipt Number *</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                       value="CR-<?= date('Ymd') ?>-<?= str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transaction_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                                <small class="form-text text-muted">Current date: <?= date('Y-m-d') ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_form" class="form-label">Payment in Form</label>
                                <select class="form-select" id="payment_form" name="payment_form">
                                    <option value="cash" selected>Cash</option>
                                    <option value="check">Check</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="check_number" class="form-label">Check Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number" 
                                       placeholder="Enter check number if applicable">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank" class="form-label">Bank</label>
                                <input type="text" class="form-control" id="bank" name="bank" 
                                       placeholder="Enter bank name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="billing_number" class="form-label">Billing Number</label>
                                <input type="text" class="form-control" id="billing_number" name="billing_number" 
                                       placeholder="Enter billing number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_for" class="form-label">Payment Description</label>
                        <textarea class="form-control" id="payment_for" name="payment_for" rows="2" 
                                  placeholder="Enter payment description..."></textarea>
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

<!-- Recent Transactions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Recent Cash Receipts</h6>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filterDate" class="form-label">Date Range</label>
                        <input type="date" class="form-control" id="filterDate" placeholder="Filter by date">
                    </div>
                    <div class="col-md-3">
                        <label for="filterReference" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="filterReference" placeholder="Filter by reference number">
                    </div>
                    <div class="col-md-3">
                        <label for="filterPaymentForm" class="form-label">Payment Form</label>
                        <select class="form-select" id="filterPaymentForm">
                            <option value="">All Payment Forms</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="bi bi-x-circle me-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Official Receipt Number</th>
                                <th>Payment Form</th>
                                <th>Total Amount</th>
                                <th>Payment Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?= htmlspecialchars($transaction['reference_no']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($transaction['payment_form'] ?? 'Cash') ?></td>
                                        <td class="text-end">₱<?= number_format($transaction['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($transaction['description'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-success">Posted</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-transaction-btn" 
                                                    data-transaction-id="<?= $transaction['id'] ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td class="text-center text-muted">No recent cash receipts</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalLabel">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionModalBody">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printTransaction()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
let accountRowCounter = 0;

$(document).ready(function() {
    $("#payment_form").val("cash");
    // Update summary when form fields change
    $('#reference_no, #transaction_date, #payment_form').on('input change', function() {
   
        updateSummary();
    });
    
    // Add initial account row

    addAccountRow();
    
    // Date field is already initialized with HTML5 date input
    // No additional datepicker needed
    
    // Initialize DataTable with a longer delay to ensure table is fully rendered
    setTimeout(function() {
        // Additional check to ensure table is ready
        const table = $('#transactionsTable');
        if (table.length > 0 && table.find('tbody tr').length > 0) {
            initializeTransactionsTable();
        } else {
            console.log('Table not ready, retrying in 500ms...');
            setTimeout(function() {
                initializeTransactionsTable();
            }, 500);
        }
    }, 1000);
    
    
    // Initialize filters
    initializeFilters();
    
    // Initial setup of view transaction buttons
    $('.view-transaction-btn').on('click', function() {
        const transactionId = $(this).data('transaction-id');
        viewTransactionDetails(transactionId);
    });
    
    // The Add Account Entry button uses inline onclick to avoid double-binding
    
    // Load recent transactions
    loadRecentTransactions();
});

function addAccountRow() {
    accountRowCounter++;
    const rowId = 'account_row_' + accountRowCounter;
    
    const row = `
        <tr id="${rowId}">
            <td>
                <select class="form-select account-select" name="accounts[${accountRowCounter}][account_id]" required onchange="updateTotals()">
                    <option value="">Select Account</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account['id'] ?>">
                            <?= htmlspecialchars((string)$account['account_code'] . ' - ' . (string)$account['account_name']) ?>
                            <?= $account['status'] !== 'active' ? ' (Inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select class="form-select project-select" name="accounts[${accountRowCounter}][project_id]">
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>">
                            <?= htmlspecialchars((string)$project['project_code'] . ' - ' . (string)$project['project_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select class="form-select department-select" name="accounts[${accountRowCounter}][department_id]">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>">
                            <?= htmlspecialchars((string)$department['department_code'] . ' - ' . (string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select class="form-select subsidiary-select" name="accounts[${accountRowCounter}][subsidiary_id]">
                    <option value="">No Subsidiary</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['id'] ?>">
                            <?= htmlspecialchars((string)$supplier['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control debit-amount" name="accounts[${accountRowCounter}][debit]" 
                       step="0.01" min="0" placeholder="0.00" onchange="updateTotals()">
            </td>
            <td>
                <input type="number" class="form-control credit-amount" name="accounts[${accountRowCounter}][credit]" 
                       step="0.01" min="0" placeholder="0.00" onchange="updateTotals()">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAccountRow('${rowId}')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#accountTableBody').append(row);
    updateTotals();
}

function removeAccountRow(rowId) {
    $('#' + rowId).remove();
    updateTotals();
}

function updateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;
    
    $('.debit-amount').each(function() {
        totalDebit += parseFloat($(this).val()) || 0;
    });
    
    $('.credit-amount').each(function() {
        totalCredit += parseFloat($(this).val()) || 0;
    });
    
    // Fix floating point precision issues by rounding to 2 decimal places
    totalDebit = Math.round(totalDebit * 100) / 100;
    totalCredit = Math.round(totalCredit * 100) / 100;
    
    $('#totalDebit').text('₱' + totalDebit.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
    
    $('#totalCredit').text('₱' + totalCredit.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
    
    $('#balanceDebit').text('₱' + totalDebit.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
    
    $('#balanceCredit').text('₱' + totalCredit.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
    
    // Check if balanced and update label color/text
    const difference = Math.abs(totalDebit - totalCredit);
    if (difference < 0.01) {
        $('#balanceLabel').removeClass('text-danger').addClass('text-success').text('BALANCED');
    } else {
        $('#balanceLabel').removeClass('text-success').addClass('text-danger').text('UNBALANCED');
    }
    
    updateSummary();
}

function updateSummary() {
    const reference = $('#reference_no').val() || '-';
    const dateValue = $('#transaction_date').val();
    console.log('Date value from field:', dateValue);
    const date = dateValue ? new Date(dateValue).toLocaleDateString('en-PH') : '-';
    console.log('Formatted date:', date);
    const paymentForm = $('#payment_form option:selected').text() || '-';
    const totalDebit = parseFloat($('#totalDebit').text().replace('₱', '').replace(',', '')) || 0;
    
    $('#summaryReference').text(reference);
    $('#summaryDate').text(date);
    $('#summaryPaymentForm').text(paymentForm);
    $('#summaryAmount').text('₱' + totalDebit.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
}

function checkBalance() {
    const totalDebit = parseFloat($('#totalDebit').text().replace('₱', '').replace(',', '')) || 0;
    const totalCredit = parseFloat($('#totalCredit').text().replace('₱', '').replace(',', '')) || 0;
    
    // Fix floating point precision issues
    const roundedDebit = Math.round(totalDebit * 100) / 100;
    const roundedCredit = Math.round(totalCredit * 100) / 100;
    const difference = Math.abs(roundedDebit - roundedCredit);
    
    if (difference < 0.01) {
        EARS.showAlert('✅ Transaction is balanced!', 'success', '#globalAlertContainer');
    } else {
        EARS.showAlert('❌ Transaction is not balanced. Difference: ₱' + difference.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }), 'danger', '#globalAlertContainer');
    }
}

function saveTransaction() {
    const form = $('#transactionForm');
    
    if (!EARS.validateForm('#transactionForm')) {
        EARS.showAlert('Please fill in all required fields.', 'warning', '#globalAlertContainer');
        return;
    }
    
    // Check if transaction is balanced
    const totalDebit = parseFloat($('#totalDebit').text().replace('₱', '').replace(',', '')) || 0;
    const totalCredit = parseFloat($('#totalCredit').text().replace('₱', '').replace(',', '')) || 0;
    
    // Fix floating point precision issues
    const roundedDebit = Math.round(totalDebit * 100) / 100;
    const roundedCredit = Math.round(totalCredit * 100) / 100;
    const difference = Math.abs(roundedDebit - roundedCredit);
    
    if (difference >= 0.01) {
        EARS.showAlert('Transaction must be balanced before saving. Difference: ₱' + difference.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }), 'danger', '#globalAlertContainer');
        return;
    }
    
    // Check if at least one account entry exists
    if ($('#accountTableBody tr').length === 0) {
        EARS.showAlert('Please add at least one account entry.', 'warning', '#globalAlertContainer');
        return;
    }
    
    // Collect form data
    const formData = {
        transaction_type: 'cash_receipt',
        reference_no: $('#reference_no').val(),
        transaction_date: $('#transaction_date').val(),
        amount: totalDebit,
        payment_form: $('#payment_form').val(),
        payment_description: $('#payment_for').val(),
        check_number: $('#check_number').val(),
        bank: $('#bank').val(),
        billing_number: $('#billing_number').val(),
        payment_for: $('#payment_for').val(),
        account_distribution: []
    };
    
    // Collect account entries
    $('#accountTableBody tr').each(function() {
        const accountId = $(this).find('.account-select').val();
        const projectId = $(this).find('.project-select').val();
        const departmentId = $(this).find('.department-select').val();
        const subsidiaryId = $(this).find('.subsidiary-select').val();
        const debit = parseFloat($(this).find('.debit-amount').val()) || 0;
        const credit = parseFloat($(this).find('.credit-amount').val()) || 0;
        
        // Only include entries that have an account selected and either debit or credit amount
        if (accountId && accountId !== '' && accountId !== 'undefined' && (debit > 0 || credit > 0)) {
            const accountEntry = {
                account_id: parseInt(accountId),
                project_id: projectId && projectId !== '' && projectId !== 'undefined' ? parseInt(projectId) : null,
                department_id: departmentId && departmentId !== '' && departmentId !== 'undefined' ? parseInt(departmentId) : null,
                subsidiary_id: subsidiaryId && subsidiaryId !== '' && subsidiaryId !== 'undefined' ? parseInt(subsidiaryId) : null,
                debit: debit,
                credit: credit
            };
            
            formData.account_distribution.push(accountEntry);
        } else {
            
        }
    });
    
    if (formData.account_distribution.length === 0) {
        EARS.showAlert('Please add at least one valid account entry.', 'warning', '#globalAlertContainer');
        return;
    }
    
    const saveBtn = $('button[onclick="saveTransaction()"]');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

    
    $.ajax({
        url: APP_URL + '/api/cash-receipt/save',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Cash receipt recorded successfully!', 'success', '#globalAlertContainer');
                form[0].reset();
                $('#reference_no').val('CR-<?= date('Ymd') ?>-<?= str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) ?>');
                $('#transaction_date').val('<?= date('Y-m-d') ?>');
                $('#accountTableBody').empty();
                addAccountRow();
                updateSummary();
                
                // Refresh recent transactions list without reloading the page
                // Add a tiny delay and cache-bust to avoid stale cached responses
                setTimeout(function(){ loadRecentTransactions(true); }, 150);
            } else {
                EARS.showAlert(response.message || response.error || 'Failed to record transaction', 'danger', '#globalAlertContainer');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to record transaction', 'danger', '#globalAlertContainer');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

function loadRecentTransactions(bustCache = false) {
    const apiUrl = APP_URL + '/api/cash-receipt/recent' + (bustCache ? ('?t=' + Date.now()) : '');
    $.ajax({
        url: apiUrl,
        method: 'GET',
        cache: false,
        headers: { 'Cache-Control': 'no-cache' },
        success: function(response) {
            if (response && response.success) {
                const transactions = response.data || response.transactions || [];
                const table = $('#transactionsTable');
                const isDT = $.fn.DataTable.isDataTable('#transactionsTable');

                // Build fresh tbody HTML
                let html = '';
                transactions.forEach(t => {
                    html += `
                        <tr>
                            <td>${new Date(t.transaction_date).toLocaleDateString('en-PH')}</td>
                            <td><span class="badge bg-success">${t.reference_no}</span></td>
                            <td>${t.payment_form || 'Cash'}</td>
                            <td class="text-end">₱${parseFloat(t.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                            <td>${t.description || '-'}</td>
                            <td><span class="badge bg-success">Posted</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary view-transaction-btn" data-transaction-id="${t.id}">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>`;
                });

                // Clear any active filters to ensure the new row is visible
                try {
                    $('#filterDate').val('');
                    $('#filterReference').val('');
                    $('#filterPaymentForm').val('');
                    if ($.fn.dataTable && $.fn.dataTable.ext) {
                        $.fn.dataTable.ext.search = [];
                    }
                } catch (e) {}

                // Destroy DT if present, replace tbody HTML, re-init DT, then draw
                if (isDT) {
                    try { table.DataTable().clear().destroy(); } catch (e) {}
                }
                const tbody = table.find('tbody');
                tbody.html(html || `<tr><td colspan="7" class="text-center text-muted">No recent cash receipts</td></tr>`);

                // Reinitialize DataTable to guarantee redraw and updated state
                initializeTransactionsTable();

                // Delegate click for dynamically added View buttons (safety)
                $('#transactionsTable tbody').off('click', '.view-transaction-btn').on('click', '.view-transaction-btn', function() {
                    const transactionId = $(this).data('transaction-id');
                    viewTransactionDetails(transactionId);
                });
            }
        },
        error: function() {
            // Silently fail for recent transactions
            console.log('Failed to load recent transactions');
        }
    });
}

// Auto-generate reference number
$('#reference_no').on('focus', function() {
    if (!$(this).val()) {
        $(this).val('CR-<?= date('Ymd') ?>-<?= str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) ?>');
        updateSummary();
    }
});

// DataTable and Filtering Functions
let transactionsTable;

function initializeTransactionsTable() {
    // Check if table exists
    const table = $('#transactionsTable');
    if (table.length === 0) {
        console.error('Transactions table not found');
        return;
    }

    // If DataTable is already initialized, destroy it to avoid duplicates
    if ($.fn.DataTable.isDataTable('#transactionsTable')) {
        try {
            $('#transactionsTable').DataTable().destroy();
        } catch (error) {
            console.warn('Error destroying existing DataTable:', error);
        }
    }
    
    // Initialize DataTable
    transactionsTable = $('#transactionsTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']], // Sort by date descending
        columnDefs: [
            { targets: 3, type: 'num-fmt', className: 'text-end' },
            { targets: -1, orderable: false, searchable: false }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        drawCallback: function() {
            // Re-attach click handlers after DataTable redraws
            $('.view-transaction-btn').off('click').on('click', function() {
                const transactionId = $(this).data('transaction-id');
                viewTransactionDetails(transactionId);
            });
        }
    });
}

function initializeFilters() {
    // Date filter
    $('#filterDate').on('change', function() {
        filterTransactions();
    });
    
    // Reference number filter
    $('#filterReference').on('keyup', function() {
        filterTransactions();
    });
    
    // Payment form filter
    $('#filterPaymentForm').on('change', function() {
        filterTransactions();
    });
}

function filterTransactions() {
    const dateFilter = $('#filterDate').val();
    const referenceFilter = $('#filterReference').val().toLowerCase();
    const paymentFormFilter = $('#filterPaymentForm').val();
    
    // Remove existing custom filtering
    $.fn.dataTable.ext.search.pop();
    
    // Custom filtering
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const rowDate = data[0]; // Date column (format: "Dec 04, 2025")
        const rowReference = data[1].toLowerCase(); // Reference column
        const rowPaymentForm = data[2].toLowerCase(); // Payment form column
        
        // Date filter - convert row date to YYYY-MM-DD format for comparison
        if (dateFilter) {
            const rowDateObj = new Date(rowDate);
            const filterDateObj = new Date(dateFilter);
            if (rowDateObj.toDateString() !== filterDateObj.toDateString()) {
                return false;
            }
        }
        
        // Reference number filter
        if (referenceFilter && !rowReference.includes(referenceFilter)) {
            return false;
        }
        
        // Payment form filter
        if (paymentFormFilter && rowPaymentForm !== paymentFormFilter.toLowerCase()) {
            return false;
        }
        
        return true;
    });
    
    transactionsTable.draw();
}

function clearFilters() {
    $('#filterDate').val('');
    $('#filterReference').val('');
    $('#filterPaymentForm').val('');
    
    // Remove all custom filtering
    while ($.fn.dataTable.ext.search.length > 0) {
        $.fn.dataTable.ext.search.pop();
    }
    transactionsTable.draw();
}

// Transaction Details Modal Functions
function viewTransactionDetails(transactionId) {
    // Show loading in modal
    $('#transactionModalBody').html(`
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading transaction details...</p>
        </div>
    `);
    
    $('#transactionModal').modal('show');
    
    // Fetch transaction details
    const apiUrl = APP_URL + '/api/cash-receipt/get/' + transactionId;
    
    $.ajax({
        url: apiUrl,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            
            if (response && response.success) {
                displayTransactionDetails(response.data);
            } else {
    
                const errorMessage = response && response.error ? response.error : 'Unknown error occurred';
                $('#transactionModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Failed to load transaction details: ${errorMessage}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            
            let errorMessage = 'Error loading transaction details. Please try again.';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response && response.error) {
                    errorMessage = response.error;
                }
            } catch (e) {
                console.error('Failed to parse error response:', e);
            }
            
            $('#transactionModalBody').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${errorMessage}
                </div>
            `);
        }
    });
}

function displayTransactionDetails(transaction) {
    // Check if this is an old transaction (no child transactions)
    const hasChildTransactions = transaction.child_transactions && transaction.child_transactions.length > 0;
    
    const modalContent = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Transaction Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Official Receipt Number:</strong></td>
                        <td><span class="badge bg-success">${transaction.reference_no}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>${new Date(transaction.transaction_date).toLocaleDateString('en-PH')}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Form:</strong></td>
                        <td>${transaction.payment_form || 'Cash'}</td>
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
                        <td><strong>Description:</strong></td>
                        <td>${transaction.description || '-'}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Amount:</strong></td>
                        <td class="fw-bold">₱${parseFloat(transaction.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    </tr>
                    ${!hasChildTransactions ? `
                    <tr>
                        <td><strong>Account:</strong></td>
                        <td>${transaction.account_name || 'N/A'}</td>
                    </tr>
                    ` : ''}
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
                            ${generateAccountRows(transaction.child_transactions || [])}
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                                <td class="text-end"><strong>₱${calculateTotalDebit(transaction.child_transactions || []).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
                                <td class="text-end"><strong>₱${calculateTotalCredit(transaction.child_transactions || []).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $('#transactionModalBody').html(modalContent);
}

function generateAccountRows(childTransactions) {
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
    
    return childTransactions.map(transaction => {
        // Determine transaction type from reference number if transaction_type is empty
        let transactionType = transaction.transaction_type;
        if (!transactionType || transactionType === '') {
            if (transaction.reference_no && transaction.reference_no.includes('-D')) {
                transactionType = 'debit';
            } else if (transaction.reference_no && transaction.reference_no.includes('-C')) {
                transactionType = 'credit';
            }
        }
        
        return `
            <tr>
                <td>${transaction.account_name || 'N/A'}</td>
                <td>${transaction.project_name || '-'}</td>
                <td>${transaction.department_name || '-'}</td>
                <td>${transaction.supplier_name || '-'}</td>
                <td class="text-end">${transactionType === 'debit' ? '₱' + parseFloat(transaction.amount).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
                <td class="text-end">${transactionType === 'credit' ? '₱' + parseFloat(transaction.amount).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
            </tr>
        `;
    }).join('');
}

function calculateTotalDebit(childTransactions) {
    return childTransactions
        .filter(t => {
            let transactionType = t.transaction_type;
            if (!transactionType || transactionType === '') {
                if (t.reference_no && t.reference_no.includes('-D')) {
                    transactionType = 'debit';
                }
            }
            return transactionType === 'debit';
        })
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
}

function calculateTotalCredit(childTransactions) {
    return childTransactions
        .filter(t => {
            let transactionType = t.transaction_type;
            if (!transactionType || transactionType === '') {
                if (t.reference_no && t.reference_no.includes('-C')) {
                    transactionType = 'credit';
                }
            }
            return transactionType === 'credit';
        })
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
}

function printTransaction() {
    const printWindow = window.open('', '_blank');
    const modalContent = $('#transactionModalBody').html();
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Transaction Details</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .text-end { text-align: right; }
                .fw-bold { font-weight: bold; }
                .badge { background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h2>Transaction Details</h2>
            ${modalContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Add custom styles for the account distribution table
$(document).ready(function() {
    // Add custom CSS for better table display
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            #accountTable {
                font-size: 0.9rem;
            }
            
            #accountTable th,
            #accountTable td {
                padding: 0.5rem;
                vertical-align: middle;
            }
            
            #accountTable .form-select,
            #accountTable .form-control {
                font-size: 0.85rem;
                padding: 0.375rem 0.5rem;
            }
            
            #accountTable .form-select option {
                font-size: 0.85rem;
            }
            
            @media (max-width: 1200px) {
                .table-responsive {
                    overflow-x: auto;
                }
                
                #accountTable {
                    min-width: 800px;
                }
            }
            
            #accountTable .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        `)
        .appendTo('head');
});
</script> 