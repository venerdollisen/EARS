<?php
require_once 'models/BaseReportModel.php';

class CashReceiptReportModel extends BaseReportModel {
    
    /**
     * Build WHERE clause specific to cash receipts
     */
    protected function buildWhereClause($filters) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "DATE(cr.transaction_date) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $whereConditions[] = "DATE(cr.transaction_date) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        if (!empty($filters['account_id'])) {
            $whereConditions[] = "crd.account_id = :account_id";
            $params[':account_id'] = $filters['account_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $whereConditions[] = "crd.supplier_id = :supplier_id";
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $whereConditions[] = "crd.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "crd.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['payment_form'])) {
            $whereConditions[] = "cr.payment_form = :payment_form";
            $params[':payment_form'] = $filters['payment_form'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "cr.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        return [
            'where' => $whereClause,
            'params' => $params
        ];
    }
    
    /**
     * Generate cash receipt report data with optimized query
     */
    public function generateReport($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            // Use GROUP BY to avoid duplicate rows from JOINs
            $sql = "SELECT 
                        cr.reference_no as reference_number,
                        cr.transaction_date,
                        cr.total_amount as amount,
                        cr.payment_form,
                        cr.status,
                        GROUP_CONCAT(DISTINCT COALESCE(coa.account_code, '') SEPARATOR ', ') as account_code,
                        GROUP_CONCAT(DISTINCT COALESCE(coa.account_name, '') SEPARATOR ', ') as account_name,
                        GROUP_CONCAT(DISTINCT COALESCE(s.supplier_name, '') SEPARATOR ', ') as supplier_name,
                        GROUP_CONCAT(DISTINCT COALESCE(s.tin, '') SEPARATOR ', ') as supplier_tin,
                        GROUP_CONCAT(DISTINCT COALESCE(s.address, '') SEPARATOR ', ') as supplier_address,
                        COALESCE(u.username, '') as created_by, s.vat_subject
                    FROM cash_receipts cr
                    LEFT JOIN cash_receipt_details crd ON cr.id = crd.cash_receipt_id
                    LEFT JOIN chart_of_accounts coa ON crd.account_id = coa.id
                    LEFT JOIN suppliers s ON crd.supplier_id = s.id
                    LEFT JOIN users u ON cr.created_by = u.id
                    {$whereClause['where']}
                    GROUP BY cr.id, cr.reference_no, cr.transaction_date, cr.total_amount, cr.payment_form, cr.status, u.username
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
            
            // Use a subquery to avoid multiplication from JOINs
            $sql = "SELECT 
                        COUNT(DISTINCT cr.id) as total_transactions,
                        SUM(cr.total_amount) as total_amount,
                        AVG(cr.total_amount) as average_amount,
                        MIN(cr.total_amount) as min_amount,
                        MAX(cr.total_amount) as max_amount
                    FROM cash_receipts cr
                    WHERE cr.id IN (
                        SELECT DISTINCT cr2.id 
                        FROM cash_receipts cr2
                        LEFT JOIN cash_receipt_details crd ON cr2.id = crd.cash_receipt_id
                        LEFT JOIN chart_of_accounts coa ON crd.account_id = coa.id
                        LEFT JOIN suppliers s ON crd.supplier_id = s.id
                        {$whereClause['where']}
                    )";
            
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
            
            // Use subquery to avoid multiplication
            $sql = "SELECT 
                        cr.payment_form,
                        COUNT(DISTINCT cr.id) as count,
                        SUM(cr.total_amount) as total_amount
                    FROM cash_receipts cr
                    WHERE cr.id IN (
                        SELECT DISTINCT cr2.id 
                        FROM cash_receipts cr2
                        LEFT JOIN cash_receipt_details crd ON cr2.id = crd.cash_receipt_id
                        LEFT JOIN chart_of_accounts coa ON crd.account_id = coa.id
                        LEFT JOIN suppliers s ON crd.supplier_id = s.id
                        {$whereClause['where']}
                    )
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
                    LEFT JOIN suppliers s ON crd.supplier_id = s.id
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
            
            // Use subquery to avoid multiplication
            $sql = "SELECT 
                        DATE_FORMAT(cr.transaction_date, '%Y-%m') as month,
                        COUNT(DISTINCT cr.id) as count,
                        SUM(cr.total_amount) as total_amount
                    FROM cash_receipts cr
                    WHERE cr.id IN (
                        SELECT DISTINCT cr2.id 
                        FROM cash_receipts cr2
                        LEFT JOIN cash_receipt_details crd ON cr2.id = crd.cash_receipt_id
                        LEFT JOIN chart_of_accounts coa ON crd.account_id = coa.id
                        LEFT JOIN suppliers s ON crd.supplier_id = s.id
                        {$whereClause['where']}
                    )
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
