<?php
require_once BASE_PATH . '/core/Model.php';

class ProjectModel extends Model {
    protected $table = 'projects';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllProjects() {
        $sql = "SELECT * FROM {$this->table} ORDER BY project_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveProjects() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY project_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProjectById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getProjectByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE project_code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createProject($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $_SESSION['user_id'] ?? 1;
        return $this->create($data);
    }
    
    public function updateProject($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $_SESSION['user_id'] ?? 1;
        return $this->update($id, $data);
    }
    
    public function deleteProject($id) {
        // Check if project is being used in transactions
        $sql = "SELECT COUNT(*) as count FROM transaction_distributions td 
                JOIN transaction_headers th ON td.header_id = th.id 
                WHERE td.project_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception('Cannot delete project. It is being used in transactions.');
        }
        
        // Soft delete - set status to inactive
        return $this->update($id, [
            'status' => 'inactive',
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id'] ?? 1
        ]);
    }
    
    public function validateProject($data) {
        $errors = [];
        
        if (empty($data['project_code'])) {
            $errors[] = 'Project code is required';
        }
        
        if (empty($data['project_name'])) {
            $errors[] = 'Project name is required';
        }
        
        // Check if project code already exists (for new projects)
        if (isset($data['id'])) {
            // Update - check if code exists for other projects
            $existing = $this->getProjectByCode($data['project_code']);
            if ($existing && $existing['id'] != $data['id']) {
                $errors[] = 'Project code already exists';
            }
        } else {
            // New project - check if code exists
            $existing = $this->getProjectByCode($data['project_code']);
            if ($existing) {
                $errors[] = 'Project code already exists';
            }
        }
        
        return $errors;
    }

    /**
     * Check if project has transactions
     */
    public function hasTransactions($projectId) {
        try {
            $sql = "SELECT COUNT(*) FROM transaction_headers WHERE project_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Error checking project transactions: ' . $e->getMessage());
            return false;
        }
    }
}
?> 