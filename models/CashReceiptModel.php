<?php

require_once 'core/Model.php';
require_once 'models/TransactionModel.php';

class CashReceiptModel extends Model {
    protected $table = 'transactions';
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
    }
    
    /**
     * Create a new cash receipt transaction
     */
    public function createCashReceipt($transactionData, $accountDistribution) {
        try {
            $this->db->beginTransaction();
            
            // Server-side guard: ensure distribution is balanced
            $sumDebit = 0.0;
            $sumCredit = 0.0;
            $headerAccountId = null;
            foreach ($accountDistribution as $distribution) {
                $sumDebit += floatval($distribution['debit'] ?? 0);
                $sumCredit += floatval($distribution['credit'] ?? 0);
                if ($headerAccountId === null && !empty($distribution['account_id'])) {
                    $headerAccountId = intval($distribution['account_id']);
                }
            }
            if (abs($sumDebit - $sumCredit) > 0.01) {
                throw new Exception('Account distribution is unbalanced. Debit ₱' . number_format($sumDebit, 2) . ' vs Credit ₱' . number_format($sumCredit, 2));
            }

            if ($headerAccountId === null || $headerAccountId <= 0) {
                throw new Exception('No valid account selected for account distribution.');
            }

            // Validate header account exists to satisfy FK constraint
            $headerAccountCheck = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE id = ?");
            $headerAccountCheck->execute([$headerAccountId]);
            if (!$headerAccountCheck->fetch()) {
                throw new Exception('Selected account does not exist (ID: ' . $headerAccountId . ').');
            }

            // Normalize header amount from totals
            $transactionData['amount'] = $sumDebit;
            // Set header account_id to satisfy NOT NULL + FK
            $transactionData['account_id'] = $headerAccountId;

            // Create main transaction
            $mainTransactionId = $this->create($transactionData);
            
            if (!$mainTransactionId) {
                throw new Exception('Failed to create main transaction');
            }
            
            // Create child transactions for account distribution
            foreach ($accountDistribution as $distribution) {
                if (empty($distribution['account_id'])) {
                    continue;
                }
                
                // Debug: Log the account distribution data
                error_log('Account distribution: ' . json_encode($distribution));
                
                // Validate account_id exists and normalize related IDs
                $accountId = intval($distribution['account_id']);
                $projectId = isset($distribution['project_id']) && $distribution['project_id'] !== '' ? intval($distribution['project_id']) : null;
                $departmentId = isset($distribution['department_id']) && $distribution['department_id'] !== '' ? intval($distribution['department_id']) : null;
                $supplierId = isset($distribution['subsidiary_id']) && $distribution['subsidiary_id'] !== '' ? intval($distribution['subsidiary_id']) : null;
                error_log('Converted account ID: ' . $accountId);
                
                if ($accountId <= 0) {
                    throw new Exception('Invalid account ID: ' . $distribution['account_id']);
                }
                
                // Check if account exists in chart_of_accounts (allow inactive accounts for now)
                $accountCheck = $this->db->prepare("SELECT id, status FROM chart_of_accounts WHERE id = ?");
                $accountCheck->execute([$accountId]);
                $account = $accountCheck->fetch();
                
                if (!$account) {
                    // Debug: Check what accounts are available
                    $allAccounts = $this->db->prepare("SELECT id, account_code, account_name, status FROM chart_of_accounts ORDER BY id");
                    $allAccounts->execute();
                    $availableAccounts = $allAccounts->fetchAll(PDO::FETCH_ASSOC);
                    error_log('Available accounts: ' . json_encode($availableAccounts));
                    
                    throw new Exception('Invalid account ID: ' . $accountId . ' - Account not found. Available accounts: ' . json_encode($availableAccounts));
                }
                
                // For now, allow inactive accounts to be used (we can make this stricter later)
                if ($account['status'] !== 'active') {
                    error_log('Warning: Using inactive account ID: ' . $accountId . ' with status: ' . $account['status']);
                }
                
                // Create debit transaction
                if (!empty($distribution['debit']) && floatval($distribution['debit']) > 0) {
                    $debitData = [
                        'parent_transaction_id' => $mainTransactionId,
                        'account_id' => $accountId,
                        'amount' => floatval($distribution['debit']),
                        'transaction_type' => 'debit',
                        'reference_no' => $transactionData['reference_no'] . '-D' . substr(md5(uniqid()), 0, 2) . '-' . rand(100, 999),
                        'transaction_date' => $transactionData['transaction_date'],
                        'description' => $distribution['description'] ?? $transactionData['description'],
                        'project_id' => $projectId,
                        'department_id' => $departmentId,
                        'supplier_id' => $supplierId,
                        'created_by' => $transactionData['created_by']
                    ];
                    
                    $this->create($debitData);
                }
                
                // Create credit transaction
                if (!empty($distribution['credit']) && floatval($distribution['credit']) > 0) {
                    $creditData = [
                        'parent_transaction_id' => $mainTransactionId,
                        'account_id' => $accountId,
                        'amount' => floatval($distribution['credit']),
                        'transaction_type' => 'credit',
                        'reference_no' => $transactionData['reference_no'] . '-C' . substr(md5(uniqid()), 0, 2) . '-' . rand(100, 999),
                        'transaction_date' => $transactionData['transaction_date'],
                        'description' => $distribution['description'] ?? $transactionData['description'],
                        'project_id' => $projectId,
                        'department_id' => $departmentId,
                        'supplier_id' => $supplierId,
                        'created_by' => $transactionData['created_by']
                    ];
                    
                    $this->create($creditData);
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'transaction_id' => $mainTransactionId,
                'message' => 'Cash receipt created successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all cash receipt transactions
     */
    public function getAllCashReceipts() {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'cash_receipt' 
                AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get cash receipt by ID with details
     */
    public function getCashReceiptById($id) {
        return $this->transactionModel->getTransactionWithDetails($id);
    }
    
    /**
     * Get cash receipts within fiscal year (fallback to last 10 if params missing)
     */
    public function getRecentCashReceipts() {
        // Fetch fiscal year range from parameters
        $fyStart = $this->db->query("SELECT parameter_value FROM accounting_parameters WHERE parameter_name = 'fiscal_year_start' LIMIT 1")->fetchColumn();
        $fyEnd = $this->db->query("SELECT parameter_value FROM accounting_parameters WHERE parameter_name = 'fiscal_year_end' LIMIT 1")->fetchColumn();

        if ($fyStart && $fyEnd) {
            $sql = "SELECT t.*, 
                           COALESCE(t.amount, 0) as total_amount,
                           DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                    FROM {$this->table} t 
                    WHERE t.transaction_type = 'cash_receipt' 
                      AND t.parent_transaction_id IS NULL
                      AND t.transaction_date BETWEEN :start AND :end
                    ORDER BY t.transaction_date DESC, t.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start', $fyStart);
            $stmt->bindValue(':end', $fyEnd);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Fallback to last 10 if fiscal parameters are not set
            $sql = "SELECT t.*, 
                           COALESCE(t.amount, 0) as total_amount,
                           DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                    FROM {$this->table} t 
                    WHERE t.transaction_type = 'cash_receipt' 
                      AND t.parent_transaction_id IS NULL
                    ORDER BY t.transaction_date DESC, t.id DESC 
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    /**
     * Update cash receipt transaction
     */
    public function updateCashReceipt($id, $updateData) {
        try {
            $this->db->beginTransaction();
            
            // Update main transaction
            $sql = "UPDATE {$this->table} SET 
                    reference_no = :reference_no,
                    transaction_date = :transaction_date,
                    check_payment_status = :check_payment_status,
                    updated_by = :updated_by,
                    updated_at = :updated_at
                    WHERE id = :id AND transaction_type = 'cash_receipt'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':reference_no', $updateData['reference_no']);
            $stmt->bindParam(':transaction_date', $updateData['transaction_date']);
            $stmt->bindParam(':check_payment_status', $updateData['payment_status']);
            $stmt->bindParam(':updated_by', $updateData['updated_by']);
            $stmt->bindParam(':updated_at', $updateData['updated_at']);
            $stmt->bindParam(':id', $id);
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception('Failed to update cash receipt');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error updating cash receipt: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete cash receipt and its child transactions
     */
    public function deleteCashReceipt($id) {
        try {
            // Delete child transactions first
            $this->deleteChildTransactions($id);
            
            // Delete main transaction
            return $this->delete($id);
            
        } catch (Exception $e) {
            throw new Exception('Failed to delete cash receipt: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete child transactions for a given parent transaction
     */
    private function deleteChildTransactions($parentId) {
        $sql = "DELETE FROM {$this->table} WHERE parent_transaction_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$parentId]);
    }
    
    /**
     * Get cash receipt statistics
     */
    public function getCashReceiptStats() {
        $sql = "SELECT 
                    COUNT(*) as total_receipts,
                    COALESCE(SUM(amount), 0) as total_amount,
                    DATE_FORMAT(transaction_date, '%Y-%m') as month
                FROM {$this->table} 
                WHERE transaction_type = 'cash_receipt' 
                AND parent_transaction_id IS NULL
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 