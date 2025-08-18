<?php
require_once BASE_PATH . '/core/Model.php';

class TransactionModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get transactions by type (for backward compatibility)
     * This method is deprecated and should be replaced with specific model calls
     */
    public function getTransactionsByType($type) {
        // This method is deprecated since we moved to separated tables
        // Return empty array to prevent errors
        return [];
    }
    
    /**
     * Generate unique reference number (for backward compatibility)
     */
    public function generateReferenceNumber($transactionType, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $prefix = '';
        switch ($transactionType) {
            case 'cash_receipt':
                $prefix = 'CR';
                break;
            case 'cash_disbursement':
                $prefix = 'CD';
                break;
            case 'check_disbursement':
                $prefix = 'CH';
                break;
            case 'debit':
                $prefix = 'DR';
                break;
            case 'credit':
                $prefix = 'CR';
                break;
            case 'journal_entry':
                $prefix = 'JE';
                break;
            default:
                $prefix = 'TR';
        }
        
        $datePart = date('Ymd', strtotime($date));
        
        // Get the last reference number for this type and date from the appropriate table
        $table = '';
        switch ($transactionType) {
            case 'cash_receipt':
                $table = 'cash_receipts';
                break;
            case 'cash_disbursement':
                $table = 'cash_disbursements';
                break;
            case 'check_disbursement':
                $table = 'check_disbursements';
                break;
            default:
                return $prefix . date('YmdHis');
        }
        
        $sql = "SELECT reference_no FROM {$table} 
                WHERE reference_no LIKE ? 
                ORDER BY reference_no DESC 
                LIMIT 1";
        
        $pattern = $prefix . '-' . $datePart . '-%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pattern]);
        $lastRef = $stmt->fetchColumn();
        
        if ($lastRef) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastRef);
            $sequence = intval($parts[2]) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . '-' . $datePart . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
?> 