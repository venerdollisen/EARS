<?php
require_once BASE_PATH . '/core/Model.php';

class JournalEntryModel extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all journal entries with user information
     */
    public function getAllJournalEntries() {
        $sql = "SELECT 
                    je.id,
                    je.reference_no,
                    je.transaction_date,
                    je.description,
                    je.total_amount,
                    je.status,
                    je.jv_status,
                    je.for_posting,
                    je.created_at,
                    u.full_name as created_by
                FROM journal_entries je
                LEFT JOIN users u ON je.created_by = u.id
                ORDER BY je.transaction_date DESC, je.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get journal entry by ID with user information
     */
    public function getJournalEntryById($id) {
        $sql = "SELECT 
                    je.*,
                    u.full_name as created_by_name
                 FROM journal_entries je
                 LEFT JOIN users u ON je.created_by = u.id
                 WHERE je.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get journal entry details with related data
     */
    public function getJournalEntryDetails($journalEntryId) {
        $sql = "SELECT 
                    jed.*,
                    coa.account_code,
                    coa.account_name,
                    p.project_code,
                    p.project_name,
                    d.department_code,
                    d.department_name,
                    s.supplier_name
                 FROM journal_entry_details jed
                 JOIN chart_of_accounts coa ON jed.account_id = coa.id
                 LEFT JOIN projects p ON jed.project_id = p.id
                 LEFT JOIN departments d ON jed.department_id = d.id
                 LEFT JOIN suppliers s ON jed.supplier_id = s.id
                 WHERE jed.journal_entry_id = ?
                 ORDER BY jed.transaction_type DESC, jed.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$journalEntryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new journal entry
     */
    public function createJournalEntry($data) {
        $this->db->beginTransaction();
        
        try {
            // Insert journal entry header
            $headerSql = "INSERT INTO journal_entries (reference_no, transaction_date, description, total_amount, status, jv_status, for_posting, reference_number1, reference_number2, cwo_number, bill_invoice_ref, created_by, created_at) 
                         VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())";
            $headerStmt = $this->db->prepare($headerSql);
            $headerStmt->execute([
                $data['reference_no'],
                $data['transaction_date'],
                $data['description'],
                $data['total_amount'],
                $data['jv_status'],
                $data['for_posting'],
                $data['reference_number1'],
                $data['reference_number2'],
                $data['cwo_number'],
                $data['bill_invoice_ref'],
                $data['created_by']
            ]);
            
            $journalEntryId = $this->db->lastInsertId();
            
            // Insert journal entry details
            $detailSql = "INSERT INTO journal_entry_details (journal_entry_id, account_id, project_id, department_id, supplier_id, transaction_type, amount, description) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $detailStmt = $this->db->prepare($detailSql);
            
            foreach ($data['entries'] as $entry) {
                $detailStmt->execute([
                    $journalEntryId,
                    $entry['account_id'],
                    $entry['project_id'] ?? null,
                    $entry['department_id'] ?? null,
                    $entry['supplier_id'] ?? null,
                    $entry['transaction_type'],
                    $entry['amount'],
                    $entry['description'] ?? ''
                ]);
            }
            
            $this->db->commit();
            return $journalEntryId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Approve journal entry
     */
    public function approveJournalEntry($id, $approvedBy) {
        $this->db->beginTransaction();
        
        try {
            // Get journal entry
            $sql = "SELECT * FROM journal_entries WHERE id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $journalEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$journalEntry) {
                throw new Exception('Journal entry not found or already processed');
            }
            
            // Get journal entry details
            $details = $this->getJournalEntryDetails($id);
            
            if (empty($details)) {
                throw new Exception('No journal entry details found');
            }
            
            // Validate that all accounts exist and are active
            foreach ($details as $detail) {
                $accountCheckSql = "SELECT id FROM chart_of_accounts WHERE id = ? AND status = 'active'";
                $accountStmt = $this->db->prepare($accountCheckSql);
                $accountStmt->execute([$detail['account_id']]);
                if (!$accountStmt->fetch()) {
                    throw new Exception('Account ID ' . $detail['account_id'] . ' not found or inactive');
                }
            }
            
            // Create parent transaction
            $parentSql = "INSERT INTO transactions (reference_no, transaction_date, transaction_type, description, STATUS, created_by, created_at) 
                         VALUES (?, ?, 'journal_entry', ?, 'approved', ?, NOW())";
            $parentStmt = $this->db->prepare($parentSql);
            $parentStmt->execute([
                $journalEntry['reference_no'],
                $journalEntry['transaction_date'],
                $journalEntry['description'],
                $approvedBy
            ]);
            $parentTransactionId = $this->db->lastInsertId();
            
            // Create child transactions
            $childSql = "INSERT INTO transactions (parent_transaction_id, account_id, project_id, department_id, supplier_id, transaction_type, amount, description, transaction_date, STATUS, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, NOW())";
            $childStmt = $this->db->prepare($childSql);
            
            foreach ($details as $detail) {
                $childStmt->execute([
                    $parentTransactionId,
                    $detail['account_id'],
                    $detail['project_id'],
                    $detail['department_id'],
                    $detail['supplier_id'],
                    $detail['transaction_type'],
                    $detail['amount'],
                    $detail['description'],
                    $journalEntry['transaction_date'],
                    $approvedBy
                ]);
            }
            
            // Update journal entry status
            $updateSql = "UPDATE journal_entries SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$approvedBy, $id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Reject journal entry
     */
    public function rejectJournalEntry($id, $rejectedBy, $reason) {
        $sql = "UPDATE journal_entries SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$rejectedBy, $reason, $id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Journal entry not found or already processed');
        }
        
        return true;
    }
    
    /**
     * Get active accounts
     */
    public function getActiveAccounts() {
        $sql = "SELECT id, account_code, account_name 
                FROM chart_of_accounts 
                WHERE status = 'active' 
                ORDER BY account_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active projects
     */
    public function getActiveProjects() {
        $sql = "SELECT id, project_code, project_name 
                FROM projects 
                WHERE status = 'active' 
                ORDER BY project_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active departments
     */
    public function getActiveDepartments() {
        $sql = "SELECT id, department_code, department_name 
                FROM departments 
                WHERE status = 'active' 
                ORDER BY department_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active suppliers
     */
    public function getActiveSuppliers() {
        $sql = "SELECT id, supplier_name 
                FROM suppliers 
                WHERE status = 'active' 
                ORDER BY supplier_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate reference number
     */
    public function generateReferenceNo() {
        $prefix = 'JE';
        $date = date('Ymd');
        
        try {
            $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE DATE(created_at) = CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] ?? 0;
        } catch (Exception $e) {
            // If table doesn't exist yet, start with 1
            $count = 0;
        }
        
        $sequence = str_pad(($count + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $sequence;
    }
    
    /**
     * Check if journal entry exists and is pending
     */
    public function isPendingJournalEntry($id) {
        $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Get journal entry count for today
     */
    public function getTodayCount() {
        $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Get journal entry balance information
     */
    public function getJournalEntryBalance($journalEntryId) {
        $sql = "SELECT 
                    SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as total_debits,
                    SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as total_credits
                FROM journal_entry_details 
                WHERE journal_entry_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$journalEntryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalDebits = $result['total_debits'] ?? 0;
        $totalCredits = $result['total_credits'] ?? 0;
        $difference = $totalDebits - $totalCredits;
        
        return [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference
        ];
    }
    
    /**
     * Get all journal entries with balance information
     */
    public function getAllJournalEntriesWithBalance() {
        $sql = "SELECT 
                    je.id,
                    je.reference_no,
                    je.transaction_date,
                    je.description,
                    je.total_amount,
                    je.status,
                    je.jv_status,
                    je.for_posting,
                    je.created_at,
                    u.full_name as created_by
                FROM journal_entries je
                LEFT JOIN users u ON je.created_by = u.id
                ORDER BY je.transaction_date DESC, je.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $journalEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add balance information for each journal entry
        foreach ($journalEntries as &$entry) {
            $balance = $this->getJournalEntryBalance($entry['id']);
            $entry['balance_info'] = $balance;
        }
        
        return $journalEntries;
    }
}
?> 