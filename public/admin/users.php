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
    }
}

// Handle delete action (moved outside POST block to handle GET requests)
if ($action === 'delete' && isset($_GET['id'])) {
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

// Get message from query parameter
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $role_filter = $_GET['role'] ?? '';
    $search = trim($_GET['search'] ?? '');
    
    // Build query with search and filters
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($role_filter) {
        $where[] = "role = ?";
        $params[] = $role_filter;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $pagination = Utils::getPagination($page, $total);
    $limit = (int)$pagination['limit'];
    $offset = (int)$pagination['offset'];
    
    // Get filtered and paginated users
    $sql = "SELECT * FROM users $whereClause ORDER BY role ASC, created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize users by role
    $admins = [];
    $organizations = [];
    $students = [];
    
    foreach ($users as $u) {
        if ($u['role'] === ROLE_ADMIN) {
            $admins[] = $u;
        } elseif ($u['role'] === ROLE_ORGANIZATION) {
            $organizations[] = $u;
        } else {
            $students[] = $u;
        }
    }
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
                    <!-- Search and Filter Bar -->
                    <div style="margin-bottom: 20px; display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                        <div>
                            <form method="GET" style="display: flex; gap: 10px;">
                                <input type="hidden" name="action" value="list">
                                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Search</button>
                                <?php if ($search || $role_filter): ?>
                                    <a href="users.php" class="btn btn-secondary" style="padding: 10px 20px;">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <form method="GET" style="display: flex; gap: 5px;">
                            <input type="hidden" name="action" value="list">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <select name="role" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <option value="">All Roles</option>
                                <option value="<?php echo ROLE_ADMIN; ?>" <?php echo $role_filter === ROLE_ADMIN ? 'selected' : ''; ?>>Admins</option>
                                <option value="<?php echo ROLE_ORGANIZATION; ?>" <?php echo $role_filter === ROLE_ORGANIZATION ? 'selected' : ''; ?>>Organizations</option>
                                <option value="<?php echo ROLE_STUDENT; ?>" <?php echo $role_filter === ROLE_STUDENT ? 'selected' : ''; ?>>Students</option>
                            </select>
                        </form>
                    </div>
                    
                    <!-- Search Results Info -->
                    <?php if ($search || $role_filter): ?>
                        <div style="margin-bottom: 20px; padding: 10px; background: #e8f4f8; border-radius: 5px; font-size: 13px; color: #0066cc;">
                            <?php 
                            $filter_text = [];
                            if ($search) $filter_text[] = "name/email containing '" . htmlspecialchars($search) . "'";
                            if ($role_filter) $filter_text[] = "role = " . ucfirst($role_filter);
                            echo "Showing users matching: " . implode(" and ", $filter_text) . " (" . $total . " results)";
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Admins Section -->
                    <?php if (!$role_filter || $role_filter === ROLE_ADMIN): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea; color: #667eea;">
                                👨‍💼 Admins (<?php echo count($admins); ?>)
                            </h3>
                            <?php if (empty($admins)): ?>
                                <p style="color: #999; padding: 20px; text-align: center;">No admin users found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admins as $u): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
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
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Organizations Section -->
                    <?php if (!$role_filter || $role_filter === ROLE_ORGANIZATION): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; color: #f093fb;">
                                🏢 Organizations (<?php echo count($organizations); ?>)
                            </h3>
                            <?php if (empty($organizations)): ?>
                                <p style="color: #999; padding: 20px; text-align: center;">No organization users found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Organization</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($organizations as $u): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['organization_name'] ?? '-'); ?></td>
                                                    <td><span class="badge badge-<?php echo strtolower($u['status']); ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                                    <td><?php echo Utils::formatDate($u['created_at']); ?></td>
                                                    <td>
                                                        <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                        <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Students Section -->
                    <?php if (!$role_filter || $role_filter === ROLE_STUDENT): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #4CAF50; color: #4CAF50;">
                                👨‍🎓 Students (<?php echo count($students); ?>)
                            </h3>
                            <?php if (empty($students)): ?>
                                <p style="color: #999; padding: 20px; text-align: center;">No student users found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Organization</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $u): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                                    <td>
                                                        <?php 
                                                            if ($u['organization_id']) {
                                                                $org_stmt = $pdo->prepare("SELECT organization_name FROM users WHERE id = ?");
                                                                $org_stmt->execute([$u['organization_id']]);
                                                                $org = $org_stmt->fetch(PDO::FETCH_ASSOC);
                                                                echo htmlspecialchars($org['organization_name'] ?? 'Unknown');
                                                            } else {
                                                                echo '<span style="color: #999;">-</span>';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><span class="badge badge-<?php echo strtolower($u['status']); ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                                    <td><?php echo Utils::formatDate($u['created_at']); ?></td>
                                                    <td>
                                                        <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                        <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
