<?php
require_once BASE_PATH . '/core/Model.php';

class TransactionModel extends Model {
    protected $table = 'transactions';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllTransactions($limit = null) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.transaction_type != '' AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC, t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTransactionById($id) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.id = ? AND t.transaction_type != ''";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createTransaction($data) {
        // $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $_SESSION['user_id'] ?? 1;
        return $this->create($data);
    }
    
    public function updateTransaction($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $_SESSION['user_id'] ?? 1;
        return $this->update($id, $data);
    }
    
    public function deleteTransaction($id) {
        // Soft delete
        return $this->update($id, [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id'] ?? 1
        ]);
    }
    
    public function getTransactionsByType($type) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.transaction_type = ? AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTransactionsByDateRange($startDate, $endDate) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.transaction_date BETWEEN ? AND ?
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTransactionsByAccount($accountId) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.account_id = ?
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTransactionsBySupplier($supplierId) {
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.supplier_id = ?
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTodayTransactions() {
        $today = date('Y-m-d');
        return $this->getTransactionsByDateRange($today, $today);
    }
    
    public function getTransactionStats() {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credits,
                    SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debits,
                    COUNT(CASE WHEN DATE(transaction_date) = CURDATE() THEN 1 END) as today_transactions
                FROM {$this->table} 
                WHERE transaction_type != ''";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAccountBalance($accountId, $asOfDate = null) {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END), 0) as credits,
                    COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END), 0) as debits
                FROM {$this->table} 
                WHERE account_id = ? AND transaction_type != ''";
        
        $params = [$accountId];
        
        if ($asOfDate) {
            $sql .= " AND transaction_date <= ?";
            $params[] = $asOfDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'credits' => $result['credits'],
            'debits' => $result['debits'],
            'balance' => $result['credits'] - $result['debits']
        ];
    }
    
    public function validateTransaction($data) {
        $errors = [];
        
        if (empty($data['transaction_date'])) {
            $errors[] = 'Transaction date is required';
        }
        
        if (empty($data['account_id'])) {
            $errors[] = 'Account is required';
        }
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'Amount must be greater than zero';
        }
        
        if (empty($data['transaction_type']) || !in_array($data['transaction_type'], ['debit', 'credit'])) {
            $errors[] = 'Transaction type must be debit or credit';
        }
        
        return $errors;
    }
    
    public function getTransactionByReference($referenceNo) {
        $sql = "SELECT id FROM {$this->table} WHERE reference_no = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$referenceNo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function generateUniqueReferenceNumber() {
        $maxAttempts = 10;
        $attempt = 0;
        
        do {
            $referenceNo = 'CR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $existing = $this->getTransactionByReference($referenceNo);
            $attempt++;
        } while ($existing && $attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            // If we still can't find a unique one, add a shorter suffix
            $referenceNo = 'CR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . '-' . rand(100, 999);
        }
        
        return $referenceNo;
    }
    
    public function createEnhancedTransaction($data) {
        try {
            $this->db->beginTransaction();
            
            $transactionId = null;
            $totalAmount = 0;
            
            // Create main transaction record (header only)
            // Normalize payment form based on transaction type
            $normalizedPaymentForm = $data['payment_form'] ?? null;
            if (($data['transaction_type'] ?? '') === 'check_disbursement') {
                $normalizedPaymentForm = 'check';
            } elseif (($data['transaction_type'] ?? '') === 'cash_disbursement') {
                $normalizedPaymentForm = 'cash';
            } else {
                $normalizedPaymentForm = $normalizedPaymentForm ?? 'cash';
            }

            $mainTransaction = [
                'transaction_type' => $data['transaction_type'], // Use the actual transaction type
                'reference_no' => $data['voucher_number'] ?? $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'description' => $data['particulars'] ?? $data['payment_for'] ?? '',
                'created_by' => $_SESSION['user_id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'payment_form' => $normalizedPaymentForm,
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                'account_id' => $data['accounts'][0]['account_id'] ?? 1, // Use first account as default
                // Cash disbursement specific fields
                'payee_name' => $data['payee_name'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'cwo_number' => $data['cwo_number'] ?? null,
                'ebr_number' => $data['ebr_number'] ?? null,
                'check_date' => $data['check_date'] ?? null,
                'cv_status' => $data['cv_status'] ?? 'Active',
                'cv_checked' => $data['cv_checked'] ?? 'Checked',
                'check_payment_status' => $data['check_payment_status'] ?? 'Approved',
                'return_reason' => $data['return_reason'] ?? null
            ];
            
            // Calculate total amount
            foreach ($data['account_distribution'] as $account) {
                $totalAmount += floatval($account['debit'] ?? 0);
            }
            
            $mainTransaction['amount'] = $totalAmount;
            
            // Insert main transaction
            $transactionId = $this->create($mainTransaction);
            
            if (!$transactionId) {
                throw new Exception('Failed to create main transaction');
            }
            
            // Create individual transaction entries for each account
            foreach ($data['account_distribution'] as $account) {
                $accountId = $account['account_id'];
                $subsidiaryId = $account['subsidiary_id'] ?? null;
                $debit = floatval($account['debit'] ?? 0);
                $credit = floatval($account['credit'] ?? 0);
                
                // Validate account_id exists
                if (empty($accountId)) {
                    throw new Exception('Account ID is required for all entries');
                }
                
                // Ensure accountId is an integer
                $accountId = intval($accountId);
                if ($accountId <= 0) {
                    throw new Exception('Invalid account ID: ' . $accountId);
                }
                
                // Check if account exists in chart_of_accounts
                $accountCheck = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE id = ? AND status = 'active'");
                $accountCheck->execute([$accountId]);
                if (!$accountCheck->fetch()) {
                    throw new Exception('Invalid account ID: ' . $accountId . ' - Account not found or inactive');
                }
                
                if ($debit > 0) {
                    $debitTransaction = [
                        'transaction_type' => "debit",
                        'reference_no' => ($data['reference_no'] ?? $data['voucher_number']) . '-D' . $accountId . '-' . rand(100, 999),
                        'transaction_date' => $data['transaction_date'],
                        'amount' => $debit,
                        'account_id' => $accountId,
                        'supplier_id' => $subsidiaryId,
                        'project_id' => $account['project_id'] ?? null,
                        'department_id' => $account['department_id'] ?? null,
                        'description' => $data['payment_for'] ?? $data['particulars'] ?? '',
                        // Propagate header statuses to distribution rows so they match
                        'payment_form' => $normalizedPaymentForm,
                        'cv_status' => $data['cv_status'] ?? null,
                        'cv_checked' => $data['cv_checked'] ?? null,
                        'check_payment_status' => $data['check_payment_status'] ?? null,
                        'created_by' => $_SESSION['user_id'] ?? 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'parent_transaction_id' => $transactionId
                    ];
                    
                    if (!$this->create($debitTransaction)) {
                        throw new Exception('Failed to create debit transaction for account ' . $accountId);
                    }
                }
                
                if ($credit > 0) {
                    $creditTransaction = [
                        'transaction_type' => "credit",
                        'reference_no' => ($data['reference_no'] ?? $data['voucher_number']) . '-C' . $accountId . '-' . rand(100, 999),
                        'transaction_date' => $data['transaction_date'],
                        'amount' => $credit,
                        'account_id' => $accountId,
                        'supplier_id' => $subsidiaryId,
                        'project_id' => $account['project_id'] ?? null,
                        'department_id' => $account['department_id'] ?? null,
                        'description' => $data['payment_for'] ?? $data['particulars'] ?? '',
                        'payment_form' => $normalizedPaymentForm,
                        'cv_status' => $data['cv_status'] ?? null,
                        'cv_checked' => $data['cv_checked'] ?? null,
                        'check_payment_status' => $data['check_payment_status'] ?? null,
                        'created_by' => $_SESSION['user_id'] ?? 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'parent_transaction_id' => $transactionId
                    ];
                    
                    if (!$this->create($creditTransaction)) {
                        throw new Exception('Failed to create credit transaction for account ' . $accountId);
                    }
                }
            }
            
            $this->db->commit();
            return $transactionId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getTransactionWithDetails($id) {
        error_log('getTransactionWithDetails called with ID: ' . $id);
        
        // Get main transaction
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, u.username as created_by_user
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN users u ON t.created_by = u.id 
                WHERE t.id = ? AND t.parent_transaction_id IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $mainTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Main transaction found: ' . ($mainTransaction ? 'YES' : 'NO'));
        if ($mainTransaction) {
            error_log('Main transaction data: ' . json_encode($mainTransaction));
        }
        
        if (!$mainTransaction) {
            return null;
        }
        
        // Get child transactions (debit/credit entries)
        $sql = "SELECT t.*, coa.account_name, s.supplier_name, p.project_name, d.department_name
                FROM {$this->table} t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                LEFT JOIN suppliers s ON t.supplier_id = s.id 
                LEFT JOIN projects p ON t.project_id = p.id 
                LEFT JOIN departments d ON t.department_id = d.id 
                WHERE t.parent_transaction_id = ? AND (t.transaction_type IN ('debit', 'credit') OR t.transaction_type = '' OR t.transaction_type IS NULL)
                ORDER BY t.transaction_type, t.account_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $childTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('Child transactions found: ' . count($childTransactions));
        if (!empty($childTransactions)) {
            error_log('Child transactions data: ' . json_encode($childTransactions));
        }
        
        $mainTransaction['child_transactions'] = $childTransactions;
        
        return $mainTransaction;
    }
    
    /**
     * Update header statuses and propagate to child distribution rows.
     */
    public function updateHeaderStatuses(int $headerId, ?string $cvStatus = null, ?string $cvChecked = null, ?string $checkPaymentStatus = null): bool {
        // Update main header
        $sets = [];
        $params = [];
        if ($cvStatus !== null) { $sets[] = 'cv_status = ?'; $params[] = $cvStatus; }
        if ($cvChecked !== null) { $sets[] = 'cv_checked = ?'; $params[] = $cvChecked; }
        if ($checkPaymentStatus !== null) { $sets[] = 'check_payment_status = ?'; $params[] = $checkPaymentStatus; }
        if (empty($sets)) { return false; }
        $params[] = $headerId;
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ? AND parent_transaction_id IS NULL';
        $stmt = $this->db->prepare($sql);
        $okHeader = $stmt->execute($params);
        
        // Propagate to children
        $paramsChild = [];
        $setsChild = [];
        if ($cvStatus !== null) { $setsChild[] = 'cv_status = ?'; $paramsChild[] = $cvStatus; }
        if ($cvChecked !== null) { $setsChild[] = 'cv_checked = ?'; $paramsChild[] = $cvChecked; }
        if ($checkPaymentStatus !== null) { $setsChild[] = 'check_payment_status = ?'; $paramsChild[] = $checkPaymentStatus; }
        $paramsChild[] = $headerId;
        $sqlChild = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $setsChild) . ', updated_at = NOW() WHERE parent_transaction_id = ?';
        $stmtChild = $this->db->prepare($sqlChild);
        $okChildren = $stmtChild->execute($paramsChild);
        
        return $okHeader && $okChildren;
    }
    

}
?> 