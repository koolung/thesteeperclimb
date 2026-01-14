<?php
/**
 * Authentication Class
 */

class Auth {
    private static $pdo;
    
    public static function initialize($pdo) {
        self::$pdo = $pdo;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Register a new user
     */
    public static function register($email, $password, $first_name, $last_name, $role = ROLE_STUDENT, $organization_id = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email exists
        $stmt = self::$pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Validate password
        if (!self::validatePassword($password)) {
            throw new Exception('Password does not meet requirements');
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        if ($role === ROLE_STUDENT && $organization_id !== null) {
            $stmt = self::$pdo->prepare('
                INSERT INTO users (email, password_hash, first_name, last_name, role, status, organization_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            return $stmt->execute([
                $email,
                $password_hash,
                $first_name,
                $last_name,
                $role,
                STATUS_ACTIVE,
                $organization_id
            ]);
        } else {
            $stmt = self::$pdo->prepare('
                INSERT INTO users (email, password_hash, first_name, last_name, role, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            return $stmt->execute([
                $email,
                $password_hash,
                $first_name,
                $last_name,
                $role,
                STATUS_ACTIVE
            ]);
        }
    }
    
    /**
     * Login user
     */
    public static function login($email, $password) {
        $stmt = self::$pdo->prepare('
            SELECT id, email, password_hash, first_name, last_name, role, status
            FROM users
            WHERE email = ? AND status = ?
        ');
        
        $stmt->execute([$email, STATUS_ACTIVE]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid email or password');
        }
        
        // Update last login
        $updateStmt = self::$pdo->prepare('
            UPDATE users SET last_login = NOW() WHERE id = ?
        ');
        $updateStmt->execute([$user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !self::isSessionExpired();
    }
    
    /**
     * Check if session is expired
     */
    private static function isSessionExpired() {
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                self::logout();
                return true;
            }
            // Refresh session timeout
            $_SESSION['login_time'] = time();
        }
        return false;
    }
    
    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Get user role
     */
    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Check if user has a specific role
     */
    public static function hasRole($role) {
        return self::isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole($roles) {
        if (!self::isLoggedIn()) {
            return false;
        }
        return in_array($_SESSION['role'], $roles);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        session_destroy();
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            return false;
        }
        
        if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if (REQUIRE_SPECIAL_CHARS && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/public/login.php');
            exit;
        }
    }
    
    /**
     * Require role
     */
    public static function requireRole($role) {
        self::requireLogin();
        if (!self::hasRole($role)) {
            header('Location: ' . APP_URL . '/public/unauthorized.php');
            exit;
        }
    }
    
    /**
     * Require any role
     */
    public static function requireAnyRole($roles) {
        self::requireLogin();
        if (!self::hasAnyRole($roles)) {
            header('Location: ' . APP_URL . '/public/unauthorized.php');
            exit;
        }
    }
}
?>
