<?php
/**
 * Admin Courses Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CourseModel.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$courseModel = new CourseModel($pdo);
$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $instructor_name = trim($_POST['instructor_name'] ?? '');
        $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
        $duration_hours = !empty($_POST['duration_hours']) ? (int)$_POST['duration_hours'] : null;
        
        if (empty($title)) {
            $error = 'Course title is required';
        } else {
            try {
                $course_id = $courseModel->create([
                    'title' => $title,
                    'description' => $description,
                    'instructor_name' => $instructor_name,
                    'difficulty_level' => $difficulty_level,
                    'duration_hours' => $duration_hours,
                    'status' => COURSE_DRAFT,
                    'created_by' => $user['id']
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'CREATE', 'course', $course_id, 'Created course: ' . $title);
                
                header('Location: courses.php?message=Course created successfully. You can now add chapters and sections.');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $instructor_name = trim($_POST['instructor_name'] ?? '');
        $difficulty_level = $_POST['difficulty_level'] ?? 'beginner';
        $duration_hours = !empty($_POST['duration_hours']) ? (int)$_POST['duration_hours'] : null;
        $status = $_POST['status'] ?? COURSE_DRAFT;
        
        if (empty($title)) {
            $error = 'Course title is required';
        } else {
            try {
                $courseModel->update($id, [
                    'title' => $title,
                    'description' => $description,
                    'instructor_name' => $instructor_name,
                    'difficulty_level' => $difficulty_level,
                    'duration_hours' => $duration_hours,
                    'status' => $status
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'UPDATE', 'course', $id, 'Updated course: ' . $title);
                
                header('Location: courses.php?message=Course updated successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $course = $courseModel->findById($id);
        
        if ($course) {
            try {
                $courseModel->delete($id);
                Utils::auditLog($pdo, $user['id'], 'DELETE', 'course', $id, 'Deleted course: ' . $course['title']);
                
                header('Location: courses.php?message=Course deleted successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'assign' && isset($_POST['course_id'])) {
        $course_id = (int)$_POST['course_id'];
        $organization_ids = $_POST['organization_ids'] ?? [];
        
        try {
            // Remove old assignments
            $stmt = $pdo->prepare("DELETE FROM organization_courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            // Add new assignments
            foreach ($organization_ids as $org_id) {
                $stmt = $pdo->prepare(
                    "INSERT INTO organization_courses (organization_id, course_id, assigned_by)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([(int)$org_id, $course_id, $user['id']]);
            }
            
            Utils::auditLog($pdo, $user['id'], 'ASSIGN', 'course', $course_id, 'Assigned course to organizations');
            
            header('Location: courses.php?message=Course assignments updated successfully');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get message from query parameter
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $total = $courseModel->count();
    $pagination = Utils::getPagination($page, $total);
    $courses = $courseModel->findAll($pagination['limit'], $pagination['offset']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses Management - Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="organizations.php">Organizations</a></li>
                    <li class="active"><a href="courses.php">Courses</a></li>
                    <li><a href="users.php">Users</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>Courses Management</h1>
                <a href="courses.php?action=create" class="btn btn-primary">+ Create New Course</a>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <section class="content-section">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Instructor</th>
                                    <th>Difficulty</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo ucfirst($course['difficulty_level']); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($course['status']); ?>"><?php echo ucfirst($course['status']); ?></span></td>
                                        <td><?php echo Utils::formatDate($course['created_at']); ?></td>
                                        <td>
                                            <a href="course-editor.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Manage</a>
                                            <a href="courses.php?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="courses.php?action=assign&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Assign</a>
                                            <a href="courses.php?action=delete&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            
            <?php elseif ($action === 'create'): ?>
                <section class="content-section">
                    <h2>Create New Course</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="title">Course Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="instructor_name">Instructor Name</label>
                            <input type="text" id="instructor_name" name="instructor_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="difficulty_level">Difficulty Level</label>
                            <select id="difficulty_level" name="difficulty_level">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_hours">Duration (hours)</label>
                            <input type="number" id="duration_hours" name="duration_hours" min="1">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Course</button>
                            <a href="courses.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            
            <?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
                <?php
                    $course = $courseModel->findById((int)$_GET['id']);
                    if (!$course) {
                        header('Location: courses.php?error=Course not found');
                        exit;
                    }
                ?>
                <section class="content-section">
                    <h2>Edit Course</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="title">Course Title *</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                        </div>
                        
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
                        
                        <div class="form-group">
                            <label for="duration_hours">Duration (hours)</label>
                            <input type="number" id="duration_hours" name="duration_hours" value="<?php echo htmlspecialchars($course['duration_hours'] ?? ''); ?>" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo $course['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $course['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $course['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Course</button>
                            <a href="courses.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            
            <?php elseif ($action === 'assign' && isset($_GET['id'])): ?>
                <?php
                    $course = $courseModel->findById((int)$_GET['id']);
                    if (!$course) {
                        header('Location: courses.php?error=Course not found');
                        exit;
                    }
                    
                    // Get assigned organizations
                    $stmt = $pdo->prepare("SELECT organization_id FROM organization_courses WHERE course_id = ?");
                    $stmt->execute([$course['id']]);
                    $assigned_orgs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <section class="content-section">
                    <h2>Assign Course: <?php echo htmlspecialchars($course['title']); ?></h2>
                    <form method="POST" class="form">
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        
                        <div class="form-group">
                            <label>Select Organizations</label>
                            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">
                                <?php
                                    $organizations = $userModel->findByRole(ROLE_ORGANIZATION);
                                    foreach ($organizations as $org) {
                                        $checked = in_array($org['id'], $assigned_orgs) ? 'checked' : '';
                                        echo '<div style="margin-bottom: 10px;">
                                            <input type="checkbox" id="org_' . $org['id'] . '" name="organization_ids[]" value="' . $org['id'] . '" ' . $checked . '>
                                            <label for="org_' . $org['id'] . '" style="display: inline; font-weight: normal;">
                                                ' . htmlspecialchars($org['organization_name']) . '
                                            </label>
                                        </div>';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="action" value="assign" class="btn btn-primary">Update Assignments</button>
                            <a href="courses.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
