<?php

require_once BASE_PATH . '/models/AuditTrailModel.php';

trait AuditTrailTrait {
    private $auditTrailModel;
    
    /**
     * Initialize audit trail model
     */
    private function initAuditTrail() {
        if (!$this->auditTrailModel) {
            $this->auditTrailModel = new AuditTrailModel();
        }
    }
    
    /**
     * Log audit trail entry
     */
    protected function logAuditTrail($action, $tableName, $recordId, $oldValues = null, $newValues = null) {
        $this->initAuditTrail();
        
        $userId = null;
        if ($this->auth && $this->auth->isLoggedIn()) {
            $user = $this->auth->getCurrentUser();
            $userId = $user['id'] ?? null;
        }
        
        return $this->auditTrailModel->log($userId, $action, $tableName, $recordId, $oldValues, $newValues);
    }
    
    /**
     * Log CREATE action
     */
    protected function logCreate($tableName, $recordId, $newValues = null) {
        return $this->logAuditTrail('CREATE', $tableName, $recordId, null, $newValues);
    }
    
    /**
     * Log UPDATE action
     */
    protected function logUpdate($tableName, $recordId, $oldValues = null, $newValues = null) {
        return $this->logAuditTrail('UPDATE', $tableName, $recordId, $oldValues, $newValues);
    }
    
    /**
     * Log DELETE action
     */
    protected function logDelete($tableName, $recordId, $oldValues = null) {
        return $this->logAuditTrail('DELETE', $tableName, $recordId, $oldValues, null);
    }
    
    /**
     * Log LOGIN action
     */
    protected function logLogin($tableName, $recordId) {
        return $this->logAuditTrail('LOGIN', $tableName, $recordId);
    }
    
    /**
     * Log LOGOUT action
     */
    protected function logLogout($tableName, $recordId) {
        return $this->logAuditTrail('LOGOUT', $tableName, $recordId);
    }
} 