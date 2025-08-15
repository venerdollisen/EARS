<?php

require_once 'core/Controller.php';
require_once 'models/AuditTrailModel.php';

class AuditTrailController extends Controller {
    private $auditTrailModel;
    
    public function __construct() {
        parent::__construct();
        $this->auditTrailModel = new AuditTrailModel();
    }
    
    /**
     * Display audit trail page
     */
    public function index() {
        $this->requireAuth();
        $this->requirePermission('audit_trail');
        
        $this->render('audit-trail/index', [
            'user' => $this->auth->getCurrentUser()
        ]);
    }
    
    /**
     * Get audit trail data for AJAX
     */
    public function getAuditTrail() {
        $this->requireAuth();
        $this->requirePermission('audit_trail');
        
        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'action' => $_GET['action'] ?? null,
                'table_name' => $_GET['table_name'] ?? null,
                'record_id' => $_GET['record_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'limit' => $_GET['limit'] ?? 100,
                'offset' => $_GET['offset'] ?? null
            ];
            
            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $data = $this->auditTrailModel->getAuditTrail($filters);
            $totalCount = $this->auditTrailModel->getAuditTrailCount($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total_count' => $totalCount,
                'current_page' => isset($_GET['offset']) ? (int)($_GET['offset'] / $_GET['limit']) + 1 : 1,
                'items_per_page' => (int)($_GET['limit'] ?? 100)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get audit trail: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get audit trail statistics
     */
    public function getStats() {
        $this->requireAuth();
        $this->requirePermission('audit_trail');
        
        try {
            $stats = $this->auditTrailModel->getAuditStats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get audit stats: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get audit trail for a specific record
     */
    public function getRecordAuditTrail($tableName, $recordId) {
        $this->requireAuth();
        $this->requirePermission('audit_trail');
        
        try {
            $data = $this->auditTrailModel->getRecordAuditTrail($tableName, $recordId);
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get record audit trail: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Export audit trail to CSV
     */
    public function export() {
        $this->requireAuth();
        $this->requirePermission('audit_trail');
        
        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'action' => $_GET['action'] ?? null,
                'table_name' => $_GET['table_name'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $data = $this->auditTrailModel->getAuditTrail($filters);
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="audit_trail_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Date/Time',
                'User',
                'Action',
                'Table',
                'Record ID',
                'IP Address',
                'User Agent'
            ]);
            
            // CSV data
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['created_at'],
                    $row['user_name'] ?? 'Unknown',
                    $row['action'],
                    $row['table_name'],
                    $row['record_id'],
                    $row['ip_address'],
                    $row['user_agent']
                ]);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to export audit trail: ' . $e->getMessage()
            ]);
        }
    }
} 