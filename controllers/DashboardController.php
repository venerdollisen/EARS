<?php

require_once 'core/Controller.php';
require_once 'models/TransactionModel.php';
require_once 'models/ChartOfAccountsModel.php';

class DashboardController extends Controller {
    private $transactionModel;
    private $chartOfAccountsModel;
    
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
    }
    
    /**
     * Display dashboard
     */
    public function index() {
        $this->requireAuth();
        
        // Get dashboard statistics
        $stats = $this->getStats();
        
        $this->render('dashboard/index', [
            'stats' => $stats,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        $this->requireAuth();
        
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            
            // Get total receipts
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'cash_receipt' AND parent_transaction_id IS NULL AND transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $receipts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total cash disbursements
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'cash_disbursement' AND parent_transaction_id IS NULL AND transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $cashDisb = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get total check disbursements
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE transaction_type = 'check_disbursement' AND parent_transaction_id IS NULL AND transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $checkDisb = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total transactions count
            $sql = "SELECT COUNT(*) as total FROM transactions WHERE parent_transaction_id IS NULL AND transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $transactions = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalReceipts = $receipts['total'] ?? 0;
            $totalCashDisb = $cashDisb['total'] ?? 0;
            $totalCheckDisb = $checkDisb['total'] ?? 0;
            
            return [
                'total_receipts' => $totalReceipts,
                'cash_disbursements' => $totalCashDisb,
                'check_disbursements' => $totalCheckDisb,
                'total_transactions' => $transactions['total'] ?? 0
            ];
            
        } catch (Exception $e) {
            return [
                'total_receipts' => 0,
                'cash_disbursements' => 0,
                'check_disbursements' => 0,
                'total_transactions' => 0
            ];
        }
    }
    
    /**
     * Get monthly transaction data for charts
     */
    public function getMonthlyData() {
        $this->requireAuth();
        
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            
            $sql = "SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(amount), 0) as total_amount
                    FROM transactions 
                    WHERE parent_transaction_id IS NULL
                    AND transaction_date BETWEEN ? AND ?
                    AND transaction_type IN ('cash_receipt','cash_disbursement','check_disbursement')
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m'), transaction_type
                    ORDER BY month ASC, transaction_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get monthly data: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get transaction type distribution for pie chart
     */
    public function getTransactionDistribution() {
        $this->requireAuth();
        
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            
            $sql = "SELECT 
                        transaction_type as type,
                        COUNT(*) as count,
                        COALESCE(SUM(amount), 0) as amount
                    FROM transactions 
                    WHERE parent_transaction_id IS NULL
                    AND transaction_date BETWEEN ? AND ?
                    AND transaction_type IN ('cash_receipt', 'cash_disbursement', 'check_disbursement')
                    GROUP BY transaction_type
                    ORDER BY amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get transaction distribution: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get account balance data for bar chart
     */
    public function getAccountBalance() {
        $this->requireAuth();
        
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            
            $sql = "SELECT 
                        coa.account_name,
                        coa.account_code,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debits,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credits,
                        (COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0)) as current_balance
                    FROM chart_of_accounts coa
                    LEFT JOIN transactions t ON coa.id = t.account_id AND t.parent_transaction_id IS NOT NULL
                    WHERE coa.status = 'active'
                    AND (t.transaction_date IS NULL OR t.transaction_date BETWEEN ? AND ?)
                    GROUP BY coa.id, coa.account_name, coa.account_code
                    HAVING (COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0)) != 0
                    ORDER BY ABS(COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0)) DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get account balance: ' . $e->getMessage()], 500);
        }
    }
    
    // Helpers
    private function resolveDateRange(): array {
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        if (!$dateFrom || !$dateTo) {
            // Default to fiscal year if set; otherwise current year
            $fy = $this->db->query("SELECT 
                    MAX(CASE WHEN parameter_name='fiscal_year_start' THEN parameter_value END) AS fy_start,
                    MAX(CASE WHEN parameter_name='fiscal_year_end' THEN parameter_value END) AS fy_end
                FROM accounting_parameters")->fetch(PDO::FETCH_ASSOC);
            $dateFrom = $fy['fy_start'] ?: date('Y-01-01');
            $dateTo = $fy['fy_end'] ?: date('Y-12-31');
        }
        return [$dateFrom, $dateTo];
    }
} 