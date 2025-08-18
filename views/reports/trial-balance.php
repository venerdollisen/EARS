<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Trial Balance Report</h4>
                    <p class="card-text">Generate and view trial balance reports with filtering options</p>
                </div>
                <div class="card-body">
                    <!-- Filters Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Filters</h5>
                                </div>
                                <div class="card-body">
                                    <form id="trialBalanceForm">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date">Start Date</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="end_date">End Date</label>
                                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="account_id">Account</label>
                                                    <select class="form-control" id="account_id" name="account_id">
                                                        <option value="">All Accounts</option>
                                                        <?php foreach ($accounts as $account): ?>
                                                            <option value="<?= $account['id'] ?>"><?= $account['account_code'] ?> - <?= $account['account_name'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="account_type_id">Account Type</label>
                                                    <select class="form-control" id="account_type_id" name="account_type_id">
                                                        <option value="">All Types</option>
                                                        <?php foreach ($accountTypes as $type): ?>
                                                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-search"></i> Generate Report
                                                </button>
                                                <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                                                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                                                </button>
                                                <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
                                                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summaryCards" style="display: none;">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Accounts</h5>
                                    <h3 id="totalAccounts">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Debits</h5>
                                    <h3 id="totalDebits">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Credits</h5>
                                    <h3 id="totalCredits">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Net Balance</h5>
                                    <h3 id="netBalance">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4" id="chartsSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Balance by Account Type</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="accountTypeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Balance Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="balanceDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Accounts Chart -->
                    <div class="row mb-4" id="topAccountsSection" style="display: none;">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Top Accounts by Balance</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="topAccountsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Trial Balance Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="loadingSpinner" class="text-center" style="display: none;">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="reportData" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="trialBalanceTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account Code</th>
                                                        <th>Account Name</th>
                                                        <th>Account Type</th>
                                                        <th class="text-right">Total Debits</th>
                                                        <th class="text-right">Total Credits</th>
                                                        <th class="text-right">Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="trialBalanceTableBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let accountTypeChart, balanceDistributionChart, topAccountsChart;

$(document).ready(function() {
    // Set default dates
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(firstDay.toISOString().split('T')[0]);
    $('#end_date').val(today.toISOString().split('T')[0]);
    
    // Form submission
    $('#trialBalanceForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
});

function generateReport() {
    const formData = new FormData($('#trialBalanceForm')[0]);
    
    // Show loading spinner
    $('#loadingSpinner').show();
    $('#reportData').hide();
    $('#noDataMessage').hide();
    $('#summaryCards').hide();
    $('#chartsSection').hide();
    $('#topAccountsSection').hide();

    console.log('Sending request to:', APP_URL + '/api/trial-balance-report/generate');

    $.ajax({
        url: APP_URL + '/api/trial-balance-report/generate',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#loadingSpinner').hide();
            console.log('Success callback triggered');
            console.log('Response type:', typeof response);
            console.log('Raw response:', response);
            
            // Parse JSON response
            let parsedResponse;
            try {
                if (typeof response === 'string') {
                    parsedResponse = JSON.parse(response);
                } else {
                    parsedResponse = response;
                }
                console.log('Parsed response:', parsedResponse);
            } catch (e) {
                console.error('Error parsing response:', e);
                showAlert('error', 'Invalid response format');
                return;
            }
            
            console.log('parsedResponse.success:', parsedResponse.success);
            console.log('parsedResponse.success === true:', parsedResponse.success === true);
            
            if (parsedResponse && parsedResponse.success === true) {
                console.log('Calling displayReport');
                displayReport(parsedResponse);
            } else {
                console.log('Showing error alert');
                showAlert('error', 'Error generating report: ' + (parsedResponse.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            console.error('Error callback triggered');
            console.error('AJAX Error:', xhr.responseText);
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response headers:', xhr.getAllResponseHeaders());
            showAlert('error', 'Error generating report: ' + error);
        }
    });
}

function displayReport(response) {
    // Update summary cards
    $('#totalAccounts').text(response.summary.total_accounts);
    $('#totalDebits').text('₱' + parseFloat(response.summary.total_debits).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#totalCredits').text('₱' + parseFloat(response.summary.total_credits).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#netBalance').text('₱' + parseFloat(response.summary.net_balance).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#summaryCards').show();
    
    // Update data table
    updateDataTable(response.data);
    $('#reportData').show();
    
    // Update charts
    updateCharts(response.charts);
    $('#chartsSection').show();
    $('#topAccountsSection').show();
}

function updateDataTable(data) {
    const tbody = $('#trialBalanceTableBody');
    tbody.empty();
    
    data.forEach(function(row) {
        const tr = $('<tr>');
        tr.append('<td>' + row.account_code + '</td>');
        tr.append('<td>' + row.account_name + '</td>');
        tr.append('<td>' + row.account_type + '</td>');
        tr.append('<td class="text-right">₱' + parseFloat(row.total_debits).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
        tr.append('<td class="text-right">₱' + parseFloat(row.total_credits).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
        tr.append('<td class="text-right">₱' + parseFloat(row.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
        tbody.append(tr);
    });
}

function updateCharts(chartData) {
    // Account Type Chart
    if (accountTypeChart) {
        accountTypeChart.destroy();
    }
    
    const accountTypeCtx = document.getElementById('accountTypeChart').getContext('2d');
    accountTypeChart = new Chart(accountTypeCtx, {
        type: 'bar',
        data: {
            labels: chartData.byAccountType.map(item => item.account_type),
            datasets: [{
                label: 'Total Debits',
                data: chartData.byAccountType.map(item => parseFloat(item.total_debits)),
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Total Credits',
                data: chartData.byAccountType.map(item => parseFloat(item.total_credits)),
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Balance Distribution Chart
    if (balanceDistributionChart) {
        balanceDistributionChart.destroy();
    }
    
    const balanceDistributionCtx = document.getElementById('balanceDistributionChart').getContext('2d');
    balanceDistributionChart = new Chart(balanceDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: chartData.balanceDistribution.map(item => item.balance_type),
            datasets: [{
                data: chartData.balanceDistribution.map(item => parseInt(item.account_count)),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(108, 117, 125, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' accounts';
                        }
                    }
                }
            }
        }
    });
    
    // Top Accounts Chart
    if (topAccountsChart) {
        topAccountsChart.destroy();
    }
    
    const topAccountsCtx = document.getElementById('topAccountsChart').getContext('2d');
    topAccountsChart = new Chart(topAccountsCtx, {
        type: 'bar',
        data: {
            labels: chartData.topAccounts.map(item => item.account_code + ' - ' + item.account_name.substring(0, 20)),
            datasets: [{
                label: 'Balance Amount',
                data: chartData.topAccounts.map(item => parseFloat(item.balance_amount)),
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Balance: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function exportReport(format) {
    const formData = new FormData($('#trialBalanceForm')[0]);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    const url = APP_URL + '/api/trial-balance-report/export' + (format === 'pdf' ? 'PDF' : 'Excel') + '?' + params.toString();
    window.open(url, '_blank');
}

function showAlert(type, message) {
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('.card-body').first().prepend(alertHtml);
}
</script>