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
    
    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create password reset token and return token and user
     */
    public function createPasswordReset($email) {
        $user = $this->getUserByEmail($email);
        if (!$user) return false;

        // Create password_resets table if not exists (safe-guard)
        $this->db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (token),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Generate secure token
        $token = bin2hex(random_bytes(20));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Insert token
        $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires]);

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Reset password using token
     */
    public function resetPasswordByToken($token, $newPassword) {
        $sql = "SELECT pr.id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN {$this->table} u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        if (strtotime($row['expires_at']) < time()) {
            // token expired
            // Optionally delete expired token
            $this->db->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$row['id']]);
            return false;
        }

        // Update user's password
        $auth = new Auth();
        $hash = $auth->hashPassword($newPassword);
        $this->db->prepare("UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?")->execute([$hash, $row['user_id']]);

        // Delete all tokens for this user
        $this->db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$row['user_id']]);

        return true;
    }
}
?>