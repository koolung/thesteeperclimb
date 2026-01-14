<?php
/**
 * Organization Course Editor
 * View course details and student progress
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ORGANIZATION);

$courseModel = new CourseModel($pdo);
$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();

// Get course ID from URL
$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header('Location: courses.php?error=Course not found');
    exit;
}

// Get course details
$course = $courseModel->findById($course_id);
if (!$course) {
    header('Location: courses.php?error=Course not found');
    exit;
}

// Verify course is assigned to this organization
$stmt = $pdo->prepare(
    "SELECT id FROM organization_courses 
     WHERE course_id = ? AND organization_id = ?"
);
$stmt->execute([$course_id, $user['id']]);
if (!$stmt->fetch()) {
    header('Location: courses.php?error=You do not have access to this course');
    exit;
}

// Get all students and their progress for this course
$stmt = $pdo->prepare(
    "SELECT DISTINCT u.id, u.email, u.first_name, u.last_name,
            sp.progress_percentage, sp.status, sp.completed_at,
            COUNT(DISTINCT qa.id) as quiz_attempts
     FROM users u
     LEFT JOIN student_progress sp ON u.id = sp.student_id AND sp.course_id = ?
     LEFT JOIN student_answers qa ON u.id = qa.student_id 
        AND qa.question_id IN (SELECT id FROM questions WHERE section_id IN (
            SELECT id FROM sections WHERE chapter_id IN (
                SELECT id FROM chapters WHERE course_id = ?
            )
        ))
     WHERE u.role = ?
     GROUP BY u.id
     ORDER BY u.first_name, u.last_name"
);
$stmt->execute([$course_id, $course_id, ROLE_STUDENT]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course structure for progress tracking
$course_with_structure = $courseModel->getWithStructure($course_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Organization Portal</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <style>
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .course-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .course-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .meta-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .meta-item strong {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .meta-item span {
            font-size: 16px;
            display: block;
        }

        .progress-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 8px 0;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-in-progress {
            background: #cfe9ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-not-started {
            background: #f8d7da;
            color: #721c24;
        }

        .student-row {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .student-row:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .student-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .student-email {
            font-size: 13px;
            color: #999;
            margin-top: 3px;
        }

        .progress-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
        }

        .progress-item {
            font-size: 13px;
        }

        .progress-item strong {
            display: block;
            color: #666;
            margin-bottom: 4px;
        }

        .progress-item span {
            display: block;
            color: #333;
            font-size: 14px;
        }

        .students-grid {
            margin-top: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>
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
            <!-- Course Header -->
            <div class="course-header">
                <a href="courses.php" style="color: white; text-decoration: none; font-size: 14px;">← Back to Courses</a>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                
                <div class="course-meta">
                    <div class="meta-item">
                        <strong>Instructor</strong>
                        <span><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <strong>Difficulty</strong>
                        <span><?php echo ucfirst($course['difficulty_level'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <strong>Duration</strong>
                        <span><?php echo $course['duration_hours'] ? $course['duration_hours'] . ' hours' : 'N/A'; ?></span>
                    </div>
                    <div class="meta-item">
                        <strong>Total Students</strong>
                        <span id="total-students"><?php echo count($students); ?></span>
                    </div>
                </div>
            </div>

            <!-- Student Progress -->
            <section class="content-section">
                <h2>Student Progress</h2>
                
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <p>No students enrolled in this course yet.</p>
                        <small>Students will appear here once they access this course.</small>
                    </div>
                <?php else: ?>
                    <div class="students-grid">
                        <?php foreach ($students as $student): 
                            $progress = $student['progress_percentage'] ?? 0;
                            $status = $student['status'] ?? 'not_started';
                            $statusLabel = match($status) {
                                'completed' => 'Completed',
                                'in_progress' => 'In Progress',
                                default => 'Not Started'
                            };
                            $statusClass = match($status) {
                                'completed' => 'status-completed',
                                'in_progress' => 'status-in-progress',
                                default => 'status-not-started'
                            };
                        ?>
                            <div class="student-row">
                                <div class="student-header">
                                    <div>
                                        <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                        <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                    </div>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </div>

                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;">
                                        <?php echo $progress > 5 ? $progress . '%' : ''; ?>
                                    </div>
                                </div>

                                <div class="progress-info">
                                    <div class="progress-item">
                                        <strong>Progress</strong>
                                        <span><?php echo $progress; ?>%</span>
                                    </div>
                                    <div class="progress-item">
                                        <strong>Quiz Attempts</strong>
                                        <span><?php echo $student['quiz_attempts'] ?? 0; ?></span>
                                    </div>
                                    <div class="progress-item">
                                        <strong>Completed Date</strong>
                                        <span><?php echo $student['completed_at'] ? Utils::formatDate($student['completed_at']) : 'Not completed'; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Summary Stats -->
                    <?php 
                        $completed = count(array_filter($students, fn($s) => $s['status'] === 'completed'));
                        $in_progress = count(array_filter($students, fn($s) => $s['status'] === 'in_progress'));
                        $not_started = count(array_filter($students, fn($s) => $s['status'] !== 'completed' && $s['status'] !== 'in_progress'));
                        $avg_progress = count($students) > 0 ? round(array_sum(array_column($students, 'progress_percentage')) / count($students)) : 0;
                    ?>
                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e9ecef;">
                        <h3>Summary Statistics</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                            <div style="background: #d4edda; padding: 20px; border-radius: 8px;">
                                <div style="font-weight: 600; color: #155724;">Completed</div>
                                <div style="font-size: 28px; font-weight: bold; color: #155724; margin-top: 10px;"><?php echo $completed; ?></div>
                                <small style="color: #155724;"><?php echo $completed > 0 ? round($completed / count($students) * 100) : 0; ?>% of students</small>
                            </div>
                            <div style="background: #cfe9ff; padding: 20px; border-radius: 8px;">
                                <div style="font-weight: 600; color: #004085;">In Progress</div>
                                <div style="font-size: 28px; font-weight: bold; color: #004085; margin-top: 10px;"><?php echo $in_progress; ?></div>
                                <small style="color: #004085;"><?php echo $in_progress > 0 ? round($in_progress / count($students) * 100) : 0; ?>% of students</small>
                            </div>
                            <div style="background: #f8d7da; padding: 20px; border-radius: 8px;">
                                <div style="font-weight: 600; color: #721c24;">Not Started</div>
                                <div style="font-size: 28px; font-weight: bold; color: #721c24; margin-top: 10px;"><?php echo $not_started; ?></div>
                                <small style="color: #721c24;"><?php echo $not_started > 0 ? round($not_started / count($students) * 100) : 0; ?>% of students</small>
                            </div>
                            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px;">
                                <div style="font-weight: 600; color: #004d99;">Average Progress</div>
                                <div style="font-size: 28px; font-weight: bold; color: #004d99; margin-top: 10px;"><?php echo $avg_progress; ?>%</div>
                                <small style="color: #004d99;">Across all students</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
