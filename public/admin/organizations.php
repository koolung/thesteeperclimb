<?php
/**
 * Admin Organization Users Management
 * Manages organization user accounts
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';
require_once __DIR__ . '/../../src/Utils/Mailer.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$userModel = new UserModel($pdo);
$adminUser = Auth::getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $organization_name = trim($_POST['organization_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $organization_website = trim($_POST['organization_website'] ?? '');
        $organization_description = trim($_POST['organization_description'] ?? '');
        
        if (empty($email) || empty($organization_name) || empty($first_name) || empty($last_name)) {
            $error = 'Email, organization name, first name, and last name are required';
        } else {
            try {
                // Check if email already exists
                if ($userModel->findByEmail($email)) {
                    $error = 'An account with this email already exists';
                } else {
                    // Create organization user
                    $tempPassword = bin2hex(random_bytes(16));
                    $userId = $userModel->create([
                        'email' => $email,
                        'password_hash' => password_hash($tempPassword, PASSWORD_BCRYPT),
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'role' => ROLE_ORGANIZATION,
                        'status' => STATUS_ACTIVE,
                        'phone' => $phone,
                        'organization_name' => $organization_name,
                        'organization_description' => $organization_description,
                        'organization_website' => $organization_website,
                        'organization_address' => $address,
                        'organization_city' => $city,
                        'organization_state' => $state,
                        'organization_postal_code' => $postal_code,
                        'organization_country' => $country,
                        'organization_contact_person' => $contact_person,
                        'organization_contact_email' => $contact_email,
                        'organization_contact_phone' => $contact_phone,
                        'created_by' => $adminUser['id']
                    ]);
                    
                    Utils::auditLog($pdo, $adminUser['id'], 'CREATE', 'user', $userId, 'Created organization user: ' . $organization_name);
                    
                    // Send welcome email
                    $setupLink = APP_URL . '/public/setup/password-reset.php?org_id=' . $userId . '&email=' . urlencode($email);
                    $emailSent = Utils\Mailer::sendOrganizationWelcome($email, $organization_name, $contact_person, $setupLink);
                    
                    if ($emailSent) {
                        $message = 'Organization account created successfully. Welcome email sent to ' . htmlspecialchars($email);
                    } else {
                        $message = 'Organization account created successfully, but welcome email could not be sent.';
                    }
                    
                    header('Location: organizations.php?message=' . urlencode($message));
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $organization_name = trim($_POST['organization_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $organization_website = trim($_POST['organization_website'] ?? '');
        $organization_description = trim($_POST['organization_description'] ?? '');
        $status = $_POST['status'] ?? STATUS_ACTIVE;
        
        if (empty($organization_name) || empty($first_name) || empty($last_name)) {
            $error = 'Organization name, first name, and last name are required';
        } else {
            try {
                $userModel->update($id, [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'status' => $status,
                    'organization_name' => $organization_name,
                    'organization_description' => $organization_description,
                    'organization_website' => $organization_website,
                    'organization_address' => $address,
                    'organization_city' => $city,
                    'organization_state' => $state,
                    'organization_postal_code' => $postal_code,
                    'organization_country' => $country,
                    'organization_contact_person' => $contact_person,
                    'organization_contact_email' => $contact_email,
                    'organization_contact_phone' => $contact_phone
                ]);
                
                Utils::auditLog($pdo, $adminUser['id'], 'UPDATE', 'user', $id, 'Updated organization: ' . $organization_name);
                
                $message = 'Organization updated successfully';
                header('Location: organizations.php?message=' . urlencode($message));
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Handle delete action (moved outside POST block to handle GET requests)
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $org = $userModel->findById($id);
        if ($org && $org['role'] === ROLE_ORGANIZATION) {
            $userModel->delete($id);
            Utils::auditLog($pdo, $adminUser['id'], 'DELETE', 'user', $id, 'Deleted organization: ' . $org['organization_name']);
            
            $message = 'Organization deleted successfully';
            header('Location: organizations.php?message=' . urlencode($message));
            exit;
        } else {
            $error = 'Organization not found';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get organization user to edit
$editOrg = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editOrg = $userModel->findById((int)$_GET['id']);
    if (!$editOrg || $editOrg['role'] !== ROLE_ORGANIZATION) {
        $error = 'Organization not found';
        $action = 'list';
    }
}

// Helper function to get assigned courses for an organization
function getOrganizationCourses($pdo, $org_id) {
    $stmt = $pdo->prepare(
        "SELECT c.* FROM courses c
         INNER JOIN organization_courses oc ON c.id = oc.course_id
         WHERE oc.organization_id = ?
         ORDER BY c.title ASC"
    );
    $stmt->execute([$org_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all organization users with search and filter
if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $search = trim($_GET['search'] ?? '');
    $status_filter = $_GET['status'] ?? '';
    
    // Build query with search and filters
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(organization_name LIKE ? OR email LIKE ? OR organization_contact_person LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($status_filter) {
        $where[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where[] = "role = ?";
    $params[] = ROLE_ORGANIZATION;
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $pagination = Utils::getPagination($page, $total);
    $limit = (int)$pagination['limit'];
    $offset = (int)$pagination['offset'];
    
    // Get filtered and paginated organizations
    $sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $organizations = $userModel->findByRole(ROLE_ORGANIZATION);
    $search = '';
    $status_filter = '';
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Organizations - Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <style>
        .org-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .org-card h3 {
            margin-top: 0;
            color: #333;
        }

        .org-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
            font-size: 14px;
        }

        .org-info p {
            margin: 5px 0;
            color: #666;
        }

        .org-info strong {
            color: #333;
        }

        .org-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .org-actions a, .org-actions button {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-edit:hover {
            background: #5568d3;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .form-section h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .org-courses {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .org-courses h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 15px;
            font-weight: 600;
        }

        .courses-list {
            display: grid;
            gap: 10px;
        }

        .course-item {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .course-item-info {
            flex: 1;
        }

        .course-item-title {
            font-weight: 600;
            color: #333;
            margin: 0 0 4px 0;
        }

        .course-item-meta {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        .course-status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .course-status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .course-status-published {
            background: #d4edda;
            color: #155724;
        }

        .no-courses {
            color: #666;
            font-size: 13px;
            font-style: italic;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
    </style>
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
                    <li class="active"><a href="organizations.php">Organizations</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="users.php">Users</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']); ?></p>
                <small><?php echo htmlspecialchars($adminUser['email']); ?></small>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1><?php echo $action === 'create' ? 'Create Organization' : ($action === 'edit' ? 'Edit Organization' : 'Organizations'); ?></h1>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($action === 'create' || $action === 'edit'): ?>
                <section class="content-section">
                    <form method="POST" action="organizations.php?action=<?php echo $action; ?><?php if ($action === 'edit') echo '&id=' . $editOrg['id']; ?>">
                        <div class="form-grid">
                            <!-- Contact Information -->
                            <div class="form-section">
                                <h4>Contact Information</h4>
                                <div class="form-group">
                                    <label>Email Address *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($editOrg['email'] ?? ''); ?>" <?php echo $action === 'edit' ? 'readonly' : 'required'; ?>>
                                </div>
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($editOrg['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($editOrg['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($editOrg['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Organization Information -->
                    <div class="form-section">
                        <h4>Organization Details</h4>
                        <div class="form-group">
                            <label>Organization Name *</label>
                            <input type="text" name="organization_name" value="<?php echo htmlspecialchars($editOrg['organization_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="organization_website" value="<?php echo htmlspecialchars($editOrg['organization_website'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($editOrg['organization_contact_person'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($editOrg['organization_contact_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($editOrg['organization_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="form-section">
                    <h4>Address Information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($editOrg['organization_address'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($editOrg['organization_city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>State/Province</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($editOrg['organization_state'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" value="<?php echo htmlspecialchars($editOrg['organization_postal_code'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($editOrg['organization_country'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-section">
                    <h4>Description</h4>
                    <div class="form-group">
                        <label>Organization Description</label>
                        <textarea name="organization_description" rows="5"><?php echo htmlspecialchars($editOrg['organization_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <?php if ($action === 'edit'): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo $editOrg['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $editOrg['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $editOrg['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                <?php endif; ?>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'create' ? 'Create Organization' : 'Update Organization'; ?></button>
                        <a href="organizations.php" class="btn btn-secondary">Cancel</a>
                    </div>
                    </form>
                </section>

            <?php else: ?>
                <!-- List View -->
                <section class="content-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2>Organization Accounts</h2>
                        <a href="organizations.php?action=create" class="btn btn-primary">+ Create Organization</a>
                    </div>
                    
                    <!-- Search and Filter Bar -->
                    <div style="margin-bottom: 20px; display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                        <div>
                            <form method="GET" style="display: flex; gap: 10px;">
                                <input type="hidden" name="action" value="list">
                                <input type="text" name="search" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Search</button>
                                <?php if ($search || $status_filter): ?>
                                    <a href="organizations.php" class="btn btn-secondary" style="padding: 10px 20px;">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <form method="GET" style="display: flex; gap: 5px;">
                            <input type="hidden" name="action" value="list">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <select name="status" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px;">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </form>
                    </div>
                    
                    <!-- Search Results Info -->
                    <?php if ($search || $status_filter): ?>
                        <div style="margin-bottom: 20px; padding: 10px; background: #e8f4f8; border-radius: 5px; font-size: 13px; color: #0066cc;">
                            <?php 
                            $filter_text = [];
                            if ($search) $filter_text[] = "name/email containing '" . htmlspecialchars($search) . "'";
                            if ($status_filter) $filter_text[] = "status = " . ucfirst($status_filter);
                            echo "Showing organizations matching: " . implode(" and ", $filter_text) . " (" . $total . " results)";
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($organizations)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No organizations found. <?php if ($search || $status_filter): ?>Try adjusting your search or filters.<?php else: ?><a href="organizations.php?action=create">Create one now</a>.<?php endif; ?></p>
            <?php else: ?>
                    <?php foreach ($organizations as $org): ?>
                        <div class="org-card">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h3><?php echo htmlspecialchars($org['organization_name']); ?></h3>
                                    <p style="margin: 0; color: #667eea; font-weight: 500;"><?php echo htmlspecialchars($org['email']); ?></p>
                                </div>
                                <span class="status-badge status-<?php echo $org['status']; ?>"><?php echo ucfirst($org['status']); ?></span>
                            </div>

                            <div class="org-info">
                                <div>
                                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($org['organization_contact_person'] ?: '-'); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($org['phone'] ?: '-'); ?></p>
                                    <p><strong>City:</strong> <?php echo htmlspecialchars($org['organization_city'] ?: '-'); ?></p>
                                </div>
                                <div>
                                    <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($org['organization_contact_email'] ?: '-'); ?></p>
                                    <p><strong>Website:</strong> <?php echo htmlspecialchars($org['organization_website'] ?: '-'); ?></p>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($org['organization_country'] ?: '-'); ?></p>
                                </div>
                            </div>

                            <?php if ($org['organization_description']): ?>
                                <p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #eee; color: #666; font-size: 14px;">
                                    <?php echo htmlspecialchars(substr($org['organization_description'], 0, 150)); ?>...
                                </p>
                            <?php endif; ?>

                            <!-- Assigned Courses -->
                            <?php 
                            $courses = getOrganizationCourses($pdo, $org['id']);
                            ?>
                            <div class="org-courses">
                                <h4>📚 Assigned Courses (<?php echo count($courses); ?>)</h4>
                                <?php if (empty($courses)): ?>
                                    <div class="no-courses">No courses assigned yet</div>
                                <?php else: ?>
                                    <div class="courses-list">
                                        <?php foreach ($courses as $course): ?>
                                            <div class="course-item">
                                                <div class="course-item-info">
                                                    <p class="course-item-title"><?php echo htmlspecialchars($course['title']); ?></p>
                                                    <p class="course-item-meta">
                                                        <?php if ($course['instructor_name']): ?>
                                                            👤 <?php echo htmlspecialchars($course['instructor_name']); ?> • 
                                                        <?php endif; ?>
                                                        <?php if ($course['duration_hours']): ?>
                                                            ⏱️ <?php echo htmlspecialchars($course['duration_hours']); ?> hours
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <span class="course-status-badge course-status-<?php echo $course['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $course['status'])); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="org-actions">
                                <a href="organizations.php?action=edit&id=<?php echo $org['id']; ?>" class="btn-edit">Edit</a>
                                <a href="organizations.php?action=delete&id=<?php echo $org['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this organization?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
            <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
