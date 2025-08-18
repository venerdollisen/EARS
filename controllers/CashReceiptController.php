<?php

require_once 'core/Controller.php';
require_once 'models/CashReceiptModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'models/NotificationModel.php';
require_once 'models/TransactionValidationModel.php';
require_once 'core/AuditTrailTrait.php';

class CashReceiptController extends Controller {
    use AuditTrailTrait;
    private $cashReceiptModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    private $notificationModel;
    private $validationModel;
    
    public function __construct() {
        parent::__construct();
        $this->cashReceiptModel = new CashReceiptModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
        $this->notificationModel = new NotificationModel();
        $this->validationModel = new TransactionValidationModel($this->db);
    }
    
    /**
     * Display cash receipt entry form
     */
    public function index() {
        $this->requireAuth();
        
        // Get accounts for dropdown (including inactive ones for now)
        $accounts = $this->chartOfAccountsModel->getAllAccountsIncludingInactive();
        
        // Get suppliers for subsidiary accounts
        $suppliers = $this->supplierModel->getAllSuppliers();
        
        // Get projects and departments for dropdowns
        $projects = $this->projectModel->getActiveProjects();
        $departments = $this->departmentModel->getActiveDepartments();
        
        // Get recent cash receipts using the new structure
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
     * Save cash receipt transaction using new normalized structure
     */
    public function save() {
        $this->requireAuth();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
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
            
            // Prepare distributions for accounting validation
            $validationDistributions = [];
            foreach ($data['account_distribution'] as $distribution) {
                if (empty($distribution['account_id'])) {
                    continue;
                }
                
                // Handle debit entries
                if (!empty($distribution['debit']) && floatval($distribution['debit']) > 0) {
                    $validationDistributions[] = [
                        'account_id' => intval($distribution['account_id']),
                        'payment_type' => 'debit',
                        'amount' => floatval($distribution['debit'])
                    ];
                }
                
                // Handle credit entries
                if (!empty($distribution['credit']) && floatval($distribution['credit']) > 0) {
                    $validationDistributions[] = [
                        'account_id' => intval($distribution['account_id']),
                        'payment_type' => 'credit',
                        'amount' => floatval($distribution['credit'])
                    ];
                }
            }
            
            // Validate using accounting principles
            $validationResult = $this->validationModel->validateTransactionDistributions($validationDistributions);
            if (!$validationResult['valid']) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Accounting validation failed',
                    'message' => 'Accounting validation errors: ' . implode(', ', $validationResult['errors'])
                ]);
                return;
            }
            
            // Show warnings if any (but allow transaction to proceed)
            if (!empty($validationResult['warnings'])) {
                echo json_encode([
                    'success' => false,
                    'warning' => 'Transaction has warnings but is valid',
                    'message' => 'Please review the warnings: ' . implode(', ', $validationResult['warnings'])
                ]);
                return;
            }
            
            // Enforce default status for assistants/users
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user','assistant'])) {
                    $data['status'] = 'pending';
                }
            } catch (Exception $e) {}
            
            // Calculate total amount - for cash receipts, total is sum of all amounts
            $totalAmount = $totalDebit + $totalCredit;
            
            // Prepare header data for new structure
            $headerData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $totalAmount,
                'description' => $data['payment_description'],
                'payment_form' => $data['payment_form'],
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                'payee_name' => null, // Cash receipts don't have payee
                'status' => $data['status'] ?? 'pending',
                'created_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Prepare distributions for new structure
            $distributions = [];
            foreach ($data['account_distribution'] as $distribution) {
                if (empty($distribution['account_id'])) {
                    continue;
                }
                
                // Handle debit entries
                if (!empty($distribution['debit']) && floatval($distribution['debit']) > 0) {
                    $distributions[] = [
                        'account_id' => intval($distribution['account_id']),
                        'amount' => floatval($distribution['debit']),
                        'description' => $distribution['description'] ?? $data['payment_description'],
                        'project_id' => !empty($distribution['project_id']) ? intval($distribution['project_id']) : null,
                        'department_id' => !empty($distribution['department_id']) ? intval($distribution['department_id']) : null,
                        'supplier_id' => !empty($distribution['subsidiary_id']) ? intval($distribution['subsidiary_id']) : null
                    ];
                }
                
                // Handle credit entries
                if (!empty($distribution['credit']) && floatval($distribution['credit']) > 0) {
                    $distributions[] = [
                        'account_id' => intval($distribution['account_id']),
                        'amount' => floatval($distribution['credit']),
                        'description' => $distribution['description'] ?? $data['payment_description'],
                        'project_id' => !empty($distribution['project_id']) ? intval($distribution['project_id']) : null,
                        'department_id' => !empty($distribution['department_id']) ? intval($distribution['department_id']) : null,
                        'supplier_id' => !empty($distribution['subsidiary_id']) ? intval($distribution['subsidiary_id']) : null
                    ];
                }
            }
            
            // Validate distributions
            if (empty($distributions)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'At least one valid account distribution is required'
                ]);
                return;
            }
            
            // Create transaction using CashReceiptModel
            $result = $this->cashReceiptModel->createCashReceipt($headerData, $distributions);
            
            if ($result['success']) {
                // Log audit trail
                $this->logCreate('cash_receipts', $result['transaction_id'], $headerData);
                
                // Notifications for admins/managers/accountants when created by assistant/user
                try {
                    $currentUser = $this->auth->getCurrentUser();
                    $creatorRole = $currentUser['role'] ?? 'user';
                    if (in_array($creatorRole, ['user', 'assistant'])) {
                        $receipt = $data['reference_no'] ?? 'CR';
                        $title = 'Cash Receipt Created';
                        $msg = sprintf('A cash receipt (CR: %s) was created by %s with status: %s.', $receipt, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User', $data['status'] ?? 'pending');
                        $link = APP_URL . '/transaction-entries/cash-receipt?id=' . urlencode((string)$result['transaction_id']);
                        $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($recipients as $r) {
                            $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error creating notifications: ' . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash receipt saved successfully',
                    'transaction_id' => $result['transaction_id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save cash receipt'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Error saving cash receipt: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash receipt by ID using new structure
     */
    public function get($id) {
        $this->requireAuth();
        
        try {
            $transaction = $this->cashReceiptModel->getCashReceiptById($id);
            
            if ($transaction) {
                echo json_encode([
                    'success' => true,
                    'data' => $transaction
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Cash receipt not found'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error retrieving cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update cash receipt transaction
     */
    public function update($id) {
        $this->requireAuth();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
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
            
            // Get original transaction for comparison
            $originalTransaction = $this->cashReceiptModel->getCashReceiptById($id);
            if (!$originalTransaction) {
                throw new Exception('Cash receipt not found');
            }
            
            // Prepare update data
            $updateData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $originalTransaction['total_amount'], // Keep original amount
                'description' => $data['description'] ?? $originalTransaction['description'],
                'status' => $data['status'] ?? $originalTransaction['status'],
                'updated_by' => $currentUser['id']
            ];
            
            // Update the transaction
            $result = $this->cashReceiptModel->updateCashReceipt($id, $updateData);
            
            if ($result) {
                // Log audit trail
                $this->logUpdate('cash_receipts', $id, $updateData);
                
                // Send notification to creator if status changed
                if (isset($data['status']) && $data['status'] !== $originalTransaction['status']) {
                    $creatorId = $originalTransaction['created_by'];
                    $title = 'Cash Receipt Status Updated';
                    $msg = 'Your cash receipt ' . $data['reference_no'] . ' status has been updated to: ' . $data['status'];
                    $link = APP_URL . '/transaction-entries/cash-receipt?id=' . urlencode((string)$id);
                    $this->notificationModel->createNotification($creatorId, $title, $msg, $link);
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
                'message' => 'Error updating cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete cash receipt transaction
     */
    public function delete($id) {
        $this->requireAuth();
        
        try {
            // Check if user has permission to delete
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role'] ?? 'user';
            
            if (!in_array($userRole, ['admin', 'manager', 'accountant'])) {
                throw new Exception('Insufficient permissions to delete transaction');
            }
            
            // Get the transaction to check its status
            $transaction = $this->cashReceiptModel->getCashReceiptById($id);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Only allow deletion of pending or rejected transactions
            if ($transaction['status'] === 'approved') {
                throw new Exception('Cannot delete approved transactions. Only pending or rejected transactions can be deleted.');
            }
            
            $result = $this->cashReceiptModel->deleteCashReceipt($id);
            
            if ($result) {
                // Log audit trail
                $this->logDelete('cash_receipts', $id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash receipt deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete cash receipt'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting cash receipt: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent cash receipts for display
     */
    public function recent() {
        // Temporarily comment out auth for debugging
        // $this->requireAuth();
        
        try {
            $transactions = $this->cashReceiptModel->getRecentCashReceipts(50);
            
            error_log('Recent cash receipts found: ' . count($transactions));
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error retrieving recent cash receipts: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Server-side DataTable processing for cash receipts
     */
    public function datatable() {
        // Temporarily comment out auth for debugging
        // $this->requireAuth();
        
        try {
            // Debug logging
            error_log('DataTable request received: ' . json_encode($_GET));
            
            // Get DataTable parameters
            $draw = $_GET['draw'] ?? 1;
            $start = $_GET['start'] ?? 0;
            $length = $_GET['length'] ?? 10;
            $search = $_GET['search']['value'] ?? '';
            $orderColumn = $_GET['order'][0]['column'] ?? 0;
            $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
            
            // Column mapping for ordering
            $columns = [
                0 => 'cr.transaction_date',
                1 => 'cr.reference_no',
                2 => 'cr.payment_form',
                3 => 'cr.total_amount',
                4 => 'cr.description',
                5 => 'cr.status'
            ];
            
            $orderBy = $columns[$orderColumn] ?? 'cr.created_at';
            
            // Get data from model
            $result = $this->cashReceiptModel->getDataTableData($start, $length, $search, $orderBy, $orderDir);
            
            $response = [
                'draw' => (int)$draw,
                'recordsTotal' => $result['totalRecords'],
                'recordsFiltered' => $result['filteredRecords'],
                'data' => $result['data']
            ];
            
            error_log('DataTable response: ' . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode([
                'draw' => (int)($draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error processing request: ' . $e->getMessage()
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
                'error' => 'Error retrieving cash receipt statistics: ' . $e->getMessage()
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