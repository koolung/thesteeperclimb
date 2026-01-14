<?php
/**
 * System Setup Verification
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$checks = [];
$all_good = true;

// Check PHP Version
$php_version = phpversion();
$checks['PHP Version'] = [
    'status' => version_compare($php_version, '7.4.0') >= 0,
    'value' => $php_version,
    'required' => '7.4+'
];

// Check PHP Extensions
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'filter'];
foreach ($required_extensions as $ext) {
    $checks["PHP Extension: $ext"] = [
        'status' => extension_loaded($ext),
        'value' => extension_loaded($ext) ? 'Loaded' : 'Not Loaded',
        'required' => 'Required'
    ];
}

// Check Directories
$required_dirs = [
    'config' => __DIR__ . '/config',
    'src' => __DIR__ . '/src',
    'public' => __DIR__ . '/public',
    'uploads' => __DIR__ . '/uploads',
    'assets' => __DIR__ . '/assets'
];

foreach ($required_dirs as $name => $path) {
    $checks["Directory: $name"] = [
        'status' => is_dir($path),
        'value' => is_dir($path) ? 'Exists' : 'Missing',
        'required' => 'Required'
    ];
}

// Check Files
$required_files = [
    'config/config.php' => __DIR__ . '/config/config.php',
    'config/database.php' => __DIR__ . '/config/database.php',
    'public/login.php' => __DIR__ . '/public/login.php'
];

foreach ($required_files as $name => $path) {
    $checks["File: $name"] = [
        'status' => file_exists($path),
        'value' => file_exists($path) ? 'Exists' : 'Missing',
        'required' => 'Required'
    ];
}

// Check Writable Directories
$writable_dirs = [
    'uploads' => __DIR__ . '/uploads'
];

foreach ($writable_dirs as $name => $path) {
    $checks["Writable: $name"] = [
        'status' => is_writable($path),
        'value' => is_writable($path) ? 'Writable' : 'Not Writable',
        'required' => 'Required'
    ];
}

// Overall check
foreach ($checks as $check) {
    if (!$check['status']) {
        $all_good = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Verification - The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        header h1 {
            margin-bottom: 5px;
        }
        
        .status {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .status.good {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }
        
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        th {
            background: #f9f9f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .status-icon.yes {
            background: #28a745;
            color: white;
        }
        
        .status-icon.no {
            background: #dc3545;
            color: white;
        }
        
        .next-steps {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .next-steps h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .next-steps ol {
            margin-left: 20px;
        }
        
        .next-steps li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .next-steps a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .next-steps a:hover {
            text-decoration: underline;
        }
        
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>System Verification</h1>
            <p>The Steeper Climb - Online Course Platform</p>
        </header>
        
        <?php if ($all_good): ?>
            <div class="status good">
                ✓ All system requirements are met! You're ready to proceed.
            </div>
        <?php else: ?>
            <div class="status warning">
                ⚠ Some system requirements are not met. Please fix the issues below.
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Value</th>
                    <th>Required</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $name => $check): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td>
                            <span class="status-icon <?php echo $check['status'] ? 'yes' : 'no'; ?>">
                                <?php echo $check['status'] ? '✓' : '✕'; ?>
                            </span>
                            <?php echo $check['status'] ? 'OK' : 'ISSUE'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($check['value']); ?></td>
                        <td><?php echo htmlspecialchars($check['required']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="next-steps">
            <h2>Next Steps</h2>
            
            <?php if ($all_good): ?>
                <ol>
                    <li>
                        <strong>Run Database Setup</strong><br>
                        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/setup/install.php">
                            Click here to create the database and tables
                        </a>
                    </li>
                    <li>
                        <strong>Create Admin Account</strong><br>
                        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/setup/create-admin.php">
                            Click here to create your admin account
                        </a>
                    </li>
                    <li>
                        <strong>Login to Platform</strong><br>
                        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/public/login.php">
                            Click here to login to your admin dashboard
                        </a>
                    </li>
                </ol>
                
                <div class="button-group">
                    <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/setup/install.php" class="btn">
                        Start Database Setup →
                    </a>
                </div>
            <?php else: ?>
                <p style="color: #d32f2f; margin-bottom: 15px;">
                    <strong>Please fix the issues above before proceeding.</strong>
                </p>
                
                <h3 style="margin-top: 20px; color: #667eea;">Common Solutions:</h3>
                <ul style="margin-left: 20px;">
                    <li>Ensure XAMPP is running (Apache and MySQL)</li>
                    <li>Check that PHP extensions are enabled in php.ini</li>
                    <li>Verify directory permissions (use chmod 755)</li>
                    <li>Restart Apache after making changes</li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
