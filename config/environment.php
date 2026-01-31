<?php
/**
 * Environment Configuration Loader
 * Loads variables from .env file
 */

if (!function_exists('loadEnvironmentVariables')) {
    function loadEnvironmentVariables($filePath = null) {
        if ($filePath === null) {
            $filePath = __DIR__ . '/../.env';
        }
        
        if (!file_exists($filePath)) {
            throw new Exception('.env file not found at: ' . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                // Set environment variable
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * Get environment variable with optional default value
 */
if (!function_exists('getEnv')) {
    function getEnv($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}
?>
