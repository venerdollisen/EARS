<?php
require_once 'models/BaseReportModel.php';

class CashDisbursementReportModel extends BaseReportModel {
    
    /**
     * Generate cash disbursement report data
     */
    public function generateReport($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        cd.id,
                        cd.reference_no as reference_number,
                        cd.transaction_date,
                        cd.total_amount as amount,
                        cd.payment_form,
                        cd.status,
                        cd.description as remarks,
                        coa.account_code,
                        coa.account_name,
                        s.supplier_name,
                        p.project_name,
                        d.department_name,
                        u.username as created_by,
                        cd.created_at
                    FROM cash_disbursements cd
                    LEFT JOIN cash_disbursement_details cdd ON cd.id = cdd.cash_disbursement_id
                    LEFT JOIN chart_of_accounts coa ON cdd.account_id = coa.id
                    LEFT JOIN suppliers s ON cdd.supplier_id = s.id
                    LEFT JOIN projects p ON cdd.project_id = p.id
                    LEFT JOIN departments d ON cdd.department_id = d.id
                    LEFT JOIN users u ON cd.created_by = u.id
                    {$whereClause['where']}
                    ORDER BY cd.transaction_date DESC, cd.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error generating cash disbursement report: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get summary statistics
     */
    public function getSummaryStats($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        COUNT(DISTINCT cd.id) as total_transactions,
                        SUM(cd.total_amount) as total_amount,
                        AVG(cd.total_amount) as average_amount,
                        MIN(cd.total_amount) as min_amount,
                        MAX(cd.total_amount) as max_amount
                    FROM cash_disbursements cd
                    {$whereClause['where']}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['total_transactions'] == 0) {
                return [
                    'total_transactions' => 0,
                    'total_amount' => 0,
                    'average_amount' => 0,
                    'min_amount' => 0,
                    'max_amount' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting cash disbursement summary stats: " . $e->getMessage());
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'average_amount' => 0,
                'min_amount' => 0,
                'max_amount' => 0
            ];
        }
    }
    
    /**
     * Get data grouped by payment form
     */
    public function getDataByPaymentForm($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        cd.payment_form,
                        COUNT(DISTINCT cd.id) as count,
                        SUM(cd.total_amount) as total_amount
                    FROM cash_disbursements cd
                    {$whereClause['where']}
                    GROUP BY cd.payment_form
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash disbursement data by payment form: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get data grouped by account
     */
    public function getDataByAccount($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        coa.account_code,
                        coa.account_name,
                        COUNT(DISTINCT cd.id) as count,
                        SUM(cdd.amount) as total_amount
                    FROM cash_disbursements cd
                    JOIN cash_disbursement_details cdd ON cd.id = cdd.cash_disbursement_id
                    JOIN chart_of_accounts coa ON cdd.account_id = coa.id
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash disbursement data by account: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get monthly trend data
     */
    public function getMonthlyTrend($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        DATE_FORMAT(cd.transaction_date, '%Y-%m') as month,
                        COUNT(DISTINCT cd.id) as count,
                        SUM(cd.total_amount) as total_amount
                    FROM cash_disbursements cd
                    {$whereClause['where']}
                    GROUP BY DATE_FORMAT(cd.transaction_date, '%Y-%m')
                    ORDER BY month DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash disbursement monthly trend: " . $e->getMessage());
            return [];
        }
    }
}
