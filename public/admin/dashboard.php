<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Models/OrganizationModel.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$userModel = new UserModel($pdo);
$orgModel = new OrganizationModel($pdo);
$courseModel = new CourseModel($pdo);

// Get statistics
$total_organizations = $orgModel->countActive();
$total_users = $userModel->count();
$total_courses = $courseModel->count();

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
$stmt->execute([ROLE_STUDENT]);
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li class="active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="organizations.php">Organizations</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="users.php">Users</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <small><?php echo htmlspecialchars($user['email']); ?></small>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            </header>
            
            <!-- Statistics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $total_organizations; ?></h3>
                        <p>Active Organizations</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #764ba2;">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">📚</div>
                    <div class="stat-content">
                        <h3><?php echo $total_courses; ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4facfe;">🔧</div>
                    <div class="stat-content">
                        <h3><?php echo $total_users - $total_students; ?></h3>
                        <p>Admin & Org Accounts</p>
                    </div>
                </div>
            </section>
            
            <!-- Quick Actions -->
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="button-group">
                    <a href="organizations.php?action=create" class="btn btn-primary">+ Add Organization</a>
                    <a href="courses.php?action=create" class="btn btn-primary">+ Add Course</a>
                    <a href="users.php?action=create" class="btn btn-secondary">+ Add User</a>
                </div>
            </section>
            
            <!-- Recent Activity -->
            <section class="recent-activity">
                <h2>System Features</h2>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h3>Organization Management</h3>
                        <p>Create, edit, and manage client organizations with full control over their student accounts and course assignments.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Course Management</h3>
                        <p>Create comprehensive courses with chapters, sections, videos, quizzes, and assignments. Manage content at every level.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Student Management</h3>
                        <p>Oversee all student accounts across organizations, monitor progress, and manage certificates.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Access Control</h3>
                        <p>Assign courses to specific organizations and monitor who has access to what content.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
