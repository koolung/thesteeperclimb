<?php
/**
 * Organization Students Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ORGANIZATION);

$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        
        if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error = 'All fields are required';
        } else {
            try {
                Auth::register($email, $password, $first_name, $last_name, ROLE_STUDENT, $user['id']);
                
                Utils::auditLog($pdo, $user['id'], 'CREATE_STUDENT', 'user', null, "Created student: $email");
                
                header('Location: students.php?message=Student added successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $status = $_POST['status'] ?? STATUS_ACTIVE;
        
        // Verify student exists and has role 'student'
        $student = $userModel->findById($id);
        if (!$student || $student['role'] !== ROLE_STUDENT) {
            header('Location: students.php?error=Student not found');
            exit;
        }
        
        if (empty($first_name) || empty($last_name)) {
            $error = 'First name and last name are required';
        } else {
            try {
                $userModel->update($id, [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'status' => $status
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'UPDATE_STUDENT', 'user', $id, "Updated student");
                
                header('Location: students.php?message=Student updated successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $student = $userModel->findById($id);
        
        // Verify student exists and has role 'student'
        if (!$student || $student['role'] !== ROLE_STUDENT) {
            header('Location: students.php?error=Student not found');
            exit;
        }
        
        try {
            $userModel->delete($id);
            Utils::auditLog($pdo, $user['id'], 'DELETE_STUDENT', 'user', $id, "Deleted student: " . $student['email']);
            
            header('Location: students.php?message=Student deleted successfully');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get message from query parameter
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $total = $userModel->countStudentsByOrganization($user['id']);
    $pagination = Utils::getPagination($page, $total);
    
    $students = $userModel->findStudentsByOrganization($user['id'], $pagination['limit'], $pagination['offset']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Organization Portal</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Organization Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="active"><a href="students.php">Students</a></li>
                    <li><a href="courses.php">My Courses</a></li>
                    <li><a href="reports.php">Reports</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>Students Management</h1>
                <a href="students.php?action=add" class="btn btn-primary">+ Add New Student</a>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <section class="content-section">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($student['status']); ?>"><?php echo ucfirst($student['status']); ?></span></td>
                                        <td><?php echo Utils::formatDate($student['created_at']); ?></td>
                                        <td>
                                            <a href="students.php?action=edit&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            
            <?php elseif ($action === 'add'): ?>
                <section class="content-section">
                    <h2>Add New Student</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required>
                            <small>At least 8 characters with uppercase, number, and special character</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Add Student</button>
                            <a href="students.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            
            <?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
                <?php
                    $student = $userModel->findById((int)$_GET['id']);
                    if (!$student || $student['role'] !== ROLE_STUDENT) {
                        header('Location: students.php?error=Student not found');
                        exit;
                    }
                ?>
                <section class="content-section">
                    <h2>Edit Student</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Student</button>
                            <a href="students.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
