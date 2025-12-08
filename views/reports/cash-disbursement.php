<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Cash Disbursement Report</h4>
                    <p class="card-text">Generate and view cash disbursement reports with filtering options</p>
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
                                    <form id="reportForm">
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
                                                    <label for="supplier_id">Supplier</label>
                                                    <select class="form-control" id="supplier_id" name="supplier_id">
                                                        <option value="">All Suppliers</option>
                                                        <?php foreach ($suppliers as $supplier): ?>
                                                            <option value="<?= $supplier['id'] ?>"><?= $supplier['supplier_name'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="department_id">Department</label>
                                                    <select class="form-control" id="department_id" name="department_id">
                                                        <option value="">All Departments</option>
                                                        <?php foreach ($departments as $department): ?>
                                                            <option value="<?= $department['id'] ?>"><?= $department['department_name'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="payment_form">Payment Form</label>
                                                    <select class="form-control" id="payment_form" name="payment_form">
                                                        <option value="">All Payment Forms</option>
                                                        <?php foreach ($paymentForms as $form): ?>
                                                            <option value="<?= $form['id'] ?>"><?= $form['name'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="status">Status</label>
                                                    <select class="form-control" id="status" name="status">
                                                        <option value="">All Statuses</option>
                                                        <?php foreach ($statuses as $status): ?>
                                                            <option value="<?= $status['id'] ?>"><?= $status['name'] ?></option>
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
                                               <!--  <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                                                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                                                </button> -->
                                                <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
                                                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                                </button>
                                                <!-- <button type="button" class="btn btn-secondary" onclick="testReport()">
                                                    <i class="bi bi-bug"></i> Test Report
                                                </button> -->
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4" id="summaryCards" style="display: none;">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Transactions</h5>
                                    <h3 id="totalTransactions">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Amount</h5>
                                    <h3 id="totalAmount">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                      <!--   <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Average Amount</h5>
                                    <h3 id="averageAmount">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Max Amount</h5>
                                    <h3 id="maxAmount">₱0.00</h3>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4" id="chartsSection" style="display: none;">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Payment Form Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="paymentFormChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Account Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="accountChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
              <!--       <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Report Data</h5>
                                </div>
                                <div class="card-body">
                                    <div id="loadingSpinner" class="text-center" style="display: none;">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="reportData" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="reportTable">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Reference No</th>
                                                        <th>Account</th>
                                                        <th>Supplier</th>
                                                        <th>Amount</th>
                                                        <th>Payment Form</th>
                                                        <th>Status</th>
                                                        <th>Created By</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reportTableBody">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div id="noDataMessage" class="text-center" style="display: none;">
                                        <p class="text-muted">No data found for the selected criteria.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Set default dates
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(firstDay.toISOString().split('T')[0]);
    $('#end_date').val(today.toISOString().split('T')[0]);

    // Handle form submission
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
});

function generateReport() {
    const formData = new FormData($('#reportForm')[0]);
    
    // Show loading spinner
    $('#loadingSpinner').show();
    $('#reportData').hide();
    $('#noDataMessage').hide();
    $('#summaryCards').hide();
    $('#chartsSection').hide();

    $.ajax({
        url: APP_URL + '/api/cash-disbursement-report/generate',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#loadingSpinner').hide();
            
            // Parse JSON response
            let parsedResponse;
            try {
                if (typeof response === 'string') {
                    parsedResponse = JSON.parse(response);
                } else {
                    parsedResponse = response;
                }

            } catch (e) {
                console.error('Error parsing response:', e);
                showAlert('error', 'Invalid response format');
                return;
            }

            
            if (parsedResponse && parsedResponse.success === true) {
                console.log('Calling displayReportData with:', parsedResponse);
                displayReportData(parsedResponse);
            } else {
                console.log('Showing error alert');
                showAlert('error', 'Error generating report: ' + (parsedResponse.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();

            showAlert('error', 'Error generating report: ' + error);
        }
    });
}

function displayReportData(response) {

    if (response.data && response.data.length > 0) {
        // Display summary cards
        if (response.summary) {

            $('#totalTransactions').text(response.summary.total_transactions || 0);
            $('#totalAmount').text(parseFloat(response.summary.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#averageAmount').text(parseFloat(response.summary.average_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#maxAmount').text(parseFloat(response.summary.max_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
            $('#summaryCards').show();
            console.log('Summary cards displayed');
        }
        
        tbody.empty();

        
        response.data.forEach(function(row, index) {
);
            const tr = $('<tr>');
            tr.append('<td>' + formatDate(row.transaction_date) + '</td>');
            tr.append('<td>' + (row.reference_number || '') + '</td>');
            tr.append('<td>' + (row.account_code || '') + ' - ' + (row.account_name || '') + '</td>');
            tr.append('<td>' + (row.supplier_name || '') + '</td>');
            tr.append('<td class="text-end">' + parseFloat(row.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</td>');
            tr.append('<td>' + (row.payment_form || '') + '</td>');
            tr.append('<td><span class="badge bg-' + getStatusColor(row.status) + '">' + (row.status || '') + '</span></td>');
            tr.append('<td>' + (row.created_by || '') + '</td>');
            tbody.append(tr);

        });
        
        $('#reportData').show();
    } else {
        console.log('No data found, showing no data message');
        $('#noDataMessage').show();
    }
}

function createPaymentFormChart(data) {
    const ctx = document.getElementById('paymentFormChart').getContext('2d');
    
    // Safely destroy existing chart
    if (window.paymentFormChart && typeof window.paymentFormChart.destroy === 'function') {
        window.paymentFormChart.destroy();
    }
    
    window.paymentFormChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => item.payment_form || 'Unknown'),
            datasets: [{
                data: data.map(item => parseFloat(item.total_amount || 0)),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + parseFloat(context.parsed).toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
}

function createAccountChart(data) {
    const ctx = document.getElementById('accountChart').getContext('2d');
    
    // Safely destroy existing chart
    if (window.accountChart && typeof window.accountChart.destroy === 'function') {
        window.accountChart.destroy();
    }
    
    window.accountChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => (item.account_code || '') + ' - ' + (item.account_name || 'Unknown')),
            datasets: [{
                label: 'Total Amount',
                data: data.map(item => parseFloat(item.total_amount || 0)),
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Total Amount: ' + parseFloat(context.parsed.y).toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
}

function exportReport(format) {
    // Get form data
    const formData = new FormData($('#reportForm')[0]);
    
    // Build URL parameters
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    // Create export URL
    const url = APP_URL + '/api/cash-disbursement-report/export' + (format === 'pdf' ? 'PDF' : 'Excel') + '?' + params.toString();
    
    // Open in new window/tab
    window.open(url, '_blank');
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US');
}

function getStatusColor(status) {
    switch (status?.toLowerCase()) {
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'rejected': return 'danger';
        case 'completed': return 'info';
        default: return 'secondary';
    }
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
