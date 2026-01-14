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
    } elseif ($action === 'delete' && isset($_GET['id'])) {
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

// Get all organization users
$organizations = $userModel->findByRole(ROLE_ORGANIZATION);

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
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>The Steeper Climb</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="courses.php">Courses</a>
        <a href="organizations.php" class="active">Organizations</a>
        <a href="students.php">Students</a>
        <a href="settings.php">Settings</a>
        <a href="<?php echo APP_URL; ?>/public/logout.php">Logout</a>
    </nav>
</div>

<div class="main-content">
    <div class="header">
        <h1><?php echo $action === 'create' ? 'Create Organization' : ($action === 'edit' ? 'Edit Organization' : 'Organizations'); ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Form -->
        <div class="content-section">
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
        </div>

    <?php else: ?>
        <!-- List View -->
        <div class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2>Organization Accounts</h2>
                <a href="organizations.php?action=create" class="btn btn-primary">+ Create Organization</a>
            </div>

            <?php if (empty($organizations)): ?>
                <p style="text-align: center; color: #666;">No organizations found. <a href="organizations.php?action=create">Create one now</a>.</p>
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

                        <div class="org-actions">
                            <a href="organizations.php?action=edit&id=<?php echo $org['id']; ?>" class="btn-edit">Edit</a>
                            <a href="organizations.php?action=delete&id=<?php echo $org['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this organization?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
