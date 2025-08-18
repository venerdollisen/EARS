<?php
require_once BASE_PATH . '/core/Model.php';

class DepartmentModel extends Model {
    protected $table = 'departments';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllDepartments() {
        $sql = "SELECT * FROM {$this->table} ORDER BY department_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveDepartments() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY department_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDepartmentById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDepartmentByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE department_code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createDepartment($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $_SESSION['user_id'] ?? 1;
        return $this->create($data);
    }
    
    public function updateDepartment($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $_SESSION['user_id'] ?? 1;
        return $this->update($id, $data);
    }
    
    public function deleteDepartment($id) {
        // Check if department is being used in transactions
        $sql = "SELECT COUNT(*) as count FROM transaction_distributions td 
                JOIN transaction_headers th ON td.header_id = th.id 
                WHERE td.department_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception('Cannot delete department. It is being used in transactions.');
        }
        
        // Soft delete - set status to inactive
        return $this->update($id, [
            'status' => 'inactive',
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id'] ?? 1
        ]);
    }
    
    public function validateDepartment($data) {
        $errors = [];
        
        if (empty($data['department_code'])) {
            $errors[] = 'Department code is required';
        }
        
        if (empty($data['department_name'])) {
            $errors[] = 'Department name is required';
        }
        
        // Check if department code already exists (for new departments)
        if (isset($data['id'])) {
            // Update - check if code exists for other departments
            $existing = $this->getDepartmentByCode($data['department_code']);
            if ($existing && $existing['id'] != $data['id']) {
                $errors[] = 'Department code already exists';
            }
        } else {
            // New department - check if code exists
            $existing = $this->getDepartmentByCode($data['department_code']);
            if ($existing) {
                $errors[] = 'Department code already exists';
            }
        }
        
        return $errors;
    }

    /**
     * Check if department has transactions
     */
    public function hasTransactions($departmentId) {
        try {
            $sql = "SELECT COUNT(*) FROM transaction_headers WHERE department_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$departmentId]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Error checking department transactions: ' . $e->getMessage());
            return false;
        }
    }
}
?> 