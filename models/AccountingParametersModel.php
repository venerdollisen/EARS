<?php
require_once BASE_PATH . '/core/Model.php';

class AccountingParametersModel extends Model {
    protected $table = 'accounting_parameters';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getParameters() {
        $sql = "SELECT * FROM {$this->table} ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getParameterByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE parameter_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function saveParameters($parameters) {
        try {
            $this->db->beginTransaction();
            
            foreach ($parameters as $param) {
                if (isset($param['id']) && isset($param['value'])) {
                    $sql = "UPDATE {$this->table} SET parameter_value = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$param['value'], $param['id']]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function saveParametersByName($parameters) {
        try {
            $this->db->beginTransaction();
            
            foreach ($parameters as $param) {
                if (isset($param['name']) && isset($param['value'])) {
                    $sql = "UPDATE {$this->table} SET parameter_value = ?, updated_at = NOW() WHERE parameter_name = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$param['value'], $param['name']]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function updateParameter($id, $value) {
        $sql = "UPDATE {$this->table} SET parameter_value = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $id]);
    }
    
    public function getParameterValue($name, $default = null) {
        $param = $this->getParameterByName($name);
        return $param ? $param['parameter_value'] : $default;
    }
}
?> 