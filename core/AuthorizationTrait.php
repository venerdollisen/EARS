<?php

trait AuthorizationTrait {
    
    /**
     * Check if current user has permission to access a specific feature
     */
    protected function requirePermission($permission) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        if (!$this->hasPermission($user, $permission)) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Access denied. Insufficient permissions.'], 403);
            } else {
                $this->render('errors/403', [
                    'message' => 'Access denied. Insufficient permissions.',
                    'user' => $user
                ]);
                exit;
            }
        }
    }
    
    /**
     * Check if user has specific permission
     */
    protected function hasPermission($user, $permission) {
        $role = $user['role'] ?? '';
        
        switch ($permission) {
            case 'file_maintenance':
                return in_array($role, ['admin', 'manager']);
            case 'parameters':
                return true;
            case 'user_management':
            case 'audit_trail':
            case 'system_settings':
                return in_array($role, ['admin', 'manager']);
                
            case 'transaction_entries':
            case 'reports':
            case 'summary':
            case 'profile_settings':
                return true; // All roles can access
                
            default:
                return false;
        }
    }
    
    /**
     * Check if user is admin
     */
    protected function requireAdmin() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Access denied. Admin privileges required.'], 403);
            } else {
                $this->render('errors/403', [
                    'message' => 'Access denied. Admin privileges required.',
                    'user' => $user
                ]);
                exit;
            }
        }
    }
    
    /**
     * Check if user is admin or manager
     */
    protected function requireAdminOrManager() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user || !in_array(($user['role'] ?? ''), ['admin', 'manager'])) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Access denied. Manager or Admin privileges required.'], 403);
            } else {
                $this->render('errors/403', [
                    'message' => 'Access denied. Manager or Admin privileges required.',
                    'user' => $user
                ]);
                exit;
            }
        }
    }
    
    /**
     * Check if user is not an assistant
     */
    protected function requireNotAssistant() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user || ($user['role'] ?? '') === 'user') {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Access denied. This feature is not available for assistants.'], 403);
            } else {
                $this->render('errors/403', [
                    'message' => 'Access denied. This feature is not available for assistants.',
                    'user' => $user
                ]);
                exit;
            }
        }
    }
    
    /**
     * Get current user role
     */
    protected function getCurrentUserRole() {
        $user = $this->auth->getCurrentUser();
        return $user['role'] ?? '';
    }
    
    /**
     * Check if current user is assistant
     */
    protected function isCurrentUserAssistant() {
        return $this->getCurrentUserRole() === 'user';
    }
    
    /**
     * Check if current user is admin
     */
    protected function isCurrentUserAdmin() {
        return $this->getCurrentUserRole() === 'admin';
    }
    
    /**
     * Check if current user is manager
     */
    protected function isCurrentUserManager() {
        return $this->getCurrentUserRole() === 'manager';
    }
}
?>
