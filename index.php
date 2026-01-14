<?php
/**
 * Index/Home Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Auth/Auth.php';

// Initialize auth to check if user is logged in
Auth::initialize(getMainDatabaseConnection());

if (Auth::isLoggedIn()) {
    // Redirect to appropriate dashboard
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

// Not logged in, show welcome page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Steeper Climb - Online Course Platform</title>
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
        
        .welcome-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 42px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #999;
            font-size: 16px;
        }
        
        .tagline {
            color: #666;
            font-size: 18px;
            margin: 30px 0;
            line-height: 1.6;
            font-style: italic;
        }
        
        .about {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: left;
            border-left: 4px solid #667eea;
        }
        
        .about h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .about p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .feature {
            padding: 15px;
            background: #f0f4ff;
            border-radius: 8px;
            border: 1px solid #ddeef7;
        }
        
        .feature h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .feature p {
            color: #666;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 40px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f4ff;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #e7ecff;
        }
        
        .setup-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            color: #856404;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .welcome-container {
                padding: 40px 20px;
            }
            
            .logo h1 {
                font-size: 32px;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="logo">
            <h1>The Steeper Climb</h1>
            <p>Online Course Platform</p>
        </div>
        
        <p class="tagline">
            "Empowering individuals through educational excellence and personal growth"
        </p>
        
        <div class="about">
            <h3>About Nancy MacLeod</h3>
            <p>
                Nancy MacLeod is the founder of The Steeper Climb, a business dedicated to the empowerment 
                and personal growth of individuals. With over 30 years of experience as an educator and 
                administrator, Nancy develops and delivers educational programming designed to support 
                individuals through self-reflection and goal setting.
            </p>
        </div>
        
        <div class="features">
            <div class="feature">
                <h4>📚 Structured Learning</h4>
                <p>Comprehensive courses with chapters, sections, and interactive content</p>
            </div>
            <div class="feature">
                <h4>📊 Progress Tracking</h4>
                <p>Monitor your learning journey with real-time progress indicators</p>
            </div>
            <div class="feature">
                <h4>🎓 Certification</h4>
                <p>Earn recognized certificates upon successful course completion</p>
            </div>
            <div class="feature">
                <h4>👥 Organizational</h4>
                <p>Built for organizations to manage their learning initiatives</p>
            </div>
        </div>
        
        <div class="actions">
            <a href="<?php echo APP_URL; ?>/public/login.php" class="btn btn-primary">Login to Platform</a>
            <a href="<?php echo APP_URL; ?>/QUICKSTART.php" class="btn btn-secondary">Quick Start Guide</a>
        </div>
        
        <div class="setup-notice">
            <strong>First Time?</strong><br>
            If this is your first time setting up the platform, please visit the 
            <a href="<?php echo APP_URL; ?>/setup/install.php" style="color: #856404; font-weight: 600;">Database Setup</a> 
            page first, then create your admin account.
        </div>
        
        <div class="footer">
            <p>The Steeper Climb © <?php echo date('Y'); ?> | Empowering Personal Growth</p>
        </div>
    </div>
</body>
</html>
