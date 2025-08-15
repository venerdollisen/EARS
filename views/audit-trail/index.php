<?php
// Audit Trail View
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="bi bi-shield-check me-2"></i>
                        Audit Trail
                    </h4>
                    <p class="card-subtitle text-muted">Track all system activities and changes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filterAction" class="form-label">Action</label>
                            <select class="form-select" id="filterAction">
                                <option value="">All Actions</option>
                                <option value="CREATE">Create</option>
                                <option value="UPDATE">Update</option>
                                <option value="DELETE">Delete</option>
                                <option value="LOGIN">Login</option>
                                <option value="LOGOUT">Logout</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filterTable" class="form-label">Table</label>
                            <select class="form-select" id="filterTable">
                                <option value="">All Tables</option>
                                <option value="transactions">Transactions</option>
                                <option value="chart_of_accounts">Chart of Accounts</option>
                                <option value="projects">Projects</option>
                                <option value="departments">Departments</option>
                                <option value="suppliers">Suppliers</option>
                                <option value="users">Users</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filterDateFrom" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="filterDateFrom">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filterDateTo" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="filterDateTo">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="filterRecordId" class="form-label">Record ID</label>
                            <input type="number" class="form-control" id="filterRecordId" placeholder="Record ID">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary" onclick="loadAuditTrail()">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        Audit Trail Log
                    </h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" onclick="exportAuditTrail()">
                            <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export CSV</span>
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="loadAuditTrail()">
                            <i class="bi bi-arrow-clockwise me-1"></i><span class="d-none d-sm-inline">Refresh</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="auditTrailTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 140px;">Date/Time</th>
                                    <th style="min-width: 100px;">User</th>
                                    <th style="min-width: 80px;">Action</th>
                                    <th style="min-width: 120px;">Table</th>
                                    <th style="min-width: 80px;">Record ID</th>
                                    <th style="min-width: 100px;">IP Address</th>
                                    <th style="min-width: 80px;">Details</th>
                                </tr>
                            </thead>
                            <tbody id="auditTrailTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="auditTrailPagination" class="d-flex justify-content-center mt-3">
                        <!-- Pagination will be added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail Details Modal -->
    <div class="modal fade" id="auditDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Audit Trail Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Old Values</h6>
                            <pre id="oldValues" class="bg-light p-3 rounded"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>New Values</h6>
                            <pre id="newValues" class="bg-light p-3 rounded"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const itemsPerPage = 25;
let totalCount = 0;

// Initialize audit trail on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAuditTrail();
});

function loadAuditTrail(page = 1) {
    currentPage = page;
    
    const filters = {
        action: document.getElementById('filterAction').value,
        table_name: document.getElementById('filterTable').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        record_id: document.getElementById('filterRecordId').value,
        limit: itemsPerPage,
        offset: (page - 1) * itemsPerPage
    };
    
    // Build query string
    const queryString = Object.keys(filters)
        .filter(key => filters[key] !== '' && filters[key] !== null)
        .map(key => `${key}=${encodeURIComponent(filters[key])}`)
        .join('&');
    
    fetch(`${APP_URL}/api/audit-trail/get?${queryString}`, { cache: 'no-store', headers: { 'Cache-Control': 'no-cache' } })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                totalCount = data.total_count || 0;
                displayAuditTrail(data.data);
            } else {
                showAlert('Error loading audit trail: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load audit trail', 'danger');
        });
}

function displayAuditTrail(data) {
    const tbody = document.getElementById('auditTrailTableBody');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No audit trail entries found</td></tr>';
        document.getElementById('auditTrailPagination').innerHTML = '';
        return;
    }
    
    data.forEach(item => {
        const row = document.createElement('tr');
        const oldEnc = encodeURIComponent(item.old_values || '');
        const newEnc = encodeURIComponent(item.new_values || '');
        row.innerHTML = `
            <td>${formatDateTime(item.created_at)}</td>
            <td>${item.user_name || 'Unknown'}</td>
            <td><span class="badge bg-${getActionBadgeColor(item.action)}">${item.action}</span></td>
            <td>${item.table_name}</td>
            <td>${item.record_id}</td>
            <td><small>${item.ip_address}</small></td>
            <td>
                <button class="btn btn-sm btn-outline-info" onclick="showAuditDetailsEncoded('${oldEnc}', '${newEnc}')">
                    <i class="bi bi-eye"></i> Details
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Add pagination controls
    displayPagination();
}

function displayPagination() {
    const paginationContainer = document.getElementById('auditTrailPagination');
    const totalPages = Math.ceil(totalCount / itemsPerPage);
    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, totalCount);
    
    if (totalCount === 0) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    paginationContainer.innerHTML = `
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center w-100 gap-2">
            <div class="text-muted text-center text-md-start">
                <small class="d-block d-md-inline">Showing ${startItem}-${endItem} of ${totalCount}</small>
                <small class="d-block d-md-inline ms-md-2">(Page ${currentPage} of ${totalPages})</small>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadAuditTrail(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline">Previous</span>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadAuditTrail(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>
                    <span class="d-none d-sm-inline">Next</span> <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    `;
}

function getActionBadgeColor(action) {
    switch (action) {
        case 'CREATE': return 'success';
        case 'UPDATE': return 'warning';
        case 'DELETE': return 'danger';
        case 'LOGIN': return 'info';
        case 'LOGOUT': return 'secondary';
        default: return 'primary';
    }
}

function formatDateTime(dateTime) {
    const date = new Date(dateTime);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function showAuditDetails(oldValues, newValues) {
    const oldValuesElement = document.getElementById('oldValues');
    const newValuesElement = document.getElementById('newValues');
    
    try {
        let oldData = null;
        let newData = null;
        if (oldValues && oldValues !== 'null') {
            try { oldData = JSON.parse(oldValues); } catch (_) { /* ignore */ }
        }
        if (newValues && newValues !== 'null') {
            try { newData = JSON.parse(newValues); } catch (_) { /* ignore */ }
        }

        oldValuesElement.textContent = oldData ? JSON.stringify(oldData, null, 2) : (oldValues ? oldValues : 'No old values');
        newValuesElement.textContent = newData ? JSON.stringify(newData, null, 2) : (newValues ? newValues : 'No new values');
        
        const modal = new bootstrap.Modal(document.getElementById('auditDetailsModal'));
        modal.show();
    } catch (error) {
        console.error('Error parsing audit details:', error);
        showAlert('Error displaying audit details', 'danger');
    }
}

function showAuditDetailsEncoded(encodedOld, encodedNew) {
    const oldDecoded = encodedOld ? decodeURIComponent(encodedOld) : '';
    const newDecoded = encodedNew ? decodeURIComponent(encodedNew) : '';
    showAuditDetails(oldDecoded, newDecoded);
}

function exportAuditTrail() {
    const filters = {
        action: document.getElementById('filterAction').value,
        table_name: document.getElementById('filterTable').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        record_id: document.getElementById('filterRecordId').value
    };
    
    // Build query string
    const queryString = Object.keys(filters)
        .filter(key => filters[key] !== '' && filters[key] !== null)
        .map(key => `${key}=${encodeURIComponent(filters[key])}`)
        .join('&');
    
    // Create download link
    const link = document.createElement('a');
    link.href = `${APP_URL}/api/audit-trail/export?${queryString}`;
    link.download = `audit_trail_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Use EARS.showAlert instead of local showAlert function
function showAlert(message, type) {
    if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert(message, type);
    } else {
        // Fallback for when EARS is not available
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove after 2.5 seconds to match EARS.showAlert
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 2500);
    }
}
</script>

<style>
pre {
    font-size: 0.8em;
    max-height: 200px;
    overflow-y: auto;
}

.badge {
    font-size: 0.75em;
}

.table th {
    font-size: 0.9em;
    font-weight: 600;
}

.table td {
    font-size: 0.9em;
    vertical-align: middle;
}

/* Mobile responsiveness improvements */
@media (max-width: 768px) {
    .table th,
    .table td {
        font-size: 0.8em;
        padding: 0.5rem 0.25rem;
    }
    
    .table th {
        white-space: nowrap;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .card-header h5 {
        font-size: 1.1rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
    
    .form-select,
    .form-control {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .table-responsive {
        font-size: 0.75em;
    }
    
    .table th,
    .table td {
        padding: 0.25rem 0.125rem;
    }
    
    .badge {
        font-size: 0.7em;
    }
    
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
}
</style> 