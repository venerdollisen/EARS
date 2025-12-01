<?php

require_once 'core/Controller.php';
require_once 'models/ChartOfAccountsModel.php';

class DashboardController extends Controller {
    private $chartOfAccountsModel;
    
    public function __construct() {
        parent::__construct();
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

            // Log the resolved date range for debugging
            error_log("Dashboard date range resolved: {$dateFrom} to {$dateTo}");
            
            // Get total receipts from cash_receipts table
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM cash_receipts WHERE transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $receipts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total cash disbursements from cash_disbursements table
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM cash_disbursements WHERE transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $cashDisb = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get total check disbursements from check_disbursements table
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM check_disbursements WHERE transaction_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $checkDisb = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total transactions count from all three tables
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM cash_receipts WHERE transaction_date BETWEEN ? AND ?) +
                        (SELECT COUNT(*) FROM cash_disbursements WHERE transaction_date BETWEEN ? AND ?) +
                        (SELECT COUNT(*) FROM check_disbursements WHERE transaction_date BETWEEN ? AND ?) as total";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
            $transactions = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalReceipts = $receipts['total'] ?? 0;
            $totalCashDisb = $cashDisb['total'] ?? 0;
            $totalCheckDisb = $checkDisb['total'] ?? 0;
            
            return [
                'total_receipts' => $totalReceipts,
                'cash_disbursements' => $totalCashDisb,
                'check_disbursements' => $totalCheckDisb,
                'total_transactions' => $transactions['total'] ?? 0,
                // include date range for debugging
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ];
            
        } catch (Exception $e) {
            return [
                'total_receipts' => 0,
                'cash_disbursements' => 0,
                'check_disbursements' => 0,
                'total_transactions' => 0,
                'date_from' => null,
                'date_to' => null
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
            
            // Combine data from all three tables
            $sql = "SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'cash_receipt' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM cash_receipts 
                    WHERE transaction_date BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'cash_disbursement' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM cash_disbursements 
                    WHERE transaction_date BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    UNION ALL
                    
                    SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') as month,
                        'check_disbursement' as transaction_type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as total_amount
                    FROM check_disbursements 
                    WHERE transaction_date BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                    
                    ORDER BY month ASC, transaction_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
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
            
            // Combine data from all three tables
            $sql = "SELECT 
                        'cash_receipt' as type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as amount
                    FROM cash_receipts 
                    WHERE transaction_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT 
                        'cash_disbursement' as type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as amount
                    FROM cash_disbursements 
                    WHERE transaction_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT 
                        'check_disbursement' as type,
                        COUNT(*) as count,
                        COALESCE(SUM(total_amount), 0) as amount
                    FROM check_disbursements 
                    WHERE transaction_date BETWEEN ? AND ?
                    
                    ORDER BY amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
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
            
            // Combine data from all three distribution tables
            $sql = "SELECT 
                        coa.account_name,
                        coa.account_code,
                        COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) as total_debits,
                        COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) +
                        COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0) as total_credits
                    FROM chart_of_accounts coa
                    LEFT JOIN cash_receipt_details crd ON coa.id = crd.account_id
                    LEFT JOIN cash_receipts cr ON crd.cash_receipt_id = cr.id AND cr.transaction_date BETWEEN ? AND ?
                    LEFT JOIN cash_disbursement_details cdd ON coa.id = cdd.account_id
                    LEFT JOIN cash_disbursements cd ON cdd.cash_disbursement_id = cd.id AND cd.transaction_date BETWEEN ? AND ?
                    LEFT JOIN check_disbursement_details chdd ON coa.id = chdd.account_id
                    LEFT JOIN check_disbursements chd ON chdd.check_disbursement_id = chd.id AND chd.transaction_date BETWEEN ? AND ?
                    WHERE coa.status = 'active'
                    GROUP BY coa.id, coa.account_name, coa.account_code
                    HAVING (COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
                           COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
                           COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) -
                           COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) -
                           COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) -
                           COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0)) != 0
                    ORDER BY ABS((COALESCE(SUM(CASE WHEN crd.transaction_type = 'debit' THEN crd.amount ELSE 0 END), 0) +
                                 COALESCE(SUM(CASE WHEN cdd.transaction_type = 'debit' THEN cdd.amount ELSE 0 END), 0) +
                                 COALESCE(SUM(CASE WHEN chdd.transaction_type = 'debit' THEN chdd.amount ELSE 0 END), 0) -
                                 COALESCE(SUM(CASE WHEN crd.transaction_type = 'credit' THEN crd.amount ELSE 0 END), 0) -
                                 COALESCE(SUM(CASE WHEN cdd.transaction_type = 'credit' THEN cdd.amount ELSE 0 END), 0) -
                                 COALESCE(SUM(CASE WHEN chdd.transaction_type = 'credit' THEN chdd.amount ELSE 0 END), 0))) DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
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
    
    // Helpers
    private function resolveDateRange(): array {
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        if (!$dateFrom || !$dateTo) {
            // Prefer per-user fiscal dates (session or users table), otherwise fallback to accounting parameters
            try {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                // Use session values if available and not default placeholders
                $sesStart = $_SESSION['year_start'] ?? null;
                $sesEnd = $_SESSION['year_end'] ?? null;
                if ($sesStart && $sesEnd && $sesStart !== '2000-01-01' && $sesEnd !== '2000-12-31') {
                    $dateFrom = $sesStart;
                    $dateTo = $sesEnd;
                } else {
                    // Try current logged-in user's DB values
                    $currentUser = $this->auth->getCurrentUser();
                    if ($currentUser && isset($currentUser['id'])) {
                        $stmt = $this->db->prepare("SELECT year_start, year_end FROM users WHERE id = :id LIMIT 1");
                        $stmt->bindParam(':id', $currentUser['id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $userFy = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($userFy && !empty($userFy['year_start']) && !empty($userFy['year_end'])
                            && $userFy['year_start'] !== '2000-01-01' && $userFy['year_end'] !== '2000-12-31') {
                            $dateFrom = $userFy['year_start'];
                            $dateTo = $userFy['year_end'];

                            // store to session for quicker access
                            $_SESSION['year_start'] = $dateFrom;
                            $_SESSION['year_end'] = $dateTo;
                        }
                    }
                }

                // Still not set? fallback to accounting_parameters
                if (!$dateFrom || !$dateTo) {
                    $fy = $this->db->query("SELECT 
                            MAX(CASE WHEN parameter_name='fiscal_year_start' THEN parameter_value END) AS fy_start,
                            MAX(CASE WHEN parameter_name='fiscal_year_end' THEN parameter_value END) AS fy_end
                        FROM accounting_parameters")->fetch(PDO::FETCH_ASSOC);
                    $dateFrom = $fy['fy_start'] ?: date('Y-01-01');
                    $dateTo = $fy['fy_end'] ?: date('Y-12-31');
                }
            } catch (Exception $e) {
                // On error, default to current calendar year
                $dateFrom = date('Y-01-01');
                $dateTo = date('Y-12-31');
            }
        }
        return [$dateFrom, $dateTo];
    }
}