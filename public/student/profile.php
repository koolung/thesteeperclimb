<?php
/**
 * Student Profile
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_STUDENT);

$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($first_name) || empty($last_name)) {
            $error = 'First name and last name are required';
        } else {
            try {
                $userModel->update($user['id'], [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone
                ]);
                
                $message = 'Profile updated successfully';
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Get user from database to verify current password
        $current_user = $userModel->findById($user['id']);
        
        if (!password_verify($current_password, $current_user['password_hash'])) {
            $error = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < MIN_PASSWORD_LENGTH) {
            $error = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters';
        } else {
            try {
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $userModel->update($user['id'], ['password_hash' => $password_hash]);
                
                $message = 'Password changed successfully';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Get current user info
$user_info = $userModel->findById($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <style>
        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #999;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Student Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php">My Courses</a></li>
                    <li><a href="certificates.php">Certificates</a></li>
                    <li class="active"><a href="profile.php">My Profile</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>My Profile</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <section class="content-section">
                <div class="profile-tabs">
                    <button class="tab-button active" onclick="switchTab('profile')">Profile Information</button>
                    <button class="tab-button" onclick="switchTab('password')">Change Password</button>
                </div>
                
                <!-- Profile Tab -->
                <div id="profile" class="tab-content active">
                    <h2>Profile Information</h2>
                    <form method="POST" class="form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_info['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_info['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <!-- Password Tab -->
                <div id="password" class="tab-content">
                    <h2>Change Password</h2>
                    <form method="POST" class="form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small>At least 8 characters with uppercase, number, and special character</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
