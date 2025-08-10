<?php
require_once BASE_PATH . '/core/Model.php';

class COAAccountTypeModel extends Model {
    protected $table = 'coa_account_types';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllTypes() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY type_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTypeById($id) {
        return $this->findById($id);
    }
    
    public function createType($data) {
        $data['status'] = 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateType($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    public function deleteType($id) {
        // Soft delete
        return $this->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function getTypesForDropdown() {
        $types = $this->getAllTypes();
        $dropdown = [];
        foreach ($types as $type) {
            $dropdown[$type['id']] = $type['type_name'];
        }
        return $dropdown;
    }
    
    public function getTypeWithGroup($id) {
        $sql = "SELECT t.*, g.group_name 
                FROM {$this->table} t 
                LEFT JOIN account_title_groups g ON t.group_id = g.id 
                WHERE t.id = ? AND t.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 