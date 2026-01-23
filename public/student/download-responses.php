<?php
/**
 * Download Quiz Responses as PDF
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_STUDENT);

$user = Auth::getCurrentUser();
$course_id = (int)($_GET['course_id'] ?? 0);
$section_id = (int)($_GET['section_id'] ?? 0);

if (!$course_id || !$section_id) {
    header('Location: courses.php?error=Invalid request');
    exit;
}

// Verify student has access to this course
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

// Get course and section details
$courseModel = new CourseModel($pdo);
$course = $courseModel->findById($course_id);

$stmt = $pdo->prepare(
    "SELECT s.* FROM sections s
     INNER JOIN chapters c ON s.chapter_id = c.id
     WHERE s.id = ? AND c.course_id = ?"
);
$stmt->execute([$section_id, $course_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section || $section['type'] !== 'quiz') {
    header('Location: course.php?id=' . $course_id . '&error=Invalid section');
    exit;
}

// Get questions and student answers
$stmt = $pdo->prepare(
    "SELECT * FROM questions WHERE section_id = ? ORDER BY `order`"
);
$stmt->execute([$section_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's answers
$stmt = $pdo->prepare(
    "SELECT question_id, answer_text, is_correct FROM student_answers 
     WHERE student_id = ? AND question_id IN (SELECT id FROM questions WHERE section_id = ?)"
);
$stmt->execute([$user['id'], $section_id]);
$answers_data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $answers_data[$row['question_id']] = $row;
}



// Generate HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            color: #667eea;
            font-size: 24px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 12px;
        }
        .question {
            page-break-inside: avoid;
            margin-bottom: 20px;
            padding: 12px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 3px;
        }
        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .question-points {
            color: #667eea;
            font-weight: 600;
            font-size: 12px;
        }
        .answer-section {
            background: white;
            padding: 10px;
            margin-top: 8px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }
        .answer-label {
            color: #666;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .answer-text {
            color: #333;
            padding: 8px;
            background: #fafafa;
            border-radius: 2px;
            word-wrap: break-word;
        }
        .unanswered {
            color: #999;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #999;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($course['title']) . '</h1>
        <p>Quiz: ' . htmlspecialchars($section['title']) . '</p>
        <p>Student: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</p>
        <p>Email: ' . htmlspecialchars($user['email']) . '</p>
        <p>Date: ' . date('F j, Y g:i A') . '</p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Your Responses</h3>';

// Add each question and answer
foreach ($questions as $index => $question) {
    $answered = isset($answers_data[$question['id']]) && !empty($answers_data[$question['id']]['answer_text']);
    
    $html .= '
        <div class="question">
            <div class="question-text">
                Q' . ($index + 1) . '. ' . htmlspecialchars($question['question_text']) . '
            </div>
            
            <div class="answer-section">';
    
    if ($answered) {
        $answer_data = $answers_data[$question['id']];
        
        // Get answer text
        $answer_text = $answer_data['answer_text'];
        
        // If it's a multiple choice or true/false, get the option text
        if (in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
            $stmt = $pdo->prepare("SELECT option_text FROM question_options WHERE id = ?");
            $stmt->execute([$answer_text]);
            $option = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($option) {
                $answer_text = $option['option_text'];
            }
        }
        
        $html .= '<div class="answer-text">' . htmlspecialchars($answer_text) . '</div>';
    } else {
        $html .= '<div class="answer-text unanswered">No answer provided</div>';
    }
    
    $html .= '
            </div>
        </div>';
}

$html .= '
    </div>
    
    <div class="footer">
        <p>This document was generated by The Steeper Climb Learning Management System</p>
        <p>Generated on ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';

// Generate PDF using built-in PHP functionality
// Using a simple approach: output as HTML that can be printed to PDF
// For better results, you could install mPDF or TCPDF

// Try to use mPDF if available, otherwise provide printable HTML
$use_mpdf = false;

// Check if mPDF is installed via composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    if (class_exists('Mpdf\Mpdf')) {
        $use_mpdf = true;
        try {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $filename = 'Quiz-Responses-' . date('Y-m-d-His') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (Exception $e) {
            // Fall back to HTML
            $use_mpdf = false;
        }
    }
}

// Fallback: Output as printable HTML
if (!$use_mpdf) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="quiz-responses-' . date('Y-m-d-His') . '.html"');
    echo $html;
    exit;
}
?>
