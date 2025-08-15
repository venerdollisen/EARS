<?php
// Helper functions for role-based access control
function isAssistant($user) {
    return ($user['role'] ?? '') === 'user';
}

function isAdmin($user) {
    return ($user['role'] ?? '') === 'admin';
}

function isManager($user) {
    return ($user['role'] ?? '') === 'manager';
}

function hasPermission($user, $permission) {
    $role = $user['role'] ?? '';
    
    switch ($permission) {
        case 'file_maintenance':
        case 'parameters':
        case 'user_management':
        case 'audit_trail':
        case 'system_settings':
            return in_array($role, ['admin', 'manager']);
        case 'transaction_entries':
        case 'reports':
        case 'summary':
        case 'profile_settings':
            return true; // All roles can access
        default:
            return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery UI CSS -->
    <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet" />
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet" />

    <script>
        const APP_URL = "<?= addslashes(APP_URL) ?>";
    </script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'views/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'views/partials/topnav.php'; ?>

            <!-- Global alert container fixed to top -->
            <div id="globalAlertContainer" class="container-fluid position-sticky top-0" style="z-index: 1080;"></div>

            <!-- Page Content Container -->
            <div class="container-fluid">
                <!-- Load JS libs first so inline scripts inside $content can use them -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

                <!-- Output dynamic page content here -->
                <?= $content ?>
            </div>

            <!-- Footer -->
            <?php include 'views/partials/footer.php'; ?>
        </div>
    </div>

    <!-- Your app.js after everything else -->
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
