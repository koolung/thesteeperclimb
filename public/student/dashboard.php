<?php
/**
 * Student Dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Models/ProgressModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_STUDENT);

$courseModel = new CourseModel($pdo);
$progressModel = new ProgressModel($pdo);
$user = Auth::getCurrentUser();

// Get student's courses
$student_courses = $progressModel->getStudentCourses($user['id']);

// Get progress summary
$summary = $progressModel->getProgressSummary($user['id']);

// Get certificates
$stmt = $pdo->prepare(
    "SELECT c.*, co.title as course_title 
     FROM certificates c
     INNER JOIN courses co ON c.course_id = co.id
     WHERE c.student_id = ?
     ORDER BY c.issued_date DESC"
);
$stmt->execute([$user['id']]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Student Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li class="active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php">My Courses</a></li>
                    <li><a href="certificates.php">Certificates</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1>Student Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            </header>
            
            <!-- Statistics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">📚</div>
                    <div class="stat-content">
                        <h3><?php echo $summary['total_courses'] ?? 0; ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #764ba2;">✓</div>
                    <div class="stat-content">
                        <h3><?php echo $summary['completed_courses'] ?? 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">📊</div>
                    <div class="stat-content">
                        <h3><?php echo round($summary['average_progress'] ?? 0); ?>%</h3>
                        <p>Average Progress</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4facfe;">🎓</div>
                    <div class="stat-content">
                        <h3><?php echo count($certificates); ?></h3>
                        <p>Certificates Earned</p>
                    </div>
                </div>
            </section>
            
            <!-- In Progress Courses -->
            <section class="content-section">
                <h2>Your Courses</h2>
                <?php if (empty($student_courses)): ?>
                    <p style="color: #999;">No courses available. Please contact your organization administrator.</p>
                <?php else: ?>
                    <div class="feature-grid">
                        <?php foreach ($student_courses as $course): ?>
                            <div class="feature-item" style="cursor: pointer;" onclick="window.location.href='course.php?id=<?php echo $course['course_id']; ?>'">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <div style="margin: 10px 0;">
                                    <small style="color: #999;">Progress</small>
                                    <div style="background: #eee; height: 8px; border-radius: 4px; margin-top: 5px; overflow: hidden;">
                                        <div style="background: #667eea; height: 100%; width: <?php echo $course['progress_percentage']; ?>%;">  </div>
                                    </div>
                                    <small style="color: #999; display: block; margin-top: 5px;"><?php echo $course['progress_percentage']; ?>% Complete</small>
                                </div>
                                <small style="color: #999; display: block; margin-top: 10px;">
                                    Status: <strong><?php echo ucfirst($course['course_status']); ?></strong>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Recent Certificates -->
            <?php if (!empty($certificates)): ?>
                <section class="content-section">
                    <h2>Your Certificates</h2>
                    <div class="feature-grid">
                        <?php foreach ($certificates as $cert): ?>
                            <div class="feature-item">
                                <h3>🎓 <?php echo htmlspecialchars($cert['course_title']); ?></h3>
                                <p>Certificate #: <?php echo htmlspecialchars($cert['certificate_number']); ?></p>
                                <small style="color: #999;">
                                    Issued: <?php echo Utils::formatDate($cert['issued_date']); ?><br>
                                    Score: <?php echo $cert['score_percentage']; ?>%
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
