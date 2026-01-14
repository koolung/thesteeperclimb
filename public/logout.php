<?php
/**
 * Logout
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

Auth::initialize(getMainDatabaseConnection());
Auth::logout();

header('Location: ' . APP_URL . '/public/login.php');
exit;
?>
