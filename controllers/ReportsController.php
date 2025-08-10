<?php
require_once BASE_PATH . '/core/Controller.php';

class ReportsController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $this->requireAuth();
        $this->render('reports/index');
    }
    
    public function trialBalance() {
        $this->requireAuth();
        
        try {
            $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
            $dateTo = $_GET['date_to'] ?? date('Y-12-31');
            
            $sql = "SELECT 
                        coa.id,
                        coa.account_code,
                        coa.account_name,
                        coa.account_type,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debits,
                        COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credits,
                        (COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) - 
                         COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0)) as balance
                    FROM chart_of_accounts coa
                    LEFT JOIN transactions t ON coa.id = t.account_id 
                        AND t.parent_transaction_id IS NOT NULL
                        AND t.transaction_date BETWEEN ? AND ?
                    WHERE coa.status = 'active'
                    GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_type
                    HAVING total_debits > 0 OR total_credits > 0
                    ORDER BY coa.account_code";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $totalDebits = 0;
            $totalCredits = 0;
            foreach ($accounts as $account) {
                $totalDebits += $account['total_debits'];
                $totalCredits += $account['total_credits'];
            }
            
            $this->render('reports/trial-balance', [
                'accounts' => $accounts,
                'totalDebits' => $totalDebits,
                'totalCredits' => $totalCredits,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function generalLedger() {
        $this->requireAuth();
        
        try {
            $accountId = $_GET['account_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
            $dateTo = $_GET['date_to'] ?? date('Y-12-31');
            
            if (!$accountId) {
                $this->render('reports/general-ledger', [
                    'accounts' => $this->getActiveAccounts(),
                    'transactions' => [],
                    'account' => null
                ]);
                return;
            }
            
            // Get account details
            $accountSql = "SELECT * FROM chart_of_accounts WHERE id = ? AND status = 'active'";
            $accountStmt = $this->db->prepare($accountSql);
            $accountStmt->execute([$accountId]);
            $account = $accountStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                $this->render('errors/404');
                return;
            }
            
            // Get transactions for the account
            $sql = "SELECT 
                        t.id,
                        t.transaction_date,
                        t.transaction_type,
                        t.amount,
                        t.description,
                        pt.reference_no,
                        pt.transaction_type as parent_type
                    FROM transactions t
                    JOIN transactions pt ON t.parent_transaction_id = pt.id
                    WHERE t.account_id = ? 
                        AND t.parent_transaction_id IS NOT NULL
                        AND t.transaction_date BETWEEN ? AND ?
                    ORDER BY t.transaction_date, t.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountId, $dateFrom, $dateTo]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->render('reports/general-ledger', [
                'accounts' => $this->getActiveAccounts(),
                'transactions' => $transactions,
                'account' => $account,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function incomeStatement() {
        $this->requireAuth();
        
        try {
            $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
            $dateTo = $_GET['date_to'] ?? date('Y-12-31');
            
            // Get revenue accounts
            $revenueSql = "SELECT 
                            coa.account_name,
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as revenue
                          FROM chart_of_accounts coa
                          LEFT JOIN transactions t ON coa.id = t.account_id 
                              AND t.parent_transaction_id IS NOT NULL
                              AND t.transaction_date BETWEEN ? AND ?
                          WHERE coa.account_type = 'Revenue' AND coa.status = 'active'
                          GROUP BY coa.id, coa.account_name
                          HAVING revenue > 0";
            
            $revenueStmt = $this->db->prepare($revenueSql);
            $revenueStmt->execute([$dateFrom, $dateTo]);
            $revenues = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get expense accounts
            $expenseSql = "SELECT 
                            coa.account_name,
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as expense
                          FROM chart_of_accounts coa
                          LEFT JOIN transactions t ON coa.id = t.account_id 
                              AND t.parent_transaction_id IS NOT NULL
                              AND t.transaction_date BETWEEN ? AND ?
                          WHERE coa.account_type = 'Expense' AND coa.status = 'active'
                          GROUP BY coa.id, coa.account_name
                          HAVING expense > 0";
            
            $expenseStmt = $this->db->prepare($expenseSql);
            $expenseStmt->execute([$dateFrom, $dateTo]);
            $expenses = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $totalRevenue = array_sum(array_column($revenues, 'revenue'));
            $totalExpenses = array_sum(array_column($expenses, 'expense'));
            $netIncome = $totalRevenue - $totalExpenses;
            
            $this->render('reports/income-statement', [
                'revenues' => $revenues,
                'expenses' => $expenses,
                'totalRevenue' => $totalRevenue,
                'totalExpenses' => $totalExpenses,
                'netIncome' => $netIncome,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    public function balanceSheet() {
        $this->requireAuth();
        
        try {
            $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
            
            // Get assets
            $assetsSql = "SELECT 
                            coa.account_name,
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) - 
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as balance
                          FROM chart_of_accounts coa
                          LEFT JOIN transactions t ON coa.id = t.account_id 
                              AND t.parent_transaction_id IS NOT NULL
                              AND t.transaction_date <= ?
                          WHERE coa.account_type = 'Asset' AND coa.status = 'active'
                          GROUP BY coa.id, coa.account_name
                          HAVING balance != 0";
            
            $assetsStmt = $this->db->prepare($assetsSql);
            $assetsStmt->execute([$asOfDate]);
            $assets = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get liabilities
            $liabilitiesSql = "SELECT 
                                coa.account_name,
                                COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) - 
                                COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as balance
                              FROM chart_of_accounts coa
                              LEFT JOIN transactions t ON coa.id = t.account_id 
                                  AND t.parent_transaction_id IS NOT NULL
                                  AND t.transaction_date <= ?
                              WHERE coa.account_type = 'Liability' AND coa.status = 'active'
                              GROUP BY coa.id, coa.account_name
                              HAVING balance != 0";
            
            $liabilitiesStmt = $this->db->prepare($liabilitiesSql);
            $liabilitiesStmt->execute([$asOfDate]);
            $liabilities = $liabilitiesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get equity
            $equitySql = "SELECT 
                            coa.account_name,
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) - 
                            COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as balance
                          FROM chart_of_accounts coa
                          LEFT JOIN transactions t ON coa.id = t.account_id 
                              AND t.parent_transaction_id IS NOT NULL
                              AND t.transaction_date <= ?
                          WHERE coa.account_type = 'Equity' AND coa.status = 'active'
                          GROUP BY coa.id, coa.account_name
                          HAVING balance != 0";
            
            $equityStmt = $this->db->prepare($equitySql);
            $equityStmt->execute([$asOfDate]);
            $equity = $equityStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $totalAssets = array_sum(array_column($assets, 'balance'));
            $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
            $totalEquity = array_sum(array_column($equity, 'balance'));
            
            $this->render('reports/balance-sheet', [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'totalAssets' => $totalAssets,
                'totalLiabilities' => $totalLiabilities,
                'totalEquity' => $totalEquity,
                'asOfDate' => $asOfDate
            ]);
            
        } catch (Exception $e) {
            $this->render('errors/500', ['error' => $e->getMessage()]);
        }
    }
    
    private function getActiveAccounts() {
        $sql = "SELECT id, account_code, account_name, account_type 
                FROM chart_of_accounts 
                WHERE status = 'active' 
                ORDER BY account_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function exportReport() {
        $this->requireAuth();
        
        $reportType = $_GET['type'] ?? '';
        $format = $_GET['format'] ?? 'pdf';
        
        switch ($reportType) {
            case 'trial_balance':
                $this->exportTrialBalance($format);
                break;
            case 'general_ledger':
                $this->exportGeneralLedger($format);
                break;
            case 'income_statement':
                $this->exportIncomeStatement($format);
                break;
            case 'balance_sheet':
                $this->exportBalanceSheet($format);
                break;
            default:
                $this->jsonResponse(['error' => 'Invalid report type'], 400);
        }
    }
    
    private function exportTrialBalance($format) {
        // Implementation for exporting trial balance
        // This would generate PDF or Excel file
        $this->jsonResponse(['message' => 'Export functionality will be implemented']);
    }
    
    private function exportGeneralLedger($format) {
        // Implementation for exporting general ledger
        $this->jsonResponse(['message' => 'Export functionality will be implemented']);
    }
    
    private function exportIncomeStatement($format) {
        // Implementation for exporting income statement
        $this->jsonResponse(['message' => 'Export functionality will be implemented']);
    }
    
    private function exportBalanceSheet($format) {
        // Implementation for exporting balance sheet
        $this->jsonResponse(['message' => 'Export functionality will be implemented']);
    }
}
?> 