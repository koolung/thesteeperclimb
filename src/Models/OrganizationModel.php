<?php
/**
 * Organization Model
 */

require_once __DIR__ . '/BaseModel.php';

class OrganizationModel extends BaseModel {
    protected $table = 'organizations';
    
    /**
     * Find by email
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active organizations
     */
    public function getActive($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([STATUS_ACTIVE]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count active organizations
     */
    public function countActive() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE status = ?");
        $stmt->execute([STATUS_ACTIVE]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Get organization with stats
     */
    public function getWithStats($id) {
        $org = $this->findById($id);
        if (!$org) return null;
        
        // Count students
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM users WHERE organization_id = ? AND role = ?"
        );
        $stmt->execute([$id, ROLE_STUDENT]);
        $org['student_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Count courses
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM organization_courses WHERE organization_id = ?"
        );
        $stmt->execute([$id]);
        $org['course_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $org;
    }
}
?>
