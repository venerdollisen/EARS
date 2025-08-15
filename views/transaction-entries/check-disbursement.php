<?php $isStatusLocked = isset($user['role']) && in_array($user['role'], ['user','assistant']); ?>
<?php
// Check Disbursement Entry View (modernized like cash disbursement)
?>

    <div class="row">
        <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Check Disbursement Entry</h1>
            <div>
                <button type="button" class="btn btn-success" onclick="saveTransaction()">
                    <i class="bi bi-save me-2"></i>Save Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow">
                <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Check Disbursement Details</h6>
                </div>
                <div class="card-body">
                <form id="transactionForm" data-autosave="false">
                    <input type="hidden" name="transaction_type" value="check_disbursement">

                        <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="voucher_number" class="form-label">Check Voucher Number *</label>
                                <input type="text" class="form-control" id="voucher_number" name="voucher_number"
                                       value="CV-<?php echo date('Ymd'); ?>-<?php echo str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT); ?>" required>
                            </div>
                                </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="transaction_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date"
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_number" class="form-label">Check Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number" placeholder="Enter check number">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                            <div class="mb-3">
                                <label for="check_date" class="form-label">Check Date</label>
                                <input type="date" class="form-control" id="check_date" name="check_date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="mb-3">
                                <label for="payee_name" class="form-label">Payee Name *</label>
                                <input type="text" class="form-control" id="payee_name" name="payee_name" placeholder="Enter payee name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bank" class="form-label">Bank</label>
                                <input type="text" class="form-control" id="bank" name="bank" placeholder="Enter bank name">
                            </div>
                        </div>
                    </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                <label for="particulars" class="form-label">Particulars *</label>
                                <textarea class="form-control" id="particulars" name="particulars" rows="3" placeholder="Enter transaction particulars..." required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="check_payment_status" class="form-label">Check Payment Status</label>
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
                            <div class="col-md-4" style="display: none;">
                            <div class="mb-3">
                                <label for="cv_status" class="form-label">CV Status</label>
                                <select class="form-select" id="cv_status" name="cv_status" <?= $isStatusLocked ? 'disabled' : '' ?> >
                                        <option value="Pending" selected>Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4" style="display: none;">
                            <div class="mb-3">
                                <label for="cv_checked" class="form-label">CV Checked</label>
                                <select class="form-select" id="cv_checked" name="cv_checked" <?= $isStatusLocked ? 'disabled' : '' ?> >
                                        <option value="Pending" selected>Pending</option>
                                        <option value="Checked">Checked</option>
                                        <option value="Unchecked">Unchecked</option>
                                    </select>
                                </div>
                            </div>
                            
                        </div>

                    <hr>

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
                            <tbody id="accountTableBody"></tbody>
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

<!-- Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
            <div class="modal-body">
                <div id="transactionDetails"></div>
                        </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCDTransaction()"><i class="bi bi-printer me-2"></i>Print</button>
                </div>
            </div>
        </div>
    </div>

<!-- Recent -->
    <div class="row mt-4">
        <div class="col-12">
        <div class="card shadow">
                <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Recent Check Disbursements</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-hover" id="chkTransactionsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                <th>Check Voucher Number</th>
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

<script>
let currentRowIndex = 0;

$(document).ready(function(){
    addAccountRow();
    initializeTable();
    loadRecentTransactions();
});

function initializeTable(){
    if ($.fn.DataTable.isDataTable('#chkTransactionsTable')){
        try { $('#chkTransactionsTable').DataTable().destroy(); } catch(e){}
    }
    $('#chkTransactionsTable').DataTable({
        pageLength: 10,
        order: [[0,'desc']],
        columnDefs: [
            { targets: 2, type: 'num-fmt', className: 'text-end' },
            { targets: -1, orderable: false, searchable: false }
        ],
        dom: 'lfrtip'
    });
}

function addAccountRow(){
    const tbody = document.getElementById('accountTableBody');
    const row = document.createElement('tr');
    row.id = `accountRow_${currentRowIndex}`;
    row.innerHTML = `
        <td>
            <select class="form-select account-select" name="accounts[${currentRowIndex}][account_id]" required onchange="calculateTotals()">
                    <option value="">Select Account</option>
                    <?php foreach ($accounts as $account): ?>
                    <option value="<?= $account['id'] ?>"><?= htmlspecialchars((string)$account['account_code'] . ' - ' . (string)$account['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
            <select class="form-select project-select" name="accounts[${currentRowIndex}][project_id]">
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars((string)$project['project_code'] . ' - ' . (string)$project['project_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
            <select class="form-select department-select" name="accounts[${currentRowIndex}][department_id]">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                    <option value="<?= $department['id'] ?>"><?= htmlspecialchars((string)$department['department_code'] . ' - ' . (string)$department['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
            <select class="form-select subsidiary-select" name="accounts[${currentRowIndex}][subsidiary_id]">
                <option value="">No Subsidiary</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars((string)$supplier['supplier_name']) ?></option>
                <?php endforeach; ?>
            </select>
            </td>
        <td><input type="text" class="form-control debit-amount" name="accounts[${currentRowIndex}][debit]" placeholder="0.00" onchange="calculateTotals()" onblur="validateAmount(this)" onkeypress="validateNumericInput(event)"></td>
        <td><input type="text" class="form-control credit-amount" name="accounts[${currentRowIndex}][credit]" placeholder="0.00" onchange="calculateTotals()" onblur="validateAmount(this)" onkeypress="validateNumericInput(event)"></td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAccountRow(${currentRowIndex})"><i class="bi bi-trash"></i></button>
        </td>`;
    tbody.appendChild(row);
    currentRowIndex++;
}

function removeAccountRow(idx){
    const row = document.getElementById(`accountRow_${idx}`);
    if (row){ row.remove(); calculateTotals(); }
}

function calculateTotals(){
    let totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('#accountTableBody tr').forEach(r => {
        const d = parseFloat(r.querySelector('.debit-amount')?.value || 0) || 0;
        const c = parseFloat(r.querySelector('.credit-amount')?.value || 0) || 0;
        totalDebit += d; totalCredit += c;
    });
    
    // Fix floating point precision issues by rounding to 2 decimal places
    totalDebit = Math.round(totalDebit * 100) / 100;
    totalCredit = Math.round(totalCredit * 100) / 100;
    
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

function saveTransaction(){
    const form = document.getElementById('transactionForm');
    const fd = new FormData(form);
    if (!fd.get('voucher_number') || !fd.get('payee_name') || !fd.get('particulars')){
        EARS.showAlert('Please fill in all required fields', 'danger');
        return;
    }
    // Verify at least one line and balanced
    let hasLine=false; let totalD=0; let totalC=0;
    document.querySelectorAll('#accountTableBody tr').forEach(r => {
        const acc = r.querySelector('.account-select')?.value || '';
        const d = parseFloat(r.querySelector('.debit-amount')?.value || 0) || 0;
        const c = parseFloat(r.querySelector('.credit-amount')?.value || 0) || 0;
        if (acc && (d>0 || c>0)) hasLine=true;
        totalD+=d; totalC+=c;
    });
    if (!hasLine){ EARS.showAlert('Please add at least one account line with amounts', 'danger'); return; }
    
    // Fix floating point precision issues
    const roundedTotalD = Math.round(totalD * 100) / 100;
    const roundedTotalC = Math.round(totalC * 100) / 100;
    if (Math.abs(roundedTotalD-roundedTotalC) > 0.001){ EARS.showAlert('Transaction is not balanced.', 'warning'); return; }

    const btn = document.querySelector('button[onclick="saveTransaction()"]');
    const btnTxt = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    const data = {
        voucher_number: fd.get('voucher_number'),
        transaction_date: fd.get('transaction_date'),
        check_number: fd.get('check_number'),
        check_date: fd.get('check_date'),
        bank: fd.get('bank'),
        payee_name: fd.get('payee_name'),
        particulars: fd.get('particulars'),
        cv_status: fd.get('cv_status'),
        cv_checked: fd.get('cv_checked'),
        check_payment_status: fd.get('check_payment_status'),
        payment_form: 'check',
        account_distribution: []
    };
    document.querySelectorAll('#accountTableBody tr').forEach(r => {
        const accountId = r.querySelector('.account-select')?.value;
        const projectId = r.querySelector('.project-select')?.value;
        const departmentId = r.querySelector('.department-select')?.value;
        const subsidiaryId = r.querySelector('.subsidiary-select')?.value;
        const d = parseFloat(r.querySelector('.debit-amount')?.value || 0) || 0;
        const c = parseFloat(r.querySelector('.credit-amount')?.value || 0) || 0;
        if (accountId && (d>0 || c>0)){
            data.account_distribution.push({
                account_id: parseInt(accountId),
                project_id: projectId? parseInt(projectId): null,
                department_id: departmentId? parseInt(departmentId): null,
                subsidiary_id: subsidiaryId? parseInt(subsidiaryId): null,
                debit: d, credit: c
            });
        }
    });
    
    $.ajax({
        url: APP_URL + '/api/check-disbursement/save',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
        success: function(resp){
            if (resp && resp.success){
                EARS.showAlert('Check disbursement saved successfully!', 'success');
                form.reset(); document.getElementById('accountTableBody').innerHTML=''; currentRowIndex=0; addAccountRow(); calculateTotals();
                loadRecentTransactions(true);
            } else {
                EARS.showAlert(resp.error || 'Failed to save transaction', 'danger');
            }
        },
        error: function(xhr){
            const r = xhr.responseJSON; EARS.showAlert(r?.error || 'Failed to save transaction', 'danger');
        },
        complete: function(){ btn.disabled=false; btn.innerHTML = btnTxt; }
    });
}

function loadRecentTransactions(bust=false){
    const url = APP_URL + '/api/check-disbursement/recent' + (bust? ('?t='+Date.now()):'');
    $.ajax({ url, method:'GET', dataType:'json', cache:false, success: function(resp){
        if (!resp || !resp.success) return;
        const items = resp.data || resp.transactions || [];
        const tbody = document.getElementById('recentTransactions');
        let html='';
        items.forEach(t => {
            const amt = t.amount != null ? t.amount : (t.total_amount != null ? t.total_amount : 0);
            const dateStr = t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'2-digit'}) : '-';
            
            // Determine status badge class based on check_payment_status
            let statusClass = 'bg-secondary';
            let status = t.check_payment_status || 'Pending';
            
            switch (status) {
                case 'Approved':
                    statusClass = 'bg-success';
                    break;
                case 'Rejected':
                    statusClass = 'bg-danger';
                    break;
                case 'On Hold':
                    statusClass = 'bg-warning';
                    break;
                case 'Pending':
                default:
                    statusClass = 'bg-secondary';
                    break;
            }
            
            html += `<tr>
                <td>${dateStr}</td>
                <td><span class="badge bg-primary">${t.reference_no || '-'}</span></td>
                <td class="text-end">₱${parseFloat(amt||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
                <td>${t.particulars || t.description || '-'}</td>
                <td>${t.payee_name || '-'}</td>
                <td><span class="badge ${statusClass}">${status}</span></td>
                <td><button type="button" class="btn btn-sm btn-outline-primary" onclick="viewDetails(${t.id})"><i class="bi bi-eye"></i> View</button></td>
            </tr>`
        });
        tbody.innerHTML = html || `<tr><td colspan="7" class="text-center text-muted">No recent check disbursements</td></tr>`;
        // (re)init DT
        if ($.fn.DataTable.isDataTable('#chkTransactionsTable')){ 
            const dt = $('#chkTransactionsTable').DataTable(); 
            dt.clear(); 
            if(items.length){
                const rows = items.map(t=>{
                    // Determine status badge class based on check_payment_status
                    let statusClass = 'bg-secondary';
                    let status = t.check_payment_status || 'Pending';
                    
                    switch (status) {
                        case 'Approved':
                            statusClass = 'bg-success';
                            break;
                        case 'Rejected':
                            statusClass = 'bg-danger';
                            break;
                        case 'On Hold':
                            statusClass = 'bg-warning';
                            break;
                        case 'Pending':
                        default:
                            statusClass = 'bg-secondary';
                            break;
                    }
                    
                    return [ 
                        new Date(t.transaction_date).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'2-digit'}), 
                        `<span class="badge bg-primary">${t.reference_no||'-'}</span>`, 
                        `₱${parseFloat((t.amount!=null?t.amount:t.total_amount)||0).toLocaleString('en-PH',{minimumFractionDigits:2})}`, 
                        (t.particulars||t.description||'-'), 
                        (t.payee_name||'-'), 
                        `<span class="badge ${statusClass}">${status}</span>`, 
                        `<button type="button" class="btn btn-sm btn-outline-primary" onclick="viewDetails(${t.id})"><i class="bi bi-eye"></i> View</button>` 
                    ];
                }); 
                dt.rows.add(rows);
            } 
            dt.draw(); 
        }
    }});
}

function viewDetails(id){
    $.ajax({ url: APP_URL + '/api/check-disbursement/get/' + id, method:'GET', dataType:'json', success: function(resp){
        if (resp && resp.success){ renderDetails(resp.data); $('#transactionModal').modal('show'); }
    }});
}

function renderDetails(t){
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Transaction Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Voucher No.:</strong></td><td><span class="badge bg-primary">${t.reference_no}</span></td></tr>
                    <tr><td><strong>Date:</strong></td><td>${t.transaction_date ? new Date(t.transaction_date).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'2-digit'}) : '-'}</td></tr>
                    <tr><td><strong>Payee:</strong></td><td>${t.payee_name || '-'}</td></tr>
                    <tr><td><strong>Bank:</strong></td><td>${t.bank || '-'}</td></tr>
                    <tr><td><strong>Description:</strong></td><td>${t.description || '-'}</td></tr>
                    <tr><td><strong>Total Amount:</strong></td><td class="fw-bold">₱${parseFloat(t.amount||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Account Distribution</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light"><tr><th>Account</th><th>Project</th><th>Department</th><th>Subsidiary</th><th>Debit</th><th>Credit</th></tr></thead>
                        <tbody>${renderAcctRows(t.child_transactions||[])}</tbody>
                        <tfoot><tr class="table-info"><td colspan="4" class="text-end"><strong>TOTAL:</strong></td><td class="text-end"><strong>₱${sumDebit(t.child_transactions||[]).toLocaleString('en-PH',{minimumFractionDigits:2})}</strong></td><td class="text-end"><strong>₱${sumCredit(t.child_transactions||[]).toLocaleString('en-PH',{minimumFractionDigits:2})}</strong></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>`;
    $('#transactionDetails').html(html);
}

function renderAcctRows(children){
    if (!children || !children.length){
        return `<tr><td colspan="6" class="text-center text-muted">No account distribution found</td></tr>`;
    }
    return children.map(tr => {
        let type = tr.transaction_type; if (!type){ if (tr.reference_no?.includes('-D')) type='debit'; else if (tr.reference_no?.includes('-C')) type='credit'; }
        return `<tr>
            <td>${tr.account_name||'N/A'}</td>
            <td>${tr.project_name||'-'}</td>
            <td>${tr.department_name||'-'}</td>
            <td>${tr.supplier_name||'-'}</td>
            <td class="text-end">${type==='debit' ? '₱'+parseFloat(tr.amount).toLocaleString('en-PH',{minimumFractionDigits:2}) : '-'}</td>
            <td class="text-end">${type==='credit' ? '₱'+parseFloat(tr.amount).toLocaleString('en-PH',{minimumFractionDigits:2}) : '-'}</td>
        </tr>`;
    }).join('');
}

function sumDebit(ch){ return (ch||[]).filter(t=> (t.transaction_type||'')==='debit' || (t.reference_no||'').includes('-D')).reduce((s,t)=> s+parseFloat(t.amount||0),0); }
function sumCredit(ch){ return (ch||[]).filter(t=> (t.transaction_type||'')==='credit' || (t.reference_no||'').includes('-C')).reduce((s,t)=> s+parseFloat(t.amount||0),0); }

function printCDTransaction(){ const w = window.open('', '_blank'); const c = document.getElementById('transactionDetails')?.innerHTML || ''; w.document.write(`<!doctype html><html><head><title>Check Disbursement Details</title><style>body{font-family:Arial,sans-serif;margin:20px}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #ddd;padding:8px}th{background:#f2f2f2}.text-end{text-align:right}.fw-bold{font-weight:700}.badge{background:#0d6efd;color:#fff;padding:4px 8px;border-radius:4px}</style></head><body><h2>Check Disbursement Details</h2>${c}</body></html>`); w.document.close(); w.print(); }
</script> 