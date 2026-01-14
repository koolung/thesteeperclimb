<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

Auth::initialize(getMainDatabaseConnection());

// If already logged in, redirect
if (Auth::isLoggedIn()) {
    $role = Auth::getRole();
    if ($role === ROLE_ADMIN) {
        header('Location: ' . APP_URL . '/public/admin/dashboard.php');
    } elseif ($role === ROLE_ORGANIZATION) {
        header('Location: ' . APP_URL . '/public/organization/dashboard.php');
    } elseif ($role === ROLE_STUDENT) {
        header('Location: ' . APP_URL . '/public/student/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        try {
            Auth::login($email, $password);
            
            // Redirect based on role
            $role = Auth::getRole();
            if ($role === ROLE_ADMIN) {
                header('Location: ' . APP_URL . '/public/admin/dashboard.php');
            } elseif ($role === ROLE_ORGANIZATION) {
                header('Location: ' . APP_URL . '/public/organization/dashboard.php');
            } elseif ($role === ROLE_STUDENT) {
                header('Location: ' . APP_URL . '/public/student/dashboard.php');
            }
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Steeper Climb</title>
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
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 50px 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #999;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
            font-size: 14px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
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
        
        .info-box {
            background: #f0f4ff;
            border: 1px solid #ddeef7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>The Steeper Climb</h1>
            <p>Online Course Platform</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>Demo Credentials:</strong><br>
            • Admin: Create via setup<br>
            • Organization/Student: Created by Admin
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    required
                >
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="footer">
            First time here? Contact your administrator to create an account.
        </div>
    </div>
</body>
</html>
