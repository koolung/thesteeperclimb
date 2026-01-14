<?php
/**
 * Create Initial Admin Account
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getMainDatabaseConnection();
            
            // Check if admin already exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
            $stmt->execute([ROLE_ADMIN]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Admin account already exists!';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare('
                    INSERT INTO users (email, password_hash, first_name, last_name, role, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                
                $stmt->execute([
                    $email,
                    $password_hash,
                    $first_name,
                    $last_name,
                    ROLE_ADMIN,
                    STATUS_ACTIVE
                ]);
                
                echo '<div class="success-message">
                    <h2>✓ Admin Account Created Successfully!</h2>
                    <p>Email: ' . htmlspecialchars($email) . '</p>
                    <p><a href="' . APP_URL . '/public/login.php">Go to Login Page</a></p>
                </div>';
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - The Steeper Climb</title>
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
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
        
        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .error-message li {
            margin: 5px 0;
        }
        
        .success-message {
            background: #efe;
            color: #060;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            border-left: 4px solid #060;
        }
        
        .success-message p {
            margin: 10px 0;
        }
        
        .success-message a {
            display: inline-block;
            background: #060;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .success-message a:hover {
            background: #0a0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>The Steeper Climb</h1>
        <p class="subtitle">Create Admin Account</p>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name"
                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                        required
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
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
                <small style="color: #666; margin-top: 5px; display: block;">
                    At least 8 characters
                </small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password"
                    required
                >
            </div>
            
            <button type="submit">Create Admin Account</button>
        </form>
    </div>
</body>
</html>
