<?php
// Summary of All Books View
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="bi bi-graph-up me-2"></i>
                        Summary of All Books
                    </h4>
                    <p class="card-subtitle text-muted">Comprehensive overview of all financial books and transactions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" id="sumDateFrom">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" id="sumDateTo">
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary" onclick="quickRange('month')">This Month</button>
                                <button class="btn btn-outline-secondary" onclick="quickRange('quarter')">This Quarter</button>
                                <button class="btn btn-outline-secondary" onclick="quickRange('fiscal')">Fiscal Year</button>
                                <button class="btn btn-outline-secondary" onclick="quickRange('year')">This Year</button>
                            </div>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <button class="btn btn-primary w-100" onclick="reloadSummary()"><i class="bi bi-search me-1"></i>Apply</button>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <button class="btn btn-outline-secondary w-100" onclick="resetSummaryFilters()"><i class="bi bi-x-circle me-1"></i>Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Receipts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₱<?php echo number_format($summaryData['overall']['total_receipts'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-coin fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Disbursements</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₱<?php echo number_format($summaryData['overall']['total_disbursements'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Net Cash Flow</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 <?php echo $summaryData['overall']['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                ₱<?php echo number_format($summaryData['overall']['net_cash_flow'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Transactions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($summaryData['overall']['total_transactions']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-journal-text fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Type Summary -->
    <div class="row mb-4">
        <!-- Cash Receipt Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-cash-coin me-2"></i>Cash Receipt Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($summaryData['cash_receipt']['total_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Transactions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    ₱<?php echo number_format($summaryData['cash_receipt']['total_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Amount</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    <?php echo number_format($summaryData['cash_receipt']['this_month_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    ₱<?php echo number_format($summaryData['cash_receipt']['this_month_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Disbursement Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="bi bi-cash-stack me-2"></i>Cash Disbursement Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($summaryData['cash_disbursement']['total_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Transactions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    ₱<?php echo number_format($summaryData['cash_disbursement']['total_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Amount</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    <?php echo number_format($summaryData['cash_disbursement']['this_month_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    ₱<?php echo number_format($summaryData['cash_disbursement']['this_month_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Check Disbursement Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="bi bi-check-circle me-2"></i>Check Disbursement Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($summaryData['check_disbursement']['total_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Transactions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    ₱<?php echo number_format($summaryData['check_disbursement']['total_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">Total Amount</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    <?php echo number_format($summaryData['check_disbursement']['this_month_count']); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h5 mb-0 font-weight-bold text-success">
                                    ₱<?php echo number_format($summaryData['check_disbursement']['this_month_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-600">This Month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Detailed Information -->
    <div class="row mb-4">
        <!-- Monthly Transaction Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Transaction Overview</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Account Type Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Account Type Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="accountTypeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Status & Pending Approvals -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Status Breakdown</h6>
                </div>
                <div class="card-body">
                    <div id="statusBreakdown" class="row text-center g-3">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Pending Approvals</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle" id="pendingApprovalsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Voucher</th>
                                    <th>Payee</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    initSavedFilters();
    loadOverview();
    loadMonthlyData();
    loadAccountTypeData();
    loadStatusCounts();
    loadPendingApprovals();
});

function loadMonthlyData() {
    $.ajax({
        url: APP_URL + '/api/summary/monthly-data',
        type: 'GET', cache: false,
        success: function(response) {
            if (response.success) {
                createMonthlyChart(response.data);
            }
        }
    });
}

function createMonthlyChart(data) {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    // Process data for chart
    const months = [...new Set(data.map(item => item.month))].sort();
    const cashReceiptData = months.map(month => {
        const monthData = data.filter(item => item.month === month && item.transaction_type === 'cash_receipt');
        return monthData.length > 0 ? parseFloat(monthData[0].total_amount) : 0;
    });
    const cashDisbursementData = months.map(month => {
        const monthData = data.filter(item => item.month === month && item.transaction_type === 'cash_disbursement');
        return monthData.length > 0 ? parseFloat(monthData[0].total_amount) : 0;
    });
    const checkDisbursementData = months.map(month => {
        const monthData = data.filter(item => item.month === month && item.transaction_type === 'check_disbursement');
        return monthData.length > 0 ? parseFloat(monthData[0].total_amount) : 0;
    });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months.map(month => {
                const date = new Date(month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Cash Receipts',
                data: cashReceiptData,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Cash Disbursements',
                data: cashDisbursementData,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }, {
                label: 'Check Disbursements',
                data: checkDisbursementData,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Transaction Overview'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function loadAccountTypeData() {
    const accountTypes = <?php echo json_encode($summaryData['accounts']['account_types']); ?>;
    createAccountTypeChart(accountTypes);
}

function createAccountTypeChart(data) {
    const ctx = document.getElementById('accountTypeChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.group_name),
            datasets: [{
                data: data.map(item => parseInt(item.account_count, 10)),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Account Type Distribution (by number of accounts)'
                }
            }
        }
    });
}

// New: Overview and filters
function initSavedFilters() {
    const saved = JSON.parse(localStorage.getItem('summary_filters') || '{}');
    if (saved.date_from) $('#sumDateFrom').val(saved.date_from);
    if (saved.date_to) $('#sumDateTo').val(saved.date_to);
}

function quickRange(kind) {
    const today = new Date();
    if (kind === 'month') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        const last = new Date(today.getFullYear(), today.getMonth()+1, 0);
        $('#sumDateFrom').val(first.toISOString().slice(0,10));
        $('#sumDateTo').val(last.toISOString().slice(0,10));
    } else if (kind === 'quarter') {
        const q = Math.floor(today.getMonth()/3);
        const first = new Date(today.getFullYear(), q*3, 1);
        const last = new Date(today.getFullYear(), q*3 + 3, 0);
        $('#sumDateFrom').val(first.toISOString().slice(0,10));
        $('#sumDateTo').val(last.toISOString().slice(0,10));
    } else if (kind === 'year') {
        $('#sumDateFrom').val(new Date(today.getFullYear(),0,1).toISOString().slice(0,10));
        $('#sumDateTo').val(new Date(today.getFullYear(),11,31).toISOString().slice(0,10));
    } else if (kind === 'fiscal') {
        // Leave blank to let backend resolve fiscal automatically
        $('#sumDateFrom').val('');
        $('#sumDateTo').val('');
    }
}

function currentFilters() {
    const date_from = $('#sumDateFrom').val();
    const date_to = $('#sumDateTo').val();
    const q = $.param({ date_from, date_to });
    localStorage.setItem('summary_filters', JSON.stringify({ date_from, date_to }));
    return q;
}

function reloadSummary() {
    loadOverview();
    loadMonthlyData();
    loadStatusCounts();
    loadPendingApprovals();
}

function resetSummaryFilters() {
    $('#sumDateFrom').val('');
    $('#sumDateTo').val('');
    localStorage.removeItem('summary_filters');
    reloadSummary();
}

function loadOverview() {
    $.get(APP_URL + '/api/summary/overview?' + currentFilters(), function(resp){
        if (!resp.success) return;
        const d = resp.data;
        // Update numbers on the cards
        // Total Receipts
        $(".card:contains('Total Receipts') .h5").text('₱' + numberFormat(d.total_receipts));
        // Total Disbursements
        $(".card:contains('Total Disbursements') .h5").text('₱' + numberFormat(d.total_disbursements));
        // Net Cash Flow
        const netEl = $(".card:contains('Net Cash Flow') .h5");
        netEl.text('₱' + numberFormat(d.net_cash_flow));
        netEl.toggleClass('text-success', d.net_cash_flow >= 0);
        netEl.toggleClass('text-danger', d.net_cash_flow < 0);
        // Total Transactions
        $(".card:contains('Total Transactions') .h5").text(numberFormat(d.total_transactions));
    });
}

function loadStatusCounts() {
    $.get(APP_URL + '/api/summary/status-counts?' + currentFilters(), function(resp){
        if (!resp.success) return;
        const d = resp.data;
        const html = [
            statusCard('Cash Receipt', d.cash_receipt),
            statusCard('Cash Disbursement', d.cash_disbursement),
            statusCard('Check Disbursement', d.check_disbursement)
        ].join('');
        $('#statusBreakdown').html(html);
    });
}

function statusCard(title, row) {
    const p = parseInt(row.pending||0,10), a = parseInt(row.approved||0,10), r = parseInt(row.rejected||0,10);
    return `
    <div class="col-12 col-md-4">
        <div class="border rounded p-2 h-100">
            <div class="fw-bold mb-1">${title}</div>
            <div class="d-flex justify-content-between"><span>Pending</span><span class="badge bg-warning">${p}</span></div>
            <div class="d-flex justify-content-between"><span>Approved</span><span class="badge bg-success">${a}</span></div>
            <div class="d-flex justify-content-between"><span>Rejected</span><span class="badge bg-danger">${r}</span></div>
        </div>
    </div>`;
}

function loadPendingApprovals() {
    $.get(APP_URL + '/api/summary/pending-approvals?' + currentFilters(), function(resp){
        if (!resp.success) return;
        const rows = resp.data || [];
        const tbody = $('#pendingApprovalsTable tbody');
        tbody.empty();
        if (rows.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No pending approvals</td></tr>');
            return;
        }
        rows.forEach(r => {
            tbody.append(`
                <tr>
                    <td>${formatDate(r.transaction_date)}</td>
                    <td>${niceType(r.transaction_type)}</td>
                    <td>${r.reference_no}</td>
                    <td>${r.payee_name || ''}</td>
                    <td class="text-end">₱${numberFormat(r.amount)}</td>
                    <td><span class="badge bg-warning">Pending</span></td>
                </tr>`);
        });
    });
}

function niceType(t){
    if (t==='cash_disbursement') return 'Cash Disb.';
    if (t==='check_disbursement') return 'Check Disb.';
    if (t==='cash_receipt') return 'Cash Receipt';
    return t;
}

function numberFormat(v){
    return parseFloat(v||0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
}

function formatDate(s){
    const d = new Date(s);
    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-600 {
    color: #858796 !important;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.h4 {
    font-size: 1.5rem;
}

.h5 {
    font-size: 1.25rem;
}

.h6 {
    font-size: 1rem;
}
</style> 