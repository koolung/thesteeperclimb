<?php
/**
 * Organization Course Editor
 * View course details, student progress, and manage quiz questions
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

// Handle question operations
$message = '';
$error = '';
$action = $_GET['action'] ?? 'view';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    try {
        if ($_POST['type'] === 'add_question') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $question_text = trim($_POST['question_text'] ?? '');
            $question_type = $_POST['question_type'] ?? 'multiple_choice';
            $points = (int)($_POST['points'] ?? 1);
            
            // Verify section belongs to this course
            $stmt = $pdo->prepare(
                "SELECT s.id FROM sections s
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE s.id = ? AND c.course_id = ?"
            );
            $stmt->execute([$section_id, $course_id]);
            if (!$stmt->fetch()) {
                $error = 'Section not found';
            } elseif (empty($question_text)) {
                $error = 'Question text is required';
            } else {
                // Get the next order number for this section
                $stmt = $pdo->prepare(
                    "SELECT MAX(`order`) as max_order FROM questions WHERE section_id = ?"
                );
                $stmt->execute([$section_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $next_order = ($result['max_order'] ?? 0) + 1;
                
                $stmt = $pdo->prepare(
                    "INSERT INTO questions (section_id, question_text, question_type, points, `order`)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$section_id, $question_text, $question_type, $points, $next_order]);
                $question_id = $pdo->lastInsertId();
                
                $message = 'Question added successfully. Click "Edit" to add options.';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'edit_question') {
            $question_id = (int)($_POST['question_id'] ?? 0);
            $question_text = trim($_POST['question_text'] ?? '');
            $question_type = $_POST['question_type'] ?? 'multiple_choice';
            $points = (int)($_POST['points'] ?? 1);
            $section_id = (int)($_POST['section_id'] ?? 0);
            
            // Verify question belongs to this course
            $stmt = $pdo->prepare(
                "SELECT q.id FROM questions q
                 INNER JOIN sections s ON q.section_id = s.id
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE q.id = ? AND c.course_id = ?"
            );
            $stmt->execute([$question_id, $course_id]);
            if (!$stmt->fetch()) {
                $error = 'Question not found';
            } elseif (empty($question_text)) {
                $error = 'Question text is required';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE questions SET question_text = ?, question_type = ?, points = ? WHERE id = ?"
                );
                $stmt->execute([$question_text, $question_type, $points, $question_id]);
                
                $message = 'Question updated successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_section&section_id=' . $section_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'delete_question') {
            $question_id = (int)($_POST['question_id'] ?? 0);
            
            // Verify question belongs to this course
            $stmt = $pdo->prepare(
                "SELECT q.id FROM questions q
                 INNER JOIN sections s ON q.section_id = s.id
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE q.id = ? AND c.course_id = ?"
            );
            $stmt->execute([$question_id, $course_id]);
            if (!$stmt->fetch()) {
                $error = 'Question not found';
            } else {
                $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                $stmt->execute([$question_id]);
                $message = 'Question deleted successfully';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'add_option') {
            $question_id = (int)($_POST['question_id'] ?? 0);
            $option_text = trim($_POST['option_text'] ?? '');
            $is_correct = isset($_POST['is_correct']) ? 1 : 0;
            
            // Verify question belongs to this course
            $stmt = $pdo->prepare(
                "SELECT q.id FROM questions q
                 INNER JOIN sections s ON q.section_id = s.id
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE q.id = ? AND c.course_id = ?"
            );
            $stmt->execute([$question_id, $course_id]);
            if (!$stmt->fetch()) {
                $error = 'Question not found';
            } elseif (empty($option_text)) {
                $error = 'Option text is required';
            } else {
                // Get the next order number for this question
                $stmt = $pdo->prepare(
                    "SELECT MAX(`order`) as max_order FROM question_options WHERE question_id = ?"
                );
                $stmt->execute([$question_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $next_order = ($result['max_order'] ?? 0) + 1;
                
                $stmt = $pdo->prepare(
                    "INSERT INTO question_options (question_id, option_text, is_correct, `order`)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$question_id, $option_text, $is_correct, $next_order]);
                $message = 'Option added successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_question&question_id=' . $question_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'delete_option') {
            $option_id = (int)($_POST['option_id'] ?? 0);
            
            // Verify option belongs to this course
            $stmt = $pdo->prepare(
                "SELECT qo.id FROM question_options qo
                 INNER JOIN questions q ON qo.question_id = q.id
                 INNER JOIN sections s ON q.section_id = s.id
                 INNER JOIN chapters c ON s.chapter_id = c.id
                 WHERE qo.id = ? AND c.course_id = ?"
            );
            $stmt->execute([$option_id, $course_id]);
            if (!$stmt->fetch()) {
                $error = 'Option not found';
            } else {
                $stmt = $pdo->prepare("DELETE FROM question_options WHERE id = ?");
                $stmt->execute([$option_id]);
                $message = 'Option deleted successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_question&question_id=' . $_POST['question_id'] . '&message=' . urlencode($message));
                exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get question if editing
$current_question = null;
$current_options = [];
if ($action === 'edit_question' && isset($_GET['question_id'])) {
    $question_id = (int)$_GET['question_id'];
    $stmt = $pdo->prepare(
        "SELECT q.* FROM questions q
         INNER JOIN sections s ON q.section_id = s.id
         INNER JOIN chapters c ON s.chapter_id = c.id
         WHERE q.id = ? AND c.course_id = ?"
    );
    $stmt->execute([$question_id, $course_id]);
    $current_question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current_question) {
        $stmt = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY `order`");
        $stmt->execute([$question_id]);
        $current_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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

        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group-inline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-primary.btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-delete.btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        .content-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .content-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 8px;
            border-left: 3px solid #667eea;
        }

        .content-list-item-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #333;
        }

        .content-list-item-info p {
            margin: 0;
            font-size: 12px;
            color: #999;
        }

        .content-list-item-actions {
            display: flex;
            gap: 8px;
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
                        <strong>Duration</strong>
                        <span><?php echo $course['duration_hours'] ? $course['duration_hours'] . ' hours' : 'N/A'; ?></span>
                    </div>
                    <div class="meta-item">
                        <strong>Total Students</strong>
                        <span id="total-students"><?php echo count($students); ?></span>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Question Editor -->
            <?php if ($action === 'edit_question' && $current_question && !isset($_GET['message'])): ?>
                <section class="content-section">
                    <h2>Edit Question</h2>
                    
                    <form method="POST" class="form-section">
                        <h3>Question Details</h3>
                        <input type="hidden" name="type" value="edit_question">
                        <input type="hidden" name="question_id" value="<?php echo $current_question['id']; ?>">
                        <input type="hidden" name="section_id" value="<?php echo $current_question['section_id']; ?>">
                        
                        <div class="form-group">
                            <label for="q_text">Question Text *</label>
                            <textarea id="q_text" name="question_text" required><?php echo htmlspecialchars($current_question['question_text']); ?></textarea>
                        </div>
                        
                        <div class="form-group-inline">
                            <div class="form-group">
                                <label for="q_type">Question Type</label>
                                <select id="q_type" name="question_type" onchange="updateQuestionType(this.value)">
                                    <option value="multiple_choice" <?php echo $current_question['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                    <option value="true_false" <?php echo $current_question['question_type'] === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                    <option value="short_answer" <?php echo $current_question['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                    <option value="essay" <?php echo $current_question['question_type'] === 'essay' ? 'selected' : ''; ?>>Essay</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="q_points">Points</label>
                                <input type="number" id="q_points" name="points" value="<?php echo $current_question['points']; ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn-primary">Save Question</button>
                            <button type="button" class="btn-secondary" onclick="window.location='course-editor.php?id=<?php echo $course_id; ?>'">Back</button>
                        </div>
                    </form>
                    
                    <!-- Question Options (for multiple choice and true/false) -->
                    <?php if (in_array($current_question['question_type'], ['multiple_choice', 'true_false'])): ?>
                        <div class="form-section" style="margin-top: 30px;">
                            <h3>Answer Options</h3>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addOption(<?php echo $current_question['id']; ?>)" style="margin-bottom: 15px;">
                                + Add Option
                            </button>
                            
                            <?php if (empty($current_options)): ?>
                                <p style="color: #999;">No options yet.</p>
                            <?php else: ?>
                                <div class="content-list">
                                    <?php foreach ($current_options as $option): ?>
                                        <div class="content-list-item">
                                            <div class="content-list-item-info">
                                                <h4><?php echo htmlspecialchars($option['option_text']); ?></h4>
                                                <p><?php echo $option['is_correct'] ? '✓ Correct Answer' : 'Incorrect'; ?></p>
                                            </div>
                                            <div class="content-list-item-actions">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="type" value="delete_option">
                                                    <input type="hidden" name="option_id" value="<?php echo $option['id']; ?>">
                                                    <input type="hidden" name="question_id" value="<?php echo $current_question['id']; ?>">
                                                    <button type="submit" class="btn-delete btn-sm" onclick="return confirm('Delete this option?');">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            
            <!-- Course Content Structure & Quiz Management -->
            <section class="content-section">
                <h2>Course Content</h2>
                
                <?php if (!$course_with_structure || empty($course_with_structure['chapters'])): ?>
                    <p style="color: #999;">No chapters in this course yet.</p>
                <?php else: ?>
                    <?php foreach ($course_with_structure['chapters'] as $chapter): ?>
                        <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                            <h3 style="margin-top: 0;"><?php echo htmlspecialchars($chapter['title']); ?></h3>
                            
                            <?php if (!empty($chapter['sections'])): ?>
                                <div style="margin-top: 15px;">
                                    <?php foreach ($chapter['sections'] as $section): ?>
                                        <div style="margin-bottom: 15px; padding: 15px; background: white; border-radius: 6px; border: 1px solid #e9ecef;">
                                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                                <div>
                                                    <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($section['title']); ?></h4>
                                                    <p style="margin: 0; font-size: 13px; color: #999;">
                                                        Type: <strong><?php echo ucfirst($section['type']); ?></strong>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <?php if ($section['type'] === 'quiz' && !empty($section['questions'])): ?>
                                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e9ecef;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                                        <strong style="font-size: 13px; color: #666;">Quiz Questions (<?php echo count($section['questions']); ?>)</strong>
                                                        <button type="button" class="btn btn-primary btn-sm" onclick="addQuestion(<?php echo $section['id']; ?>)" style="padding: 4px 12px; font-size: 12px;">
                                                            + Add
                                                        </button>
                                                    </div>
                                                    <div style="padding-left: 15px;">
                                                        <?php foreach ($section['questions'] as $q): ?>
                                                            <div style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #667eea;">
                                                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                                                    <div style="flex: 1;">
                                                                        <div style="margin-bottom: 5px;"><strong><?php echo htmlspecialchars(substr($q['question_text'], 0, 60)); ?><?php echo strlen($q['question_text']) > 60 ? '...' : ''; ?></strong></div>
                                                                        <small style="color: #999;">
                                                                            <?php echo ucfirst(str_replace('_', ' ', $q['question_type'])); ?> • <?php echo $q['points']; ?> pts
                                                                        </small>
                                                                    </div>
                                                                    <div style="display: flex; gap: 8px;">
                                                                        <a href="course-editor.php?id=<?php echo $course_id; ?>&action=edit_question&question_id=<?php echo $q['id']; ?>" class="btn-edit" style="font-size: 12px;">Edit</a>
                                                                        <button type="button" class="btn-delete btn-sm" onclick="deleteQuestion(<?php echo $q['id']; ?>)" style="font-size: 12px;">Delete</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php elseif ($section['type'] === 'quiz'): ?>
                                                <div style="margin-top: 12px; padding: 12px; background: #fff3cd; border-radius: 4px; border-left: 3px solid #ffc107;">
                                                    <p style="margin: 0; font-size: 13px; color: #856404;">
                                                        No questions yet. 
                                                        <button type="button" onclick="addQuestion(<?php echo $section['id']; ?>)" style="background: none; border: none; color: #0c5460; text-decoration: underline; cursor: pointer; padding: 0;">Add first question</button>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

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
    
    <script>
        function addQuestion(sectionId) {
            const text = prompt('Question Text:');
            if (text) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="add_question">
                    <input type="hidden" name="section_id" value="${sectionId}">
                    <input type="hidden" name="question_text" value="${text}">
                    <input type="hidden" name="question_type" value="multiple_choice">
                    <input type="hidden" name="points" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteQuestion(questionId) {
            if (confirm('Delete this question? This will also delete all its options and answers.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="delete_question">
                    <input type="hidden" name="question_id" value="${questionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function addOption(questionId) {
            const text = prompt('Option Text:');
            if (text) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="add_option">
                    <input type="hidden" name="question_id" value="${questionId}">
                    <input type="hidden" name="option_text" value="${text}">
                    <input type="hidden" name="is_correct" value="0">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateQuestionType(type) {
            // This function can be extended for conditional option display
            console.log('Question type changed to: ' + type);
        }
    </script>
</body>
</html>
