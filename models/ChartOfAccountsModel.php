<?php
require_once BASE_PATH . '/core/Model.php';

class ChartOfAccountsModel extends Model {
    protected $table = 'chart_of_accounts';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllAccounts() {
        $sql = "SELECT coa.*, atg.group_name, cat.type_name 
                FROM {$this->table} coa 
                LEFT JOIN account_title_groups atg ON coa.group_id = atg.id 
                LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id 
                WHERE coa.status = 'active' 
                ORDER BY coa.account_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllAccountsIncludingInactive() {
        $sql = "SELECT coa.*, atg.group_name, cat.type_name 
                FROM {$this->table} coa 
                LEFT JOIN account_title_groups atg ON coa.group_id = atg.id 
                LEFT JOIN coa_account_types cat ON coa.account_type_id = cat.id 
                ORDER BY coa.account_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAccountById($id) {
        return $this->findById($id);
    }
    
    public function createAccount($data) {
        $data['status'] = 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateAccount($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    public function deleteAccount($id) {
        // Check if account is being used by suppliers or transactions
        $dependencies = $this->checkAccountDependencies($id);
        
        if (!empty($dependencies)) {
            throw new Exception('Cannot delete account. It is being used by: ' . implode(', ', $dependencies));
        }
        
        // Soft delete
        return $this->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function checkAccountDependencies($accountId) {
        $dependencies = [];
        
        // Check suppliers
        $sql = "SELECT COUNT(*) FROM suppliers WHERE account_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        $supplierCount = $stmt->fetchColumn();
        
        if ($supplierCount > 0) {
            $dependencies[] = "$supplierCount supplier(s)";
        }
        
        // Check transactions
        $sql = "SELECT COUNT(*) FROM transactions WHERE account_id = ? AND status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        $transactionCount = $stmt->fetchColumn();
        
        if ($transactionCount > 0) {
            $dependencies[] = "$transactionCount transaction(s)";
        }
        
        return $dependencies;
    }
    
    public function getAccountsForDropdown() {
        $accounts = $this->getAllAccounts();
        $dropdown = [];
        foreach ($accounts as $account) {
            $dropdown[$account['id']] = $account['account_code'] . ' - ' . $account['account_name'];
        }
        return $dropdown;
    }
    
    public function getAccountsByType($typeId) {
        $sql = "SELECT * FROM {$this->table} WHERE account_type_id = ? AND status = 'active' ORDER BY account_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$typeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAccountsByGroup($groupId) {
        $sql = "SELECT * FROM {$this->table} WHERE group_id = ? AND status = 'active' ORDER BY account_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkAccountCodeExists($accountCode, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE account_code = ? AND status = 'active'";
        $params = [$accountCode];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getAccountBalance($accountId) {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as credits,
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as debits
                FROM transactions t 
                WHERE t.account_id = ? AND t.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'credits' => $result['credits'],
            'debits' => $result['debits'],
            'balance' => $result['credits'] - $result['debits']
        ];
    }
}
?> 