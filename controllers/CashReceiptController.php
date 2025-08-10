<?php

require_once 'core/Controller.php';
require_once 'models/CashReceiptModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'core/AuditTrailTrait.php';

class CashReceiptController extends Controller {
    use AuditTrailTrait;
    private $cashReceiptModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    
    public function __construct() {
        parent::__construct();
        $this->cashReceiptModel = new CashReceiptModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
    }
    
    /**
     * Display cash receipt entry form
     */
    public function index() {
        $this->requireAuth();
        
        // Get accounts for dropdown (including inactive ones for now)
        $accounts = $this->chartOfAccountsModel->getAllAccountsIncludingInactive();
        
        // Debug: Log the accounts being loaded
        error_log('Accounts loaded for cash receipt form: ' . json_encode($accounts));
        
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
            // Debug: Log the received data
            error_log('Cash receipt save data received: ' . json_encode($data));
            
            // Validate required fields (amount will be computed; payment fields optional)
            $requiredFields = ['reference_no', 'transaction_date'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields),
                    'error' => 'Missing required fields: ' . implode(', ', $missingFields)
                ]);
                return;
            }
            
            // Validate account distribution
            if (empty($data['account_distribution']) || !is_array($data['account_distribution'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Account distribution is required',
                    'error' => 'Account distribution is required'
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
                $msg = 'Total debit (₱' . number_format($totalDebit, 2) . ') must equal total credit (₱' . number_format($totalCredit, 2) . ')';
                echo json_encode([
                    'success' => false,
                    'message' => $msg,
                    'error' => $msg
                ]);
                return;
            }
            
            // Prepare transaction data
            $transactionData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                // Always set amount from computed totals for consistency
                'amount' => $totalDebit,
                'transaction_type' => 'cash_receipt',
                'payment_form' => !empty($data['payment_form']) ? $data['payment_form'] : 'cash',
                'description' => $data['payment_description'] ?? ($data['payment_for'] ?? ''),
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'created_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Create the cash receipt transaction
            $result = $this->cashReceiptModel->createCashReceipt($transactionData, $data['account_distribution']);
            
            if ($result['success']) {
                // Log audit trail
                $this->logCreate('transactions', $result['transaction_id'], $transactionData);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash receipt saved successfully',
                    'transaction_id' => $result['transaction_id'],
                    'reference_no' => $data['reference_no']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save cash receipt: ' . $result['message'],
                    'error' => 'Failed to save cash receipt: ' . $result['message']
                ]);
            }
            
        } catch (Exception $e) {
            $msg = 'Failed to save cash receipt: ' . $e->getMessage();
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'error' => $msg
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
            if (empty($data['reference_no']) || empty($data['amount']) || empty($data['accounts'])) {
                throw new Exception('Missing required fields');
            }
            
            // Update cash receipt
            $result = $this->cashReceiptModel->updateCashReceipt($id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cash receipt updated successfully',
                'data' => $result
            ]);
            
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