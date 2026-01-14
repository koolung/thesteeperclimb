<?php
/**
 * Organization - My Courses
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ORGANIZATION);

$courseModel = new CourseModel($pdo);
$user = Auth::getCurrentUser();

// Get organization's courses (show all statuses)
$stmt = $pdo->prepare(
    "SELECT c.* FROM courses c
     INNER JOIN organization_courses oc ON c.id = oc.course_id
     WHERE oc.organization_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Organization Portal</title>
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
                    <li><a href="students.php">Students</a></li>
                    <li class="active"><a href="courses.php">My Courses</a></li>
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
                <h1>My Courses</h1>
                <p>Courses assigned to your organization</p>
            </header>
            
            <?php if (empty($courses)): ?>
                <section class="content-section">
                    <p style="color: #999; text-align: center; padding: 40px;">
                        No courses assigned yet. Please contact your administrator to assign courses to your organization.
                    </p>
                </section>
            <?php else: ?>
                <section class="content-section">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Instructor</th>
                                    <th>Difficulty</th>
                                    <th>Duration</th>
                                    <th>Students Enrolled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <?php
                                        // Get count of students enrolled in this course
                                        $stmt = $pdo->prepare(
                                            "SELECT COUNT(DISTINCT sp.student_id) as count 
                                             FROM student_progress sp
                                             WHERE sp.course_id = ?"
                                        );
                                        $stmt->execute([$course['id']]);
                                        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo ucfirst($course['difficulty_level']); ?></td>
                                        <td><?php echo $course['duration_hours'] ? $course['duration_hours'] . ' hrs' : 'N/A'; ?></td>
                                        <td><?php echo $enrollment; ?></td>
                                        <td>
                                            <a href="course-editor.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
