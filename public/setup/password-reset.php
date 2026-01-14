<?php
/**
 * Organization Password Setup
 * Link sent via email allows organizations to set their password
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/Auth.php';
require_once __DIR__ . '/../src/Models/UserModel.php';
require_once __DIR__ . '/../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);

$error = '';
$success = '';
$org_id = isset($_GET['org_id']) ? (int)$_GET['org_id'] : 0;
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password) || empty($password_confirm)) {
        $error = 'Both password fields are required';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match';
    } elseif (!Auth::validatePassword($password)) {
        $error = 'Password does not meet requirements: minimum 8 characters, 1 uppercase letter, 1 number, 1 special character';
    } else {
        try {
            $userModel = new UserModel($pdo);
            $org_user = $userModel->findByEmail($email);
            
            if (!$org_user || $org_user['organization_id'] != $org_id) {
                $error = 'Invalid organization credentials';
            } else {
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $org_user['id']]);
                
                Utils::auditLog($pdo, $org_user['id'], 'SET_PASSWORD', 'user', $org_user['id'], 'Organization set password');
                
                $success = 'Password set successfully! You can now log in.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password - The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 50px 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #999;
            font-size: 14px;
        }

        .logo {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .password-requirements h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .password-requirements ul {
            list-style: none;
            margin-left: 0;
        }

        .password-requirements li {
            padding: 4px 0;
            color: #666;
        }

        .password-requirements li:before {
            content: "• ";
            color: #667eea;
            margin-right: 8px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .footer p {
            font-size: 13px;
            color: #999;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .success-state {
            text-align: center;
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .success-state h2 {
            color: #155724;
            margin-bottom: 15px;
        }

        .success-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .login-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success-state">
                <div class="success-icon">✅</div>
                <h2>Password Set Successfully!</h2>
                <p><?php echo htmlspecialchars($success); ?></p>
                <a href="<?php echo APP_URL; ?>/public/login.php" class="login-btn">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="header">
                <div class="logo">🚀</div>
                <h1>Set Your Password</h1>
                <p>Complete your account setup for The Steeper Climb</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                </div>

                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>Minimum 8 characters</li>
                        <li>At least 1 uppercase letter (A-Z)</li>
                        <li>At least 1 number (0-9)</li>
                        <li>At least 1 special character (!@#$%^&*)</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" placeholder="Confirm your password" required>
                </div>

                <button type="submit" class="submit-btn">Set Password</button>
            </form>

            <div class="footer">
                <p>Need help? <a href="<?php echo APP_URL; ?>">Contact Support</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
