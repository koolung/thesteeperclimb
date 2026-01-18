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

$action = $_GET['action'] ?? 'list';
$search = trim($_GET['search'] ?? '');
$difficulty_filter = $_GET['difficulty'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get organization's courses with search and filters
$where = ["oc.organization_id = ?"];
$params = [$user['id']];

if ($search) {
    $where[] = "(c.title LIKE ? OR c.description LIKE ? OR c.instructor_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($difficulty_filter) {
    $where[] = "c.difficulty_level = ?";
    $params[] = $difficulty_filter;
}

if ($status_filter) {
    $where[] = "c.status = ?";
    $params[] = $status_filter;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Get courses
$sql = "SELECT c.* FROM courses c
        INNER JOIN organization_courses oc ON c.id = oc.course_id
        $whereClause
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
            
            <section class="content-section">
                <!-- Search and Filter Bar -->
                <div style="margin-bottom: 20px; display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <form method="GET" style="display: flex; gap: 10px;">
                            <input type="text" name="search" placeholder="Search by title, instructor..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Search</button>
                            <?php if ($search || $difficulty_filter || $status_filter): ?>
                                <a href="courses.php" class="btn btn-secondary" style="padding: 10px 20px;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </form>
                </div>
                
                <!-- Search Results Info -->
                <?php if ($search || $difficulty_filter || $status_filter): ?>
                    <div style="margin-bottom: 20px; padding: 10px; background: #e8f4f8; border-radius: 5px; font-size: 13px; color: #0066cc;">
                        <?php 
                        $filter_text = [];
                        if ($search) $filter_text[] = "title/instructor containing '" . htmlspecialchars($search) . "'";
                        if ($difficulty_filter) $filter_text[] = "difficulty = " . ucfirst($difficulty_filter);
                        if ($status_filter) $filter_text[] = "status = " . ucfirst($status_filter);
                        echo "Showing courses matching: " . implode(" and ", $filter_text) . " (" . count($courses) . " results)";
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($courses)): ?>
                    <div style="padding: 40px; text-align: center; color: #999;">
                        <p>No courses found. <?php if ($search || $difficulty_filter || $status_filter): ?>Try adjusting your search or filters.<?php else: ?>No courses assigned yet. Please contact your administrator.<?php endif; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Instructor</th>
                                    <th>Duration</th>
                                    <th>Students Enrolled</th>
                                    <th>Status</th>
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
                                        <td><?php echo $course['duration_hours'] ? $course['duration_hours'] . ' hrs' : 'N/A'; ?></td>
                                        <td><?php echo $enrollment; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($course['status']); ?>"><?php echo ucfirst($course['status']); ?></span></td>
                                        <td>
                                            <a href="course-editor.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                        </td>
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
