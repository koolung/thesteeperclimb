<?php
/**
 * Admin Users Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? ROLE_STUDENT;
        
        if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error = 'All fields are required';
        } else {
            try {
                Auth::register($email, $password, $first_name, $last_name, $role);
                
                Utils::auditLog($pdo, $user['id'], 'CREATE', 'user', null, "Created user: $email");
                
                header('Location: users.php?message=User created successfully');
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
        
        if (empty($first_name) || empty($last_name)) {
            $error = 'First name and last name are required';
        } else {
            try {
                $userModel->update($id, [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'status' => $status
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'UPDATE', 'user', $id, "Updated user");
                
                header('Location: users.php?message=User updated successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $target_user = $userModel->findById($id);
        
        if ($target_user && $target_user['id'] !== $user['id']) {
            try {
                $userModel->delete($id);
                Utils::auditLog($pdo, $user['id'], 'DELETE', 'user', $id, "Deleted user: " . $target_user['email']);
                
                header('Location: users.php?message=User deleted successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'Cannot delete this user';
        }
    }
}

// Get message from query parameter
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $role_filter = $_GET['role'] ?? '';
    
    $total = $userModel->count();
    $pagination = Utils::getPagination($page, $total);
    
    $limit = (int)$pagination['limit'];
    $offset = (int)$pagination['offset'];
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="organizations.php">Organizations</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li class="active"><a href="users.php">Users</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>Users Management</h1>
                <a href="users.php?action=create" class="btn btn-primary">+ Add New User</a>
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
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($u['role']); ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                        <td><span class="badge badge-<?php echo strtolower($u['status']); ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                        <td><?php echo Utils::formatDate($u['created_at']); ?></td>
                                        <td>
                                            <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <?php if ($u['id'] !== $user['id']): ?>
                                                <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            
            <?php elseif ($action === 'create'): ?>
                <section class="content-section">
                    <h2>Create New User</h2>
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
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="<?php echo ROLE_ADMIN; ?>">Admin</option>
                                <option value="<?php echo ROLE_STUDENT; ?>" selected>Student</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            
            <?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
                <?php
                    $target_user = $userModel->findById((int)$_GET['id']);
                    if (!$target_user) {
                        header('Location: users.php?error=User not found');
                        exit;
                    }
                ?>
                <section class="content-section">
                    <h2>Edit User</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($target_user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($target_user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($target_user['email']); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?php echo ucfirst($target_user['role']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $target_user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $target_user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $target_user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
