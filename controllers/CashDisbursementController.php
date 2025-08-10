<?php

require_once 'core/Controller.php';
require_once 'models/CashDisbursementModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'models/NotificationModel.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';

class CashDisbursementController extends Controller {
    use AuditTrailTrait;
    private $cashDisbursementModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->cashDisbursementModel = new CashDisbursementModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
        $this->notificationModel = new NotificationModel();
    }
    
    /**
     * Display cash disbursement entry form
     */
    public function index() {
        $this->requireAuth();
        
        // Get accounts for dropdown
        $accounts = $this->chartOfAccountsModel->getAllAccounts();
        
        // Get suppliers for subsidiary accounts
        $suppliers = $this->supplierModel->getAllSuppliers();
        
        // Get projects and departments for dropdowns
        $projects = $this->projectModel->getActiveProjects();
        $departments = $this->departmentModel->getActiveDepartments();
        
        // Get recent cash disbursements
        $transactions = $this->cashDisbursementModel->getRecentCashDisbursements();
        
        $this->render('transaction-entries/cash-disbursement', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'projects' => $projects,
            'departments' => $departments,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    /**
     * Save cash disbursement transaction
     */
    public function save() {
        $this->requireAuth();
        
        try {
            $data = $this->getRequestData();
            
            // Normalize expected fields
            if (empty($data['voucher_number']) && empty($data['reference_no'])) {
                throw new Exception('Voucher number is required');
            }
            if (empty($data['transaction_date'])) {
                throw new Exception('Transaction date is required');
            }
            if (empty($data['account_distribution']) || !is_array($data['account_distribution'])) {
                throw new Exception('Account distribution is required');
            }
            
            // Create cash disbursement via model (model enforces balance and computes amount)
            // Enforce default Pending statuses for assistants/users
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user','assistant'])) {
                    $data['cv_status'] = 'Pending';
                    $data['cv_checked'] = 'Pending';
                    $data['check_payment_status'] = 'Pending';
                }
            } catch (Exception $e) {}

            $result = $this->cashDisbursementModel->createCashDisbursement($data);
            // Audit trail
            try { $this->logCreate('transactions', (int)$result, $data); } catch (Exception $e) {}

            // Notifications for admins/managers/accountants when created by assistant/user
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user', 'assistant'])) {
                    $voucher = $data['voucher_number'] ?? $data['reference_no'] ?? 'CV';
                    $title = 'Cash Disbursement Pending Approval';
                    $msg = sprintf('A cash disbursement (CV: %s) was created by %s and is pending review.', $voucher, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User');
                    $link = APP_URL . '/transaction-entries/cash-disbursement?id=' . urlencode((string)$result);
                    $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($recipients as $r) {
                        $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                    }
                }
            } catch (Exception $notifyEx) {
                error_log('Notification error (cash disbursement save): ' . $notifyEx->getMessage());
            }
            
            // Create notifications for admins/managers when created by assistant/user
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user', 'assistant'])) {
                    $voucher = $data['voucher_number'] ?? $data['reference_no'] ?? 'CV';
                    $title = 'Cash Disbursement Pending Approval';
                    $msg = sprintf('A cash disbursement (CV: %s) was created by %s and is pending review.', $voucher, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User');
                    $link = APP_URL . '/transaction-entries/cash-disbursement';
                    // Fetch admin/manager recipients
                    $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($recipients as $r) {
                        $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                    }
                }
            } catch (Exception $notifyEx) {
                // Do not fail the save if notifications fail; optionally log
                error_log('Notification error (cash disbursement save): ' . $notifyEx->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cash disbursement saved successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash disbursement by ID
     */
    public function get($id) {
        $this->requireAuth();
        
        try {
            $transaction = $this->cashDisbursementModel->getCashDisbursementById($id);
            
            if (!$transaction) {
                throw new Exception('Cash disbursement not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transaction
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update cash disbursement transaction
     */
    public function update($id) {
        $this->requireAuth();
        
        try {
            $data = $_POST;
            
            // Validate required fields
            if (empty($data['reference_no']) || empty($data['amount']) || empty($data['accounts'])) {
                throw new Exception('Missing required fields');
            }
            
            // Update cash disbursement
            $result = $this->cashDisbursementModel->updateCashDisbursement($id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cash disbursement updated successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete cash disbursement transaction
     */
    public function delete($id) {
        $this->requireAuth();
        
        try {
            $result = $this->cashDisbursementModel->deleteCashDisbursement($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cash disbursement deleted successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent cash disbursements for AJAX
     */
    public function recent() {
        $this->requireAuth();
        
        try {
            $transactions = $this->cashDisbursementModel->getRecentCashDisbursements();
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch recent cash disbursements: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash disbursement statistics
     */
    public function stats() {
        $this->requireAuth();
        
        try {
            $stats = $this->cashDisbursementModel->getCashDisbursementStats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get disbursements by status
     */
    public function byStatus($status) {
        $this->requireAuth();
        
        try {
            $transactions = $this->cashDisbursementModel->getDisbursementsByStatus($status);
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch disbursements: ' . $e->getMessage()
            ]);
        }
    }
} 