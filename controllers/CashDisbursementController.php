<?php

require_once 'core/Controller.php';
require_once 'models/CashDisbursementModel.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/SupplierModel.php';
require_once 'models/ProjectModel.php';
require_once 'models/DepartmentModel.php';
require_once 'models/NotificationModel.php';
require_once 'models/TransactionValidationModel.php';
require_once 'core/AuditTrailTrait.php';

class CashDisbursementController extends Controller {
    use AuditTrailTrait;
    private $cashDisbursementModel;
    private $chartOfAccountsModel;
    private $supplierModel;
    private $projectModel;
    private $departmentModel;
    private $notificationModel;
    private $validationModel;
    
    public function __construct() {
        parent::__construct();
        $this->cashDisbursementModel = new CashDisbursementModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->supplierModel = new SupplierModel();
        $this->projectModel = new ProjectModel();
        $this->departmentModel = new DepartmentModel();
        $this->notificationModel = new NotificationModel();
        $this->validationModel = new TransactionValidationModel($this->db);
    }
    
    /**
     * Display cash disbursement entry form
     */
    public function index() {
        $this->requireAuth();
        
        // Get accounts for dropdown
        $accounts = $this->chartOfAccountsModel->getAllAccountsIncludingInactive();
        
        // Get suppliers for subsidiary accounts
        $suppliers = $this->supplierModel->getAllSuppliers();
        
        // Get projects and departments for dropdowns
        $projects = $this->projectModel->getActiveProjects();
        $departments = $this->departmentModel->getActiveDepartments();
        
        // Get recent cash disbursements using the new structure
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
     * Save cash disbursement transaction using new normalized structure
     */
    public function save() {
        $this->requireAuth();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $requiredFields = ['voucher_number', 'transaction_date', 'payee_name', 'particulars'];
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
            
            // Enforce default Pending statuses for assistants/users
            try {
                $currentUser = $this->auth->getCurrentUser();
                $creatorRole = $currentUser['role'] ?? 'user';
                if (in_array($creatorRole, ['user','assistant'])) {
                    $data['status'] = 'pending';
                }
            } catch (Exception $e) {}
            
            // Calculate total amount from distributions
            $totalAmount = 0;
            foreach ($data['account_distribution'] as $distribution) {
                if (!empty($distribution['debit'])) {
                    $totalAmount += floatval($distribution['debit']);
                }
                if (!empty($distribution['credit'])) {
                    $totalAmount += floatval($distribution['credit']);
                }
            }
            
            // Prepare header data for new structure
            $headerData = [
                'reference_no' => $data['voucher_number'],
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $totalAmount,
                'description' => $data['particulars'],
                'supplier_id' => null, // Will be set from distributions if needed
                'project_id' => null, // Will be set from distributions if needed
                'department_id' => null, // Will be set from distributions if needed
                'payee_name' => $data['payee_name'],
                'payment_form' => $data['payment_form'] ?? 'cash',
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'cwo_number' => $data['cwo_number'] ?? null,
                'ebr_number' => $data['ebr_number'] ?? null,
                'check_date' => $data['check_date'] ?? null,
                'return_reason' => $data['return_reason'] ?? null,
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
                        'description' => $distribution['description'] ?? $data['particulars'],
                        'project_id' => !empty($distribution['project_id']) ? intval($distribution['project_id']) : null,
                        'department_id' => !empty($distribution['department_id']) ? intval($distribution['department_id']) : null,
                        'supplier_id' => !empty($distribution['subsidiary_id']) ? intval($distribution['subsidiary_id']) : null
                    ];
                }
                
                // Handle credit entries
                if (!empty($distribution['credit']) && floatval($distribution['credit']) > 0) {
                    $distributions[] = [
                        'account_id' => intval($distribution['account_id']),
                        'amount' => floatval($distribution['credit']), // Positive for credit
                        'description' => $distribution['description'] ?? $data['particulars'],
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
            
            // Create transaction using CashDisbursementModel
            $result = $this->cashDisbursementModel->createCashDisbursement($headerData, $distributions);
            
            if ($result['success']) {
                // Log the creation
                $this->logCreate('cash_disbursements', $result['transaction_id'], $headerData);
                
                // Notifications for admins/managers/accountants when created by assistant/user
                try {
                    $currentUser = $this->auth->getCurrentUser();
                    $creatorRole = $currentUser['role'] ?? 'user';
                    if (in_array($creatorRole, ['user', 'assistant'])) {
                        $voucher = $data['voucher_number'] ?? 'CV';
                        $title = 'Cash Disbursement Pending Approval';
                        $msg = sprintf('A cash disbursement (CV: %s) was created by %s and is pending review.', $voucher, $currentUser['full_name'] ?? $currentUser['username'] ?? 'User');
                        $link = APP_URL . '/transaction-entries/cash-disbursement?id=' . urlencode((string)$result['transaction_id']);
                        $recipients = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($recipients as $r) {
                            $this->notificationModel->createNotification((int)$r['id'], $title, $msg, $link);
                        }
                    }
                } catch (Exception $notifyEx) {
                    error_log('Notification error (cash disbursement save): ' . $notifyEx->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash disbursement saved successfully',
                    'transaction_id' => $result['transaction_id'],
                    'reference_no' => $data['voucher_number']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save cash disbursement: ' . $result['message']
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cash disbursement by ID using new structure
     */
    public function get($id) {
        $this->requireAuth();
        
        try {
            $transaction = $this->cashDisbursementModel->getCashDisbursementById($id);
            
            if ($transaction) {
                echo json_encode([
                    'success' => true,
                    'data' => $transaction
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Cash disbursement not found'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error retrieving cash disbursement: ' . $e->getMessage()
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
            if (empty($data['voucher_number'])) {
                throw new Exception('Voucher number is required');
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
                'reference_no' => $data['voucher_number'],
                'transaction_date' => $data['transaction_date'],
                'status' => $data['status'] ?? 'pending',
                'updated_by' => $currentUser['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update the transaction
            $result = $this->cashDisbursementModel->updateCashDisbursement($id, $updateData);
            
            if ($result) {
                // Log audit trail
                $this->logUpdate('cash_disbursements', $id, $updateData);
                
                // Send notification to creator if status changed
                $originalTransaction = $this->cashDisbursementModel->getCashDisbursementById($id);
                if ($originalTransaction && (isset($data['status']))) {
                    $creatorId = $originalTransaction['header']['created_by'];
                    $title = 'Cash Disbursement Status Updated';
                    $msg = 'Your cash disbursement ' . $data['voucher_number'] . ' status has been updated.';
                    if (isset($data['status'])) {
                        $msg .= ' Status: ' . $data['status'];
                    }
                    $link = APP_URL . '/transaction-entries/cash-disbursement?id=' . urlencode((string)$id);
                    $this->notificationModel->createNotification($creatorId, $title, $msg, $link);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash disbursement updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update cash disbursement'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete cash disbursement transaction
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
            $transaction = $this->cashDisbursementModel->getCashDisbursementById($id);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Only allow deletion of pending or rejected transactions
            if ($transaction['status'] === 'approved') {
                throw new Exception('Cannot delete approved transactions. Only pending or rejected transactions can be deleted.');
            }
            
            $result = $this->cashDisbursementModel->deleteCashDisbursement($id);
            
            if ($result) {
                // Log audit trail
                $this->logDelete('transaction_headers', $id);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cash disbursement deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete cash disbursement'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting cash disbursement: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent cash disbursements for display
     */
    public function recent() {
        // Temporarily comment out auth for debugging
        // $this->requireAuth();
        
        try {
            $transactions = $this->cashDisbursementModel->getRecentCashDisbursements(50);
            
            error_log('Recent cash disbursements found: ' . count($transactions));
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error retrieving recent cash disbursements: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Server-side DataTable processing for cash disbursements
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
            $userRole = $_GET['userRole'] ?? 'user';
            
            // Column mapping for ordering
            $columns = [
                0 => 'cd.transaction_date',
                1 => 'cd.reference_no',
                2 => 'cd.total_amount',
                3 => 'cd.payee_name',
                4 => 'cd.payment_form',
                5 => 'cd.status'
            ];
            
            $orderBy = $columns[$orderColumn] ?? 'cd.created_at';
            
            // Get data from model
            $result = $this->cashDisbursementModel->getDataTableData($start, $length, $search, $orderBy, $orderDir, $userRole);
            
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
                'error' => 'Error retrieving cash disbursement statistics: ' . $e->getMessage()
            ]);
        }
    }
} 