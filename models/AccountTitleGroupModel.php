<?php
require_once BASE_PATH . '/core/Model.php';

class AccountTitleGroupModel extends Model {
    protected $table = 'account_title_groups';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllGroups() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY group_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getGroupById($id) {
        return $this->findById($id);
    }
    
    public function createGroup($data) {
        $data['status'] = 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    public function updateGroup($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    public function deleteGroup($id) {
        // Soft delete
        return $this->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function getGroupsForDropdown() {
        $groups = $this->getAllGroups();
        $dropdown = [];
        foreach ($groups as $group) {
            $dropdown[$group['id']] = $group['group_name'];
        }
        return $dropdown;
    }
}
?> 