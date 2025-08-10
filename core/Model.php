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
}
?> 