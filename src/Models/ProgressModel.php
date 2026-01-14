<?php
/**
 * Progress Model
 */

require_once __DIR__ . '/BaseModel.php';

class ProgressModel extends BaseModel {
    protected $table = 'student_progress';
    
    /**
     * Get or create student progress
     */
    public function getOrCreate($student_id, $course_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE student_id = ? AND course_id = ?"
        );
        $stmt->execute([$student_id, $course_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            // Create new progress record
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (student_id, course_id, status, progress_percentage)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$student_id, $course_id, PROGRESS_NOT_STARTED, 0]);
            
            return $this->findById($this->pdo->lastInsertId());
        }
        
        return $progress;
    }
    
    /**
     * Get student's courses (filtered by organization)
     */
    public function getStudentCourses($student_id, $status = null) {
        $sql = "SELECT 
                c.id as course_id,
                c.title,
                c.description,
                c.difficulty_level,
                c.duration_hours,
                c.status as course_status,
                COALESCE(sp.id, 0) as progress_id,
                COALESCE(sp.progress_percentage, 0) as progress_percentage,
                COALESCE(sp.status, 'not_started') as progress_status,
                COALESCE(sp.started_at, NULL) as started_at,
                COALESCE(sp.completed_at, NULL) as completed_at,
                COALESCE(sp.updated_at, c.created_at) as updated_at
                FROM courses c
                INNER JOIN organization_courses oc ON oc.course_id = c.id
                INNER JOIN users u ON u.id = ? AND oc.organization_id = u.organization_id
                LEFT JOIN {$this->table} sp ON sp.student_id = ? AND sp.course_id = c.id";
        
        if ($status) {
            $sql .= " WHERE sp.status = ?";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($status) {
            $stmt->execute([$student_id, $student_id, $status]);
        } else {
            $stmt->execute([$student_id, $student_id]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update progress percentage
     */
    public function updateProgress($student_id, $course_id) {
        // Get total sections
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM sections s
             INNER JOIN chapters c ON s.chapter_id = c.id
             WHERE c.course_id = ?"
        );
        $stmt->execute([$course_id]);
        $total_sections = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($total_sections === 0) {
            return;
        }
        
        // Get completed sections
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as completed FROM section_completion
             WHERE student_id = ? AND section_id IN (
                SELECT s.id FROM sections s
                INNER JOIN chapters c ON s.chapter_id = c.id
                WHERE c.course_id = ?
             )"
        );
        $stmt->execute([$student_id, $course_id]);
        $completed_sections = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        $progress_percentage = (int)(($completed_sections / $total_sections) * 100);
        
        // Determine status
        $status = PROGRESS_IN_PROGRESS;
        if ($progress_percentage === 100) {
            $status = PROGRESS_COMPLETED;
        } elseif ($progress_percentage === 0) {
            $status = PROGRESS_NOT_STARTED;
        }
        
        // Update progress
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} 
             SET progress_percentage = ?, status = ?, updated_at = NOW()
             WHERE student_id = ? AND course_id = ?"
        );
        $stmt->execute([$progress_percentage, $status, $student_id, $course_id]);
        
        return $progress_percentage;
    }
    
    /**
     * Mark section as completed
     */
    public function completeSection($student_id, $section_id) {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO section_completion (student_id, section_id, completed_at)
             VALUES (?, ?, NOW())"
        );
        return $stmt->execute([$student_id, $section_id]);
    }
    
    /**
     * Get student progress summary
     */
    public function getProgressSummary($student_id) {
        $stmt = $this->pdo->prepare(
            "SELECT 
                COUNT(*) as total_courses,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_courses,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_courses,
                AVG(progress_percentage) as average_progress
            FROM {$this->table}
            WHERE student_id = ?"
        );
        $stmt->execute([PROGRESS_COMPLETED, PROGRESS_IN_PROGRESS, $student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
