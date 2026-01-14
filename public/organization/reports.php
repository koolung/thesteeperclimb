<?php
/**
 * Organization - Reports
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Models/CertificateModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ORGANIZATION);

$userModel = new UserModel($pdo);
$certModel = new CertificateModel($pdo);
$user = Auth::getCurrentUser();

// Get organization statistics
$student_count = $userModel->countStudentsByOrganization($user['organization_id']);
$cert_count = $certModel->countByOrganization($user['organization_id']);

// Get student progress
$stmt = $pdo->prepare(
    "SELECT u.id, u.first_name, u.last_name, sp.course_id, c.title, sp.progress_percentage, sp.status
     FROM users u
     LEFT JOIN student_progress sp ON u.id = sp.student_id
     LEFT JOIN courses c ON sp.course_id = c.id
     WHERE u.organization_id = ? AND u.role = ?
     ORDER BY u.first_name, u.last_name, c.title"
);
$stmt->execute([$user['organization_id'], ROLE_STUDENT]);
$progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize progress by student
$student_progress = [];
foreach ($progress_data as $row) {
    $student_id = $row['id'];
    if (!isset($student_progress[$student_id])) {
        $student_progress[$student_id] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'courses' => []
        ];
    }
    if ($row['course_id']) {
        $student_progress[$student_id]['courses'][] = [
            'title' => $row['title'],
            'progress' => $row['progress_percentage'],
            'status' => $row['status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Organization Portal</title>
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
                    <li><a href="courses.php">My Courses</a></li>
                    <li class="active"><a href="reports.php">Reports</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>Reports & Analytics</h1>
            </header>
            
            <!-- Summary Stats -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $student_count; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #764ba2;">🎓</div>
                    <div class="stat-content">
                        <h3><?php echo $cert_count; ?></h3>
                        <p>Certificates Issued</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $student_count > 0 ? round(($cert_count / $student_count) * 100) : 0; ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>
            </section>
            
            <!-- Student Progress Details -->
            <section class="content-section">
                <h2>Student Progress Details</h2>
                
                <?php if (empty($student_progress)): ?>
                    <p style="color: #999;">No student data available.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Courses Enrolled</th>
                                    <th>Average Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_progress as $student): ?>
                                    <?php
                                        $total_progress = 0;
                                        if (!empty($student['courses'])) {
                                            foreach ($student['courses'] as $course) {
                                                $total_progress += $course['progress'];
                                            }
                                            $avg_progress = round($total_progress / count($student['courses']));
                                        } else {
                                            $avg_progress = 0;
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                        <td><?php echo count($student['courses']); ?></td>
                                        <td>
                                            <div style="background: #eee; height: 20px; border-radius: 3px; overflow: hidden; width: 150px;">
                                                <div style="background: #667eea; height: 100%; width: <?php echo $avg_progress; ?>%; transition: width 0.3s;"></div>
                                            </div>
                                            <small style="color: #999;"><?php echo $avg_progress; ?>%</small>
                                        </td>
                                        <td><?php echo $avg_progress === 100 ? '<span class="badge badge-active">Completed</span>' : '<span class="badge badge-inactive">In Progress</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
