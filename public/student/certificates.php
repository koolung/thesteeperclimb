<?php
/**
 * Student Certificates
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/Auth.php';
require_once __DIR__ . '/../../src/Models/CertificateModel.php';
require_once __DIR__ . '/../../src/Utils/Utils.php';

$pdo = getMainDatabaseConnection();
Auth::initialize($pdo);
Auth::requireRole(ROLE_STUDENT);

$certModel = new CertificateModel($pdo);
$user = Auth::getCurrentUser();

// Get student's certificates
$certificates = $certModel->getStudentCertificates($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - The Steeper Climb</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>The Steeper Climb</h2>
                <p>Student Portal</p>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php">My Courses</a></li>
                    <li class="active"><a href="certificates.php">Certificates</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                </ul>
            </nav>
            
            <div class="user-info">
                <p><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <a href="<?php echo APP_URL; ?>/public/logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h1>My Certificates</h1>
            </header>
            
            <?php if (empty($certificates)): ?>
                <section class="content-section">
                    <p style="color: #999; text-align: center; padding: 40px;">
                        You haven't earned any certificates yet. Complete your courses to earn certificates!
                    </p>
                </section>
            <?php else: ?>
                <section class="content-section">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Certificate #</th>
                                    <th>Score</th>
                                    <th>Issued Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cert['course_title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cert['certificate_number']); ?></td>
                                        <td><?php echo $cert['score_percentage']; ?>%</td>
                                        <td><?php echo Utils::formatDate($cert['issued_date']); ?></td>
                                        <td>
                                            <a href="view-certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
