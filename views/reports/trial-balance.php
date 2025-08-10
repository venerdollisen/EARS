<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Trial Balance</h1>
                <p class="text-muted mb-0">Account balances as of <?= date('F d, Y', strtotime($dateFrom)) ?> to <?= date('F d, Y', strtotime($dateTo)) ?></p>
            </div>
            <div>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="exportReport('pdf')">
                    <i class="bi bi-file-pdf me-1"></i>Export PDF
                </button>
                <button class="btn btn-outline-success btn-sm me-2" onclick="exportReport('excel')">
                    <i class="bi bi-file-excel me-1"></i>Export Excel
                </button>
                <a href="<?= APP_URL ?>/reports" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" action="<?= APP_URL ?>/reports/trial-balance" class="row g-3">
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?= htmlspecialchars($dateFrom) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?= htmlspecialchars($dateTo) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Generate Report
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetDates()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Trial Balance Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Trial Balance Report</h6>
            </div>
            <div class="card-body">
                <?php if (empty($accounts)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No transactions found</h5>
                        <p class="text-muted">There are no transactions in the selected date range.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="trialBalanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Account Type</th>
                                    <th class="text-end">Debits</th>
                                    <th class="text-end">Credits</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalDebits = 0;
                                $totalCredits = 0;
                                $currentType = '';
                                foreach ($accounts as $account): 
                                    if ($currentType !== $account['account_type']):
                                        if ($currentType !== ''): ?>
                                            <tr class="table-light">
                                                <td colspan="3"><strong><?= htmlspecialchars($currentType) ?> Total</strong></td>
                                                <td class="text-end"><strong><?= number_format($typeDebits, 2) ?></strong></td>
                                                <td class="text-end"><strong><?= number_format($typeCredits, 2) ?></strong></td>
                                                <td class="text-end"><strong><?= number_format($typeDebits - $typeCredits, 2) ?></strong></td>
                                            </tr>
                                        <?php endif;
                                        $currentType = $account['account_type'];
                                        $typeDebits = 0;
                                        $typeCredits = 0;
                                    endif;
                                    
                                    $typeDebits += $account['total_debits'];
                                    $typeCredits += $account['total_credits'];
                                    $totalDebits += $account['total_debits'];
                                    $totalCredits += $account['total_credits'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($account['account_code']) ?></td>
                                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                                    <td><?= htmlspecialchars($account['account_type']) ?></td>
                                    <td class="text-end"><?= number_format($account['total_debits'], 2) ?></td>
                                    <td class="text-end"><?= number_format($account['total_credits'], 2) ?></td>
                                    <td class="text-end <?= $account['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format(abs($account['balance']), 2) ?>
                                        <?= $account['balance'] < 0 ? '(Cr)' : '(Dr)' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Last type total -->
                                <?php if ($currentType !== ''): ?>
                                    <tr class="table-light">
                                        <td colspan="3"><strong><?= htmlspecialchars($currentType) ?> Total</strong></td>
                                        <td class="text-end"><strong><?= number_format($typeDebits, 2) ?></strong></td>
                                        <td class="text-end"><strong><?= number_format($typeCredits, 2) ?></strong></td>
                                        <td class="text-end"><strong><?= number_format($typeDebits - $typeCredits, 2) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="3"><strong>GRAND TOTAL</strong></td>
                                    <td class="text-end"><strong><?= number_format($totalDebits, 2) ?></strong></td>
                                    <td class="text-end"><strong><?= number_format($totalCredits, 2) ?></strong></td>
                                    <td class="text-end">
                                        <strong class="<?= ($totalDebits - $totalCredits) == 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($totalDebits - $totalCredits, 2) ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Balance Check -->
                    <?php if (($totalDebits - $totalCredits) == 0): ?>
                        <div class="alert alert-success mt-3">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Trial Balance is balanced!</strong> Total debits equal total credits.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Trial Balance is not balanced!</strong> 
                            Difference: <?= number_format(abs($totalDebits - $totalCredits), 2) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<?php if (!empty($accounts)): ?>
<div class="row mt-4">
    <div class="col-md-4">
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
                        <i class="bi bi-arrow-up-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
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
                        <i class="bi bi-arrow-down-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-left-<?= ($totalDebits - $totalCredits) == 0 ? 'success' : 'danger' ?> shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-<?= ($totalDebits - $totalCredits) == 0 ? 'success' : 'danger' ?> text-uppercase mb-1">
                            Difference
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₱<?= number_format($totalDebits - $totalCredits, 2) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-<?= ($totalDebits - $totalCredits) == 0 ? 'check' : 'x' ?>-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function exportReport(format) {
    const url = `${APP_URL}/reports/export-report?type=trial_balance&format=${format}&date_from=${document.getElementById('date_from').value}&date_to=${document.getElementById('date_to').value}`;
    
    // Show loading
    EARS.showAlert('Generating report...', 'info');
    
    // For now, just show a message
    setTimeout(() => {
        EARS.showAlert('Export functionality will be implemented in the next phase', 'warning');
    }, 1000);
}

function resetDates() {
    const currentYear = new Date().getFullYear();
    document.getElementById('date_from').value = `${currentYear}-01-01`;
    document.getElementById('date_to').value = `${currentYear}-12-31`;
}

// Initialize DataTable
$(document).ready(function() {
    $('#trialBalanceTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script> 