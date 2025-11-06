<?php
require_once 'core/Model.php';

class BaseReportModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get active accounts for dropdowns
     */
    public function getActiveAccounts() {
        $sql = "SELECT coa.id, coa.account_code, coa.account_name, cat.type_name as account_type 
                FROM chart_of_accounts coa
                LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id
                WHERE coa.status = 'active' 
                ORDER BY coa.account_code";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active accounts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active account types for dropdowns
     */
    public function getActiveAccountTypes() {
        $sql = "SELECT id, type_name 
                FROM coa_account_types 
                WHERE status = 'active' 
                ORDER BY type_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active account types: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active suppliers for dropdowns
     */
    public function getActiveSuppliers() {
        $sql = "SELECT id, supplier_name 
                FROM suppliers 
                WHERE status = 'active' 
                ORDER BY supplier_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active suppliers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active projects for dropdowns
     */
    public function getActiveProjects() {
        $sql = "SELECT id, project_name, project_code 
                FROM projects 
                WHERE status = 'active' 
                ORDER BY project_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active projects: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active departments for dropdowns
     */
    public function getActiveDepartments() {
        $sql = "SELECT id, department_name, department_code 
                FROM departments 
                WHERE status = 'active' 
                ORDER BY department_name";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active departments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment forms for dropdowns
     */
    public function getPaymentForms() {
        return [
            ['id' => 'cash', 'name' => 'Cash'],
            ['id' => 'check', 'name' => 'Check'],
            ['id' => 'bank_transfer', 'name' => 'Bank Transfer'],
            ['id' => 'credit_card', 'name' => 'Credit Card'],
            ['id' => 'debit_card', 'name' => 'Debit Card']
        ];
    }
    
    /**
     * Get statuses for dropdowns
     */
    public function getStatuses() {
        return [
            ['id' => 'pending', 'name' => 'Pending'],
            ['id' => 'approved', 'name' => 'Approved'],
            ['id' => 'rejected', 'name' => 'Rejected']
            // ['id' => 'completed', 'name' => 'Completed']
        ];
    }
    
    /**
     * Build WHERE clause from filters
     */
    protected function buildWhereClause($filters) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "DATE(th.transaction_date) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $whereConditions[] = "DATE(th.transaction_date) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        if (!empty($filters['account_id'])) {
            $whereConditions[] = "td.account_id = :account_id";
            $params[':account_id'] = $filters['account_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $whereConditions[] = "th.supplier_id = :supplier_id";
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $whereConditions[] = "th.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "th.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['payment_form'])) {
            $whereConditions[] = "th.payment_form = :payment_form";
            $params[':payment_form'] = $filters['payment_form'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "th.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = ' AND ' . implode(' AND ', $whereConditions);
        }
        
        return [
            'where' => $whereClause,
            'params' => $params
        ];
    }
}
