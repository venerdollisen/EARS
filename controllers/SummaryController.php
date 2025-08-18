<?php

require_once 'core/Controller.php';
require_once 'models/ChartOfAccountsModel.php';
require_once 'models/CashReceiptModel.php';
require_once 'models/CashDisbursementModel.php';
require_once 'models/CheckDisbursementModel.php';

class SummaryController extends Controller {
    private $chartOfAccountsModel;
    private $cashReceiptModel;
    private $cashDisbursementModel;
    private $checkDisbursementModel;
    
    public function __construct() {
        parent::__construct();
        $this->chartOfAccountsModel = new ChartOfAccountsModel();
        $this->cashReceiptModel = new CashReceiptModel();
        $this->cashDisbursementModel = new CashDisbursementModel();
        $this->checkDisbursementModel = new CheckDisbursementModel();
    }
    
    /**
     * Display summary of all books
     */
    public function index() {
        $this->requireAuth();
        
        // Get summary statistics
        $summaryData = $this->getSummaryData();
        
        $this->render('summary/index', [
            'summaryData' => $summaryData,
            'user' => $this->auth->getCurrentUser()
        ]);
    }

    /**
     * API: Overview numbers for cards with optional fiscal/custom date filters
     */
    public function getOverview() {
        $this->requireAuth();
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            $cashDisbursementTotal = $this->sumAmount('cash_disbursement', $dateFrom, $dateTo);
            $checkDisbursementTotal = $this->sumAmount('check_disbursement', $dateFrom, $dateTo);
            $totalReceipts = $this->sumAmount('cash_receipt', $dateFrom, $dateTo);
            
            $overview = [
                'total_receipts' => $totalReceipts,
                'cash_disbursement_total' => $cashDisbursementTotal,
                'check_disbursement_total' => $checkDisbursementTotal,
                'total_disbursements' => $cashDisbursementTotal + $checkDisbursementTotal,
                'net_cash_flow' => $totalReceipts - ($cashDisbursementTotal + $checkDisbursementTotal),
                'total_transactions' => $this->countHeaders(null, $dateFrom, $dateTo)
            ];
            $this->jsonResponse(['success' => true, 'data' => $overview]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Status counts per type (Pending/Approved/Rejected) + payment form split
     */
    public function getStatusCounts() {
        $this->requireAuth();
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            $data = [
                'cash_receipt' => $this->statusCounts('cash_receipt', $dateFrom, $dateTo),
                'cash_disbursement' => $this->statusCounts('cash_disbursement', $dateFrom, $dateTo),
                'check_disbursement' => $this->statusCounts('check_disbursement', $dateFrom, $dateTo),
                'payment_forms' => $this->paymentFormSplit($dateFrom, $dateTo)
            ];
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Pending approvals list (latest items)
     */
    public function getPendingApprovals() {
        $this->requireAuth();
        try {
            [$dateFrom, $dateTo] = $this->resolveDateRange();
            
            // Combine pending approvals from all three tables
            $sql = "SELECT id, 'cash_disbursement' as transaction_type, reference_no, payee_name, total_amount as amount, transaction_date, status as payment_status
                FROM cash_disbursements 
                WHERE status = 'pending'
                AND transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT id, 'check_disbursement' as transaction_type, reference_no, payee_name, total_amount as amount, transaction_date, status as payment_status
                FROM check_disbursements 
                WHERE status = 'pending'
                AND transaction_date BETWEEN ? AND ?
                
                ORDER BY transaction_date DESC 
                LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->jsonResponse(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get summary data for all books
     */
    private function getSummaryData() {
        $data = [];
        
        // Cash Receipt Summary
        $data['cash_receipt'] = [
            'total_count' => $this->getTransactionCount('cash_receipt'),
            'total_amount' => $this->getTransactionAmount('cash_receipt'),
            'this_month_count' => $this->getTransactionCountByMonth('cash_receipt'),
            'this_month_amount' => $this->getTransactionAmountByMonth('cash_receipt'),
            'recent_transactions' => $this->cashReceiptModel->getRecentCashReceipts(5)
        ];
        
        // Cash Disbursement Summary
        $data['cash_disbursement'] = [
            'total_count' => $this->getTransactionCount('cash_disbursement'),
            'total_amount' => $this->getTransactionAmount('cash_disbursement'),
            'this_month_count' => $this->getTransactionCountByMonth('cash_disbursement'),
            'this_month_amount' => $this->getTransactionAmountByMonth('cash_disbursement'),
            'recent_transactions' => $this->cashDisbursementModel->getRecentCashDisbursements(5)
        ];
        
        // Check Disbursement Summary
        $data['check_disbursement'] = [
            'total_count' => $this->getTransactionCount('check_disbursement'),
            'total_amount' => $this->getTransactionAmount('check_disbursement'),
            'this_month_count' => $this->getTransactionCountByMonth('check_disbursement'),
            'this_month_amount' => $this->getTransactionAmountByMonth('check_disbursement'),
            'recent_transactions' => $this->checkDisbursementModel->getRecentCheckDisbursements(5)
        ];
        
        // Account Summary
        $data['accounts'] = [
            'total_accounts' => $this->chartOfAccountsModel->getAllAccounts(),
            'account_types' => $this->getAccountTypeSummary()
        ];
        
        // Overall Summary
        $data['overall'] = [
            'total_receipts' => $data['cash_receipt']['total_amount'],
            'total_disbursements' => $data['cash_disbursement']['total_amount'] + $data['check_disbursement']['total_amount'],
            'net_cash_flow' => $data['cash_receipt']['total_amount'] - ($data['cash_disbursement']['total_amount'] + $data['check_disbursement']['total_amount']),
            'total_transactions' => $data['cash_receipt']['total_count'] + $data['cash_disbursement']['total_count'] + $data['check_disbursement']['total_count']
        ];
        
        return $data;
    }
    
    /**
     * Get transaction count by type
     */
    private function getTransactionCount($type) {
        $table = $this->getTableName($type);
        if (!$table) return 0;
        
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get transaction amount by type
     */
    private function getTransactionAmount($type) {
        $table = $this->getTableName($type);
        if (!$table) return 0;
        
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM {$table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Get transaction count by type for current month
     */
    private function getTransactionCountByMonth($type) {
        $table = $this->getTableName($type);
        if (!$table) return 0;
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get transaction amount by type for current month
     */
    private function getTransactionAmountByMonth($type) {
        $table = $this->getTableName($type);
        if (!$table) return 0;
        
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM {$table} WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Get table name for transaction type
     */
    private function getTableName($type) {
        switch ($type) {
            case 'cash_receipt':
                return 'cash_receipts';
            case 'cash_disbursement':
                return 'cash_disbursements';
            case 'check_disbursement':
                return 'check_disbursements';
            default:
                return null;
        }
    }
    
    /**
     * Get account type summary
     */
    private function getAccountTypeSummary() {
        $sql = "SELECT 
                    at.group_name,
                    COUNT(coa.id) as account_count,
                    0 as total_balance
                FROM account_title_groups at
                LEFT JOIN chart_of_accounts coa ON at.id = coa.group_id
                WHERE coa.status = 'active'
                GROUP BY at.id, at.group_name
                ORDER BY at.group_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helpers
    private function resolveDateRange(): array {
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        if (!$dateFrom || !$dateTo) {
            // Default to current year
            $dateFrom = date('Y-01-01');
            $dateTo = date('Y-12-31');
        }
        return [$dateFrom, $dateTo];
    }

    private function sumAmount(?string $type, string $dateFrom, string $dateTo): float {
        $table = $this->getTableName($type);
        if (!$table) return 0;

        $sql = "SELECT COALESCE(SUM(total_amount),0) AS total FROM {$table}
                WHERE transaction_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    private function countHeaders(?string $type, string $dateFrom, string $dateTo): int {
        $table = $this->getTableName($type);
        if (!$table) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM {$table}
                WHERE transaction_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];
        if ($type) { $sql .= " AND transaction_type = ?"; $params[] = $type; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    }

    private function statusCounts(string $type, string $dateFrom, string $dateTo): array {
        $table = $this->getTableName($type);
        if (!$table) return ['pending'=>0,'approved'=>0,'rejected'=>0];

        $sql = "SELECT 
                    SUM(status = 'pending') AS pending,
                    SUM(status = 'approved') AS approved,
                    SUM(status = 'rejected') AS rejected
                FROM {$table}
                WHERE transaction_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['pending'=>0,'approved'=>0,'rejected'=>0];
        return $row;
    }

    private function paymentFormSplit(string $dateFrom, string $dateTo): array {
        $table = $this->getTableName('cash_receipt'); // Assuming cash_receipts is the source for payment_form
        if (!$table) return [];

        $sql = "SELECT payment_form, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS total
                FROM {$table}
                WHERE transaction_date BETWEEN ? AND ?
                GROUP BY payment_form";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly summary data for charts
     */
    public function getMonthlyData() {
        $this->requireAuth();
        
        try {
            // Combine data from all three tables
            $sql = "SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'cash_receipt' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM cash_receipts 
                    WHERE transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'cash_disbursement' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM cash_disbursements 
                    WHERE transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'check_disbursement' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM check_disbursements 
                    WHERE transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    ORDER BY month DESC, transaction_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get monthly data: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get account balance summary
     */
    public function getAccountBalance() {
        $this->requireAuth();
        
        try {
            // Combine data from all three distribution tables
            $sql = "SELECT 
                        coa.account_name,
                        coa.account_code,
                        0 as opening_balance,
                        COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) as total_debits,
                        COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0) as total_credits
                    FROM chart_of_accounts coa
                    LEFT JOIN cash_receipt_details crd ON coa.id = crd.account_id
                    LEFT JOIN cash_disbursement_details cdd ON coa.id = cdd.account_id
                    LEFT JOIN check_disbursement_details chdd ON coa.id = chdd.account_id
                    WHERE coa.status = 'active'
                    GROUP BY coa.id, coa.account_name, coa.account_code
                    ORDER BY coa.account_code";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate current balance for each account
            foreach ($data as &$row) {
                $row['current_balance'] = $row['total_debits'] - $row['total_credits'];
            }
            
            $this->jsonResponse(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get account balance: ' . $e->getMessage()], 500);
        }
    }
} 