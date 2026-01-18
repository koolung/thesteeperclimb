<?php
/**
 * Student Courses List
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

$search = trim($_GET['search'] ?? '');
$difficulty_filter = $_GET['difficulty'] ?? '';
$progress_filter = $_GET['progress'] ?? '';

// Get student's courses
$all_courses = $progressModel->getStudentCourses($user['id']);

// Filter courses based on search and filters
$student_courses = array_filter($all_courses, function($course) use ($search, $difficulty_filter, $progress_filter) {
    if ($search) {
        $search_lower = strtolower($search);
        if (stripos($course['title'], $search_lower) === false && 
            stripos($course['description'] ?? '', $search_lower) === false) {
            return false;
        }
    }
    
    if ($difficulty_filter && $course['difficulty_level'] !== $difficulty_filter) {
        return false;
    }
    
    if ($progress_filter) {
        if ($progress_filter === 'not_started' && $course['progress_percentage'] != 0) {
            return false;
        } elseif ($progress_filter === 'in_progress' && ($course['progress_percentage'] == 0 || $course['progress_percentage'] == 100)) {
            return false;
        } elseif ($progress_filter === 'completed' && $course['progress_percentage'] != 100) {
            return false;
        }
    }
    
    return true;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Student Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="active"><a href="courses.php">My Courses</a></li>
                    <li><a href="certificates.php">Certificates</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>My Courses</h1>
            </header>
            
            <?php if (empty($student_courses)): ?>
                <section class="content-section">
                    <p style="color: #999; text-align: center; padding: 40px;">
                        No courses assigned yet. Please contact your organization administrator.
                    </p>
                </section>
            <?php else: ?>
                <section class="content-section">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($student_courses as $course): ?>
                            <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($course['title']); ?></h3>
                                
                                <div style="margin: 15px 0;">
                                    <small style="color: #999;">Progress</small>
                                    <div style="background: #eee; height: 10px; border-radius: 5px; margin-top: 8px; overflow: hidden;">
                                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: <?php echo $course['progress_percentage']; ?>%;"></div>
                                    </div>
                                    <small style="color: #999; display: block; margin-top: 8px;"><?php echo $course['progress_percentage']; ?>% Complete</small>
                                </div>
                                
                                <p style="color: #666; font-size: 13px; margin: 10px 0;">
                                    Status: <strong><?php echo ucfirst($course['course_status']); ?></strong>
                                </p>
                                
                                <a href="course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 15px;">
                                    <?php echo $course['progress_percentage'] == 0 ? 'Start Course' : 'Continue'; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
