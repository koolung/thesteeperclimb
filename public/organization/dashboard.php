<?php
/**
 * Organization Dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Models/OrganizationModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ORGANIZATION);

$userModel = new UserModel($pdo);
$courseModel = new CourseModel($pdo);
$orgModel = new OrganizationModel($pdo);
$user = Auth::getCurrentUser();

// Get organization details
$organization = $orgModel->getWithStats($user['organization_id']);
$student_count = $userModel->countStudentsByOrganization($user['organization_id']);
$course_count = $courseModel->countForOrganization($user['organization_id']);

// Get latest courses
$stmt = $pdo->prepare(
    "SELECT c.* FROM courses c
     INNER JOIN organization_courses oc ON c.id = oc.course_id
     WHERE oc.organization_id = ? AND c.status = ?
     ORDER BY c.created_at DESC LIMIT 5"
);
$stmt->execute([$user['organization_id'], COURSE_PUBLISHED]);
$recent_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Organization Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li class="active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="students.php">Students</a></li>
                    <li><a href="courses.php">My Courses</a></li>
                    <li><a href="reports.php">Reports</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <small><?php echo htmlspecialchars($organization['name'] ?? ''); ?></small>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Organization Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($organization['name'] ?? 'Organization'); ?>!</p>
            </header>
            
            <!-- Statistics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $student_count; ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #764ba2;">📚</div>
                    <div class="stat-content">
                        <h3><?php echo $course_count; ?></h3>
                        <p>Assigned Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">📊</div>
                    <div class="stat-content">
                        <h3>?</h3>
                        <p>Avg Student Progress</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4facfe;">🎓</div>
                    <div class="stat-content">
                        <h3>?</h3>
                        <p>Certificates Issued</p>
                    </div>
                </div>
            </section>
            
            <!-- Quick Actions -->
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="button-group">
                    <a href="students.php?action=add" class="btn btn-primary">+ Add Student</a>
                    <a href="students.php" class="btn btn-secondary">Manage Students</a>
                    <a href="courses.php" class="btn btn-secondary">View Courses</a>
                </div>
            </section>
            
            <!-- Recent Courses -->
            <section class="content-section">
                <h2>Your Assigned Courses</h2>
                <?php if (empty($recent_courses)): ?>
                    <p style="color: #999;">No courses assigned yet. Contact your administrator to assign courses.</p>
                <?php else: ?>
                    <div class="feature-grid">
                        <?php foreach ($recent_courses as $course): ?>
                            <div class="feature-item">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...</p>
                                <small style="color: #999;">
                                    Instructor: <?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?><br>
                                    Difficulty: <?php echo ucfirst($course['difficulty_level']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
