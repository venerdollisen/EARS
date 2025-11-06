<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Department Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="openDepartmentModal()">
                    <i class="bi bi-plus-circle me-2"></i>Add New Department
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Departments List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="departmentsTable">
                            <thead>
                                <tr>
                                    <th>Department Code</th>
                                    <th>Department Name</th>
                                    <th>Manager</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $department): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($department['department_code']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($department['department_name']) ?></td>
                                            <td><?= htmlspecialchars($department['manager'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($department['location'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = $department['status'] === 'active' ? 'bg-success' : 'bg-danger';
                                                ?>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= ucfirst($department['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editDepartment(<?= $department['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?= $department['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                   <!--  <tr>
                                        <td colspan="5" class="text-center text-muted">No departments found</td>
                                    </tr> -->
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="departmentForm">
                <div class="modal-body">
                    <input type="hidden" id="departmentId" name="id">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="table" value="departments">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departmentCode" class="form-label">Department Code *</label>
                                <input type="text" class="form-control" id="departmentCode" name="department_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departmentName" class="form-label">Department Name *</label>
                                <input type="text" class="form-control" id="departmentName" name="department_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="departmentDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departmentManager" class="form-label">Manager</label>
                                <input type="text" class="form-control" id="departmentManager" name="manager">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departmentLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="departmentLocation" name="location">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentStatus" class="form-label">Status</label>
                        <select class="form-select" id="departmentStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#departmentsTable').DataTable({
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
    
    // Form submission
    $('#departmentForm').on('submit', function(e) {
        e.preventDefault();
        saveDepartment();
    });
});

function openDepartmentModal() {
    $('#departmentModalLabel').text('Add New Department');
    $('#departmentForm')[0].reset();
    $('#departmentId').val('');
    $('input[name="action"]').val('create');
}

function editDepartment(id) {
    // Fetch department data and populate form
    $.ajax({
        url: APP_URL + '/api/file-maintenance/get-department/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const department = response.data;
                $('#departmentModalLabel').text('Edit Department');
                $('#departmentId').val(department.id);
                $('#departmentCode').val(department.department_code);
                $('#departmentName').val(department.department_name);
                $('#departmentDescription').val(department.description);
                $('#departmentManager').val(department.manager);
                $('#departmentLocation').val(department.location);
                $('#departmentStatus').val(department.status);
                $('input[name="action"]').val('update');
                $('#departmentModal').modal('show');
            } else {
                EARS.showAlert('Failed to load department data', 'danger');
            }
        },
        error: function() {
            EARS.showAlert('Failed to load department data', 'danger');
        }
    });
}

function saveDepartment() {
    const formData = new FormData($('#departmentForm')[0]);
    const data = Object.fromEntries(formData.entries());
    
    $.ajax({
        url: APP_URL + '/api/file-maintenance/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Department saved successfully!', 'success');
                $('#departmentModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                EARS.showAlert(response.error || 'Failed to save department', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save department', 'danger');
        }
    });
}

function deleteDepartment(id) {
    if (confirm('Are you sure you want to delete this department?')) {
        $.ajax({
            url: APP_URL + '/api/file-maintenance/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                table: 'departments',
                id: id
            }),
            success: function(response) {
                if (response.success) {
                    EARS.showAlert('Department deleted successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    EARS.showAlert(response.error || 'Failed to delete department', 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                EARS.showAlert(response?.error || 'Failed to delete department', 'danger');
            }
        });
    }
}
</script> 