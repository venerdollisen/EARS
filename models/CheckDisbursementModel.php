<?php

require_once 'core/Model.php';
require_once 'models/TransactionModel.php';

class CheckDisbursementModel extends Model {
    protected $table = 'transactions';
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
    }
    
    /**
     * Create a new check disbursement transaction
     */
    public function createCheckDisbursement($data) {
        try {
            // Adapt to enhanced payload like cash disbursement
            // Validate distribution
            if (empty($data['account_distribution']) || !is_array($data['account_distribution'])) {
                throw new Exception('Missing required fields for check disbursement');
            }

            // Ensure transaction type
            $data['transaction_type'] = 'check_disbursement';
            // Ensure reference number exists (use voucher_number when provided)
            if (empty($data['reference_no']) && !empty($data['voucher_number'])) {
                $data['reference_no'] = $data['voucher_number'];
            }
            // Forward particulars as description
            if (!empty($data['particulars']) && empty($data['payment_for'])) {
                $data['payment_for'] = $data['particulars'];
            }
            // Force payment form to 'check'
            $data['payment_form'] = 'check';

            // Total amount will be computed in createEnhancedTransaction; just ensure transaction_date exists
            if (empty($data['transaction_date'])) {
                throw new Exception('Missing required fields for check disbursement');
            }

            return $this->transactionModel->createEnhancedTransaction($data);

        } catch (Exception $e) {
            throw new Exception('Failed to create check disbursement: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all check disbursement transactions
     */
    public function getAllCheckDisbursements() {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'check_disbursement' 
                AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get check disbursement by ID with details
     */
    public function getCheckDisbursementById($id) {
        return $this->transactionModel->getTransactionWithDetails($id);
    }
    
    /**
     * Get recent check disbursements (last 10)
     */
    public function getRecentCheckDisbursements($limit = 10) {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'check_disbursement' 
                AND t.parent_transaction_id IS NULL
                ORDER BY t.transaction_date DESC, t.id DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update check disbursement transaction
     */
    public function updateCheckDisbursement($id, $data) {
        try {
            // Validate required fields
            if (empty($data['reference_no']) || empty($data['amount']) || empty($data['accounts'])) {
                throw new Exception('Missing required fields for check disbursement update');
            }
            
            // Set transaction type
            $data['transaction_type'] = 'check_disbursement';
            
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
            throw new Exception('Failed to update check disbursement: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete check disbursement and its child transactions
     */
    public function deleteCheckDisbursement($id) {
        try {
            // Delete child transactions first
            $this->deleteChildTransactions($id);
            
            // Delete main transaction
            return $this->delete($id);
            
        } catch (Exception $e) {
            throw new Exception('Failed to delete check disbursement: ' . $e->getMessage());
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
     * Get check disbursement statistics
     */
    public function getCheckDisbursementStats() {
        $sql = "SELECT 
                    COUNT(*) as total_disbursements,
                    COALESCE(SUM(amount), 0) as total_amount,
                    DATE_FORMAT(transaction_date, '%Y-%m') as month
                FROM {$this->table} 
                WHERE transaction_type = 'check_disbursement' 
                AND parent_transaction_id IS NULL
                GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get check disbursements by status
     */
    public function getCheckDisbursementsByStatus($status) {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'check_disbursement' 
                AND t.parent_transaction_id IS NULL
                AND t.cv_status = ?
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get check disbursements by check payment status
     */
    public function getCheckDisbursementsByPaymentStatus($status) {
        $sql = "SELECT t.*, 
                       COALESCE(t.amount, 0) as total_amount,
                       DATE_FORMAT(t.transaction_date, '%d/%m/%Y') as formatted_date
                FROM {$this->table} t 
                WHERE t.transaction_type = 'check_disbursement' 
                AND t.parent_transaction_id IS NULL
                AND t.check_payment_status = ?
                ORDER BY t.transaction_date DESC, t.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 