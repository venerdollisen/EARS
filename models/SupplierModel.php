<?php
require_once BASE_PATH . '/core/Model.php';

class SupplierModel extends Model {
    protected $table = 'suppliers';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllSuppliers() {
        $sql = "SELECT s.*, coa.account_code, coa.account_name,
                       vat_coa.account_code as vat_account_code, vat_coa.account_name as vat_account_name
                FROM {$this->table} s 
                LEFT JOIN chart_of_accounts coa ON s.account_id = coa.id 
                LEFT JOIN chart_of_accounts vat_coa ON s.vat_account_id = vat_coa.id 
                WHERE s.status = 'active' 
                ORDER BY s.supplier_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSupplierById($id) {
        return $this->findById($id);
    }
    
    public function createSupplier($data) {
        $data['status'] = 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateSupplier($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    public function deleteSupplier($id) {
        // Soft delete
        return $this->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function getSuppliersForDropdown() {
        $suppliers = $this->getAllSuppliers();
        $dropdown = [];
        foreach ($suppliers as $supplier) {
            $dropdown[$supplier['id']] = $supplier['supplier_name'];
        }
        return $dropdown;
    }
    
    public function searchSuppliers($searchTerm) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'active' 
                AND (supplier_name LIKE ? OR supplier_code LIKE ? OR contact_person LIKE ?)
                ORDER BY supplier_name ASC";
        $searchPattern = "%{$searchTerm}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkSupplierCodeExists($supplierCode, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE supplier_code = ? AND status = 'active'";
        $params = [$supplierCode];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getSupplierTransactions($supplierId) {
        $sql = "SELECT t.*, coa.account_name 
                FROM transactions t 
                LEFT JOIN chart_of_accounts coa ON t.account_id = coa.id 
                WHERE t.supplier_id = ? AND t.status = 'active' 
                ORDER BY t.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSupplierBalance($supplierId) {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as credits,
                    COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as debits
                FROM transactions t 
                WHERE t.supplier_id = ? AND t.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$supplierId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'credits' => $result['credits'],
            'debits' => $result['debits'],
            'balance' => $result['credits'] - $result['debits']
        ];
    }
}
?> 