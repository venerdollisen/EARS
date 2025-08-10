<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Financial Reports</h1>
                <p class="text-muted mb-0">Generate and view essential financial reports</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Trial Balance -->
    <div class="col-lg-6 col-xl-3 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Trial Balance
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Account Balances
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calculator fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/reports/trial-balance" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- General Ledger -->
    <div class="col-lg-6 col-xl-3 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            General Ledger
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Account Details
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-journal-text fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/reports/general-ledger" class="btn btn-success btn-sm">
                        <i class="bi bi-eye me-1"></i>View Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Income Statement -->
    <div class="col-lg-6 col-xl-3 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Income Statement
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Profit & Loss
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/reports/income-statement" class="btn btn-info btn-sm">
                        <i class="bi bi-eye me-1"></i>View Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Sheet -->
    <div class="col-lg-6 col-xl-3 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Balance Sheet
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            Financial Position
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-pie-chart fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/reports/balance-sheet" class="btn btn-warning btn-sm">
                        <i class="bi bi-eye me-1"></i>View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Export Reports</h6>
                        <div class="mb-2">
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="exportReport('trial_balance', 'pdf')">
                                <i class="bi bi-file-pdf me-1"></i>Export Trial Balance (PDF)
                            </button>
                        </div>
                        <div class="mb-2">
                            <button class="btn btn-outline-success btn-sm me-2" onclick="exportReport('income_statement', 'excel')">
                                <i class="bi bi-file-excel me-1"></i>Export Income Statement (Excel)
                            </button>
                        </div>
                        <div class="mb-2">
                            <button class="btn btn-outline-warning btn-sm me-2" onclick="exportReport('balance_sheet', 'pdf')">
                                <i class="bi bi-file-pdf me-1"></i>Export Balance Sheet (PDF)
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Report Settings</h6>
                        <div class="mb-2">
                            <label class="form-label">Default Date Range</label>
                            <select class="form-select form-select-sm" id="defaultDateRange">
                                <option value="current_month">Current Month</option>
                                <option value="current_quarter">Current Quarter</option>
                                <option value="current_year" selected>Current Year</option>
                                <option value="last_year">Last Year</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Default Currency</label>
                            <select class="form-select form-select-sm" id="defaultCurrency">
                                <option value="PHP" selected>Philippine Peso (PHP)</option>
                                <option value="USD">US Dollar (USD)</option>
                                <option value="EUR">Euro (EUR)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reports -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Reports</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="recentReportsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Report Type</th>
                                <th>Generated Date</th>
                                <th>Date Range</th>
                                <th>Generated By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recent reports found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(reportType, format) {
    const url = `${APP_URL}/reports/export-report?type=${reportType}&format=${format}`;
    
    // Show loading
    EARS.showAlert('Generating report...', 'info');
    
    // For now, just show a message
    setTimeout(() => {
        EARS.showAlert('Export functionality will be implemented in the next phase', 'warning');
    }, 1000);
}

// Set default date range based on selection
document.getElementById('defaultDateRange').addEventListener('change', function() {
    const range = this.value;
    let dateFrom, dateTo;
    
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth();
    
    switch(range) {
        case 'current_month':
            dateFrom = new Date(currentYear, currentMonth, 1).toISOString().split('T')[0];
            dateTo = new Date(currentYear, currentMonth + 1, 0).toISOString().split('T')[0];
            break;
        case 'current_quarter':
            const quarter = Math.floor(currentMonth / 3);
            dateFrom = new Date(currentYear, quarter * 3, 1).toISOString().split('T')[0];
            dateTo = new Date(currentYear, (quarter + 1) * 3, 0).toISOString().split('T')[0];
            break;
        case 'current_year':
            dateFrom = `${currentYear}-01-01`;
            dateTo = `${currentYear}-12-31`;
            break;
        case 'last_year':
            dateFrom = `${currentYear - 1}-01-01`;
            dateTo = `${currentYear - 1}-12-31`;
            break;
    }
    
    // Store in session storage for use in reports
    sessionStorage.setItem('defaultDateFrom', dateFrom);
    sessionStorage.setItem('defaultDateTo', dateTo);
});

// Initialize with current year
document.addEventListener('DOMContentLoaded', function() {
    const currentYear = new Date().getFullYear();
    sessionStorage.setItem('defaultDateFrom', `${currentYear}-01-01`);
    sessionStorage.setItem('defaultDateTo', `${currentYear}-12-31`);
});
</script> 