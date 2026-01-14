<?php
/**
 * Utility Functions
 */

class Utils {
    /**
     * Log audit action
     */
    public static function auditLog($pdo, $user_id, $action, $entity_type, $entity_id = null, $description = null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $description,
            $ip_address
        ]);
    }
    
    /**
     * Create notification
     */
    public static function notify($pdo, $user_id, $type, $title, $message, $entity_id = null) {
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, title, message, related_entity_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $user_id,
            $type,
            $title,
            $message,
            $entity_id
        ]);
    }
    
    /**
     * Generate certificate number
     */
    public static function generateCertificateNumber() {
        return 'CERT-' . strtoupper(uniqid());
    }
    
    /**
     * Format date
     */
    public static function formatDate($date, $format = 'M d, Y') {
        if (!$date) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Escape HTML
     */
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate file upload
     */
    public static function validateUpload($file, $allowed_types, $max_size) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['error' => 'No file uploaded'];
        }
        
        if ($file['size'] > $max_size) {
            return ['error' => 'File size exceeds maximum allowed'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            return ['error' => 'File type not allowed'];
        }
        
        return ['success' => true, 'mime' => $mime];
    }
    
    /**
     * Save uploaded file
     */
    public static function saveUpload($file, $directory) {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $directory . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Get pagination info
     */
    public static function getPagination($current_page, $total_items, $items_per_page = ITEMS_PER_PAGE) {
        $total_pages = ceil($total_items / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        
        return [
            'current_page' => max(1, $current_page),
            'total_pages' => max(1, $total_pages),
            'total_items' => $total_items,
            'offset' => $offset,
            'limit' => $items_per_page
        ];
    }
    
    /**
     * Generate random password
     */
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
?>
