<?php
/**
 * Student Course Learning Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Models/ProgressModel.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_STUDENT);

$courseModel = new CourseModel($pdo);
$progressModel = new ProgressModel($pdo);
$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();

// Get course ID
$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

// Get course with structure
$course = $courseModel->getWithStructure($course_id);
if (!$course) {
    header('Location: courses.php?error=Course not found');
    exit;
}

// Verify course is assigned to student's organization
$stmt = $pdo->prepare(
    "SELECT oc.id FROM organization_courses oc
     INNER JOIN users u ON u.id = ?
     WHERE oc.course_id = ? AND oc.organization_id = u.organization_id"
);
$stmt->execute([$user['id'], $course_id]);
if (!$stmt->fetch()) {
    header('Location: courses.php?error=You do not have access to this course');
    exit;
}

// Get or create progress
$progress = $progressModel->getOrCreate($user['id'], $course_id);

// Handle section completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_section') {
    $section_id = (int)($_POST['section_id'] ?? 0);
    
    if ($section_id) {
        try {
            $progressModel->completeSection($user['id'], $section_id);
            $new_percentage = $progressModel->updateProgress($user['id'], $course_id);
            
            // Check if course is completed and issue certificate
            if ($new_percentage === 100) {
                $certModel = new CertificateModel($pdo);
                $cert_id = $certModel->issueCertificate($user['id'], $course_id, 100);
                
                if ($cert_id) {
                    header('Location: course.php?id=' . $course_id . '&message=Course completed! Certificate issued.');
                    exit;
                }
            }
            
            header('Location: course.php?id=' . $course_id . '&message=Section marked as completed');
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get total sections for progress calculation
$total_sections = $courseModel->getTotalSections($course_id);

// Get completed sections
$stmt = $pdo->prepare(
    "SELECT COUNT(*) as completed FROM section_completion
     WHERE student_id = ? AND section_id IN (
        SELECT s.id FROM sections s
        INNER JOIN chapters c ON s.chapter_id = c.id
        WHERE c.course_id = ?
     )"
);
$stmt->execute([$user['id'], $course_id]);
$completed_count = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .course-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: 100vh;
            gap: 20px;
            padding: 20px;
        }
        
        .sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar-header h3 {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar div {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: <?php echo ($total_sections > 0 ? ($completed_count / $total_sections) * 100 : 0); ?>%;
            transition: width 0.3s;
        }
        
        .progress-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .chapters-list {
            list-style: none;
        }
        
        .chapter {
            margin-bottom: 15px;
        }
        
        .chapter-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
            cursor: pointer;
            user-select: none;
        }
        
        .sections-list {
            list-style: none;
            padding-left: 0;
            display: none;
        }
        
        .sections-list.active {
            display: block;
        }
        
        .section-item {
            padding: 8px 10px;
            margin-bottom: 4px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9f9f9;
        }
        
        .section-item:hover {
            background: #eee;
        }
        
        .section-item.completed {
            color: #28a745;
            font-weight: 600;
        }
        
        .main-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .course-header {
            margin-bottom: 30px;
        }
        
        .course-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .course-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .section-content {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .video-container {
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
            aspect-ratio: 16/9;
        }
        
        .video-container video {
            width: 100%;
            height: 100%;
        }
        
        .complete-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s;
            margin-top: 20px;
        }
        
        .complete-button:hover {
            transform: translateY(-2px);
        }
        
        .complete-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .course-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                top: auto;
            }
        }
    </style>
</head>
<body>
    <div class="course-container">
        <!-- Sidebar - Course Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Course Progress</h3>
                <p><?php echo htmlspecialchars($course['title']); ?></p>
                <div class="progress-bar">
                    <div></div>
                </div>
                <p class="progress-text"><?php echo $completed_count; ?>/<?php echo $total_sections; ?> Sections Completed</p>
            </div>
            
            <ul class="chapters-list">
                <?php foreach ($course['chapters'] as $chapter): ?>
                    <li class="chapter">
                        <div class="chapter-title" onclick="toggleSections(this)">
                            📖 <?php echo htmlspecialchars($chapter['title']); ?>
                        </div>
                        <ul class="sections-list active">
                            <?php 
                                // Check which sections are completed
                                $stmt = $pdo->prepare(
                                    "SELECT section_id FROM section_completion 
                                     WHERE student_id = ?"
                                );
                                $stmt->execute([$user['id']]);
                                $completed_sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            ?>
                            <?php foreach ($chapter['sections'] as $section): ?>
                                <li class="section-item <?php echo in_array($section['id'], $completed_sections) ? 'completed' : ''; ?>">
                                    <?php echo in_array($section['id'], $completed_sections) ? '✓' : '○'; ?>
                                    <?php echo htmlspecialchars($section['title']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <a href="courses.php" class="back-link" style="display: block; margin-top: 30px; text-align: center;">← Back to Courses</a>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <a href="courses.php" class="back-link">← Back to My Courses</a>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            
            <div class="course-header">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <div class="course-meta">
                    <span>📚 <?php echo count($course['chapters']); ?> Chapters</span>
                    <span>📊 Difficulty: <?php echo ucfirst($course['difficulty_level']); ?></span>
                    <?php if ($course['duration_hours']): ?>
                        <span>⏱️ <?php echo $course['duration_hours']; ?> hours</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($course['description']): ?>
                <div class="section-content">
                    <h3>About This Course</h3>
                    <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="section-content">
                <h3>Start Learning</h3>
                <p>Select a chapter from the sidebar to begin. Complete each section and mark it as done to track your progress.</p>
                <p style="color: #999; font-size: 14px; margin-top: 10px;">
                    Your progress is automatically saved as you complete sections. Complete all sections to earn your certificate!
                </p>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSections(element) {
            const sectionsList = element.nextElementSibling;
            sectionsList.classList.toggle('active');
        }
    </script>
</body>
</html>
