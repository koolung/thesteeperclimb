<?php
/**
 * Base Model Class
 */

abstract class BaseModel {
    protected $pdo;
    protected $table;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Find by ID
     */
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find all
     */
    public function findAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create
     */
    public function create($data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update
     */
    public function update($id, $data) {
        $sets = [];
        foreach (array_keys($data) as $field) {
            $sets[] = "$field = ?";
        }
        $setString = implode(', ', $sets);
        
        $sql = "UPDATE {$this->table} SET $setString WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Count
     */
    public function count($where = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE $where";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
?>
