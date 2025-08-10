<?php
require_once BASE_PATH . '/models/UserModel.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';

class AuthController extends Controller {
    use AuditTrailTrait;
    
    public function login() {
        if ($this->auth->isLoggedIn()) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }
        
        $this->render('auth/login');
    }
    
    public function apiLogin() {
        $data = $this->getRequestData();
        
        if (!isset($data['username']) || !isset($data['password'])) {
            $this->jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        
        $userModel = new UserModel();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            $this->auth->setUserSession($user);
            // Audit trail: login
            try { $this->logLogin('users', (int)$user['id']); } catch (Exception $e) {}
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'redirect' => $basePath . '/dashboard'
            ]);
        } else {
            $this->jsonResponse(['error' => 'Invalid username or password'], 401);
        }
    }
    
    public function logout() {
        $current = $this->auth->getCurrentUser();
        if ($current && isset($current['id'])) {
            try { $this->logLogout('users', (int)$current['id']); } catch (Exception $e) {}
        }
        $this->auth->logout();
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        header('Location: ' . $basePath . '/login');
        exit;
    }
    
    public function apiLogout() {
        $current = $this->auth->getCurrentUser();
        if ($current && isset($current['id'])) {
            try { $this->logLogout('users', (int)$current['id']); } catch (Exception $e) {}
        }
        $this->auth->logout();
        $this->jsonResponse(['success' => true, 'message' => 'Logout successful']);
    }
}
?> 