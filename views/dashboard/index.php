<?php
// Dashboard View
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Dashboard Overview
                    </h4>
                    <p class="card-subtitle text-muted">Real-time financial overview and analytics</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Cash Receipts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($stats['total_receipts'] ?? 0, 2); ?></div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Cash Disbursements
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($stats['cash_disbursements'] ?? 0, 2); ?></div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Check Disbursements
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($stats['check_disbursements'] ?? 0, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-receipt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-journal-text fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Monthly Transaction Trends -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Transaction Trends</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Chart Options:</div>
                            <a class="dropdown-item" href="#" onclick="updateChart('monthly')">Monthly View</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('quarterly')">Quarterly View</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('yearly')">Yearly View</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlyTransactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Type Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Type Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="transactionTypeChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="bi bi-circle-fill text-success"></i> Cash Receipt
                        </span>
                        <span class="mr-2">
                            <i class="bi bi-circle-fill text-primary"></i> Cash Disbursement
                        </span>
                        <span class="mr-2">
                            <i class="bi bi-circle-fill text-info"></i> Check Disbursement
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Balance Chart -->
    <div class="row mb-4" style="display: none;">
        <div class="col-xl-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Account Balance Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="accountBalanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Global chart variables
let monthlyTransactionChart;
let transactionTypeChart;
let accountBalanceChart;

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    // Only load recent tables if they exist on the page
    const hasRecentTables = document.getElementById('recentReceiptsTable') || document.getElementById('recentDisbursementsTable');
    if (hasRecentTables) {
        loadRecentTransactions();
    }
    
    // Refresh data every 5 minutes
    setInterval(function() {
        loadRecentTransactions();
        updateChartData();
    }, 300000);
});

function initializeCharts() {
    // Monthly Transaction Chart
    const monthlyCtx = document.getElementById('monthlyTransactionChart').getContext('2d');
    monthlyTransactionChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Cash Receipts',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Cash Disbursements',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }, {
                label: 'Check Disbursements',
                data: [],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Transaction Trends'
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

    // Transaction Type Distribution Chart
    const typeCtx = document.getElementById('transactionTypeChart').getContext('2d');
    transactionTypeChart = new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Cash Receipts', 'Cash Disbursements', 'Check Disbursements'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Account Balance Chart
    const balanceCtx = document.getElementById('accountBalanceChart').getContext('2d');
    accountBalanceChart = new Chart(balanceCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Account Balance',
                data: [],
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Account Balance Overview'
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

    // Load initial data
    updateChartData();
}

function updateChartData() {
    const API_BASE = (typeof APP_URL !== 'undefined' && APP_URL) ? APP_URL : '';
    // Load monthly transaction data
    fetch(API_BASE + '/api/dashboard/monthly-data', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMonthlyChart(data.data);
            }
        })
        .catch(error => console.error('Error loading monthly data:', error));

    // Load transaction type distribution
    fetch(API_BASE + '/api/dashboard/transaction-distribution', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTransactionTypeChart(data.data);
            }
        })
        .catch(error => console.error('Error loading transaction distribution:', error));

    // Load account balance data
    fetch(API_BASE + '/api/dashboard/account-balance', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            console.log('Account balance API response:', data);
            if (data.success) {
                console.log('Account balance data:', data.data);
                updateAccountBalanceChart(data.data);
            } else {
                console.error('Account balance API error:', data.message);
            }
        })
        .catch(error => console.error('Error loading account balance:', error));
}

function updateMonthlyChart(data) {
    const months = [];
    const receipts = [];
    const cashDisbursements = [];
    const checkDisbursements = [];

    // Process data by month
    const monthlyData = {};
    data.forEach(item => {
        if (!monthlyData[item.month]) {
            monthlyData[item.month] = {
                cash_receipt: 0,
                cash_disbursement: 0,
                check_disbursement: 0
            };
        }
        monthlyData[item.month][item.transaction_type] = parseFloat(item.total_amount);
    });

    // Sort months and prepare chart data
    Object.keys(monthlyData).sort().forEach(month => {
        const monthName = new Date(month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        months.push(monthName);
        receipts.push(monthlyData[month].cash_receipt || 0);
        cashDisbursements.push(monthlyData[month].cash_disbursement || 0);
        checkDisbursements.push(monthlyData[month].check_disbursement || 0);
    });

    // Update chart
    monthlyTransactionChart.data.labels = months;
    monthlyTransactionChart.data.datasets[0].data = receipts;
    monthlyTransactionChart.data.datasets[1].data = cashDisbursements;
    monthlyTransactionChart.data.datasets[2].data = checkDisbursements;
    monthlyTransactionChart.update();
}

function updateTransactionTypeChart(data) {
    const labels = [];
    const values = [];
    const colors = ['rgba(75, 192, 192, 0.8)', 'rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)'];

    data.forEach((item, index) => {
        labels.push(item.type);
        values.push(parseFloat(item.amount));
    });

    transactionTypeChart.data.labels = labels;
    transactionTypeChart.data.datasets[0].data = values;
    transactionTypeChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
    transactionTypeChart.update();
}

function updateAccountBalanceChart(data) {
    console.log('updateAccountBalanceChart called with data:', data);
    
    const labels = [];
    const balances = [];

    data.forEach(item => {
        labels.push(item.account_name);
        balances.push(parseFloat(item.current_balance));
    });

    console.log('Chart labels:', labels);
    console.log('Chart balances:', balances);

    accountBalanceChart.data.labels = labels;
    accountBalanceChart.data.datasets[0].data = balances;
    accountBalanceChart.update();
    
    console.log('Chart updated');
}

function loadRecentTransactions() {
    const API_BASE = (typeof APP_URL !== 'undefined' && APP_URL) ? APP_URL : '';
    // Load recent cash receipts
    if (document.getElementById('recentReceiptsTable')) {
      fetch(API_BASE + '/api/cash-receipt/recent?limit=5', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRecentReceiptsTable(data.data);
            }
        })
        .catch(error => console.error('Error loading recent receipts:', error));
    }

    // Load recent disbursements
    if (document.getElementById('recentDisbursementsTable')) {
      fetch(API_BASE + '/api/cash-disbursement/recent?limit=5', { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRecentDisbursementsTable(data.data);
            }
        })
        .catch(error => console.error('Error loading recent disbursements:', error));
    }
}

function updateRecentReceiptsTable(data) {
    const tbody = document.getElementById('recentReceiptsTable');
    tbody.innerHTML = '';

    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.formatted_date}</td>
            <td>${item.reference_no}</td>
            <td>₱${parseFloat(item.amount).toLocaleString()}</td>
            <td><span class="badge bg-success">Completed</span></td>
        `;
        tbody.appendChild(row);
    });
}

function updateRecentDisbursementsTable(data) {
    const tbody = document.getElementById('recentDisbursementsTable');
    tbody.innerHTML = '';

    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.formatted_date}</td>
            <td>${item.reference_no}</td>
            <td>₱${parseFloat(item.amount).toLocaleString()}</td>
            <td><span class="badge bg-primary">Cash</span></td>
        `;
        tbody.appendChild(row);
    });
}

function updateChart(period) {
    // Update chart based on selected period
    console.log('Updating chart for period:', period);
    // Implementation for different chart periods
}
</script>

<style>
.chart-area {
    position: relative;
    height: 20rem;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 15rem;
    width: 100%;
}

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

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-xs {
    font-size: 0.7rem !important;
}

.text-uppercase {
    text-transform: uppercase !important;
}

.h5 {
    font-size: 1.25rem !important;
}

.mb-0 {
    margin-bottom: 0 !important;
}

.mb-1 {
    margin-bottom: 0.25rem !important;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.h-100 {
    height: 100% !important;
}

.no-gutters {
    margin-right: 0;
    margin-left: 0;
}

.no-gutters > .col,
.no-gutters > [class*="col-"] {
    padding-right: 0;
    padding-left: 0;
}

.align-items-center {
    align-items: center !important;
}

.col-auto {
    flex: 0 0 auto;
    width: auto;
    max-width: 100%;
}

.col {
    flex-basis: 0;
    flex-grow: 1;
    max-width: 100%;
}

.col mr-2 {
    margin-right: 0.5rem !important;
}

.fa-2x {
    font-size: 2em;
}
</style> 