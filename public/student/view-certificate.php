<?php
/**
 * View Certificate
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

// Get certificate ID
$cert_id = (int)($_GET['id'] ?? 0);
if (!$cert_id) {
    header('Location: certificates.php');
    exit;
}

// Get certificate
$stmt = $pdo->prepare(
    "SELECT c.*, u.first_name, u.last_name, co.title as course_title
     FROM certificates c
     INNER JOIN users u ON c.student_id = u.id
     INNER JOIN courses co ON c.course_id = co.id
     WHERE c.id = ? AND c.student_id = ?"
);
$stmt->execute([$cert_id, $user['id']]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cert) {
    header('Location: certificates.php?error=Certificate not found');
    exit;
}

// Format the issued date
$issued_date = new DateTime($cert['issued_date']);
$formatted_date = $issued_date->format('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($cert['course_title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .certificate-container {
            background-image: url('<?php echo APP_URL; ?>/uploads/certificates/tsc_certificate.jpg');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        
        .certificate {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 60px 0;
            position: relative;
            background: transparent;
        }
        
        .certificate::before {
            display: none;
        }
        
        .certificate::after {
            display: none;
        }
        
        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .certificate-body {
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-top: 213px;
        }
        

        
        .recipient-name {
            font-size: 36px;
            font-weight: bold;
            color: #e8ce3b;
            margin: 20px 0;
        }
        
        .achievement-text {
            font-size: 12px;
            color: #cecece;
            margin: 0 15px 0 0;
            line-height: 1.5;
            width: 80%;
            margin: 0 auto;
        }
        
        .certificate-footer {
            display: grid;
            gap: 40px;
            margin-left: auto;
            margin-right: auto;
            width: fit-content;
        }
        .footer-item {
            text-align: center;
            font-size: 10px;
            margin-bottom: 35px;
        }
        
        .cert-number {
            margin-top: 62px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        
        .actions {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
        }
        
        @media print {
            @page {
                size: landscape;
                margin: 0;
            }
            
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .certificate-container {
                background-image: url('<?php echo APP_URL; ?>/uploads/certificates/tsc_certificate.jpg');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .actions {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .certificate {
                padding: 40px 30px;
            }

            .certificate-body {
                margin-top: 210px;
            }
            
            .certificate-title {
                font-size: 32px;
            }
            
            .recipient-name {
                font-size: 50px;
            }
            
            .course-name {
                font-size: 14px;
            }
            
            .certificate-footer {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate">

            
            <div class="certificate-body">
                
                <div class="recipient-name" style="text-transform: capitalize;">
                    <?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?>
                </div>
                
                <p class="achievement-text">For successfully completing the course of '<i><?php echo htmlspecialchars($cert['course_title']); ?></i>' .</p>
                <p class="achievement-text"> In this certificate program, <i><?php echo htmlspecialchars($cert['first_name']); ?> </i>
                        has learned essential skills and knowledge, demonstrating commitment to personal and professional growth. </p>
            
            </div>
            
            <div>
                <div class="certificate-footer">
                    <div class="footer-item" style="color: #cecece;">
                        <div class="signature-label">Issuing Date</div>
                        <div class="signature-line" style="border-color: #cecece; border: 1px solid; width: 100px;"></div>
                        <div style="color: #cecece;">
                            <?php echo $formatted_date; ?>
                        </div>
                    </div>
                </div>
                
                <div class="cert-number">
                    <div class="cert-number-label">Certificate Number: <?php echo htmlspecialchars($cert['certificate_number']); ?></div>
                    <div class="cert-number-value">www.thesteeperclimb.ca</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Certificate</button>
        <a href="certificates.php" class="btn btn-secondary">← Back to Certificates</a>
    </div>
    
    <script>
        // Auto-load certificate on page load
        window.addEventListener('load', function() {
            // You can add auto-print functionality here if desired
            // window.print();
        });
    </script>
</body>
</html>
