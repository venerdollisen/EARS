<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Journal Entries</h1>
                <p class="text-muted mb-0">Manage manual journal entries and approvals</p>
            </div>
            <a href="<?= APP_URL ?>/journal-entries/create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Create Journal Entry
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Journal Entries
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEntries">
                            <?= count($journalEntries) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-journal-text fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pending Approval
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingEntries">
                            <?= count(array_filter($journalEntries, function($entry) { return $entry['status'] === 'pending'; })) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock-history fa-2x text-gray-300"></i>
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
                            Approved
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="approvedEntries">
                            <?= count(array_filter($journalEntries, function($entry) { return $entry['status'] === 'approved'; })) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Rejected
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="rejectedEntries">
                            <?= count(array_filter($journalEntries, function($entry) { return $entry['status'] === 'rejected'; })) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-x-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Journal Entries List</h6>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterEntries('all')">All</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="filterEntries('pending')">Pending</button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="filterEntries('approved')">Approved</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="filterEntries('rejected')">Rejected</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($journalEntries)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-journal-text fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No journal entries found</h5>
                        <p class="text-muted">Create your first journal entry to get started.</p>
                        <a href="<?= APP_URL ?>/journal-entries/create" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Create Journal Entry
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="journalEntriesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Reference No</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Total Debits</th>
                                    <th>Total Credits</th>
                                    <th>Balance</th>
                                    <th>JV Status</th>
                                    <th>For Posting</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($journalEntries as $entry): 
                                    // Use balance information from the data
                                    $balanceInfo = $entry['balance_info'];
                                    $isBalanced = abs($balanceInfo['difference']) < 0.01;
                                ?>
                                <tr class="entry-row" data-status="<?= $entry['status'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($entry['reference_no']) ?></strong>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($entry['transaction_date'])) ?></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($entry['description'] ?: 'No description') ?>">
                                            <?= htmlspecialchars($entry['description'] ?: 'No description') ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-primary">₱<?= number_format($balanceInfo['total_debits'], 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success">₱<?= number_format($balanceInfo['total_credits'], 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($isBalanced): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Balanced
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger" title="Difference: ₱<?= number_format(abs($balanceInfo['difference']), 2) ?>">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Unbalanced
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $jvStatusClass = $entry['jv_status'] === 'active' ? 'success' : 'secondary';
                                        $jvStatusText = ucfirst($entry['jv_status']);
                                        ?>
                                        <span class="badge bg-<?= $jvStatusClass ?>"><?= $jvStatusText ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $postingClass = $entry['for_posting'] === 'for_posting' ? 'primary' : 'warning';
                                        $postingText = str_replace('_', ' ', ucfirst($entry['for_posting']));
                                        ?>
                                        <span class="badge bg-<?= $postingClass ?>"><?= $postingText ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($entry['status']) {
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
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($entry['created_by'] ?? 'Unknown') ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($entry['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= APP_URL ?>/journal-entries/view/<?= $entry['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($entry['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="approveJournalEntry(<?= $entry['id'] ?>)" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="rejectJournalEntry(<?= $entry['id'] ?>)" title="Reject">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
let currentJournalEntryId = null;

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
    currentJournalEntryId = id;
    $('#rejectionModal').modal('show');
}

function confirmReject() {
    const reason = $('#rejection_reason').val().trim();
    
    if (!reason) {
        EARS.showAlert('Please provide a reason for rejection', 'danger');
        return;
    }
    
    $.ajax({
        url: APP_URL + '/api/journal-entries/' + currentJournalEntryId + '/reject',
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

function filterEntries(status) {
    if (status === 'all') {
        $('.entry-row').show();
    } else {
        $('.entry-row').hide();
        $('.entry-row[data-status="' + status + '"]').show();
    }
    
    // Update summary counts
    updateSummaryCounts();
}

function updateSummaryCounts() {
    const visibleRows = $('.entry-row:visible');
    const total = visibleRows.length;
    const pending = visibleRows.filter('[data-status="pending"]').length;
    const approved = visibleRows.filter('[data-status="approved"]').length;
    const rejected = visibleRows.filter('[data-status="rejected"]').length;
    
    $('#totalEntries').text(total);
    $('#pendingEntries').text(pending);
    $('#approvedEntries').text(approved);
    $('#rejectedEntries').text(rejected);
}

// Initialize DataTable
$(document).ready(function() {
    $('#journalEntriesTable').DataTable({
        pageLength: 25,
        order: [[1, 'desc']], // Sort by date descending
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            { orderable: false, targets: [11] } // Disable sorting on Actions column
        ]
    });
    
    // Reset modal when closed
    $('#rejectionModal').on('hidden.bs.modal', function() {
        $('#rejection_reason').val('');
        currentJournalEntryId = null;
    });
});


</script> 