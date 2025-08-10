<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Project Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="openProjectModal()">
                    <i class="bi bi-plus-circle me-2"></i>Add New Project
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Projects List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="projectsTable">
                            <thead>
                                <tr>
                                    <th>Project Code</th>
                                    <th>Project Name</th>
                                    <th>Manager</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($project['project_code']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($project['project_name']) ?></td>
                                            <td><?= htmlspecialchars($project['manager'] ?? '-') ?></td>
                                            <td><?= $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '-' ?></td>
                                            <td><?= $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '-' ?></td>
                                            <td class="text-end">₱<?= number_format($project['budget'], 2) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'bg-secondary';
                                                switch ($project['status']) {
                                                    case 'active':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'bg-info';
                                                        break;
                                                    case 'on_hold':
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                    case 'inactive':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editProject(<?= $project['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProject(<?= $project['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No projects found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalLabel">Add New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="projectForm">
                <div class="modal-body">
                    <input type="hidden" id="projectId" name="id">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="table" value="projects">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="projectCode" class="form-label">Project Code *</label>
                                <input type="text" class="form-control" id="projectCode" name="project_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="projectName" class="form-label">Project Name *</label>
                                <input type="text" class="form-control" id="projectName" name="project_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="projectDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="projectDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="projectBudget" class="form-label">Budget</label>
                                <input type="number" class="form-control" id="projectBudget" name="budget" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="projectManager" class="form-label">Manager</label>
                                <input type="text" class="form-control" id="projectManager" name="manager">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="projectStatus" class="form-label">Status</label>
                        <select class="form-select" id="projectStatus" name="status">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Project</button>
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
    $('#projectsTable').DataTable({
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
    $('#projectForm').on('submit', function(e) {
        e.preventDefault();
        saveProject();
    });
});

function openProjectModal() {
    $('#projectModalLabel').text('Add New Project');
    $('#projectForm')[0].reset();
    $('#projectId').val('');
    $('input[name="action"]').val('create');
}

function editProject(id) {
    // Fetch project data and populate form
    $.ajax({
        url: APP_URL + '/api/file-maintenance/get-project/' + id,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const project = response.data;
                $('#projectModalLabel').text('Edit Project');
                $('#projectId').val(project.id);
                $('#projectCode').val(project.project_code);
                $('#projectName').val(project.project_name);
                $('#projectDescription').val(project.description);
                $('#startDate').val(project.start_date);
                $('#endDate').val(project.end_date);
                $('#projectBudget').val(project.budget);
                $('#projectManager').val(project.manager);
                $('#projectStatus').val(project.status);
                $('input[name="action"]').val('update');
                $('#projectModal').modal('show');
            } else {
                EARS.showAlert('Failed to load project data', 'danger');
            }
        },
        error: function() {
            EARS.showAlert('Failed to load project data', 'danger');
        }
    });
}

function saveProject() {
    const formData = new FormData($('#projectForm')[0]);
    const data = Object.fromEntries(formData.entries());
    
    $.ajax({
        url: APP_URL + '/api/file-maintenance/save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                EARS.showAlert('Project saved successfully!', 'success');
                $('#projectModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                EARS.showAlert(response.error || 'Failed to save project', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            EARS.showAlert(response?.error || 'Failed to save project', 'danger');
        }
    });
}

function deleteProject(id) {
    if (confirm('Are you sure you want to delete this project?')) {
        $.ajax({
            url: APP_URL + '/api/file-maintenance/delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                table: 'projects',
                id: id
            }),
            success: function(response) {
                if (response.success) {
                    EARS.showAlert('Project deleted successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    EARS.showAlert(response.error || 'Failed to delete project', 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                EARS.showAlert(response?.error || 'Failed to delete project', 'danger');
            }
        });
    }
}
</script> 