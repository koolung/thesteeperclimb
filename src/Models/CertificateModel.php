<?php
/**
 * Certificate Model
 */

require_once __DIR__ . '/BaseModel.php';

class CertificateModel extends BaseModel {
    protected $table = 'certificates';
    
    /**
     * Issue certificate
     */
    public function issueCertificate($student_id, $course_id, $score_percentage) {
        // Check if certificate already exists
        $stmt = $this->pdo->prepare(
            "SELECT id FROM {$this->table} WHERE student_id = ? AND course_id = ?"
        );
        $stmt->execute([$student_id, $course_id]);
        if ($stmt->fetch()) {
            return null; // Certificate already exists
        }
        
        // Generate unique certificate number
        $certificate_number = 'CERT-' . strtoupper(uniqid());
        
        // Insert certificate
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (student_id, course_id, certificate_number, issued_date, score_percentage)
             VALUES (?, ?, ?, NOW(), ?)"
        );
        $stmt->execute([$student_id, $course_id, $certificate_number, $score_percentage]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get student certificates
     */
    public function getStudentCertificates($student_id) {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.first_name, u.last_name, co.title as course_title
             FROM {$this->table} c
             INNER JOIN users u ON c.student_id = u.id
             INNER JOIN courses co ON c.course_id = co.id
             WHERE c.student_id = ?
             ORDER BY c.issued_date DESC"
        );
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get certificate by number
     */
    public function getByCertificateNumber($certificate_number) {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.first_name, u.last_name, co.title as course_title
             FROM {$this->table} c
             INNER JOIN users u ON c.student_id = u.id
             INNER JOIN courses co ON c.course_id = co.id
             WHERE c.certificate_number = ?"
        );
        $stmt->execute([$certificate_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get organization certificates count
     */
    public function countByOrganization($organization_id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as total FROM {$this->table} c
             INNER JOIN users u ON c.student_id = u.id
             WHERE u.organization_id = ?"
        );
        $stmt->execute([$organization_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
?>
