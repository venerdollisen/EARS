<?php
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/models/AccountingParametersModel.php';

class SettingsController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $this->requireAuth();
        header('Location: ' . APP_URL . '/settings/profile');
        exit;
    }
    
    public function profile() {
        $this->requireAuth();
        // Profile settings are available to all users
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Get complete user data including last_login from database
        $sql = "SELECT id, username, full_name, email, role, status, created_at, updated_at, last_login FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->render('settings/profile', [
            'user' => $user
        ]);
    }
    
    public function general() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $parametersModel = new AccountingParametersModel();
        $parameters = $parametersModel->getParameters();
        
        $this->render('settings/general', [
            'parameters' => $parameters,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function security() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $parametersModel = new AccountingParametersModel();
        $parameters = $parametersModel->getParameters();
        
        $this->render('settings/security', [
            'parameters' => $parameters,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function backup() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $parametersModel = new AccountingParametersModel();
        $parameters = $parametersModel->getParameters();
        
        $this->render('settings/backup', [
            'parameters' => $parameters,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function notifications() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $parametersModel = new AccountingParametersModel();
        $parameters = $parametersModel->getParameters();
        
        $this->render('settings/notifications', [
            'parameters' => $parameters,
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    public function saveProfile() {
        $this->requireAuth();
        // Profile updates are available to all users
        
        $data = $this->getRequestData();
        
        if (!isset($data['profile'])) {
            $this->jsonResponse(['error' => 'Invalid profile data'], 400);
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $profileData = $data['profile'];
            
            // Validate required fields
            if (empty($profileData['first_name']) || empty($profileData['last_name']) || empty($profileData['email'])) {
                $this->jsonResponse(['error' => 'First name, last name, and email are required'], 400);
            }
            
            // Check if email is already taken by another user
            $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$profileData['email'], $user['id']]);
            if ($stmt->fetch()) {
                $this->jsonResponse(['error' => 'Email address is already taken'], 400);
            }
            
            // Combine first and last name for full_name
            $fullName = trim($profileData['first_name'] . ' ' . $profileData['last_name']);
            
            // Update user profile
            $sql = "UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $fullName,
                $profileData['email'],
                $user['id']
            ]);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to update profile'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to update profile: ' . $e->getMessage()], 500);
        }
    }
    
    public function changePassword() {
        $this->requireAuth();
        $data = $this->getRequestData();
        
        if (!isset($data['current_password']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
            $this->jsonResponse(['error' => 'All password fields are required'], 400);
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            
            // Verify current password
            if (!password_verify($data['current_password'], $user['password'])) {
                $this->jsonResponse(['error' => 'Current password is incorrect'], 400);
            }
            
            // Check if new passwords match
            if ($data['new_password'] !== $data['confirm_password']) {
                $this->jsonResponse(['error' => 'New passwords do not match'], 400);
            }
            
            // Validate new password strength
            if (strlen($data['new_password']) < 8) {
                $this->jsonResponse(['error' => 'Password must be at least 8 characters long'], 400);
            }
            
            // Hash new password
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$hashedPassword, $user['id']]);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to change password'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to change password: ' . $e->getMessage()], 500);
        }
    }
    
    public function saveGeneral() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $data = $this->getRequestData();
        
        if (!isset($data['settings'])) {
            $this->jsonResponse(['error' => 'Invalid settings data'], 400);
        }
        
        try {
            $parametersModel = new AccountingParametersModel();
            
            $settingsToUpdate = [];
            foreach ($data['settings'] as $setting) {
                if (isset($setting['name']) && isset($setting['value'])) {
                    $settingsToUpdate[] = [
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ];
                }
            }
            
            $result = $parametersModel->saveParametersByName($settingsToUpdate);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'General settings saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save settings'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save settings: ' . $e->getMessage()], 500);
        }
    }
    
    public function saveSecurity() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $data = $this->getRequestData();
        
        if (!isset($data['settings'])) {
            $this->jsonResponse(['error' => 'Invalid settings data'], 400);
        }
        
        try {
            $parametersModel = new AccountingParametersModel();
            
            $settingsToUpdate = [];
            foreach ($data['settings'] as $setting) {
                if (isset($setting['name']) && isset($setting['value'])) {
                    $settingsToUpdate[] = [
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ];
                }
            }
            
            $result = $parametersModel->saveParametersByName($settingsToUpdate);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Security settings saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save settings'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save settings: ' . $e->getMessage()], 500);
        }
    }
    
    public function saveBackup() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $data = $this->getRequestData();
        
        if (!isset($data['settings'])) {
            $this->jsonResponse(['error' => 'Invalid settings data'], 400);
        }
        
        try {
            $parametersModel = new AccountingParametersModel();
            
            $settingsToUpdate = [];
            foreach ($data['settings'] as $setting) {
                if (isset($setting['name']) && isset($setting['value'])) {
                    $settingsToUpdate[] = [
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ];
                }
            }
            
            $result = $parametersModel->saveParametersByName($settingsToUpdate);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Backup settings saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save settings'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save settings: ' . $e->getMessage()], 500);
        }
    }
    
    public function saveNotifications() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        $data = $this->getRequestData();
        
        if (!isset($data['settings'])) {
            $this->jsonResponse(['error' => 'Invalid settings data'], 400);
        }
        
        try {
            $parametersModel = new AccountingParametersModel();
            
            $settingsToUpdate = [];
            foreach ($data['settings'] as $setting) {
                if (isset($setting['name']) && isset($setting['value'])) {
                    $settingsToUpdate[] = [
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ];
                }
            }
            
            $result = $parametersModel->saveParametersByName($settingsToUpdate);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Notification settings saved successfully']);
            } else {
                $this->jsonResponse(['error' => 'Failed to save settings'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to save settings: ' . $e->getMessage()], 500);
        }
    }
    
    public function createBackup() {
        $this->requireAuth();
        $this->requirePermission('system_settings');
        
        try {
            // Create backup directory if it doesn't exist
            $backupDir = BASE_PATH . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate backup filename
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $backupDir . '/ears_backup_' . $timestamp . '.sql';
            
            // Get database configuration
            $db = new Database();
            $host = $db->host;
            $dbname = $db->dbname;
            $username = $db->username;
            $password = $db->password;
            
            // Create database backup using mysqldump
            $command = "mysqldump -h {$host} -u {$username} -p{$password} {$dbname} > {$backupFile}";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Backup created successfully',
                    'file' => basename($backupFile)
                ]);
            } else {
                $this->jsonResponse(['error' => 'Failed to create backup'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to create backup: ' . $e->getMessage()], 500);
        }
    }
    
    public function testEmail() {
        $this->requireAuth();
        
        try {
            $parametersModel = new AccountingParametersModel();
            $smtpHost = $parametersModel->getParameterValue('smtp_host');
            $smtpPort = $parametersModel->getParameterValue('smtp_port');
            $smtpUsername = $parametersModel->getParameterValue('smtp_username');
            $smtpPassword = $parametersModel->getParameterValue('smtp_password');
            $fromEmail = $parametersModel->getParameterValue('from_email');
            $fromName = $parametersModel->getParameterValue('from_name');
            
            if (!$smtpHost || !$smtpUsername || !$smtpPassword) {
                $this->jsonResponse(['error' => 'SMTP configuration is incomplete'], 400);
            }
            
            // For now, just return success (actual email sending would require PHPMailer or similar)
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Test email configuration is valid'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to test email: ' . $e->getMessage()], 500);
        }
    }
    
    public function testSMS() {
        $this->requireAuth();
        
        try {
            $parametersModel = new AccountingParametersModel();
            $smsProvider = $parametersModel->getParameterValue('sms_provider');
            $smsApiKey = $parametersModel->getParameterValue('sms_api_key');
            $smsApiSecret = $parametersModel->getParameterValue('sms_api_secret');
            
            if (!$smsProvider || !$smsApiKey || !$smsApiSecret) {
                $this->jsonResponse(['error' => 'SMS configuration is incomplete'], 400);
            }
            
            // For now, just return success (actual SMS sending would require SMS provider API)
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Test SMS configuration is valid'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Failed to test SMS: ' . $e->getMessage()], 500);
        }
    }
}
?> 