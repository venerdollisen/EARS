<?php
require_once BASE_PATH . '/models/ChartOfAccountsModel.php';
require_once BASE_PATH . '/models/SupplierModel.php';
require_once BASE_PATH . '/models/TransactionModel.php';
require_once BASE_PATH . '/models/NotificationModel.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';

class TransactionController extends Controller {
    use AuditTrailTrait;
    
    public function index() {
        $this->requireAuth();
        
        $this->render('transaction-entries/index', [
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function cashReceipt() {
        $this->requireAuth();
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $supplierModel = new SupplierModel();
        $transactionModel = new TransactionModel();
        
        // Get accounts for dropdown
        $accounts = $chartOfAccountsModel->getAllAccounts();
        error_log('Cash Receipt - Number of accounts: ' . count($accounts));
        
        // Get suppliers for subsidiary accounts
        $suppliers = $supplierModel->getAllSuppliers();
        error_log('Cash Receipt - Number of suppliers: ' . count($suppliers));
        
        // Get recent cash receipts
        $transactions = $transactionModel->getTransactionsByType('cash_receipt');
        
        $this->render('transaction-entries/cash-receipt', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function disbursement() {
        $this->requireAuth();
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $supplierModel = new SupplierModel();
        $transactionModel = new TransactionModel();
        
        // Get accounts for dropdown
        $accounts = $chartOfAccountsModel->getAllAccounts();
        
        // Get suppliers for dropdown
        $suppliers = $supplierModel->getAllSuppliers();
        
        // Get recent disbursements
        $transactions = $transactionModel->getTransactionsByType('disbursement');
        
        $this->render('transaction-entries/disbursement', [
            'accounts' => $accounts,
            'suppliers' => $suppliers,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function journalAdjustment() {
        $this->requireAuth();
        
        $chartOfAccountsModel = new ChartOfAccountsModel();
        $transactionModel = new TransactionModel();
        
        // Get accounts for dropdown
        $accounts = $chartOfAccountsModel->getAllAccounts();
        
        // Get recent journal adjustments
        $transactions = $transactionModel->getTransactionsByType('journal_adjustment');
        
        $this->render('transaction-entries/journal-adjustment', [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function saveTransaction() {
        $this->requireAuth();
        
        $data = $this->getRequestData();
        
        if (!isset($data['transaction_type']) || !isset($data['amount']) || !isset($data['account_id'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $transactionModel = new TransactionModel();
            
            // Validate transaction data
            $errors = $transactionModel->validateTransaction($data);
            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Validation failed', 'details' => $errors], 400);
            }
            
            $result = $transactionModel->createTransaction($data);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Transaction saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save transaction'], 500);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save transaction: ' . $e->getMessage()], 500);
        }
    }
    
    public function saveEnhancedTransaction() {
        $this->requireAuth();
        
        $data = $this->getRequestData();
        
        // Debug: Log the received data
        error_log('Received transaction data: ' . json_encode($data));
        
        if (!isset($data['transaction_type']) || !isset($data['reference_no']) || !isset($data['accounts'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        // Check if reference number already exists
        $transactionModel = new TransactionModel();
        $existingTransaction = $transactionModel->getTransactionByReference($data['reference_no']);
        if ($existingTransaction) {
            // Generate a new unique reference number
            $data['reference_no'] = $transactionModel->generateUniqueReferenceNumber();
            error_log('Reference number already exists, generated new one: ' . $data['reference_no']);
        }
        
        try {
            $transactionModel = new TransactionModel();
            
            // Validate that accounts array is not empty
            if (empty($data['accounts'])) {
                $this->jsonResponse(['error' => 'At least one account entry is required'], 400);
            }
            
            // Validate that transaction is balanced
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($data['accounts'] as $account) {
                $totalDebit += floatval($account['debit'] ?? 0);
                $totalCredit += floatval($account['credit'] ?? 0);
            }
            
            $difference = abs($totalDebit - $totalCredit);
            if ($difference >= 0.01) {
                $this->jsonResponse(['error' => 'Transaction must be balanced. Difference: ₱' . number_format($difference, 2)], 400);
            }
            
            // Create the enhanced transaction
            $result = $transactionModel->createEnhancedTransaction($data);
            
            if ($result) {
                // Notification: only about header (no distribution), for disbursements
                if (in_array(($data['transaction_type'] ?? ''), ['cash_disbursement','check_disbursement'], true)) {
                    try {
                        $notifModel = new NotificationModel();
                        $admins = $this->db->query("SELECT id FROM users WHERE role IN ('admin','manager','accountant') AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                        $creator = $this->auth->getCurrentUser();
                        $isCheck = (($data['transaction_type'] ?? '') === 'check_disbursement');
                        $title = $isCheck ? 'Check Disbursement Pending Approval' : 'Cash Disbursement Pending Approval';
                        $msg = sprintf('%s (CV: %s) encoded by %s', $isCheck ? 'Check Disbursement' : 'Cash Disbursement', $data['voucher_number'] ?? $data['reference_no'], $creator['full_name'] ?? 'User');
                        $link = APP_URL . ($isCheck ? '/transaction-entries/check-disbursement' : '/transaction-entries/cash-disbursement');
                        // If we have the new header id from createEnhancedTransaction, append it for deep linking
                        if (!empty($result)) {
                            $link .= '?id=' . urlencode((string)$result);
                        }
                        foreach ($admins as $admin) {
                            $notifModel->createNotification((int)$admin['id'], $title, $msg, $link);
                        }
                    } catch (Exception $e) {
                        error_log('Notification error (saveEnhancedTransaction): ' . $e->getMessage());
                    }
                }
                $this->jsonResponse(['success' => true, 'message' => 'Transaction saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save transaction'], 500);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save transaction: ' . $e->getMessage()], 500);
        }
    }
    
    public function getTransactionDetails($id) {
        $this->requireAuth();
        
        error_log('getTransactionDetails called with ID: ' . $id);
        
        try {
            $transactionModel = new TransactionModel();
            $transaction = $transactionModel->getTransactionWithDetails($id);
            
            error_log('Transaction data retrieved: ' . json_encode($transaction));
            
            if ($transaction) {
                $this->jsonResponse(['success' => true, 'data' => $transaction]);
            } else {
                error_log('Transaction not found for ID: ' . $id);
                $this->jsonResponse(['error' => 'Transaction not found'], 404);
            }
            
        } catch (Exception $e) {
            error_log('Error in getTransactionDetails: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to get transaction details: ' . $e->getMessage()], 500);
        }
    }
    
    public function getRecentTransactions() {
        $this->requireAuth();
        
        try {
            $transactionModel = new TransactionModel();
            $type = $_GET['type'] ?? null;
            
            if ($type) {
                $transactions = $transactionModel->getTransactionsByType($type);
            } else {
                $transactions = $transactionModel->getAllTransactions();
            }
            
            // Limit to recent 10 transactions
            $recentTransactions = array_slice($transactions, 0, 10);
            
            $this->jsonResponse(['success' => true, 'transactions' => $recentTransactions]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to get recent transactions: ' . $e->getMessage()], 500);
        }
    }
    
    public function getAccountsList() {
        $this->requireAuth();
        
        try {
            $chartOfAccountsModel = new ChartOfAccountsModel();
            $accounts = $chartOfAccountsModel->getAllAccounts();
            
            $this->jsonResponse(['success' => true, 'accounts' => $accounts]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to get accounts list: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve a pending disbursement (cash/check). Header-only decision; children auto-propagated.
     */
    public function approveTransaction($id) {
        $this->requireAuth();
        try {
            // Only admin/manager/accountant can approve
            $user = $this->auth->getCurrentUser();
            if (!in_array(($user['role'] ?? ''), ['admin','manager','accountant'], true)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $transactionModel = new TransactionModel();
            // Capture old values for audit
            $stmtOld = $this->db->prepare("SELECT cv_status, cv_checked, check_payment_status FROM transactions WHERE id = ? AND parent_transaction_id IS NULL");
            $stmtOld->execute([(int)$id]);
            $old = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: null;
            // Set statuses to Approved/Checked
            $ok = $transactionModel->updateHeaderStatuses((int)$id, 'Approved', 'Checked', 'Approved');
            if ($ok) {
                try { $this->logUpdate('transactions', (int)$id, null, ['cv_status'=>'Approved','cv_checked'=>'Checked','check_payment_status'=>'Approved']); } catch (Exception $e) {}
                try { $this->logAuditTrail('APPROVE', 'transactions', (int)$id, $old, ['cv_status'=>'Approved','cv_checked'=>'Checked','check_payment_status'=>'Approved']); } catch (Exception $e) {}
                // Remove related notifications
                try {
                    $notifModel = new NotificationModel();
                    // Get header reference_no for message match
                    $stmt = $this->db->prepare("SELECT reference_no FROM transactions WHERE id = ?");
                    $stmt->execute([(int)$id]);
                    $ref = $stmt->fetchColumn();
                    $notifModel->deleteByTransaction((int)$id, $ref ?: null);
                } catch (Exception $e) { error_log('Delete notif on approve failed: ' . $e->getMessage()); }
            }
            if ($ok) {
                return $this->jsonResponse(['success' => true, 'message' => 'Transaction approved']);
            }
            return $this->jsonResponse(['success' => false, 'message' => 'Update failed or nothing to change'], 400);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject a pending disbursement (cash/check). Header-only decision; children auto-propagated.
     */
    public function rejectTransaction($id) {
        $this->requireAuth();
        try {
            $user = $this->auth->getCurrentUser();
            if (!in_array(($user['role'] ?? ''), ['admin','manager','accountant'], true)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
            }
            $reason = $_POST['reason'] ?? ($this->getRequestData()['reason'] ?? null);
            $transactionModel = new TransactionModel();
            // Capture old values for audit
            $stmtOld = $this->db->prepare("SELECT cv_status, cv_checked, check_payment_status FROM transactions WHERE id = ? AND parent_transaction_id IS NULL");
            $stmtOld->execute([(int)$id]);
            $old = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: null;
            $ok = $transactionModel->updateHeaderStatuses((int)$id, 'Rejected', 'Unchecked', 'Rejected');
            if ($ok) {
                try { $this->logUpdate('transactions', (int)$id, null, ['cv_status'=>'Rejected','cv_checked'=>'Unchecked','check_payment_status'=>'Rejected','return_reason'=>$reason]); } catch (Exception $e) {}
                try { $this->logAuditTrail('REJECT', 'transactions', (int)$id, $old, ['cv_status'=>'Rejected','cv_checked'=>'Unchecked','check_payment_status'=>'Rejected','return_reason'=>$reason]); } catch (Exception $e) {}
                // Remove related notifications on final decision
                try {
                    $notifModel = new NotificationModel();
                    $stmt = $this->db->prepare("SELECT reference_no FROM transactions WHERE id = ?");
                    $stmt->execute([(int)$id]);
                    $ref = $stmt->fetchColumn();
                    $notifModel->deleteByTransaction((int)$id, $ref ?: null);
                } catch (Exception $e) { error_log('Delete notif on reject failed: ' . $e->getMessage()); }
            }
            if ($ok && $reason) {
                // save return_reason on header
                $stmt = $this->db->prepare("UPDATE transactions SET return_reason = ?, updated_at = NOW() WHERE id = ? AND parent_transaction_id IS NULL");
                $stmt->execute([$reason, (int)$id]);
            }
            if ($ok) {
                return $this->jsonResponse(['success' => true, 'message' => 'Transaction rejected']);
            }
            return $this->jsonResponse(['success' => false, 'message' => 'Update failed or nothing to change'], 400);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
?> 