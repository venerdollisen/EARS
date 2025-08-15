<?php

require_once BASE_PATH . '/core/Model.php';

class AuditTrailModel extends Model {
    protected $table = 'audit_trail';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Log an audit trail entry
     */
    public function log($userId, $action, $tableName, $recordId, $oldValues = null, $newValues = null) {
        $sql = "INSERT INTO {$this->table} (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $this->getClientIP(),
            $this->getUserAgent()
        ]);
    }
    
    /**
     * Get audit trail entries with filters
     */
    public function getAuditTrail($filters = []) {
        $sql = "SELECT at.*, u.full_name as user_name 
                FROM {$this->table} at 
                LEFT JOIN users u ON at.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND at.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND at.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND at.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND at.record_id = ?";
            $params[] = $filters['record_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(at.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(at.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY at.created_at DESC";

        // LIMIT/OFFSET must be numeric literals (cannot be bound as strings in MySQL/MariaDB for some versions)
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : null;
        if ($limit > 0) {
            if ($offset !== null && $offset >= 0) {
                $sql .= " LIMIT {$offset}, {$limit}";
            } else {
                $sql .= " LIMIT {$limit}";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total count of audit trail entries with filters
     */
    public function getAuditTrailCount($filters = []) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} at 
                LEFT JOIN users u ON at.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND at.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND at.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND at.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND at.record_id = ?";
            $params[] = $filters['record_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(at.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(at.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
    
    /**
     * Get audit trail for a specific record
     */
    public function getRecordAuditTrail($tableName, $recordId) {
        return $this->getAuditTrail([
            'table_name' => $tableName,
            'record_id' => $recordId
        ]);
    }
    
    /**
     * Get recent audit trail entries
     */
    public function getRecentAuditTrail($limit = 50) {
        return $this->getAuditTrail(['limit' => $limit]);
    }
    
    /**
     * Get audit trail statistics
     */
    public function getAuditStats() {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                GROUP BY action, DATE(created_at)
                ORDER BY date DESC, count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Get user agent
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Clean old audit trail entries (older than specified days)
     */
    public function cleanOldEntries($days = 365) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$days]);
    }
} 