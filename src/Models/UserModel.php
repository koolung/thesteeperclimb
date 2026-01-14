<?php
/**
 * User Model
 */

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';
    
    /**
     * Find by email
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find by role
     */
    public function findByRole($role, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ?";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find students by organization
     */
    public function findStudentsByOrganization($organization_id, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE role = ? AND status = ? AND organization_id = ?";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([ROLE_STUDENT, STATUS_ACTIVE, $organization_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count students by organization
     */
    public function countStudentsByOrganization($organization_id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM {$this->table} 
             WHERE role = ? AND status = ? AND organization_id = ?"
        );
        $stmt->execute([ROLE_STUDENT, STATUS_ACTIVE, $organization_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Find organizations
     */
    public function findOrganizations($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([ROLE_ORGANIZATION]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
