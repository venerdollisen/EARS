<?php

require_once 'core/Controller.php';
require_once 'models/CheckDisbursementModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'models/NotificationModel.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';

class CheckDisbursementController extends Controller {
    use AuditTrailTrait;
    private $checkDisbursementModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->checkDisbursementModel = new CheckDisbursementModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
        $this->notificationModel = new NotificationModel();
    }
    
    /**
     * Display check disbursement entry form
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
        
        // Get recent check disbursements
        $transactions = $this->checkDisbursementModel->getRecentCheckDisbursements();
        
        $this->render('transaction-entries/check-disbursement', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'projects' => $projects,
            'departments' => $departments,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    /**
     * Save check disbursement transaction
     */
    public function save() {
        $this->requireAuth();
        
        try {
            $data = $this->getRequestData(); // support JSON payloads
            
            // Expect enhanced payload similar to cash-disbursement
            if (empty($data['voucher_number']) && empty($data['reference_no'])) {
                throw new Exception('Voucher number is required');
            }
            if (empty($data['transaction_date'])) {
                throw new Exception('Transaction date is required');
            }
            if (empty($data['account_distribution']) || !is_array($data['account_distribution'])) {
                throw new Exception('Account distribution is required');
            }
            
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

            // Create via model using enhanced transaction creation (reuse cash model semantics)
            $result = $this->checkDisbursementModel->createCheckDisbursement($data);
            // Audit trail
            try { $this->logCreate('transactions', (int)$result, $data); } catch (Exception $e) {}

            // Notifications for admins/managers/accountants when created by assistant/user
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user', 'assistant'])) {
                    $voucher = $data['voucher_number'] ?? $data['reference_no'] ?? 'CV';
                    $title = 'Check Disbursement Pending Approval';
                    $msg = sprintf('A check disbursement (CV: %s) was created by %s and is pending review.', $voucher, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User');
                    $link = APP_URL . '/transaction-entries/check-disbursement?id=' . urlencode((string)$result);
                    $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($recipients as $r) {
                        $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                    }
                }
            } catch (Exception $notifyEx) {
                error_log('Notification error (check disbursement save): ' . $notifyEx->getMessage());
            }
            try { $this->logCreate('transactions', (int)$result, $data); } catch (Exception $e) {}
            
            echo json_encode([
                'success' => true,
                'message' => 'Check disbursement saved successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save check disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get check disbursement by ID
     */
    public function get($id) {
        $this->requireAuth();
        
        try {
            $transaction = $this->checkDisbursementModel->getCheckDisbursementById($id);
            
            if (!$transaction) {
                throw new Exception('Check disbursement not found');
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
     * Update check disbursement transaction
     */
    public function update($id) {
        $this->requireAuth();
        
        try {
            $data = $_POST;
            
            // Validate required fields
            if (empty($data['reference_no']) || empty($data['amount']) || empty($data['accounts'])) {
                throw new Exception('Missing required fields');
            }
            
            // Update check disbursement
            $result = $this->checkDisbursementModel->updateCheckDisbursement($id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Check disbursement updated successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update check disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete check disbursement transaction
     */
    public function delete($id) {
        $this->requireAuth();
        
        try {
            $result = $this->checkDisbursementModel->deleteCheckDisbursement($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Check disbursement deleted successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete check disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent check disbursements for AJAX
     */
    public function recent() {
        $this->requireAuth();
        
        try {
            $transactions = $this->checkDisbursementModel->getRecentCheckDisbursements();
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch recent check disbursements: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get check disbursement statistics
     */
    public function stats() {
        $this->requireAuth();
        
        try {
            $stats = $this->checkDisbursementModel->getCheckDisbursementStats();
            
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
     * Get check disbursements by status
     */
    public function byStatus($status) {
        $this->requireAuth();
        
        try {
            $transactions = $this->checkDisbursementModel->getCheckDisbursementsByStatus($status);
            
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
    
    /**
     * Get check disbursements by payment status
     */
    public function byPaymentStatus($status) {
        $this->requireAuth();
        
        try {
            $transactions = $this->checkDisbursementModel->getCheckDisbursementsByPaymentStatus($status);
            
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