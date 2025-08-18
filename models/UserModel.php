<?php
require_once BASE_PATH . '/core/Model.php';

class UserModel extends Model {
    protected $table = 'users';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $auth = new Auth();
            
            // Try new method first (with salt)
            if ($auth->verifyPassword($password, $user['password'])) {
                return $user;
            }
            
            // Try old method (without salt) for backward compatibility
            if (password_verify($password, $user['password'])) {
                // If old method works, upgrade the password to new format
                $this->upgradePassword($user['id'], $password);
                return $user;
            }
        }
        return false;
    }
    
    private function upgradePassword($userId, $password) {
        try {
            $auth = new Auth();
            $newHash = $auth->hashPassword($password);
            
            $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newHash, $userId]);
        } catch (Exception $e) {
            // Log error but don't fail authentication
            error_log("Failed to upgrade password for user ID {$userId}: " . $e->getMessage());
        }
    }

    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, username, full_name, email, role, status, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersByRole($role) {
        $stmt = $this->db->prepare("SELECT id, username, full_name, email, role, status, created_at FROM users WHERE role = ? ORDER BY created_at DESC");
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createUser($data) {
        $auth = new Auth();
        $data['password'] = $auth->hashPassword($data['password']); 
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateUser($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $auth = new Auth();
            $data['password'] = $auth->hashPassword($data['password']);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    public function getActiveUsers() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserById($id) {
        return $this->findById($id);
    }
    
    public function deleteUser($id) {
        // Soft delete - set status to inactive
        return $this->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
?> 