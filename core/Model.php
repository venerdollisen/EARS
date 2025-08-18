<?php
class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function findAll() {
        $query = "SELECT * FROM {$this->table}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldList = implode(', ', $fields);
        
        $query = "INSERT INTO {$this->table} ({$fieldList}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($query);
        
        foreach ($data as $field => $value) {
            $stmt->bindValue(":{$field}", $value);
        }
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $fields = array_keys($data);
        $setClause = '';
        foreach ($fields as $field) {
            $setClause .= "{$field} = :{$field}, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $query = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        // foreach ($data as $field => $value) {
        //     $stmt->bindValue(":{$field}", $value);
        // }
        foreach ($data as $field => $value) {
            if (is_array($value)) {
                // Convert array to JSON string (or serialize if you prefer)
                $value = json_encode($value);
            }
            $stmt->bindValue(":{$field}", $value);
        }

        $stmt->bindValue(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function where($conditions) {
        $whereClause = '';
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause .= "{$field} = :{$field} AND ";
            $params[":{$field}"] = $value;
        }
        $whereClause = rtrim($whereClause, ' AND ');
        
        $query = "SELECT * FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count() {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get fiscal year start and end dates from accounting parameters
     * @return array ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
     */
    protected function getFiscalYearDates() {
        try {
            $sql = "SELECT 
                    MAX(CASE WHEN parameter_name = 'fiscal_year_start' THEN parameter_value END) AS fy_start,
                    MAX(CASE WHEN parameter_name = 'fiscal_year_end' THEN parameter_value END) AS fy_end
                FROM accounting_parameters";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Default to current year if fiscal year not set
            $fyStart = $result['fy_start'] ?: date('Y-01-01');
            $fyEnd = $result['fy_end'] ?: date('Y-12-31');
            
            return [
                'start' => $fyStart,
                'end' => $fyEnd
            ];
        } catch (Exception $e) {
            error_log('Error getting fiscal year dates: ' . $e->getMessage());
            // Fallback to current year
            return [
                'start' => date('Y-01-01'),
                'end' => date('Y-12-31')
            ];
        }
    }
}
?> 