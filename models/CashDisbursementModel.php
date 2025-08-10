<?php

require_once 'core/Model.php';
require_once 'models/TransactionModel.php';
require_once 'models/AccountingParametersModel.php';

class CashDisbursementModel extends Model {
    protected $table = 'transactions';
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
    }
    
    /**
     * Create a new cash disbursement transaction
     */
    public function createCashDisbursement($data) {
        try {
            // Adapt input from view to enhanced transaction format
            $accounts = [];
            $totalDebit = 0; $totalCredit = 0;
            foreach ($data['account_distribution'] as $row) {
                $accountId = intval($row['account_id'] ?? 0);
                $debit = floatval($row['debit'] ?? 0);
                $credit = floatval($row['credit'] ?? 0);
                if ($accountId > 0 && ($debit > 0 || $credit > 0)) {
                    $accounts[] = [
                        'account_id' => $accountId,
                        'project_id' => isset($row['project_id']) && $row['project_id'] !== '' ? intval($row['project_id']) : null,
                        'department_id' => isset($row['department_id']) && $row['department_id'] !== '' ? intval($row['department_id']) : null,
                        'subsidiary_id' => isset($row['subsidiary_id']) && $row['subsidiary_id'] !== '' ? intval($row['subsidiary_id']) : null,
                        'debit' => $debit,
                        'credit' => $credit
                    ];
                    $totalDebit += $debit; $totalCredit += $credit;
                }
            }
            if (empty($accounts)) {
                throw new Exception('At least one valid account entry is required');
            }
            if (abs($totalDebit - $totalCredit) >= 0.01) {
                throw new Exception('Transaction must be balanced. Difference: ₱' . number_format(abs($totalDebit - $totalCredit), 2));
            }

            // Ensure unique voucher/reference number with CV- prefix
            $reference = $data['voucher_number'] ?? $data['reference_no'] ?? null;
            if (!$reference) {
                $reference = 'CV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            // Check and regenerate if duplicate
            $attempts = 0;
            while ($this->referenceExists($reference) && $attempts < 10) {
                $reference = 'CV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $attempts++;
            }

            $payload = [
                'transaction_type' => 'cash_disbursement',
                'reference_no' => $reference,
                'transaction_date' => $data['transaction_date'],
                'payment_form' => 'cash',
                'check_number' => $data['check_number'] ?? null,
                'bank' => $data['bank'] ?? null,
                'billing_number' => $data['billing_number'] ?? null,
                // carry over particulars so TransactionModel can save description
                'particulars' => $data['particulars'] ?? ($data['description'] ?? null),
                'payee_name' => $data['payee_name'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'cwo_number' => $data['cwo_number'] ?? null,
                'ebr_number' => $data['ebr_number'] ?? null,
                'check_date' => $data['check_date'] ?? null,
                'cv_status' => $data['cv_status'] ?? 'Pending',
                'cv_checked' => $data['cv_checked'] ?? 'Pending',
                'check_payment_status' => $data['check_payment_status'] ?? 'Pending',
                'return_reason' => $data['return_reason'] ?? null,
                // TransactionModel expects 'account_distribution'
                'account_distribution' => $accounts,
                // Enhanced model calculates amount from accounts
            ];

            return $this->transactionModel->createEnhancedTransaction($payload);

        } catch (Exception $e) {
            throw new Exception('Failed to create cash disbursement: ' . $e->getMessage());
        }
    }

    private function referenceExists(string $reference): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE reference_no = ? LIMIT 1");
        $stmt->execute([$reference]);
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Get all cash disbursement transactions
     */
    public function getAllCashDisbursements() {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'cash_disbursement' 
                AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get cash disbursement by ID with details
     */
    public function getCashDisbursementById($id) {
        return $this->transactionModel->getTransactionWithDetails($id);
    }
    
    /**
     * Get cash disbursements within fiscal year (fallback to last 10 if params missing)
     */
    public function getRecentCashDisbursements($limit = 10) {
        // Fetch fiscal year range from parameters (mirror cash receipts behavior)
        $fyStart = $this->db->query("SELECT parameter_value FROM accounting_parameters WHERE parameter_name = 'fiscal_year_start' LIMIT 1")->fetchColumn();
        $fyEnd = $this->db->query("SELECT parameter_value FROM accounting_parameters WHERE parameter_name = 'fiscal_year_end' LIMIT 1")->fetchColumn();

        if ($fyStart && $fyEnd) {
            $sql = "SELECT t.*, 
                           COALESCE(t.amount, 0) as total_amount,
                           DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                    FROM {$this->table} t 
                    WHERE t.transaction_type = 'cash_disbursement' 
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
                    WHERE t.transaction_type = 'cash_disbursement' 
                      AND t.parent_transaction_id IS NULL
                    ORDER BY t.transaction_date DESC, t.id DESC 
                    LIMIT :lim";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    /**
     * Update cash disbursement transaction
     */
    public function updateCashDisbursement($id, $data) {
        try {
            // Validate required fields
            if (empty($data['reference_no']) || empty($data['amount']) || empty($data['accounts'])) {
                throw new Exception('Missing required fields for cash disbursement update');
            }
            
            // Set transaction type
            $data['transaction_type'] = 'cash_disbursement';
            
            // First, delete existing child transactions
            $this->deleteChildTransactions($id);
            
            // Update the main transaction
            $updateData = [
                'reference_no' => $data['reference_no'],
                'transaction_date' => $data['transaction_date'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? '',
                'payment_form' => $data['payment_form'] ?? '',
                'check_number' => $data['check_number'] ?? '',
                'bank' => $data['bank'] ?? '',
                'billing_number' => $data['billing_number'] ?? '',
                'payment_description' => $data['payment_description'] ?? '',
                'cv_status' => $data['cv_status'] ?? 'Pending',
                'cv_checked' => $data['cv_checked'] ?? 'Pending',
                'check_payment_status' => $data['check_payment_status'] ?? 'Pending',
                'return_reason' => $data['return_reason'] ?? '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->update($id, $updateData);
            
            // Create new child transactions
            return $this->transactionModel->createEnhancedTransaction($data);
            
        } catch (Exception $e) {
            throw new Exception('Failed to update cash disbursement: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete cash disbursement and its child transactions
     */
    public function deleteCashDisbursement($id) {
        try {
            // Delete child transactions first
            $this->deleteChildTransactions($id);
            
            // Delete main transaction
            return $this->delete($id);
            
        } catch (Exception $e) {
            throw new Exception('Failed to delete cash disbursement: ' . $e->getMessage());
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
     * Get cash disbursement statistics
     */
    public function getCashDisbursementStats() {
        $sql = "SELECT 
                    COUNT(*) as total_disbursements,
                    COALESCE(SUM(amount), 0) as total_amount,
                    DATE_FORMAT(transaction_date, '%Y-%m') as month
                FROM {$this->table} 
                WHERE transaction_type = 'cash_disbursement' 
                AND parent_transaction_id IS NULL
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get disbursements by status
     */
    public function getDisbursementsByStatus($status) {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'cash_disbursement' 
                AND t.parent_transaction_id IS NULL
                AND t.cv_status = ?
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 