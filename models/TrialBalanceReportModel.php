<?php
require_once 'models/BaseReportModel.php';

class TrialBalanceReportModel extends BaseReportModel {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Generate trial balance report data
     */
    public function generateReport($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
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
                        coa.id,
                        coa.account_code,
                        coa.account_name,
                        cat.type_name as account_type,
                        COALESCE(SUM(td.debit_amount), 0) as total_debits,
                        COALESCE(SUM(td.credit_amount), 0) as total_credits,
                        (COALESCE(SUM(td.debit_amount), 0) - COALESCE(SUM(td.credit_amount), 0)) as balance
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN ({$subquery}) td ON coa.id = td.account_id
                    WHERE coa.status = 'active'
                    GROUP BY coa.id, coa.account_code, coa.account_name, cat.type_name
                    HAVING total_debits > 0 OR total_credits > 0
                    ORDER BY coa.account_code";
            
            // Get date range from filters or use fiscal year
            $fiscalYear = $this->getFiscalYearDates();
            $startDate = $filters['start_date'] ?? $fiscalYear['start'];
            $endDate = $filters['end_date'] ?? $fiscalYear['end'];
            
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error generating trial balance report: " . $e->getMessage());
            return [];
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
                        COUNT(DISTINCT coa.id) as total_accounts,
                        COALESCE(SUM(td.debit_amount), 0) as total_debits,
                        COALESCE(SUM(td.credit_amount), 0) as total_credits,
                        (COALESCE(SUM(td.debit_amount), 0) - COALESCE(SUM(td.credit_amount), 0)) as net_balance
                    FROM chart_of_accounts coa
                    LEFT JOIN ({$subquery}) td ON coa.id = td.account_id
                    WHERE coa.status = 'active'";
            
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
                    'total_accounts' => 0,
                    'total_debits' => 0,
                    'total_credits' => 0,
                    'net_balance' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting trial balance summary stats: " . $e->getMessage());
            return [
                'total_accounts' => 0,
                'total_debits' => 0,
                'total_credits' => 0,
                'net_balance' => 0
            ];
        }
    }
    
    /**
     * Get data grouped by account type
     */
    public function getDataByAccountType($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        cat.type_name as account_type,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) as total_debits,
                        COALESCE(SUM(CASE WHEN td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0) as total_credits
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active'
                    {$whereClause['where']}
                    GROUP BY cat.id, cat.type_name
                    HAVING total_debits > 0 OR total_credits > 0
                    ORDER BY cat.type_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting trial balance data by account type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get balance distribution
     */
    public function getBalanceDistribution($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        CASE 
                            WHEN balance > 0 THEN 'Debit Balance'
                            WHEN balance < 0 THEN 'Credit Balance'
                            ELSE 'Zero Balance'
                        END as balance_type,
                        COUNT(*) as account_count
                    FROM (
                        SELECT 
                            coa.id,
                            (COALESCE(SUM(CASE WHEN td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) - 
                             COALESCE(SUM(CASE WHEN td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0)) as balance
                        FROM chart_of_accounts coa
                        LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                        LEFT JOIN transaction_headers th ON td.header_id = th.id
                        WHERE coa.status = 'active'
                        {$whereClause['where']}
                        GROUP BY coa.id
                        HAVING balance != 0
                    ) as account_balances
                    GROUP BY balance_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting trial balance distribution: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top accounts by balance
     */
    public function getTopAccounts($filters = [], $limit = 10) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_code,
                        coa.account_name,
                        cat.type_name as account_type,
                        (COALESCE(SUM(CASE WHEN td.payment_type = 'debit' THEN td.amount ELSE 0 END), 0) - 
                         COALESCE(SUM(CASE WHEN td.payment_type = 'credit' THEN td.amount ELSE 0 END), 0)) as balance
                    FROM chart_of_accounts coa
                    LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                    LEFT JOIN transaction_distributions td ON coa.id = td.account_id
                    LEFT JOIN transaction_headers th ON td.header_id = th.id
                    WHERE coa.status = 'active'
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name, cat.type_name
                    HAVING balance != 0
                    ORDER BY ABS(balance) DESC
                    LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting trial balance top accounts: " . $e->getMessage());
            return [];
        }
    }
}
