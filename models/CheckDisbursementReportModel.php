<?php
require_once 'models/BaseReportModel.php';

class CheckDisbursementReportModel extends BaseReportModel {
    
    /**
     * Generate check disbursement report data
     */
    protected function buildWhereClause($filters) {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $whereConditions[] = "DATE(chd.transaction_date) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereConditions[] = "DATE(chd.transaction_date) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['account_id'])) {
            // Use EXISTS to avoid requiring a direct join in the main query
            $whereConditions[] = "EXISTS (SELECT 1 FROM check_disbursement_details dd WHERE dd.check_disbursement_id = chd.id AND dd.account_id = :account_id)";
            $params[':account_id'] = $filters['account_id'];
        }

        if (!empty($filters['supplier_id'])) {
            $whereConditions[] = "EXISTS (SELECT 1 FROM check_disbursement_details dd WHERE dd.check_disbursement_id = chd.id AND dd.supplier_id = :supplier_id)";
            $params[':supplier_id'] = $filters['supplier_id'];
        }

        if (!empty($filters['project_id'])) {
            // project_id filter intentionally removed — project filtering is disabled
        }

        if (!empty($filters['department_id'])) {
            $whereConditions[] = "EXISTS (SELECT 1 FROM check_disbursement_details dd WHERE dd.check_disbursement_id = chd.id AND dd.department_id = :department_id)";
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['payment_form'])) {
            $whereConditions[] = "chd.payment_form = :payment_form";
            $params[':payment_form'] = $filters['payment_form'];
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = "chd.status = :status";
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

    public function generateReport($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);

            $sql = "SELECT 
                        chd.id,
                        MIN(chd.reference_no) as reference_number,
                        MIN(chd.transaction_date) as transaction_date,
                        MIN(chd.total_amount) as amount,
                        MIN(chd.check_number) as check_number,
                        MIN(chd.bank) as bank,
                        MIN(chd.check_date) as check_date,
                        MIN(chd.payee_name) as payee_name,
                        MIN(chd.status) as status,
                        MIN(s.vat_subject) as vat_subject,
                        MIN(chd.description) as remarks,
                        GROUP_CONCAT(DISTINCT coa.account_code SEPARATOR ', ') as account_code,
                        GROUP_CONCAT(DISTINCT coa.account_name SEPARATOR ', ') as account_name,
                        GROUP_CONCAT(DISTINCT s.supplier_name SEPARATOR ', ') as supplier_name,
                        MIN(d.department_name) as department_name,
                        MIN(u.username) as created_by,
                        MIN(chd.created_at) as created_at
                    FROM check_disbursements chd
                    LEFT JOIN check_disbursement_details chdd ON chd.id = chdd.check_disbursement_id
                    LEFT JOIN chart_of_accounts coa ON chdd.account_id = coa.id
                    LEFT JOIN suppliers s ON chdd.supplier_id = s.id
                    LEFT JOIN departments d ON chdd.department_id = d.id
                    LEFT JOIN users u ON chd.created_by = u.id
                    " . $whereClause['where'] . "
                    GROUP BY chd.id
                    ORDER BY MIN(chd.transaction_date) DESC, chd.id DESC";

            $stmt = $this->db->prepare($sql);

            // Debug: log SQL and params before execution to troubleshoot empty result
            error_log("CheckDisbursementReportModel::generateReport SQL: " . $sql);
            error_log("Params: " . json_encode($whereClause['params']));

            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error generating check disbursement report: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'N/A') . " Params: " . json_encode($whereClause['params'] ?? []));
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
                        COUNT(DISTINCT chd.id) as total_transactions,
                        SUM(chd.total_amount) as total_amount,
                        AVG(chd.total_amount) as average_amount,
                        MIN(chd.total_amount) as min_amount,
                        MAX(chd.total_amount) as max_amount,
                        COUNT(DISTINCT chd.bank) as total_banks
                    FROM check_disbursements chd
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
                    'max_amount' => 0,
                    'total_banks' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting check disbursement summary stats: " . $e->getMessage());
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'average_amount' => 0,
                'min_amount' => 0,
                'max_amount' => 0,
                'total_banks' => 0
            ];
        }
    }

    /**
     * Get data grouped by bank
     */
    public function getDataByBank($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            
            $sql = "SELECT 
                        chd.bank,
                        COUNT(DISTINCT chd.id) as count,
                        SUM(chd.total_amount) as total_amount
                    FROM check_disbursements chd
                    {$whereClause['where']}
                    GROUP BY chd.bank
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting check disbursement data by bank: " . $e->getMessage());
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
                        COUNT(DISTINCT chd.id) as count,
                        SUM(chdd.amount) as total_amount
                    FROM check_disbursements chd
                    JOIN check_disbursement_details chdd ON chd.id = chdd.check_disbursement_id
                    JOIN chart_of_accounts coa ON chdd.account_id = coa.id
                    {$whereClause['where']}
                    GROUP BY coa.id, coa.account_code, coa.account_name
                    ORDER BY total_amount DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting check disbursement data by account: " . $e->getMessage());
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
                        DATE_FORMAT(chd.transaction_date, '%Y-%m') as month,
                        COUNT(DISTINCT chd.id) as count,
                        SUM(chd.total_amount) as total_amount
                    FROM check_disbursements chd
                    {$whereClause['where']}
                    GROUP BY DATE_FORMAT(chd.transaction_date, '%Y-%m')
                    ORDER BY month DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting check disbursement monthly trend: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top payees
     */
    public function getTopPayees($filters = [], $limit = 10) {
        try {
            $whereClause = $this->buildWhereClause($filters);
            // Ensure we don't produce a double WHERE; append the non-null condition properly
            $where = $whereClause['where'];
            if (empty($where)) {
                $where = ' WHERE chd.payee_name IS NOT NULL';
            } else {
                $where .= ' AND chd.payee_name IS NOT NULL';
            }
            
            $sql = "SELECT 
                        chd.payee_name,
                        COUNT(DISTINCT chd.id) as count,
                        SUM(chd.total_amount) as total_amount
                    FROM check_disbursements chd
                    " . $where . "
                    GROUP BY chd.payee_name
                    ORDER BY total_amount DESC
                    LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting check disbursement top payees: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get check number range
     */
    public function getCheckNumberRange($filters = []) {
        try {
            $whereClause = $this->buildWhereClause($filters);

            // Append check_number IS NOT NULL without duplicating WHERE
            $where = $whereClause['where'];
            if (empty($where)) {
                $where = ' WHERE chd.check_number IS NOT NULL';
            } else {
                $where .= ' AND chd.check_number IS NOT NULL';
            }
            
            $sql = "SELECT 
                        MIN(chd.check_number) as min_check_number,
                        MAX(chd.check_number) as max_check_number,
                        COUNT(DISTINCT chd.check_number) as total_checks
                    FROM check_disbursements chd
                    " . $where;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($whereClause['params']);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting check disbursement check number range: " . $e->getMessage());
            return [
                'min_check_number' => null,
                'max_check_number' => null,
                'total_checks' => 0
            ];
        }
    }
}
