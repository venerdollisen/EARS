<?php
// Ensure $user variable is always defined
if (!isset($user)) {
    $user = [];
}
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h3 class="sidebar-brand">
            <i class="bi bi-graph-up"></i>
            EARS
        </h3>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="<?=APP_URL?>/dashboard" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if (hasPermission($user, 'parameters')): ?>
        <li class="nav-item">
            <a href="#parametersSubmenu" data-bs-toggle="collapse" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/parameters') !== false ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Parameters</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav flex-column <?= strpos($_SERVER['REQUEST_URI'], '/parameters') !== false ? 'show' : '' ?>" id="parametersSubmenu">
                <li class="nav-item">
                    <a href="<?=APP_URL?>/parameters/accounting" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Accounting Parameters</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (hasPermission($user, 'file_maintenance')): ?>
        <li class="nav-item">
            <a href="#fileMaintenanceSubmenu" data-bs-toggle="collapse" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/file-maintenance') !== false ? 'active' : '' ?>">
                <i class="bi bi-folder"></i>
                <span>File Maintenance</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav flex-column <?= strpos($_SERVER['REQUEST_URI'], '/file-maintenance') !== false ? 'show' : '' ?>" id="fileMaintenanceSubmenu">
                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/coa-account-type" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>COA Account Type</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/account-title-group" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Account Title Group</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/chart-of-accounts" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Chart of Accounts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/subsidiary-account" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Subsidiary Account (Suppliers)</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/projects" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Projects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/file-maintenance/departments" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Departments</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission($user, 'transaction_entries')): ?>
        <li class="nav-item">
            <a href="#transactionSubmenu" data-bs-toggle="collapse" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/transaction-entries') !== false ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span>Transaction Management</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav flex-column <?= strpos($_SERVER['REQUEST_URI'], '/transaction-entries') !== false ? 'show' : '' ?>" id="transactionSubmenu">
                <li class="nav-item">
                    <a href="<?=APP_URL?>/transaction-entries/cash-receipt" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Cash Receipt</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/transaction-entries/disbursement" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Cash Disbursement</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/transaction-entries/check-disbursement" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Check Disbursement</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/journal-entries" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Journal Entries</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <?php if (hasPermission($user, 'user_management')): ?>
        <li class="nav-item">
            <a href="<?=APP_URL?>/users" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>User Management</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission($user, 'summary')): ?>
        <li class="nav-item">
            <a href="<?=APP_URL?>/summary" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/summary') !== false ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i>
                <span>Summary</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (hasPermission($user, 'reports')): ?>
        <li class="nav-item">
            <a href="#reportsSubmenu" data-bs-toggle="collapse" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/reports') !== false || strpos($_SERVER['REQUEST_URI'], '-report') !== false ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Reports</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav flex-column <?= strpos($_SERVER['REQUEST_URI'], '/reports') !== false || strpos($_SERVER['REQUEST_URI'], '-report') !== false ? 'show' : '' ?>" id="reportsSubmenu">
              
                <li class="nav-item">
                    <a href="<?=APP_URL?>/cash-receipt-report" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Cash Receipt Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/cash-disbursement-report" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Cash Disbursement Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/check-disbursement-report" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Check Disbursement Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/trial-balance-report" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Trial Balance Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/income-statement-report" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Income Statement Report</span>
                    </a>
                </li>
                
            </ul>
        </li>
        <?php endif; ?>

        <?php if (hasPermission($user, 'audit_trail')): ?>
        <li class="nav-item">
            <a href="<?=APP_URL?>/audit-trail" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/audit-trail') !== false ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i>
                <span>Audit Trail</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>Settings</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul class="collapse nav flex-column <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'show' : '' ?>" id="settingsSubmenu">
                <li class="nav-item">
                    <a href="<?=APP_URL?>/settings/profile" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <?php if (hasPermission($user, 'system_settings')): ?>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/settings/general" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>General Settings</span>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="<?=APP_URL?>/settings/security" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Security Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/settings/backup" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Backup & Recovery</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?=APP_URL?>/settings/notifications" class="nav-link">
                        <i class="bi bi-circle"></i>
                        <span>Notifications</span>
                    </a>
                </li> -->
                <?php endif; ?>
            </ul>
        </li>
    </ul>
</nav> 