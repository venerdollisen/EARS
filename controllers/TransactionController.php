<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';
require_once BASE_PATH . '/models/CashReceiptModel.php';
require_once BASE_PATH . '/models/CashDisbursementModel.php';
require_once BASE_PATH . '/models/CheckDisbursementModel.php';

class TransactionController extends Controller {
    use AuditTrailTrait;
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Approve a transaction
     */
    public function approveTransaction($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Approval request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to approve
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            // Try to find and approve transaction in all three tables
            $cashReceiptModel = new CashReceiptModel();
            $cashDisbursementModel = new CashDisbursementModel();
            $checkDisbursementModel = new CheckDisbursementModel();
            
            $success = false;
            $transactionType = '';
            
            // Try cash receipts
            $transaction = $cashReceiptModel->getCashReceiptById($id);
            if ($transaction) {
                $success = $cashReceiptModel->updateStatus($id, 'approved');
                $transactionType = 'cash_receipt';
            }
            
            // Try cash disbursements
            if (!$success) {
                $transaction = $cashDisbursementModel->getCashDisbursementById($id);
                if ($transaction) {
                    error_log('Found cash disbursement transaction, updating status...');
                    $success = $cashDisbursementModel->updateStatus($id, 'approved');
                    $transactionType = 'cash_disbursement';
                    error_log('Cash disbursement status update result: ' . ($success ? 'success' : 'failed'));
                } else {
                    error_log('Cash disbursement transaction not found with ID: ' . $id);
                }
            }
            
            // Try check disbursements
            if (!$success) {
                $transaction = $checkDisbursementModel->getCheckDisbursementById($id);
                if ($transaction) {
                    error_log('Found check disbursement transaction, updating status...');
                    $success = $checkDisbursementModel->updateStatus($id, 'approved');
                    $transactionType = 'check_disbursement';
                    error_log('Check disbursement status update result: ' . ($success ? 'success' : 'failed'));
                } else {
                    error_log('Check disbursement transaction not found with ID: ' . $id);
                }
            }
            
            if ($success) {
                // Log the approval
                $this->logUpdate($transactionType . 's', $id, ['status' => 'approved']);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Transaction approved successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Transaction not found'], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve transaction: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Reject a transaction
     */
    public function rejectTransaction($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Rejection request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to reject
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $returnReason = trim($input['return_reason'] ?? $input['comment'] ?? '');
            
            error_log('Rejection request - ID: ' . $id . ', Reason: ' . $returnReason);
            
            if (empty($returnReason)) {
                $this->jsonResponse(['error' => 'Return reason is required for rejection'], 400);
                return;
            }
            
            // Try to find and reject transaction in all three tables
            $cashReceiptModel = new CashReceiptModel();
            $cashDisbursementModel = new CashDisbursementModel();
            $checkDisbursementModel = new CheckDisbursementModel();
            
            $success = false;
            $transactionType = '';
            
            // Try cash receipts
            $transaction = $cashReceiptModel->getCashReceiptById($id);
            if ($transaction) {
                error_log('Found cash receipt transaction, updating status...');
                $success = $cashReceiptModel->updateStatus($id, 'rejected', $returnReason);
                $transactionType = 'cash_receipt';
                error_log('Cash receipt status update result: ' . ($success ? 'success' : 'failed'));
            }
            
            // Try cash disbursements
            if (!$success) {
                $transaction = $cashDisbursementModel->getCashDisbursementById($id);
                if ($transaction) {
                    error_log('Found cash disbursement transaction, updating status...');
                    $success = $cashDisbursementModel->updateStatus($id, 'rejected', $returnReason);
                    $transactionType = 'cash_disbursement';
                    error_log('Cash disbursement status update result: ' . ($success ? 'success' : 'failed'));
                }
            }
            
            // Try check disbursements
            if (!$success) {
                $transaction = $checkDisbursementModel->getCheckDisbursementById($id);
                if ($transaction) {
                    error_log('Found check disbursement transaction, updating status...');
                    $success = $checkDisbursementModel->updateStatus($id, 'rejected', $returnReason);
                    $transactionType = 'check_disbursement';
                    error_log('Check disbursement status update result: ' . ($success ? 'success' : 'failed'));
                }
            }
            
            if ($success) {
                error_log('Transaction rejected successfully - Type: ' . $transactionType . ', ID: ' . $id);
                // Log the rejection
                $this->logUpdate($transactionType . 's', $id, ['status' => 'rejected', 'return_reason' => $returnReason]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Transaction rejected successfully'
                ]);
            } else {
                error_log('Transaction not found for rejection - ID: ' . $id);
                $this->jsonResponse(['error' => 'Transaction not found'], 404);
            }
            
        } catch (Exception $e) {
            error_log('Error rejecting transaction: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to reject transaction: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Approve a cash receipt transaction
     */
    public function approveCashReceipt($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Cash Receipt Approval request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to approve
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $cashReceiptModel = new CashReceiptModel();
            $success = $cashReceiptModel->updateStatus($id, 'approved');
            
            if ($success) {
                // Log the approval
                $this->logUpdate('cash_receipts', $id, ['status' => 'approved']);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Cash receipt approved successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Cash receipt not found'], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve cash receipt: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Reject a cash receipt transaction
     */
    public function rejectCashReceipt($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Cash Receipt Rejection request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to reject
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $returnReason = trim($input['return_reason'] ?? $input['comment'] ?? '');
            
            error_log('Cash Receipt Rejection request - ID: ' . $id . ', Reason: ' . $returnReason);
            
            if (empty($returnReason)) {
                $this->jsonResponse(['error' => 'Return reason is required for rejection'], 400);
                return;
            }
            
            $cashReceiptModel = new CashReceiptModel();
            $success = $cashReceiptModel->updateStatus($id, 'rejected', $returnReason);
            
            if ($success) {
                error_log('Cash receipt rejected successfully - ID: ' . $id);
                // Log the rejection
                $this->logUpdate('cash_receipts', $id, ['status' => 'rejected', 'return_reason' => $returnReason]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Cash receipt rejected successfully'
                ]);
            } else {
                error_log('Cash receipt not found for rejection - ID: ' . $id);
                $this->jsonResponse(['error' => 'Cash receipt not found'], 404);
            }
            
        } catch (Exception $e) {
            error_log('Error rejecting cash receipt: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to reject cash receipt: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Approve a cash disbursement transaction
     */
    public function approveCashDisbursement($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Cash Disbursement Approval request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to approve
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $cashDisbursementModel = new CashDisbursementModel();
            $success = $cashDisbursementModel->updateStatus($id, 'approved');
            
            if ($success) {
                // Log the approval
                $this->logUpdate('cash_disbursements', $id, ['status' => 'approved']);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Cash disbursement approved successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Cash disbursement not found'], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve cash disbursement: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Reject a cash disbursement transaction
     */
    public function rejectCashDisbursement($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Cash Disbursement Rejection request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to reject
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $returnReason = trim($input['return_reason'] ?? $input['comment'] ?? '');
            
            error_log('Cash Disbursement Rejection request - ID: ' . $id . ', Reason: ' . $returnReason);
            
            if (empty($returnReason)) {
                $this->jsonResponse(['error' => 'Return reason is required for rejection'], 400);
                return;
            }
            
            $cashDisbursementModel = new CashDisbursementModel();
            $success = $cashDisbursementModel->updateStatus($id, 'rejected', $returnReason);
            
            if ($success) {
                error_log('Cash disbursement rejected successfully - ID: ' . $id);
                // Log the rejection
                $this->logUpdate('cash_disbursements', $id, ['status' => 'rejected', 'return_reason' => $returnReason]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Cash disbursement rejected successfully'
                ]);
            } else {
                error_log('Cash disbursement not found for rejection - ID: ' . $id);
                $this->jsonResponse(['error' => 'Cash disbursement not found'], 404);
            }
            
        } catch (Exception $e) {
            error_log('Error rejecting cash disbursement: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to reject cash disbursement: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Approve a check disbursement transaction
     */
    public function approveCheckDisbursement($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Check Disbursement Approval request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to approve
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $checkDisbursementModel = new CheckDisbursementModel();
            $success = $checkDisbursementModel->updateStatus($id, 'approved');
            
            if ($success) {
                // Log the approval
                $this->logUpdate('check_disbursements', $id, ['status' => 'approved']);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Check disbursement approved successfully'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Check disbursement not found'], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to approve check disbursement: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Reject a check disbursement transaction
     */
    public function rejectCheckDisbursement($id) {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            error_log('Check Disbursement Rejection request received - Transaction ID: ' . $id . ', User: ' . $user['username'] . ', Role: ' . $user['role']);
            
            // Check if user has permission to reject
            if (!in_array($user['role'], ['admin', 'manager', 'accountant'])) {
                $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $returnReason = trim($input['return_reason'] ?? $input['comment'] ?? '');
            
            error_log('Check Disbursement Rejection request - ID: ' . $id . ', Reason: ' . $returnReason);
            
            if (empty($returnReason)) {
                $this->jsonResponse(['error' => 'Return reason is required for rejection'], 400);
                return;
            }
            
            $checkDisbursementModel = new CheckDisbursementModel();
            $success = $checkDisbursementModel->updateStatus($id, 'rejected', $returnReason);
            
            if ($success) {
                error_log('Check disbursement rejected successfully - ID: ' . $id);
                // Log the rejection
                $this->logUpdate('check_disbursements', $id, ['status' => 'rejected', 'return_reason' => $returnReason]);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Check disbursement rejected successfully'
                ]);
            } else {
                error_log('Check disbursement not found for rejection - ID: ' . $id);
                $this->jsonResponse(['error' => 'Check disbursement not found'], 404);
            }
            
        } catch (Exception $e) {
            error_log('Error rejecting check disbursement: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to reject check disbursement: ' . $e->getMessage()], 500);
        }
    }
}
?> 