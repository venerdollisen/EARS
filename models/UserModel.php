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
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
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
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateUser($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
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