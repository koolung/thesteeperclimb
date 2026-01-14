<?php
/**
 * Admin Organizations Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/OrganizationModel.php';
require_once __DIR__ . '/../../src/Models/UserModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';
require_once __DIR__ . '/../../src/Utils/Mailer.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_ADMIN);

$orgModel = new OrganizationModel($pdo);
$userModel = new UserModel($pdo);
$user = Auth::getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            try {
                $org_id = $orgModel->create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'contact_person' => $contact_person,
                    'status' => STATUS_ACTIVE,
                    'created_by' => $user['id']
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'CREATE', 'organization', $org_id, 'Created organization: ' . $name);
                
                // Send welcome email to organization
                $setupLink = APP_URL . '/public/setup/password-reset.php?org_id=' . $org_id . '&email=' . urlencode($email);
                $emailSent = Utils\Mailer::sendOrganizationWelcome($email, $name, $contact_person, $setupLink);
                
                if ($emailSent) {
                    $message = 'Organization created successfully. Welcome email sent to ' . htmlspecialchars($email);
                } else {
                    $message = 'Organization created successfully, but welcome email could not be sent. Organization may need to be contacted manually.';
                }
                
                header('Location: organizations.php?message=' . urlencode($message));
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $status = $_POST['status'] ?? STATUS_ACTIVE;
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            try {
                $orgModel->update($id, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'contact_person' => $contact_person,
                    'status' => $status
                ]);
                
                Utils::auditLog($pdo, $user['id'], 'UPDATE', 'organization', $id, 'Updated organization: ' . $name);
                
                header('Location: organizations.php?message=Organization updated successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $org = $orgModel->findById($id);
        
        if ($org) {
            try {
                $orgModel->delete($id);
                Utils::auditLog($pdo, $user['id'], 'DELETE', 'organization', $id, 'Deleted organization: ' . $org['name']);
                
                header('Location: organizations.php?message=Organization deleted successfully');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Get message from query parameter
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $pagination = Utils::getPagination($page, $orgModel->countActive());
    $organizations = $orgModel->getActive($pagination['limit'], $pagination['offset']);
    
    // Add stats to each organization
    foreach ($organizations as &$org) {
        $org = $orgModel->getWithStats($org['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations - Admin Dashboard</title>
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
                    <li class="active"><a href="organizations.php">Organizations</a></li>
                    <li><a href="courses.php">Courses</a></li>
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
                <h1>Organizations Management</h1>
                <a href="organizations.php?action=create" class="btn btn-primary">+ Add New Organization</a>
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
                                    <th>Organization Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Students</th>
                                    <th>Courses</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($organizations as $org): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($org['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($org['email']); ?></td>
                                        <td><?php echo htmlspecialchars($org['contact_person'] ?? 'N/A'); ?></td>
                                        <td><?php echo $org['student_count']; ?></td>
                                        <td><?php echo $org['course_count']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($org['status']); ?>"><?php echo ucfirst($org['status']); ?></span></td>
                                        <td>
                                            <a href="organizations.php?action=edit&id=<?php echo $org['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="organizations.php?action=delete&id=<?php echo $org['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            
            <?php elseif ($action === 'create'): ?>
                <section class="content-section">
                    <h2>Create New Organization</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Organization Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" id="contact_person" name="contact_person">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Create Organization</button>
                            <a href="organizations.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            
            <?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
                <?php
                    $org = $orgModel->findById((int)$_GET['id']);
                    if (!$org) {
                        header('Location: organizations.php?error=Organization not found');
                        exit;
                    }
                ?>
                <section class="content-section">
                    <h2>Edit Organization</h2>
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Organization Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($org['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($org['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($org['contact_person'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($org['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($org['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $org['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $org['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $org['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Organization</button>
                            <a href="organizations.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
