<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Income Statement Report</h4>
                    <p class="card-text">Generate and view income statement reports with filtering options</p>
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
                                    <form id="incomeStatementForm">
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
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Revenue</h5>
                                    <h3 id="totalRevenue">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Expenses</h5>
                                    <h3 id="totalExpenses">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Net Income</h5>
                                    <h3 id="netIncome">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Profit Margin</h5>
                                    <h3 id="profitMargin">0%</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4" id="chartsSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Revenue by Category</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueCategoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Expenses by Category</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="expenseCategoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend Chart -->
                    <div class="row mb-4" id="monthlyTrendSection" style="display: none;">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Monthly Trend</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlyTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Accounts Charts -->
                    <div class="row mb-4" id="topAccountsSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Top Revenue Accounts</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="topRevenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Top Expense Accounts</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="topExpenseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Tables -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Revenue Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="loadingSpinnerRevenue" class="text-center" style="display: none;">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="revenueData" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="revenueTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account Code</th>
                                                        <th>Account Name</th>
                                                        <th>Type</th>
                                                        <th class="text-right">Total Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="revenueTableBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Expense Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="loadingSpinnerExpense" class="text-center" style="display: none;">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="expenseData" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="expenseTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account Code</th>
                                                        <th>Account Name</th>
                                                        <th>Type</th>
                                                        <th class="text-right">Total Expense</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="expenseTableBody">
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
let revenueCategoryChart, expenseCategoryChart, monthlyTrendChart, topRevenueChart, topExpenseChart;

$(document).ready(function() {
    // Set default dates
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(firstDay.toISOString().split('T')[0]);
    $('#end_date').val(today.toISOString().split('T')[0]);
    
    // Form submission
    $('#incomeStatementForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
});

function generateReport() {
    const formData = new FormData($('#incomeStatementForm')[0]);
    
    // Show loading spinners
    $('#loadingSpinnerRevenue').show();
    $('#loadingSpinnerExpense').show();
    $('#revenueData').hide();
    $('#expenseData').hide();
    $('#summaryCards').hide();
    $('#chartsSection').hide();
    $('#monthlyTrendSection').hide();
    $('#topAccountsSection').hide();

    console.log('Sending request to:', APP_URL + '/api/income-statement-report/generate');

    $.ajax({
        url: APP_URL + '/api/income-statement-report/generate',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#loadingSpinnerRevenue').hide();
            $('#loadingSpinnerExpense').hide();
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
            $('#loadingSpinnerRevenue').hide();
            $('#loadingSpinnerExpense').hide();
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
    console.log('displayReport called with response:', response);
    
    // Update summary cards
    $('#totalRevenue').text('₱' + parseFloat(response.summary.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#totalExpenses').text('₱' + parseFloat(response.summary.total_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#netIncome').text('₱' + parseFloat(response.summary.net_income || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#profitMargin').text((response.ratios.profit_margin || 0).toFixed(2) + '%');
    $('#summaryCards').show();
    
    // Update data tables
    updateDataTables(response.data);
    $('#revenueData').show();
    $('#expenseData').show();
    
    // Update charts
    updateCharts(response.charts);
    $('#chartsSection').show();
    $('#monthlyTrendSection').show();
    $('#topAccountsSection').show();
}

function updateDataTables(data) {
    console.log('updateDataTables called with data:', data);
    
    // Update Revenue table
    const revenueTbody = $('#revenueTableBody');
    revenueTbody.empty();
    
    if (data.revenue && Array.isArray(data.revenue)) {
        data.revenue.forEach(function(row) {
            const tr = $('<tr>');
            tr.append('<td>' + (row.account_code || '') + '</td>');
            tr.append('<td>' + (row.account_name || '') + '</td>');
            tr.append('<td>' + (row.account_type || '') + '</td>');
            tr.append('<td class="text-right">₱' + parseFloat(row.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
            revenueTbody.append(tr);
        });
    }
    
    // Update Expense table
    const expenseTbody = $('#expenseTableBody');
    expenseTbody.empty();
    
    if (data.expenses && Array.isArray(data.expenses)) {
        data.expenses.forEach(function(row) {
            const tr = $('<tr>');
            tr.append('<td>' + (row.account_code || '') + '</td>');
            tr.append('<td>' + (row.account_name || '') + '</td>');
            tr.append('<td>' + (row.account_type || '') + '</td>');
            tr.append('<td class="text-right">₱' + parseFloat(row.total_expenses || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
            expenseTbody.append(tr);
        });
    }
}

function updateCharts(chartData) {
    console.log('updateCharts called with chartData:', chartData);
    
    // Revenue Category Chart
    if (revenueCategoryChart) {
        revenueCategoryChart.destroy();
    }
    
    const revenueCategoryCtx = document.getElementById('revenueCategoryChart').getContext('2d');
    revenueCategoryChart = new Chart(revenueCategoryCtx, {
        type: 'doughnut',
        data: {
            labels: (chartData.revenueByCategory || []).map(item => item.category),
            datasets: [{
                data: (chartData.revenueByCategory || []).map(item => parseFloat(item.total_revenue || 0)),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
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
                            return context.label + ': ₱' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Expense Category Chart
    if (expenseCategoryChart) {
        expenseCategoryChart.destroy();
    }
    
    const expenseCategoryCtx = document.getElementById('expenseCategoryChart').getContext('2d');
    expenseCategoryChart = new Chart(expenseCategoryCtx, {
        type: 'doughnut',
        data: {
            labels: (chartData.expensesByCategory || []).map(item => item.category),
            datasets: [{
                data: (chartData.expensesByCategory || []).map(item => parseFloat(item.total_expenses || 0)),
                backgroundColor: [
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(40, 167, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(40, 167, 69, 1)'
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
                            return context.label + ': ₱' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Monthly Trend Chart
    if (monthlyTrendChart) {
        monthlyTrendChart.destroy();
    }
    
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    monthlyTrendChart = new Chart(monthlyTrendCtx, {
        type: 'line',
        data: {
            labels: (chartData.monthlyTrend || []).map(item => item.month),
            datasets: [{
                label: 'Revenue',
                data: (chartData.monthlyTrend || []).map(item => parseFloat(item.revenue || 0)),
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Expenses',
                data: (chartData.monthlyTrend || []).map(item => parseFloat(item.expenses || 0)),
                borderColor: 'rgba(220, 53, 69, 1)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Net Income',
                data: (chartData.monthlyTrend || []).map(item => parseFloat(item.net_income || 0)),
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true
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
    
    // Top Revenue Chart
    if (topRevenueChart) {
        topRevenueChart.destroy();
    }
    
    const topRevenueCtx = document.getElementById('topRevenueChart').getContext('2d');
    topRevenueChart = new Chart(topRevenueCtx, {
        type: 'bar',
        data: {
            labels: (chartData.topRevenueAccounts || []).map(item => item.account_code + ' - ' + (item.account_name || '').substring(0, 15)),
            datasets: [{
                label: 'Revenue',
                data: (chartData.topRevenueAccounts || []).map(item => parseFloat(item.total_revenue || 0)),
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
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
                            return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Top Expense Chart
    if (topExpenseChart) {
        topExpenseChart.destroy();
    }
    
    const topExpenseCtx = document.getElementById('topExpenseChart').getContext('2d');
    topExpenseChart = new Chart(topExpenseCtx, {
        type: 'bar',
        data: {
            labels: (chartData.topExpenseAccounts || []).map(item => item.account_code + ' - ' + (item.account_name || '').substring(0, 15)),
            datasets: [{
                label: 'Expense',
                data: (chartData.topExpenseAccounts || []).map(item => parseFloat(item.total_expenses || 0)),
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: 'rgba(220, 53, 69, 1)',
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
                            return 'Expense: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function exportReport(format) {
    const formData = new FormData($('#incomeStatementForm')[0]);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    const url = APP_URL + '/api/income-statement-report/export' + (format === 'pdf' ? 'PDF' : 'Excel') + '?' + params.toString();
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