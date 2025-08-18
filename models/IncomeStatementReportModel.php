<?php
require_once 'models/BaseReportModel.php';

class IncomeStatementReportModel extends BaseReportModel {
    
    /**
     * Generate income statement report data
     */
    public function generateReport($filters = []) {
        try {
            // Create a subquery that combines all transaction distributions
            $subquery = "
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM cash_receipt_details crd
                JOIN cash_receipts cr ON crd.cash_receipt_id = cr.id
                WHERE cr.transaction_date >= ? AND cr.transaction_date <= ?
                
                UNION ALL
                
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM cash_disbursement_details cdd
                JOIN cash_disbursements cd ON cdd.cash_disbursement_id = cd.id
                WHERE cd.transaction_date >= ? AND cd.transaction_date <= ?
                
                UNION ALL
                
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM check_disbursement_details chdd
                JOIN check_disbursements chd ON chdd.check_disbursement_id = chd.id
                WHERE chd.transaction_date >= ? AND chd.transaction_date <= ?
            ";
            
            // Get date range from filters or use fiscal year
            $fiscalYear = $this->getFiscalYearDates();
            $startDate = $filters['start_date'] ?? $fiscalYear['start'];
            $endDate = $filters['end_date'] ?? $fiscalYear['end'];
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            
            // Get revenue accounts (Income accounts)
            $revenueSql = "SELECT 
                            coa.id,
                            coa.account_code,
                            coa.account_name,
                            cat.type_name as account_type,
                            COALESCE(SUM(td.credit_amount), 0) as total_revenue,
                            COALESCE(SUM(td.debit_amount), 0) as total_expenses
                        FROM chart_of_accounts coa
                        LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                        LEFT JOIN ({$subquery}) td ON coa.id = td.account_id
                        WHERE coa.status = 'active' 
                        AND cat.type_name = 'Income'
                        GROUP BY coa.id, coa.account_code, coa.account_name, cat.type_name
                        HAVING total_revenue > 0 OR total_expenses > 0
                        ORDER BY total_revenue DESC";
            
            $stmt = $this->db->prepare($revenueSql);
            $stmt->execute($params);
            $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get expense accounts (Expense accounts)
            $expenseSql = "SELECT 
                            coa.id,
                            coa.account_code,
                            coa.account_name,
                            cat.type_name as account_type,
                            COALESCE(SUM(td.debit_amount), 0) as total_expenses,
                            COALESCE(SUM(td.credit_amount), 0) as total_revenue
                        FROM chart_of_accounts coa
                        LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                        LEFT JOIN ({$subquery}) td ON coa.id = td.account_id
                        WHERE coa.status = 'active' 
                        AND cat.type_name = 'Expense'
                        GROUP BY coa.id, coa.account_code, coa.account_name, cat.type_name
                        HAVING total_expenses > 0 OR total_revenue > 0
                        ORDER BY total_expenses DESC";
            
            $stmt = $this->db->prepare($expenseSql);
            $stmt->execute($params);
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'revenue' => $revenue,
                'expenses' => $expenses
            ];
        } catch (PDOException $e) {
            error_log("Error generating income statement report: " . $e->getMessage());
            return [
                'revenue' => [],
                'expenses' => []
            ];
        }
    }
    
    /**
     * Get summary statistics
     */
    public function getSummaryStats($filters = []) {
        try {
            // Create a subquery that combines all transaction distributions
            $subquery = "
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM cash_receipt_details crd
                JOIN cash_receipts cr ON crd.cash_receipt_id = cr.id
                WHERE cr.transaction_date >= ? AND cr.transaction_date <= ?
                
                UNION ALL
                
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM cash_disbursement_details cdd
                JOIN cash_disbursements cd ON cdd.cash_disbursement_id = cd.id
                WHERE cd.transaction_date >= ? AND cd.transaction_date <= ?
                
                UNION ALL
                
                SELECT 
                    account_id,
                    CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END as debit_amount,
                    CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END as credit_amount
                FROM check_disbursement_details chdd
                JOIN check_disbursements chd ON chdd.check_disbursement_id = chd.id
                WHERE chd.transaction_date >= ? AND chd.transaction_date <= ?
            ";
            
            $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN cat.type_name = 'Income' THEN coa.id END) as revenue_accounts,
                        COUNT(DISTINCT CASE WHEN cat.type_name = 'Expense' THEN coa.id END) as expense_accounts,
                        COALESCE(SUM(CASE WHEN cat.type_name = 'Income' THEN td.credit_amount ELSE 0 END), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN cat.type_name = 'Expense' THEN td.debit_amount ELSE 0 END), 0) as total_expenses
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN ({$subquery}) td ON coa.id = td.account_id
                    WHERE coa.status = 'active'
                    AND cat.type_name IN ('Income', 'Expense')";
            
            // Get date range from filters or use fiscal year
            $fiscalYear = $this->getFiscalYearDates();
            $startDate = $filters['start_date'] ?? $fiscalYear['start'];
            $endDate = $filters['end_date'] ?? $fiscalYear['end'];
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'revenue_accounts' => 0,
                    'expense_accounts' => 0,
                    'total_revenue' => 0,
                    'total_expenses' => 0,
                    'net_income' => 0,
                    'profit_margin' => 0
                ];
            }
            
            $netIncome = $result['total_revenue'] - $result['total_expenses'];
            $profitMargin = $result['total_revenue'] > 0 ? ($netIncome / $result['total_revenue']) * 100 : 0;
            
            return [
                'revenue_accounts' => $result['revenue_accounts'],
                'expense_accounts' => $result['expense_accounts'],
                'total_revenue' => $result['total_revenue'],
                'total_expenses' => $result['total_expenses'],
                'net_income' => $netIncome,
                'profit_margin' => $profitMargin
            ];
        } catch (PDOException $e) {
            error_log("Error getting income statement summary stats: " . $e->getMessage());
            return [
                'revenue_accounts' => 0,
                'expense_accounts' => 0,
                'total_revenue' => 0,
                'total_expenses' => 0,
                'net_income' => 0,
                'profit_margin' => 0
            ];
        }
    }
    
    /**
     * Get revenue by category
     */
    public function getRevenueByCategory($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_name as category,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0) as total_revenue
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active' 
                    AND cat.type_name = 'Income'
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_name
                    HAVING total_revenue > 0
                    ORDER BY total_revenue DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting income statement revenue by category: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get expenses by category
     */
    public function getExpensesByCategory($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_name as category,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) as total_expenses
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active' 
                    AND cat.type_name = 'Expense'
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_name
                    HAVING total_expenses > 0
                    ORDER BY total_expenses DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting income statement expenses by category: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get monthly trend
     */
    public function getMonthlyTrend($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        DATE_FORMAT(th.transaction_date, '%Y-%m') as month,
                        COALESCE(SUM(CASE WHEN cat.type_name = 'Income' AND td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0) as revenue,
                        COALESCE(SUM(CASE WHEN cat.type_name = 'Expense' AND td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) as expenses
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active'
                    AND cat.type_name IN ('Income', 'Expense')
                    {$whereClause['where']}
                    GROUP BY DATE_FORMAT(th.transaction_date, '%Y-%m')
                    HAVING revenue > 0 OR expenses > 0
                    ORDER BY month DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting income statement monthly trend: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top revenue accounts
     */
    public function getTopRevenueAccounts($filters = [], $limit = 10) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_code,
                        coa.account_name,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0) as total_revenue
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active' 
                    AND cat.type_name = 'Income'
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name
                    HAVING total_revenue > 0
                    ORDER BY total_revenue DESC
                    LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting income statement top revenue accounts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top expense accounts
     */
    public function getTopExpenseAccounts($filters = [], $limit = 10) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_code,
                        coa.account_name,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) as total_expenses
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active' 
                    AND cat.type_name = 'Expense'
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name
                    HAVING total_expenses > 0
                    ORDER BY total_expenses DESC
                    LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting income statement top expense accounts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get profitability ratios
     */
    public function getProfitabilityRatios($filters = []) {
        try {
            $summary = $this->getSummaryStats($filters);
            
            $grossProfitMargin = $summary['total_revenue'] > 0 ? 
                (($summary['total_revenue'] - $summary['total_expenses']) / $summary['total_revenue']) * 100 : 0;
            
            $expenseRatio = $summary['total_revenue'] > 0 ? 
                ($summary['total_expenses'] / $summary['total_revenue']) * 100 : 0;
            
            return [
                'gross_profit_margin' => $grossProfitMargin,
                'expense_ratio' => $expenseRatio,
                'profit_margin' => $summary['profit_margin']
            ];
        } catch (Exception $e) {
            error_log("Error getting profitability ratios: " . $e->getMessage());
            return [
                'gross_profit_margin' => 0,
                'expense_ratio' => 0,
                'profit_margin' => 0
            ];
        }
    }
}
