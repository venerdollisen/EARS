<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Journal Entry Details</h1>
                <p class="text-muted mb-0">Reference: <?= htmlspecialchars($journalEntry['reference_no']) ?></p>
            </div>
            <div>
                <a href="<?= APP_URL ?>/journal-entries" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
                <?php if ($journalEntry['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-success me-2" onclick="approveJournalEntry(<?= $journalEntry['id'] ?>)">
                        <i class="bi bi-check-circle me-2"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectJournalEntry(<?= $journalEntry['id'] ?>)">
                        <i class="bi bi-x-circle me-2"></i>Reject
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Balance Summary Cards -->
<?php 
$totalDebits = 0;
$totalCredits = 0;
foreach ($details as $detail): 
    if ($detail['transaction_type'] === 'debit') {
        $totalDebits += $detail['amount'];
    } else {
        $totalCredits += $detail['amount'];
    }
endforeach;
$difference = $totalDebits - $totalCredits;
$isBalanced = abs($difference) < 0.01;
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Debits
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₱<?= number_format($totalDebits, 2) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-arrow-up-circle fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Credits
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₱<?= number_format($totalCredits, 2) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-arrow-down-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-<?= $isBalanced ? 'success' : 'danger' ?> shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-<?= $isBalanced ? 'success' : 'danger' ?> text-uppercase mb-1">
                            Balance Status
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php if ($isBalanced): ?>
                                <span class="text-success">Balanced</span>
                            <?php else: ?>
                                <span class="text-danger">Unbalanced</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php if ($isBalanced): ?>
                            <i class="bi bi-check-circle fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle fa-2x text-danger"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Difference
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <span class="text-<?= $isBalanced ? 'success' : 'danger' ?>">
                                ₱<?= number_format(abs($difference), 2) ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calculator fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Journal Entry Details -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Journal Entry Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reference Number</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['reference_no']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Transaction Date</label>
                            <p class="form-control-plaintext"><?= date('F d, Y', strtotime($journalEntry['transaction_date'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">JV Status</label>
                            <p class="form-control-plaintext">
                                <?php
                                $jvStatusClass = $journalEntry['jv_status'] === 'active' ? 'success' : 'secondary';
                                $jvStatusText = ucfirst($journalEntry['jv_status']);
                                ?>
                                <span class="badge bg-<?= $jvStatusClass ?>"><?= $jvStatusText ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">For Posting</label>
                            <p class="form-control-plaintext">
                                <?php
                                $postingClass = $journalEntry['for_posting'] === 'for_posting' ? 'primary' : 'warning';
                                $postingText = str_replace('_', ' ', ucfirst($journalEntry['for_posting']));
                                ?>
                                <span class="badge bg-<?= $postingClass ?>"><?= $postingText ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Particulars</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['description'] ?: 'No description') ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reference Number 1</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['reference_number1'] ?: '-') ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reference Number 2</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['reference_number2'] ?: '-') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">CWO Number</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['cwo_number'] ?: '-') ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bill Invoice Ref. No.</label>
                            <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['bill_invoice_ref'] ?: '-') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Total Amount</label>
                            <p class="form-control-plaintext h5 text-primary">₱<?= number_format($journalEntry['total_amount'], 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p class="form-control-plaintext">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch ($journalEntry['status']) {
                                    case 'pending':
                                        $statusClass = 'warning';
                                        $statusText = 'Pending';
                                        break;
                                    case 'approved':
                                        $statusClass = 'success';
                                        $statusText = 'Approved';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'danger';
                                        $statusText = 'Rejected';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusText ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Journal Entry Lines -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Journal Entry Lines</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Account</th>
                                <th>Project</th>
                                <th>Department</th>
                                <th>Supplier</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $detail): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($detail['account_code']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($detail['account_name']) ?></small>
                                </td>
                                <td>
                                    <?php if ($detail['project_code']): ?>
                                        <strong><?= htmlspecialchars($detail['project_code']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($detail['project_name']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($detail['department_code']): ?>
                                        <strong><?= htmlspecialchars($detail['department_code']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($detail['department_name']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($detail['supplier_name']): ?>
                                        <strong><?= htmlspecialchars($detail['supplier_name']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($detail['transaction_type'] === 'debit'): ?>
                                        <span class="text-primary fw-bold">₱<?= number_format($detail['amount'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">₱0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($detail['transaction_type'] === 'credit'): ?>
                                        <span class="text-success fw-bold">₱<?= number_format($detail['amount'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">₱0.00</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="table-primary">
                                <td colspan="4" class="text-end"><strong>Total Debits:</strong></td>
                                <td class="text-end"><strong class="text-primary">₱<?= number_format($totalDebits, 2) ?></strong></td>
                                <td class="text-end"><strong class="text-muted">₱0.00</strong></td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="4" class="text-end"><strong>Total Credits:</strong></td>
                                <td class="text-end"><strong class="text-muted">₱0.00</strong></td>
                                <td class="text-end"><strong class="text-success">₱<?= number_format($totalCredits, 2) ?></strong></td>
                            </tr>
                            <tr class="table-<?= $isBalanced ? 'success' : 'danger' ?>">
                                <td colspan="4" class="text-end"><strong>Difference:</strong></td>
                                <td colspan="2" class="text-end">
                                    <strong class="text-<?= $isBalanced ? 'success' : 'danger' ?>">
                                        ₱<?= number_format($difference, 2) ?>
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Status Information -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Status Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Created By</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($journalEntry['created_by_name'] ?? 'Unknown') ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Created Date</label>
                    <p class="form-control-plaintext"><?= date('F d, Y H:i', strtotime($journalEntry['created_at'])) ?></p>
                </div>
                
                <?php if ($journalEntry['status'] === 'approved' && $journalEntry['approved_at']): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Approved Date</label>
                    <p class="form-control-plaintext"><?= date('F d, Y H:i', strtotime($journalEntry['approved_at'])) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($journalEntry['status'] === 'rejected' && $journalEntry['rejected_at']): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Rejected Date</label>
                    <p class="form-control-plaintext"><?= date('F d, Y H:i', strtotime($journalEntry['rejected_at'])) ?></p>
                </div>
                
                <?php if ($journalEntry['rejection_reason']): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Rejection Reason</label>
                    <p class="form-control-plaintext text-danger"><?= htmlspecialchars($journalEntry['rejection_reason']) ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Balance Check -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Balance Check</h6>
            </div>
            <div class="card-body">
                <?php if ($isBalanced): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Journal entry is balanced!</strong><br>
                        Total debits equal total credits.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Journal entry is not balanced!</strong><br>
                        Difference: ₱<?= number_format(abs($difference), 2) ?>
                    </div>
                <?php endif; ?>
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted">Total Debits</small><br>
                            <strong class="text-primary">₱<?= number_format($totalDebits, 2) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <small class="text-muted">Total Credits</small><br>
                            <strong class="text-success">₱<?= number_format($totalCredits, 2) ?></strong>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <small class="text-muted">Difference</small><br>
                    <strong class="text-<?= $isBalanced ? 'success' : 'danger' ?> fs-5">
                        ₱<?= number_format(abs($difference), 2) ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Journal Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_reason" name="reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">Reject Entry</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveJournalEntry(id) {
    if (confirm('Are you sure you want to approve this journal entry?')) {
        $.ajax({
            url: APP_URL + '/api/journal-entries/' + id + '/approve',
            method: 'POST',
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    EARS.showAlert('Journal entry approved successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    EARS.showAlert(response.error || 'Failed to approve journal entry', 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                EARS.showAlert(response?.error || 'Failed to approve journal entry', 'danger');
            }
        });
    }
}

function rejectJournalEntry(id) {
    $('#rejectionModal').modal('show');
}

function confirmReject() {
    const reason = $('#rejection_reason').val().trim();
    
    if (!reason) {
        EARS.showAlert('Please provide a reason for rejection', 'danger');
        return;
    }
    
    $.ajax({
        url: APP_URL + '/api/journal-entries/<?= $journalEntry['id'] ?>/reject',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ reason: reason }),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Journal entry rejected successfully!', 'success');
                $('#rejectionModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                EARS.showAlert(response.error || 'Failed to reject journal entry', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to reject journal entry', 'danger');
        }
    });
}

// Reset modal when closed
$(document).ready(function() {
    $('#rejectionModal').on('hidden.bs.modal', function() {
        $('#rejection_reason').val('');
    });
});
</script> 