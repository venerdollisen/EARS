<?php
require_once 'models/BaseReportModel.php';

class CashReceiptReportModel extends BaseReportModel {
    
    /**
     * Generate cash receipt report data
     */
    public function generateReport($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        cr.id,
                        cr.reference_no as reference_number,
                        cr.transaction_date,
                        cr.total_amount as amount,
                        cr.payment_form,
                        cr.status,
                        cr.description as remarks,
                        coa.account_code,
                        coa.account_name,
                        s.supplier_name,
                        p.project_name,
                        d.department_name,
                        u.username as created_by,
                        cr.created_at
                    FROM cash_receipts cr
                    LEFT JOIN cash_receipt_details crd ON cr.id = crd.cash_receipt_id
                    LEFT JOIN chart_of_accounts coa ON crd.account_id = coa.id
                    LEFT JOIN suppliers s ON crd.supplier_id = s.id
                    LEFT JOIN projects p ON crd.project_id = p.id
                    LEFT JOIN departments d ON crd.department_id = d.id
                    LEFT JOIN users u ON cr.created_by = u.id
                    {$whereClause['where']}
                    ORDER BY cr.transaction_date DESC, cr.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error generating cash receipt report: " . $e->getMessage());
            // Return empty array if no data found
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
                        COUNT(DISTINCT cr.id) as total_transactions,
                        SUM(cr.total_amount) as total_amount,
                        AVG(cr.total_amount) as average_amount,
                        MIN(cr.total_amount) as min_amount,
                        MAX(cr.total_amount) as max_amount
                    FROM cash_receipts cr
                    {$whereClause['where']}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Return zeros if no data found
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
            error_log("Error getting cash receipt summary stats: " . $e->getMessage());
            // Return zeros if error
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
                        cr.payment_form,
                        COUNT(DISTINCT cr.id) as count,
                        SUM(cr.total_amount) as total_amount
                    FROM cash_receipts cr
                    {$whereClause['where']}
                    GROUP BY cr.payment_form
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash receipt data by payment form: " . $e->getMessage());
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
                        COUNT(DISTINCT cr.id) as count,
                        SUM(crd.amount) as total_amount
                    FROM cash_receipts cr
                    JOIN cash_receipt_details crd ON cr.id = crd.cash_receipt_id
                    JOIN chart_of_accounts coa ON crd.account_id = coa.id
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash receipt data by account: " . $e->getMessage());
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
                        DATE_FORMAT(cr.transaction_date, '%Y-%m') as month,
                        COUNT(DISTINCT cr.id) as count,
                        SUM(cr.total_amount) as total_amount
                    FROM cash_receipts cr
                    {$whereClause['where']}
                    GROUP BY DATE_FORMAT(cr.transaction_date, '%Y-%m')
                    ORDER BY month DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cash receipt monthly trend: " . $e->getMessage());
            return [];
        }
    }
}
