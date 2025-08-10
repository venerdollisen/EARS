<?php
require_once BASE_PATH . '/models/JournalEntryModel.php';
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Journal Entry</h1>
                <p class="text-muted mb-0">Create a new manual journal entry</p>
            </div>
            <div class="text-end">
                <p class="text-muted mb-0">Today is <?= date('m/d/Y') ?></p>
                <a href="<?= APP_URL ?>/journal-entries" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Journal Entry Details</h6>
            </div>
            <div class="card-body">
                <form id="journalEntryForm">
                                         <!-- Header Information -->
                     <div class="row mb-3">
                         <div class="col-md-4">
                             <label for="reference_no" class="form-label">Journal Voucher Number</label>
                             <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                    value="<?= (new JournalEntryModel())->generateReferenceNo() ?>" readonly>
                         </div>
                         <div class="col-md-4">
                             <label for="transaction_date" class="form-label">Date</label>
                             <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                    value="<?= date('Y-m-d') ?>" required>
                         </div>
                         <div class="col-md-4">
                             <label for="jv_status" class="form-label">JV Status</label>
                             <select class="form-select" id="jv_status" name="jv_status" required>
                                 <option value="active">Active</option>
                                 <option value="inactive">Inactive</option>
                             </select>
                         </div>
                         <div class="col-md-4">
                             <label for="for_posting" class="form-label">For Posting</label>
                             <select class="form-select" id="for_posting" name="for_posting" required>
                                 <option value="for_checking">For Checking</option>
                                 <option value="for_posting">For Posting</option>
                             </select>
                         </div>
                         <div class="col-md-4">
                             <label for="reference_number" class="form-label">Reference Number</label>
                             <input type="text" class="form-control" id="reference_number" name="reference_number">
                         </div>
                         <div class="col-md-4">
                             <label for="bill_invoice_ref" class="form-label">Bill Invoice Ref. No.</label>
                             <input type="text" class="form-control" id="bill_invoice_ref" name="bill_invoice_ref">
                         </div>
                     </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="description" class="form-label">Particulars</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Enter journal entry description" required></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Account Distribution Section -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-primary fw-bold">Account Distribution</h6>
                        </div>
                    </div>
                    
                                         <div class="table-responsive">
                         <table class="table table-bordered" id="accountDistributionTable">
                             <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                 <tr>
                                     <th style="width: 15%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Account Title</th>
                                     <th style="width: 15%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Project</th>
                                     <th style="width: 15%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Department</th>
                                     <th style="width: 20%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Subsidiary Account</th>
                                     <th style="width: 10%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Debit</th>
                                     <th style="width: 10%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Credit</th>
                                     <th style="width: 10%; padding: 8px; font-weight: bold; color: #495057; border: 1px solid #dee2e6;">Action</th>
                                 </tr>
                             </thead>
                            <tbody id="accountDistributionLines">
                                <!-- Account distribution lines will be added here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end">
                                        <strong>
                                            <span id="balanceStatus" class="text-success">BALANCED</span>:
                                        </strong>
                                    </td>
                                    <td class="text-end"><strong id="totalDebitAmount">₱0.00</strong></td>
                                    <td class="text-end"><strong id="totalCreditAmount">₱0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Add Entry Button -->
                    <div class="row mb-3">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-outline-primary" onclick="addAccountEntry()">
                                <i class="bi bi-plus-circle me-2"></i>Add Account Entry
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                </button>
                                <button type="button" class="btn btn-primary" onclick="saveJournalEntry()" id="saveBtn">
                                    <i class="bi bi-save me-2"></i>Save Journal Entry
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let lineCounter = 0;
let accounts = <?= json_encode($accounts) ?>;
let projects = <?= json_encode($projects) ?>;
let departments = <?= json_encode($departments) ?>;
let suppliers = <?= json_encode($suppliers) ?>;

function addAccountEntry() {
    lineCounter++;
    const lineHtml = `
        <tr class="account-line" id="line_${lineCounter}">
            <td>
                <select class="form-select account-select" name="entries[${lineCounter}][account_id]" required>
                    <option value="">Select Account</option>
                    ${accounts.map(account => 
                        `<option value="${account.id}">${account.account_code} - ${account.account_name}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <select class="form-select project-select" name="entries[${lineCounter}][project_id]">
                    <option value="">Select Project</option>
                    ${projects.map(project => 
                        `<option value="${project.id}">${project.project_code} - ${project.project_name}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <select class="form-select department-select" name="entries[${lineCounter}][department_id]">
                    <option value="">Select Department</option>
                    ${departments.map(dept => 
                        `<option value="${dept.id}">${dept.department_code} - ${dept.department_name}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <select class="form-select supplier-select" name="entries[${lineCounter}][supplier_id]">
                    <option value="">No Subsidiary</option>
                    ${suppliers.map(supp => 
                        `<option value="${supp.id}">${supp.supplier_name}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <input type="text" class="form-control debit-input" 
                       name="entries[${lineCounter}][debit_amount]" 
                       placeholder="0.00"
                       onkeypress="validateNumericInput(event)"
                       onblur="validateAmount(this, 'debit')" 
                       oninput="updateBalance()">
            </td>
            <td>
                <input type="text" class="form-control credit-input" 
                       name="entries[${lineCounter}][credit_amount]" 
                       placeholder="0.00"
                       onkeypress="validateNumericInput(event)"
                       onblur="validateAmount(this, 'credit')" 
                       oninput="updateBalance()">
            </td>
            <td>
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="removeAccountLine(${lineCounter})" title="Remove Line">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#accountDistributionLines').append(lineHtml);
    updateBalance();
}

function removeAccountLine(lineId) {
    $(`#line_${lineId}`).remove();
    updateBalance();
}

function validateNumericInput(event) {
    const char = String.fromCharCode(event.which);
    const input = event.target;
    const value = input.value;
    
    // Allow: backspace, delete, tab, escape, enter, and decimal point
    if (event.keyCode === 8 || event.keyCode === 9 || event.keyCode === 27 || event.keyCode === 13 || event.keyCode === 46) {
        return;
    }
    
    // Allow decimal point only once
    if (char === '.' && value.indexOf('.') !== -1) {
        event.preventDefault();
        return;
    }
    
    // Allow only numbers and decimal point
    if (!/[\d.]/.test(char)) {
        event.preventDefault();
        return;
    }
    
    // Check if adding this character would exceed 2 decimal places
    if (char !== '.' && value.includes('.')) {
        const decimalIndex = value.indexOf('.');
        const decimalPlaces = value.length - decimalIndex - 1;
        if (decimalPlaces >= 2) {
            event.preventDefault();
            return;
        }
    }
}

function validateAmount(input, type) {
    let value = parseFloat(input.value) || 0;
    
    // Ensure only 2 decimal places
    value = Math.round(value * 100) / 100;
    input.value = value.toFixed(2);
    
    // Additional check for decimal places in the input string
    const inputValue = input.value;
    if (inputValue.includes('.')) {
        const decimalIndex = inputValue.indexOf('.');
        const decimalPlaces = inputValue.length - decimalIndex - 1;
        if (decimalPlaces > 2) {
            // Truncate to 2 decimal places
            const truncatedValue = parseFloat(inputValue.substring(0, decimalIndex + 3));
            input.value = truncatedValue.toFixed(2);
        }
    }
    
    // Clear the other field in the same row
    const row = $(input).closest('tr');
    if (type === 'debit') {
        row.find('.credit-input').val('0.00');
    } else {
        row.find('.debit-input').val('0.00');
    }
    
    updateBalance();
}

function updateBalance() {
    let totalDebits = 0;
    let totalCredits = 0;
    
    $('.account-line').each(function() {
        const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
        const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;
        
        totalDebits += debitAmount;
        totalCredits += creditAmount;
    });
    
    const difference = totalDebits - totalCredits;
    
    $('#totalDebitAmount').text(totalDebits.toFixed(2));
    $('#totalCreditAmount').text(totalCredits.toFixed(2));
    
    // Update balance status
    const balanceStatus = $('#balanceStatus');
    if (Math.abs(difference) === 0) {
        balanceStatus.removeClass('text-danger').addClass('text-success').text('BALANCED:');
    } else {
        balanceStatus.removeClass('text-success').addClass('text-danger').text('UNBALANCED:');
    }
    
    // Enable/disable save button based on balance
    const saveBtn = $('#saveBtn');
    if (Math.abs(difference) === 0 && totalDebits > 0) {
        saveBtn.prop('disabled', false);
    } else {
        saveBtn.prop('disabled', true);
    }
}

function saveJournalEntry() {
    const form = $('#journalEntryForm');
    const formData = {};
    
    // Collect form data
    form.serializeArray().forEach(function(item) {
        if (item.name.includes('[')) {
            // Handle array fields
            const matches = item.name.match(/entries\[(\d+)\]\[(\w+)\]/);
            if (matches) {
                const index = matches[1];
                const field = matches[2];
                if (!formData.entries) formData.entries = {};
                if (!formData.entries[index]) formData.entries[index] = {};
                formData.entries[index][field] = item.value;
            }
        } else {
            formData[item.name] = item.value;
        }
    });
    
    // Convert entries object to array and process debit/credit
    if (formData.entries) {
        formData.entries = Object.values(formData.entries).map(entry => {
            const debitAmount = parseFloat(entry.debit_amount) || 0;
            const creditAmount = parseFloat(entry.credit_amount) || 0;
            
            return {
                account_id: entry.account_id,
                project_id: entry.project_id || null,
                department_id: entry.department_id || null,
                supplier_id: entry.supplier_id || null,
                transaction_type: debitAmount > 0 ? 'debit' : 'credit',
                amount: debitAmount > 0 ? debitAmount : creditAmount,
                description: entry.description || ''
            };
        }).filter(entry => entry.amount > 0); // Remove empty entries
    }
    
    // Validate required fields
    if (!formData.transaction_date || !formData.description) {
        EARS.showAlert('Please fill in all required fields', 'danger');
        return;
    }
    
    if (!formData.entries || formData.entries.length === 0) {
        EARS.showAlert('Please add at least one account entry', 'danger');
        return;
    }
    
    // Validate each entry
    for (let entry of formData.entries) {
        if (!entry.account_id) {
            EARS.showAlert('Please select an account for each entry line', 'danger');
            return;
        }
    }
    
    // Show loading
    const saveBtn = $('#saveBtn');
    const originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    // Make API request
    $.ajax({
        url: APP_URL + '/api/journal-entries/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Journal entry created successfully!', 'success');
                setTimeout(function() {
                    window.location.href = APP_URL + '/journal-entries';
                }, 1500);
            } else {
                EARS.showAlert(response.error || 'Failed to create journal entry', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to create journal entry', 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html(originalText);
        }
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
        $('#journalEntryForm')[0].reset();
        $('#accountDistributionLines').empty();
        lineCounter = 0;
        updateBalance();
    }
}

// Initialize with one line
$(document).ready(function() {
    addAccountEntry();
    
    // Update balance when any input changes
    $(document).on('input', '.debit-input, .credit-input', function() {
        updateBalance();
    });
});
</script> 