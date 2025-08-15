<?php

require_once 'core/Controller.php';
require_once 'models/CashReceiptModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'models/NotificationModel.php';
require_once 'core/AuditTrailTrait.php';

class CashReceiptController extends Controller {
    use AuditTrailTrait;
    private $cashReceiptModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->cashReceiptModel = new CashReceiptModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
        $this->notificationModel = new NotificationModel();
    }
    
    /**
     * Display cash receipt entry form
     */
    public function index() {
        $this->requireAuth();
        
        // Get accounts for dropdown (including inactive ones for now)
        $accounts = $this->chartOfAccountsModel->getAllAccountsIncludingInactive();
        
        // Debug: Log the accounts being loaded (commented out to prevent output issues)
        // error_log('Accounts loaded for cash receipt form: ' . json_encode($accounts));
        
        // Get suppliers for subsidiary accounts
        $suppliers = $this->supplierModel->getAllSuppliers();
        
        // Get projects and departments for dropdowns
        $projects = $this->projectModel->getActiveProjects();
        $departments = $this->departmentModel->getActiveDepartments();
        
        // Get recent cash receipts
        $transactions = $this->cashReceiptModel->getRecentCashReceipts();
        
        $this->render('transaction-entries/cash-receipt', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'projects' => $projects,
            'departments' => $departments,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    /**
     * Save cash receipt transaction
     */
    public function save() {
        $this->requireAuth();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            // var_dump($data);
            // die();
            // Debug: Log the received data (commented out to prevent output issues)
            // error_log('Cash receipt save data received: ' . json_encode($data));
            
            // Validate required fields
            $requiredFields = ['reference_no', 'transaction_date', 'amount', 'payment_form', 'payment_description'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ]);
                return;
            }
            
            // Validate account distribution
            if (empty($data['account_distribution']) || !is_array($data['account_distribution'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Account distribution is required'
                ]);
                return;
            }
            
            // Validate total debit equals total credit
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($data['account_distribution'] as $distribution) {
                if (!empty($distribution['debit'])) {
                    $totalDebit += floatval($distribution['debit']);
                }
                if (!empty($distribution['credit'])) {
                    $totalCredit += floatval($distribution['credit']);
                }
            }
            
            if (abs($totalDebit - $totalCredit) > 0.01) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Total debit (₱' . number_format($totalDebit, 2) . ') must equal total credit (₱' . number_format($totalCredit, 2) . ')'
                ]);
                return;
            }
            
            // Enforce default Pending statuses for assistants/users
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user','assistant'])) {
                    $data['payment_status'] = 'Pending';
                }
            } catch (Exception $e) {}
            
            // Prepare transaction data
            $transactionData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'amount' => $data['amount'],
                'transaction_type' => 'cash_receipt',
                'payment_form' => $data['payment_form'],
                'description' => $data['payment_description'],
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'check_payment_status' => $data['payment_status'] ?? 'Pending',
                'created_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Create the cash receipt transaction
            $result = $this->cashReceiptModel->createCashReceipt($transactionData, $data['account_distribution']);
            
            if ($result['success']) {
                // Log audit trail
                $this->logCreate('transactions', $result['transaction_id'], $transactionData);
                
                // Notifications for admins/managers/accountants when created by assistant/user
                try {
                    $currentUser = $this->auth->getCurrentUser();
                    $creatorRole = $currentUser['role'] ?? 'user';
                    if (in_array($creatorRole, ['user', 'assistant'])) {
                        $receipt = $data['reference_no'] ?? 'CR';
                        $title = 'Cash Receipt Pending Approval';
                        $msg = sprintf('A cash receipt (CR: %s) was created by %s and is pending review.', $receipt, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User');
                        $link = APP_URL . '/transaction-entries/cash-receipt?id=' . urlencode((string)$result['transaction_id']);
                        $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($recipients as $r) {
                            $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                        }
                    }
                } catch (Exception $notifyEx) {
                    error_log('Notification error (cash receipt save): ' . $notifyEx->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash receipt saved successfully',
                    'transaction_id' => $result['transaction_id'],
                    'reference_no' => $data['reference_no']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save cash receipt: ' . $result['message']
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash receipt by ID
     */
    public function get($id) {
        $this->requireAuth();
        
        try {
            $transaction = $this->cashReceiptModel->getCashReceiptById($id);
            
            if (!$transaction) {
                throw new Exception('Cash receipt not found');
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
     * Update cash receipt transaction
     */
    public function update($id) {
        $this->requireAuth();
        
        try {
            $data = $_POST;
            
            // Validate required fields
            if (empty($data['reference_no'])) {
                throw new Exception('Reference number is required');
            }
            if (empty($data['transaction_date'])) {
                throw new Exception('Transaction date is required');
            }
            
            // Check if user has permission to update status
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role'] ?? 'user';
            
            // Only admins, managers, and accountants can update status
            if (!in_array($userRole, ['admin', 'manager', 'accountant'])) {
                throw new Exception('Insufficient permissions to update transaction status');
            }
            
            // Prepare update data
            $updateData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'check_payment_status' => $data['payment_status'] ?? 'Pending',
                'updated_by' => $currentUser['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update the transaction
            $result = $this->cashReceiptModel->updateCashReceipt($id, $updateData);
            
            if ($result) {
                // Log audit trail
                $this->logUpdate('transactions', $id, $updateData);
                
                // Send notification to creator if status changed
                $originalTransaction = $this->cashReceiptModel->getCashReceiptById($id);
                if ($originalTransaction && $originalTransaction['created_by'] != $currentUser['id']) {
                    $statusChanged = false;
                    if (($data['payment_status'] ?? '') !== ($originalTransaction['check_payment_status'] ?? '')) {
                        $statusChanged = true;
                    }
                    
                    if ($statusChanged) {
                        $title = 'Cash Receipt Status Updated';
                        $msg = sprintf('Your cash receipt (CR: %s) status has been updated by %s.', 
                                     $data['reference_no'], $currentUser['full_name'] ?? $currentUser['username']);
                        $link = APP_URL . '/transaction-entries/cash-receipt?id=' . urlencode((string)$id);
                        $this->notificationModel->createNotification($originalTransaction['created_by'], $title, $msg, $link);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash receipt updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update cash receipt'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete cash receipt transaction
     */
    public function delete($id) {
        $this->requireAuth();
        
        try {
            $result = $this->cashReceiptModel->deleteCashReceipt($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cash receipt deleted successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent cash receipts for AJAX
     */
    public function recent() {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        
        $this->requireAuth();
        
        try {
            $transactions = $this->cashReceiptModel->getRecentCashReceipts();
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch recent cash receipts: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash receipt statistics
     */
    public function stats() {
        $this->requireAuth();
        
        try {
            $stats = $this->cashReceiptModel->getCashReceiptStats();
            
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
     * Debug endpoint to check available accounts
     */
    public function debugAccounts() {
        $this->requireAuth();
        
        try {
            $accounts = $this->chartOfAccountsModel->getAllAccounts();
            
            echo json_encode([
                'success' => true,
                'accounts' => $accounts,
                'count' => count($accounts)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch accounts: ' . $e->getMessage()
            ]);
        }
    }
} 