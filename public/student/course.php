<?php
/**
 * Student Course Learning Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Models/ProgressModel.php';
require_once __DIR__ . '/../../src/Models/CertificateModel.php';
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
     WHERE oc.course_id = ? AND oc.organization_id = u.organization_id
     AND u.organization_id IS NOT NULL"
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
            
            // Find and redirect to next section
            $stmt = $pdo->prepare(
                "SELECT s.*, c.id as chapter_id FROM sections s
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE c.course_id = ?
                 ORDER BY c.`order`, s.`order`"
            );
            $stmt->execute([$course_id]);
            $all_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $next_section_id = null;
            $found_current = false;
            
            foreach ($all_sections as $section) {
                if ($found_current) {
                    $next_section_id = $section['id'];
                    break;
                }
                if ($section['id'] == $section_id) {
                    $found_current = true;
                }
            }
            
            if ($next_section_id) {
                header('Location: course.php?id=' . $course_id . '&section_id=' . $next_section_id . '&message=Section marked as completed');
            } else {
                header('Location: course.php?id=' . $course_id . '&message=Section marked as completed');
            }
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_id']) && !isset($_POST['action'])) {
    $section_id = (int)($_POST['section_id'] ?? 0);
    
    if ($section_id) {
        try {
            // Get all questions for this section
            $stmt = $pdo->prepare("SELECT id, points FROM questions WHERE section_id = ? ORDER BY `order`");
            $stmt->execute([$section_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_points = 0;
            $earned_points = 0;
            
            // Process each question
            foreach ($questions as $question) {
                $question_id = $question['id'];
                $points = $question['points'];
                $total_points += $points;
                
                // Get the student's answer
                $answer_key = "question_" . $question_id;
                if (isset($_POST[$answer_key])) {
                    $student_answer = $_POST[$answer_key];
                    
                    // Get the question type and check answer
                    $stmt = $pdo->prepare("SELECT question_type FROM questions WHERE id = ?");
                    $stmt->execute([$question_id]);
                    $q_type = $stmt->fetch(PDO::FETCH_ASSOC)['question_type'];
                    
                    $is_correct = 0;
                    
                    if ($q_type === 'multiple_choice' || $q_type === 'true_false') {
                        // Check if selected option is correct
                        $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                        $stmt->execute([$student_answer]);
                        $option = $stmt->fetch(PDO::FETCH_ASSOC);
                        $is_correct = $option['is_correct'] ? 1 : 0;
                    } elseif ($q_type === 'short_answer') {
                        // Short answer: minimum 5 characters = correct
                        $is_correct = strlen($student_answer) >= 5 ? 1 : 0;
                    } elseif ($q_type === 'essay') {
                        // Essay: requires manual grading, mark as 0
                        $is_correct = 0;
                    }
                    
                    // Record the answer
                    $stmt = $pdo->prepare(
                        "INSERT INTO student_answers (student_id, question_id, answer_text, is_correct)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text), is_correct = VALUES(is_correct)"
                    );
                    $stmt->execute([$user['id'], $question_id, $student_answer, $is_correct]);
                    
                    if ($is_correct) {
                        $earned_points += $points;
                    }
                }
            }
            
            // Calculate percentage
            $score_percentage = $total_points > 0 ? round(($earned_points / $total_points) * 100) : 0;
            
            // Count how many questions were answered
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE section_id = ?");
            $stmt->execute([$section_id]);
            $total_questions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as count FROM student_answers 
                 WHERE student_id = ? AND question_id IN (SELECT id FROM questions WHERE section_id = ?)
                 AND answer_text IS NOT NULL AND answer_text != ''"
            );
            $stmt->execute([$user['id'], $section_id]);
            $answered_questions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Mark section as complete if all questions are answered
            if ($answered_questions === $total_questions && $total_questions > 0) {
                $progressModel->completeSection($user['id'], $section_id);
                $new_percentage = $progressModel->updateProgress($user['id'], $course_id);
                
                // Check if course is completed
                if ($new_percentage === 100) {
                    $certModel = new CertificateModel($pdo);
                    $cert_id = $certModel->issueCertificate($user['id'], $course_id, 100);
                    
                    if ($cert_id) {
                        header('Location: course.php?id=' . $course_id . '&section_id=' . $section_id . '&message=All questions answered! Course completed. Certificate issued.');
                        exit;
                    }
                }
                
                header('Location: course.php?id=' . $course_id . '&section_id=' . $section_id . '&message=All questions answered! Section completed.');
                exit;
            } else {
                header('Location: course.php?id=' . $course_id . '&section_id=' . $section_id . '&message=Response submitted. (' . $answered_questions . '/' . $total_questions . ' questions answered)');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Error submitting quiz: ' . $e->getMessage();
        }
    }
}

// Get section if viewing
$current_section = null;
$prev_section = null;
$next_section = null;

if (isset($_GET['section_id'])) {
    $section_id = (int)$_GET['section_id'];
    $stmt = $pdo->prepare(
        "SELECT s.* FROM sections s
         INNER JOIN chapters c ON s.chapter_id = c.id
         WHERE s.id = ? AND c.course_id = ?"
    );
    $stmt->execute([$section_id, $course_id]);
    $current_section = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current_section) {
        // Get all sections in order across all chapters
        $stmt = $pdo->prepare(
            "SELECT s.*, c.id as chapter_id FROM sections s
             INNER JOIN chapters c ON s.chapter_id = c.id
             WHERE c.course_id = ?
             ORDER BY c.`order`, s.`order`"
        );
        $stmt->execute([$course_id]);
        $all_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find current position and get prev/next
        foreach ($all_sections as $index => $section) {
            if ($section['id'] == $section_id) {
                if ($index > 0) {
                    $prev_section = $all_sections[$index - 1];
                }
                if ($index < count($all_sections) - 1) {
                    $next_section = $all_sections[$index + 1];
                }
                break;
            }
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

// Check if current section is completed
$current_section_completed = false;
$completion_requirement = '';

if ($current_section) {
    // Get all completed sections for this course
    $stmt = $pdo->prepare(
        "SELECT section_id FROM section_completion 
         WHERE student_id = ?"
    );
    $stmt->execute([$user['id']]);
    $completed_sections_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $current_section_completed = in_array($current_section['id'], $completed_sections_list);
    
    // For quiz sections, check if all questions are answered
    if ($current_section['type'] === 'quiz' && !$current_section_completed) {
        // Get total number of questions
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE section_id = ?");
        $stmt->execute([$current_section['id']]);
        $total_qs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get number of answered questions
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as answered FROM student_answers 
             WHERE student_id = ? AND question_id IN (SELECT id FROM questions WHERE section_id = ?)
             AND answer_text IS NOT NULL AND answer_text != ''"
        );
        $stmt->execute([$user['id'], $current_section['id']]);
        $answered_qs = $stmt->fetch(PDO::FETCH_ASSOC)['answered'];
        
        // Check if all questions are answered
        if ($total_qs > 0 && $answered_qs === $total_qs) {
            $current_section_completed = true;
            // Mark as completed if not already done
            try {
                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO section_completion (student_id, section_id, completed_at)
                     VALUES (?, ?, NOW())"
                );
                $stmt->execute([$user['id'], $current_section['id']]);
            } catch (Exception $e) {
                // Already completed
            }
        } else {
            $completion_requirement = 'You must answer all questions to proceed to the next section.';
        }
    } elseif ($current_section['type'] !== 'quiz' && !$current_section_completed) {
        $completion_requirement = 'You must mark this section as complete before proceeding.';
    }
}
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
        
        .section-item.active {
            background: #667eea;
            color: white;
            font-weight: 600;
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
        
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 999;
            background: #667eea;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-toggle:hover {
            background: #764ba2;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }
        
        .section-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .nav-button {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .nav-button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .nav-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .nav-button.prev::before {
            content: '← ';
        }
        
        .nav-button.next::after {
            content: ' →';
        }
        
        .nav-spacer {
            flex: 1;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .course-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 300px;
                height: 100vh;
                max-height: 100vh;
                overflow-y: auto;
                z-index: 1000;
                transition: transform 0.3s ease-in-out;
                transform: translateX(-100%);
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
                border-radius: 0;
                padding-top: 20px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .button-group {
                flex-direction: column !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">☰</button>
    
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
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
                                
                                // Get all sections and find what's unlocked
                                $all_course_sections = [];
                                $stmt = $pdo->prepare(
                                    "SELECT s.*, c.id as chapter_id FROM sections s
                                     INNER JOIN chapters c ON s.chapter_id = c.id
                                     WHERE c.course_id = ?
                                     ORDER BY c.`order`, s.`order`"
                                );
                                $stmt->execute([$course_id]);
                                $all_course_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php foreach ($chapter['sections'] as $index => $section): ?>
                                <?php
                                    // Find global index of this section
                                    $global_index = 0;
                                    $is_unlocked = true;
                                    
                                    foreach ($all_course_sections as $i => $s) {
                                        if ($s['id'] == $section['id']) {
                                            $global_index = $i;
                                            break;
                                        }
                                    }
                                    
                                    // First section is always unlocked
                                    // Otherwise, previous section must be completed
                                    if ($global_index > 0) {
                                        $is_unlocked = in_array($all_course_sections[$global_index - 1]['id'], $completed_sections);
                                    }
                                ?>
                                <?php if ($is_unlocked): ?>
                                    <a href="course.php?id=<?php echo $course_id; ?>&section_id=<?php echo $section['id']; ?>" class="section-item <?php echo in_array($section['id'], $completed_sections) ? 'completed' : ''; ?> <?php echo ($current_section && $current_section['id'] == $section['id']) ? 'active' : ''; ?>" style="display: block; text-decoration: none; color: inherit;">
                                        <?php echo in_array($section['id'], $completed_sections) ? '✓' : '○'; ?>
                                        <?php echo htmlspecialchars($section['title']); ?>
                                    </a>
                                <?php else: ?>
                                    <div class="section-item" style="opacity: 0.5; cursor: not-allowed; color: #999;">
                                        🔒
                                        <?php echo htmlspecialchars($section['title']); ?>
                                    </div>
                                <?php endif; ?>
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
            
            <?php if ($current_section): ?>
                <!-- Section Content Display -->
                <div class="section-content">
                    <h2 class="section-title"><?php echo htmlspecialchars($current_section['title']); ?></h2>
                    
                    <?php if ($current_section['description']): ?>
                        <p style="color: #666; margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars($current_section['description'])); ?></p>
                    <?php endif; ?>
                    
                    <!-- VIDEO Section -->
                    <?php if ($current_section['type'] === 'video'): ?>
                        <?php if ($current_section['video_url']): ?>
                            <div class="video-container">
                                <?php 
                                    // Check if it's an embed URL or regular URL
                                    if (strpos($current_section['video_url'], 'youtube.com') !== false || strpos($current_section['video_url'], 'youtu.be') !== false || strpos($current_section['video_url'], 'youtube.com/embed') !== false) {
                                        // YouTube embed
                                        echo '<iframe width="100%" height="100%" src="' . htmlspecialchars($current_section['video_url']) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                    } elseif (strpos($current_section['video_url'], 'vimeo') !== false) {
                                        // Vimeo embed
                                        echo '<iframe src="' . htmlspecialchars($current_section['video_url']) . '" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
                                    } else {
                                        // Local video file - disable download
                                        echo '<video width="100%" height="100%" controls controlsList="nodownload"><source src="' . htmlspecialchars($current_section['video_url']) . '" type="video/mp4">Your browser does not support the video tag.</video>';
                                    }
                                ?>
                            </div>
                            <?php if ($current_section['video_duration_seconds']): ?>
                                <p style="font-size: 12px; color: #999;">Duration: <?php echo floor($current_section['video_duration_seconds'] / 60); ?> minutes</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #999;">No video uploaded for this section yet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- READING/ASSIGNMENT Section -->
                    <?php if (in_array($current_section['type'], ['reading', 'assignment'])): ?>
                        <?php if ($current_section['content']): ?>
                            <div style="background: white; padding: 15px; border-left: 4px solid #667eea; margin: 15px 0;">
                                <?php echo nl2br(htmlspecialchars($current_section['content'])); ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #999;">No content available for this section yet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- QUIZ Section -->
                    <?php if ($current_section['type'] === 'quiz'): ?>
                        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0;">
                            <p style="color: #999;">📝 This section contains a quiz. Complete the questions below and submit to see your score.</p>
                        </div>
                        
                        <?php
                        // Show latest quiz result if available
                        $stmt = $pdo->prepare(
                            "SELECT SUM(q.points) as total_points, SUM(CASE WHEN sa.is_correct = 1 THEN q.points ELSE 0 END) as earned_points
                             FROM questions q
                             INNER JOIN student_answers sa ON q.id = sa.question_id
                             WHERE q.section_id = ? AND sa.student_id = ? 
                             AND sa.answer_text IS NOT NULL AND sa.answer_text != ''"
                        );
                        $stmt->execute([$current_section['id'], $user['id']]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $quiz_total = $result['total_points'] ?? 0;
                        $quiz_earned = $result['earned_points'] ?? 0;
                        $quiz_score = $quiz_total > 0 ? round(($quiz_earned / $quiz_total) * 100) : 0;
                        
                        // Check if student has attempted this quiz (answered at least one question in this section)
                        $stmt = $pdo->prepare(
                            "SELECT COUNT(*) as count FROM student_answers sa
                             INNER JOIN questions q ON sa.question_id = q.id
                             WHERE q.section_id = ? AND sa.student_id = ?
                             AND sa.answer_text IS NOT NULL AND sa.answer_text != ''"
                        );
                        $stmt->execute([$current_section['id'], $user['id']]);
                        $has_attempted = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                        
                        if ($has_attempted && $quiz_score > 0): ?>
                            <div style="background: <?php echo $quiz_score >= 100 ? '#d4edda' : '#fff3cd'; ?>; border: 1px solid <?php echo $quiz_score >= 100 ? '#c3e6cb' : '#ffeaa7'; ?>; border-left: 4px solid <?php echo $quiz_score >= 100 ? '#28a745' : '#ffc107'; ?>; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: <?php echo $quiz_score >= 100 ? '#155724' : '#856404'; ?>;">
                                <strong><?php echo $quiz_score >= 100 ? '✓ Completed!' : '⚠️ Not Completed'; ?></strong>
                                <p style="margin: 10px 0 0 0;">
                                    Responses: <strong><?php echo $quiz_score; ?>%</strong> (<?php echo $quiz_earned; ?>/<?php echo $quiz_total; ?> )
                                </p>
                                <?php if ($quiz_score < 100): ?>
                                    <p style="margin: 5px 0 0 0; font-size: 13px;">You need to respond to all the questions to proceed to the next section.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $stmt = $pdo->prepare(
                            "SELECT * FROM questions WHERE section_id = ? ORDER BY `order`"
                        );
                        $stmt->execute([$current_section['id']]);
                        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Fetch student's existing answers (only non-empty)
                        $stmt = $pdo->prepare(
                            "SELECT question_id, answer_text FROM student_answers 
                             WHERE student_id = ? AND question_id IN (SELECT id FROM questions WHERE section_id = ?)
                             AND answer_text IS NOT NULL AND answer_text != ''"
                        );
                        $stmt->execute([$user['id'], $current_section['id']]);
                        $existing_answers = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $existing_answers[$row['question_id']] = $row['answer_text'];
                        }
                        
                        // Check if all questions are answered
                        $all_answered = count($existing_answers) === count($questions) && count($questions) > 0;
                        ?>
                        
                        <?php if (!empty($questions)): ?>
                            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-left: 4px solid #0066cc; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; color: #004085;">
                                <strong>📋 Progress:</strong> <?php echo count($existing_answers); ?> of <?php echo count($questions); ?> questions answered
                            </div>
                            <form method="POST" style="margin-top: 20px;">
                                <input type="hidden" name="section_id" value="<?php echo $current_section['id']; ?>">
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php $answered = isset($existing_answers[$question['id']]); ?>
                                    <div style="background: white; padding: 15px; margin-bottom: 15px; border-radius: 5px; border: 1px solid <?php echo $answered ? '#d4edda' : '#ddd'; ?>; <?php echo $answered ? 'opacity: 0.8;' : ''; ?>">
                                        <h4 style="margin-bottom: 10px;">Q<?php echo $index + 1; ?>. <?php echo htmlspecialchars($question['question_text']); ?> <?php echo $answered ? '<br/><span style="border-radius: 10px; color: white; padding: 2px 8px; background-color: #28a745; font-size: 0.8rem; font-weight: 600;">Answered</span>' : ''; ?></h4>
                                        
                                        <?php if (in_array($question['question_type'], ['multiple_choice', 'true_false'])): ?>
                                            <?php
                                            $stmt = $pdo->prepare(
                                                "SELECT * FROM question_options WHERE question_id = ? ORDER BY `order`"
                                            );
                                            $stmt->execute([$question['id']]);
                                            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <div style="margin-left: 20px;">
                                                <?php foreach ($options as $option): ?>
                                                    <label style="display: block; margin-bottom: 8px; cursor: pointer; <?php echo $answered ? 'opacity: 0.6;' : ''; ?>">
                                                        <input type="radio" name="question_<?php echo $question['id']; ?>" value="<?php echo $option['id']; ?>" style="margin-right: 8px;" <?php echo $answered ? 'disabled' : ''; ?> <?php echo ($answered && $existing_answers[$question['id']] == $option['id']) ? 'checked' : ''; ?>>
                                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                            <input type="text" name="question_<?php echo $question['id']; ?>" placeholder="Your answer..." style="width: 90%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-left: 20px;" value="<?php echo $answered ? htmlspecialchars($existing_answers[$question['id']]) : ''; ?>" <?php echo $answered ? 'readonly' : ''; ?>>
                                        <?php elseif ($question['question_type'] === 'essay'): ?>
                                            <textarea name="question_<?php echo $question['id']; ?>" placeholder="Your essay answer..." style="width: 90%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-left: 20px; min-height: 100px;" <?php echo $answered ? 'readonly' : ''; ?>><?php echo $answered ? htmlspecialchars($existing_answers[$question['id']]) : ''; ?></textarea>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div style="display: flex; gap: 10px; margin-top: 20px;" class="button-group">
                                    <button type="submit" class="complete-button" style="flex: 1;" <?php echo $all_answered ? 'disabled title="All questions already answered"' : ''; ?>>Submit Quiz</button>
                                    <?php if ($has_attempted): ?>
                                        <a href="download-responses.php?course_id=<?php echo $course_id; ?>&section_id=<?php echo $current_section['id']; ?>" class="complete-button" style="flex: 1; text-align: center; text-decoration: none; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                                            📥 Download Responses (PDF)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php else: ?>
                            <p style="color: #999;">No questions in this quiz yet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Mark as Complete Button -->
                    <?php if ($current_section['type'] !== 'quiz'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="complete_section">
                            <input type="hidden" name="section_id" value="<?php echo $current_section['id']; ?>">
                            <button type="submit" class="complete-button">
                                <?php 
                                    $completed = in_array($current_section['id'], $completed_sections);
                                    echo $completed ? '✓ Completed' : 'Mark as Complete';
                                ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Section Navigation -->
                    <div class="section-navigation">
                        <?php if ($prev_section): ?>
                            <a href="course.php?id=<?php echo $course_id; ?>&section_id=<?php echo $prev_section['id']; ?>" class="nav-button prev">
                                Previous
                            </a>
                        <?php else: ?>
                            <button class="nav-button prev" disabled>Previous</button>
                        <?php endif; ?>
                        
                        <div class="nav-spacer">
                            <?php 
                                // Count current section position
                                $stmt = $pdo->prepare(
                                    "SELECT COUNT(*) as count FROM sections s
                                     INNER JOIN chapters c ON s.chapter_id = c.id
                                     WHERE c.course_id = ? AND (c.`order` < (SELECT c2.`order` FROM chapters c2 WHERE c2.id = ?) 
                                     OR (c.`order` = (SELECT c2.`order` FROM chapters c2 WHERE c2.id = ?) AND s.`order` <= ?))"
                                );
                                $stmt->execute([$course_id, $current_section['chapter_id'], $current_section['chapter_id'], $current_section['order']]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $current_position = $result['count'];
                            ?>
                            Section <?php echo $current_position; ?> of <?php echo $total_sections; ?>
                        </div>
                        
                        <?php if ($next_section): ?>
                            <?php if ($current_section_completed): ?>
                                <a href="course.php?id=<?php echo $course_id; ?>&section_id=<?php echo $next_section['id']; ?>" class="nav-button next">
                                    Next
                                </a>
                            <?php else: ?>
                                <button class="nav-button next" disabled title="<?php echo htmlspecialchars($completion_requirement); ?>">Next</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="nav-button next" disabled>Next</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($completion_requirement): ?>
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-left: 4px solid #ffc107; padding: 12px 15px; border-radius: 4px; margin-top: 15px; color: #856404;">
                            <strong>⚠️ Progress Blocked:</strong> <?php echo htmlspecialchars($completion_requirement); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Initial Course View -->
                <div class="section-content">
                    <h3>Start Learning</h3>
                    <p>Select a chapter from the sidebar to begin. Complete each section and mark it as done to track your progress.</p>
                    <p style="color: #999; font-size: 14px; margin-top: 10px;">
                        Your progress is automatically saved as you complete sections. Complete all sections to earn your certificate!
                    </p>
                    
                    <?php 
                        // Determine which section to start with
                        $start_section_id = null;
                        
                        if (!empty($all_course_sections)) {
                            // Find first uncompleted section
                            $stmt = $pdo->prepare(
                                "SELECT section_id FROM section_completion 
                                 WHERE student_id = ?"
                            );
                            $stmt->execute([$user['id']]);
                            $completed_sections_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            foreach ($all_course_sections as $section) {
                                if (!in_array($section['id'], $completed_sections_list)) {
                                    $start_section_id = $section['id'];
                                    break;
                                }
                            }
                        }
                    ?>
                    
                    <?php if ($start_section_id): ?>
                        <a href="course.php?id=<?php echo $course_id; ?>&section_id=<?php echo $start_section_id; ?>" style="display: inline-block; margin-top: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer;">
                            🚀 Start Course
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleSections(element) {
            const sectionsList = element.nextElementSibling;
            sectionsList.classList.toggle('active');
        }
        
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        
        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
        
        // Close sidebar when clicking on a section
        document.querySelectorAll('.section-item').forEach(item => {
            item.addEventListener('click', closeSidebar);
        });
        
        // Close sidebar on window resize (when switching from mobile to desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
