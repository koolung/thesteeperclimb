<?php
/**
 * Course Model
 */

require_once __DIR__ . '/BaseModel.php';

class CourseModel extends BaseModel {
    protected $table = 'courses';
    
    /**
     * Get published courses
     */
    public function getPublished($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([COURSE_PUBLISHED]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get courses for organization
     */
    public function getForOrganization($organization_id, $limit = null, $offset = 0) {
        $sql = "SELECT c.* FROM {$this->table} c
                INNER JOIN organization_courses oc ON c.id = oc.course_id
                WHERE oc.organization_id = ? AND c.status = ?
                ORDER BY c.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$organization_id, COURSE_PUBLISHED]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count courses for organization
     */
    public function countForOrganization($organization_id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM {$this->table} c
             INNER JOIN organization_courses oc ON c.id = oc.course_id
             WHERE oc.organization_id = ? AND c.status = ?"
        );
        $stmt->execute([$organization_id, COURSE_PUBLISHED]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Get course with sections and chapters
     */
    public function getWithStructure($id) {
        $course = $this->findById($id);
        if (!$course) return null;
        
        // Get chapters
        $stmt = $this->pdo->prepare(
            "SELECT id, title, description, `order` FROM chapters 
             WHERE course_id = ? ORDER BY `order` ASC"
        );
        $stmt->execute([$id]);
        $course['chapters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sections for each chapter
        foreach ($course['chapters'] as &$chapter) {
            $stmt = $this->pdo->prepare(
                "SELECT id, title, description, type, `order` FROM sections 
                 WHERE chapter_id = ? ORDER BY `order` ASC"
            );
            $stmt->execute([$chapter['id']]);
            $chapter['sections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $course;
    }
    
    /**
     * Get total sections in course
     */
    public function getTotalSections($course_id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM sections s
             INNER JOIN chapters c ON s.chapter_id = c.id
             WHERE c.course_id = ?"
        );
        $stmt->execute([$course_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
?>
