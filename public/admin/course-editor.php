<?php
/**
 * Admin - Course Editor
 * Comprehensive course content management: chapters, sections, videos, quizzes, etc.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$courseModel = new CourseModel($pdo);
$user = Auth::getCurrentUser();
$course_id = (int)($_GET['id'] ?? 0);

if (!$course_id) {
    header('Location: courses.php?error=Course ID required');
    exit;
}

$course = $courseModel->getWithStructure($course_id);
if (!$course) {
    header('Location: courses.php?error=Course not found');
    exit;
}

$action = $_GET['action'] ?? 'view';
$message = '';
$error = '';

// Handle chapter operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    try {
        if ($_POST['type'] === 'add_chapter') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $order = (int)($_POST['order'] ?? 1);
            
            if (empty($title)) {
                $error = 'Chapter title is required';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO chapters (course_id, title, description, `order`)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$course_id, $title, $description, $order]);
                $message = 'Chapter added successfully';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'edit_chapter') {
            $chapter_id = (int)($_POST['chapter_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (!$chapter_id || empty($title)) {
                $error = 'Invalid chapter data';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE chapters SET title = ?, description = ? WHERE id = ? AND course_id = ?"
                );
                $stmt->execute([$title, $description, $chapter_id, $course_id]);
                $message = 'Chapter updated successfully';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'delete_chapter') {
            $chapter_id = (int)($_POST['chapter_id'] ?? 0);
            
            if ($chapter_id) {
                $stmt = $pdo->prepare("DELETE FROM chapters WHERE id = ? AND course_id = ?");
                $stmt->execute([$chapter_id, $course_id]);
                $message = 'Chapter deleted successfully';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'add_section') {
            $chapter_id = (int)($_POST['chapter_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $section_type = $_POST['section_type'] ?? 'video';
            $description = trim($_POST['description'] ?? '');
            $order = (int)($_POST['order'] ?? 1);
            $video_url = trim($_POST['video_url'] ?? '');
            $video_duration = (int)($_POST['video_duration'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            
            if (!$chapter_id || empty($title)) {
                $error = 'Invalid section data';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO sections (chapter_id, title, description, type, `order`, video_url, video_duration_seconds, content)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$chapter_id, $title, $description, $section_type, $order, $video_url, $video_duration, $content]);
                $section_id = $pdo->lastInsertId();
                
                $message = 'Section added successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_section&section_id=' . $section_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'edit_section') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $section_type = $_POST['section_type'] ?? 'video';
            $description = trim($_POST['description'] ?? '');
            $video_url = trim($_POST['video_url'] ?? '');
            $video_duration = (int)($_POST['video_duration'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            
            if (!$section_id || empty($title)) {
                $error = 'Invalid section data';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE sections SET title = ?, description = ?, type = ?, video_url = ?, video_duration_seconds = ?, content = ?
                     WHERE id = ? AND chapter_id IN (SELECT id FROM chapters WHERE course_id = ?)"
                );
                $stmt->execute([$title, $description, $section_type, $video_url, $video_duration, $content, $section_id, $course_id]);
                $message = 'Section updated successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_section&section_id=' . $section_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'delete_section') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            
            if ($section_id) {
                $stmt = $pdo->prepare(
                    "DELETE FROM sections WHERE id = ? AND chapter_id IN (SELECT id FROM chapters WHERE course_id = ?)"
                );
                $stmt->execute([$section_id, $course_id]);
                $message = 'Section deleted successfully';
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'add_question') {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $question_text = trim($_POST['question_text'] ?? '');
            $question_type = $_POST['question_type'] ?? 'multiple_choice';
            $points = (int)($_POST['points'] ?? 1);
            $order = (int)($_POST['order'] ?? 1);
            
            if (!$section_id || empty($question_text)) {
                $error = 'Invalid question data';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO questions (section_id, question_text, question_type, points, `order`)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$section_id, $question_text, $question_type, $points, $order]);
                $question_id = $pdo->lastInsertId();
                
                $message = 'Question added successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_question&question_id=' . $question_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'add_option') {
            $question_id = (int)($_POST['question_id'] ?? 0);
            $option_text = trim($_POST['option_text'] ?? '');
            $is_correct = isset($_POST['is_correct']) ? 1 : 0;
            $order = (int)($_POST['option_order'] ?? 1);
            
            if (!$question_id || empty($option_text)) {
                $error = 'Invalid option data';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO question_options (question_id, option_text, is_correct, `order`)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$question_id, $option_text, $is_correct, $order]);
                $message = 'Option added successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_question&question_id=' . $question_id . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'delete_option') {
            $option_id = (int)($_POST['option_id'] ?? 0);
            
            if ($option_id) {
                $stmt = $pdo->prepare("DELETE FROM question_options WHERE id = ?");
                $stmt->execute([$option_id]);
                $message = 'Option deleted successfully';
                header('Location: course-editor.php?id=' . $course_id . '&action=edit_question&question_id=' . $_POST['question_id'] . '&message=' . urlencode($message));
                exit;
            }
        }
        
        elseif ($_POST['type'] === 'edit_course') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $instructor_name = trim($_POST['instructor_name'] ?? '');
            $difficulty = $_POST['difficulty_level'] ?? 'beginner';
            $duration = (int)($_POST['duration_hours'] ?? 0);
            $pass_percentage = (int)($_POST['pass_percentage'] ?? 70);
            
            if (empty($title)) {
                $error = 'Course title is required';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE courses SET title = ?, description = ?, instructor_name = ?, difficulty_level = ?, duration_hours = ?, pass_percentage = ?
                     WHERE id = ?"
                );
                $stmt->execute([$title, $description, $instructor_name, $difficulty, $duration, $pass_percentage, $course_id]);
                $message = 'Course updated successfully';
                $course = $courseModel->getWithStructure($course_id);
                header('Location: course-editor.php?id=' . $course_id . '&message=' . urlencode($message));
                exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get section if editing
$current_section = null;
if ($action === 'edit_section' && isset($_GET['section_id'])) {
    $section_id = (int)$_GET['section_id'];
    $stmt = $pdo->prepare(
        "SELECT s.* FROM sections s
         INNER JOIN chapters c ON s.chapter_id = c.id
         WHERE s.id = ? AND c.course_id = ?"
    );
    $stmt->execute([$section_id, $course_id]);
    $current_section = $stmt->fetch(PDO::FETCH_ASSOC);
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

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Editor - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <style>
        .course-editor-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            padding: 20px;
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        .editor-sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .course-title-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .course-title-header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .course-title-header p {
            margin: 0;
            font-size: 12px;
            color: #999;
        }
        
        .chapter-item {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .chapter-header {
            background: #f9f9f9;
            padding: 12px;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chapter-header:hover {
            background: #f0f0f0;
        }
        
        .chapter-header h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .chapter-actions {
            display: flex;
            gap: 5px;
        }
        
        .chapter-actions button {
            padding: 4px 8px;
            font-size: 11px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .chapter-actions button:hover {
            background: #5568d3;
        }
        
        .sections-list {
            display: none;
            background: white;
            border-top: 1px solid #eee;
        }
        
        .sections-list.active {
            display: block;
        }
        
        .section-item {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            font-size: 12px;
            color: #666;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-item:hover {
            background: #f9f9f9;
        }
        
        .section-item.active {
            background: #e8eaf6;
            color: #667eea;
            font-weight: 600;
        }
        
        .section-type-badge {
            font-size: 10px;
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .editor-main {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .editor-header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .editor-header .actions {
            display: flex;
            gap: 10px;
        }
        
        .editor-header .actions a {
            padding: 8px 16px;
            background: #999;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .editor-header .actions a:hover {
            background: #777;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .form-section h3 {
            margin-top: 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group-inline {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-group button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #ddd;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #ccc;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .content-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .content-list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-list-item:last-child {
            border-bottom: none;
        }
        
        .content-list-item-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
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
        
        .content-list-item-actions a,
        .content-list-item-actions button {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .content-list-item-actions .btn-edit {
            background: #667eea;
            color: white;
        }
        
        .content-list-item-actions .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 1024px) {
            .course-editor-layout {
                grid-template-columns: 1fr;
            }
            
            .editor-sidebar {
                position: static;
                top: auto;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <div class="course-editor-layout">
        <!-- Sidebar - Course Structure -->
        <div class="editor-sidebar">
            <div class="course-title-header">
                <h2>📚 <?php echo htmlspecialchars(substr($course['title'], 0, 25)); ?></h2>
                <p><?php echo count($course['chapters']); ?> chapters</p>
            </div>
            
            <button class="btn btn-primary btn-sm" onclick="addChapter()" style="width: 100%; margin-bottom: 15px;">
                + Add Chapter
            </button>
            
            <div id="chapters-list">
                <?php foreach ($course['chapters'] as $chapter): ?>
                    <div class="chapter-item">
                        <div class="chapter-header" onclick="toggleSections(this)">
                            <h4><?php echo htmlspecialchars($chapter['title']); ?></h4>
                            <div class="chapter-actions">
                                <button onclick="editChapter(<?php echo $chapter['id']; ?>); event.stopPropagation();">Edit</button>
                                <button onclick="deleteChapter(<?php echo $chapter['id']; ?>); event.stopPropagation();" class="btn-danger">Del</button>
                            </div>
                        </div>
                        <div class="sections-list">
                            <div style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0;">
                                <button class="btn btn-primary btn-sm" onclick="addSection(<?php echo $chapter['id']; ?>)" style="width: 100%; font-size: 11px;">+ Add Section</button>
                            </div>
                            <?php if (isset($chapter['sections']) && is_array($chapter['sections'])): ?>
                                <?php foreach ($chapter['sections'] as $section): ?>
                                    <a href="course-editor.php?id=<?php echo $course_id; ?>&action=edit_section&section_id=<?php echo $section['id']; ?>" class="section-item <?php echo ($action === 'edit_section' && $_GET['section_id'] == $section['id']) ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars(substr($section['title'], 0, 20)); ?></span>
                                        <span class="section-type-badge"><?php echo ucfirst(substr($section['type'], 0, 3)); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Main Editor Content -->
        <div class="editor-main">
            <a href="courses.php" class="back-link">← Back to Courses</a>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Course Basic Info Editor -->
            <?php if ($action === 'view'): ?>
                <div class="editor-header">
                    <div>
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p style="color: #999; margin: 5px 0 0 0;">Status: <strong><?php echo ucfirst($course['status']); ?></strong></p>
                    </div>
                    <div class="actions">
                        <button onclick="document.querySelector('[data-tab=edit-course]').click()" class="btn btn-primary btn-sm">Edit Course</button>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div>
                        <h3>Course Details</h3>
                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'Not set'); ?></p>
                        <p><strong>Difficulty:</strong> <?php echo ucfirst($course['difficulty_level']); ?></p>
                        <p><strong>Duration:</strong> <?php echo $course['duration_hours'] ?? 'Not set'; ?> hours</p>
                        <p><strong>Pass Percentage:</strong> <?php echo $course['pass_percentage']; ?>%</p>
                    </div>
                    <div>
                        <h3>Statistics</h3>
                        <p><strong>Chapters:</strong> <?php echo count($course['chapters']); ?></p>
                        <p><strong>Sections:</strong> 
                            <?php 
                                $section_count = 0;
                                foreach ($course['chapters'] as $ch) {
                                    if (isset($ch['sections'])) $section_count += count($ch['sections']);
                                }
                                echo $section_count;
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="form-section" style="border-left-color: #4CAF50;">
                    <h3>Next Steps</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #666;">
                        <li>Create chapters to organize your course content</li>
                        <li>Add sections (videos, quizzes, readings) to each chapter</li>
                        <li>For quizzes, add questions and answer options</li>
                        <li>Publish the course when ready</li>
                    </ul>
                </div>
                
                <!-- Edit Course Form (Hidden by default) -->
                <form method="POST" class="form-section" style="display: none;" data-tab="edit-course">
                    <h3>Edit Course Information</h3>
                    <input type="hidden" name="type" value="edit_course">
                    
                    <div class="form-group">
                        <label for="title">Course Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group-inline">
                        <div class="form-group">
                            <label for="instructor_name">Instructor Name</label>
                            <input type="text" id="instructor_name" name="instructor_name" value="<?php echo htmlspecialchars($course['instructor_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="difficulty_level">Difficulty Level</label>
                            <select id="difficulty_level" name="difficulty_level">
                                <option value="beginner" <?php echo $course['difficulty_level'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo $course['difficulty_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $course['difficulty_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group-inline">
                        <div class="form-group">
                            <label for="duration_hours">Duration (hours)</label>
                            <input type="number" id="duration_hours" name="duration_hours" value="<?php echo $course['duration_hours'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="pass_percentage">Pass Percentage</label>
                            <input type="number" id="pass_percentage" name="pass_percentage" min="0" max="100" value="<?php echo $course['pass_percentage']; ?>">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="location.reload()">Cancel</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Section Editor -->
            <?php if ($action === 'edit_section' && $current_section): ?>
                <h2>Edit Section: <?php echo htmlspecialchars($current_section['title']); ?></h2>
                
                <form method="POST" class="form-section">
                    <h3>Section Details</h3>
                    <input type="hidden" name="type" value="edit_section">
                    <input type="hidden" name="section_id" value="<?php echo $current_section['id']; ?>">
                    
                    <div class="form-group">
                        <label for="title">Section Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($current_section['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="section_type">Type *</label>
                        <select id="section_type" name="section_type" onchange="updateSectionType(this.value)">
                            <option value="video" <?php echo $current_section['type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                            <option value="quiz" <?php echo $current_section['type'] === 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                            <option value="assignment" <?php echo $current_section['type'] === 'assignment' ? 'selected' : ''; ?>>Assignment</option>
                            <option value="reading" <?php echo $current_section['type'] === 'reading' ? 'selected' : ''; ?>>Reading</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($current_section['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Video Section Fields -->
                    <div id="video-fields" style="display: <?php echo $current_section['type'] === 'video' ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="video_url">Video URL</label>
                            <input type="url" id="video_url" name="video_url" placeholder="https://youtube.com/embed/..." value="<?php echo htmlspecialchars($current_section['video_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="video_duration">Video Duration (seconds)</label>
                            <input type="number" id="video_duration" name="video_duration" value="<?php echo $current_section['video_duration_seconds'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Reading/Assignment Content -->
                    <div id="content-field" style="display: <?php echo in_array($current_section['type'], ['reading', 'assignment']) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content"><?php echo htmlspecialchars($current_section['content'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn-primary">Save Section</button>
                        <button type="button" class="btn-secondary" onclick="window.history.back()">Cancel</button>
                        <button type="button" class="btn-danger" onclick="deleteSection(<?php echo $current_section['id']; ?>)">Delete Section</button>
                    </div>
                </form>
                
                <!-- Questions for Quiz Sections -->
                <?php if ($current_section['type'] === 'quiz'): ?>
                    <div class="form-section" style="margin-top: 30px;">
                        <h3>Quiz Questions</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addQuestion(<?php echo $current_section['id']; ?>)" style="margin-bottom: 15px;">
                            + Add Question
                        </button>
                        
                        <?php
                        $stmt = $pdo->prepare(
                            "SELECT * FROM questions WHERE section_id = ? ORDER BY `order`"
                        );
                        $stmt->execute([$current_section['id']]);
                        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (empty($questions)): ?>
                            <p style="color: #999;">No questions yet. Click 'Add Question' to create one.</p>
                        <?php else: ?>
                            <div class="content-list">
                                <?php foreach ($questions as $q): ?>
                                    <div class="content-list-item">
                                        <div class="content-list-item-info">
                                            <h4><?php echo htmlspecialchars(substr($q['question_text'], 0, 50)); ?>...</h4>
                                            <p><?php echo ucfirst($q['question_type']); ?> • <?php echo $q['points']; ?> points</p>
                                        </div>
                                        <div class="content-list-item-actions">
                                            <a href="course-editor.php?id=<?php echo $course_id; ?>&action=edit_question&question_id=<?php echo $q['id']; ?>" class="btn-edit">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Question Editor -->
            <?php if ($action === 'edit_question' && $current_question): ?>
                <h2>Edit Question</h2>
                
                <form method="POST" class="form-section">
                    <h3>Question Details</h3>
                    <input type="hidden" name="type" value="edit_section">
                    <input type="hidden" name="section_id" value="<?php echo $current_question['section_id']; ?>">
                    
                    <div class="form-group">
                        <label for="q_text">Question Text *</label>
                        <textarea id="q_text" name="question_text" required><?php echo htmlspecialchars($current_question['question_text']); ?></textarea>
                    </div>
                    
                    <div class="form-group-inline">
                        <div class="form-group">
                            <label for="q_type">Question Type</label>
                            <select id="q_type" name="question_type">
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
                        <button type="button" class="btn-secondary" onclick="window.history.back()">Back</button>
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
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleSections(element) {
            const list = element.nextElementSibling;
            if (list) {
                list.classList.toggle('active');
            }
        }
        
        function addChapter() {
            const title = prompt('Chapter Title:');
            if (title) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="add_chapter">
                    <input type="hidden" name="title" value="${title}">
                    <input type="hidden" name="order" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editChapter(chapterId) {
            const title = prompt('Chapter Title:');
            if (title) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="edit_chapter">
                    <input type="hidden" name="chapter_id" value="${chapterId}">
                    <input type="hidden" name="title" value="${title}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteChapter(chapterId) {
            if (confirm('Delete this chapter and all its sections?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="delete_chapter">
                    <input type="hidden" name="chapter_id" value="${chapterId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function addSection(chapterId) {
            const title = prompt('Section Title:');
            if (title) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="add_section">
                    <input type="hidden" name="chapter_id" value="${chapterId}">
                    <input type="hidden" name="title" value="${title}">
                    <input type="hidden" name="section_type" value="video">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteSection(sectionId) {
            if (confirm('Delete this section?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="type" value="delete_section">
                    <input type="hidden" name="section_id" value="${sectionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
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
        
        function updateSectionType(type) {
            document.getElementById('video-fields').style.display = type === 'video' ? 'block' : 'none';
            document.getElementById('content-field').style.display = ['reading', 'assignment'].includes(type) ? 'block' : 'none';
        }
    </script>
</body>
</html>
