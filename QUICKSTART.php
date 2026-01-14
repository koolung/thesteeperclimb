<?php
/**
 * Quick Start Guide
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Start Guide - The Steeper Climb</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 8px;
            margin-bottom: 40px;
            text-align: center;
        }
        
        header h1 {
            margin-bottom: 10px;
            font-size: 36px;
        }
        
        header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .guide-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .guide-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .guide-section h3 {
            color: #764ba2;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        
        .step {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .step strong {
            color: #667eea;
        }
        
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #c7254e;
        }
        
        .link-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .link-button:hover {
            transform: translateY(-2px);
        }
        
        .credentials {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .credentials h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .credentials p {
            color: #856404;
            margin: 5px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background: #f9f9f9;
            font-weight: 600;
            color: #667eea;
        }
        
        ul, ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        li {
            margin-bottom: 8px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .feature-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        
        .feature-card h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .feature-card ul {
            margin-left: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>The Steeper Climb</h1>
            <p>Online Course Platform - Quick Start Guide</p>
        </header>
        
        <!-- Step 1: Installation -->
        <section class="guide-section">
            <h2>1. Installation & Setup</h2>
            
            <h3>Prerequisites</h3>
            <ul>
                <li>XAMPP installed and running</li>
                <li>Apache and MySQL services active</li>
                <li>Project located at: <code>C:\xampp\htdocs\thesteeperclimb</code></li>
            </ul>
            
            <h3>Setup Steps</h3>
            <div class="step">
                <strong>Step 1: Create Database</strong>
                <p>Visit: <a href="http://localhost/thesteeperclimb/setup/install.php" class="link-button">Run Database Setup</a></p>
                <p>This will create the database and all necessary tables.</p>
            </div>
            
            <div class="step">
                <strong>Step 2: Create Admin Account</strong>
                <p>After database setup, visit: <a href="http://localhost/thesteeperclimb/setup/create-admin.php" class="link-button">Create Admin Account</a></p>
                <p>Fill in your admin credentials and submit the form.</p>
            </div>
            
            <div class="step">
                <strong>Step 3: Login</strong>
                <p>Visit: <a href="http://localhost/thesteeperclimb/public/login.php" class="link-button">Go to Login</a></p>
                <p>Login with your admin credentials.</p>
            </div>
        </section>
        
        <!-- Roles & Features -->
        <section class="guide-section">
            <h2>2. User Roles & Features</h2>
            
            <h3>Three Main Roles</h3>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h4>👨‍💼 Admin</h4>
                    <ul>
                        <li>Manage organizations</li>
                        <li>Create courses</li>
                        <li>Assign courses</li>
                        <li>Manage all users</li>
                        <li>View system logs</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4>🏢 Organization</h4>
                    <ul>
                        <li>Manage own profile</li>
                        <li>View assigned courses</li>
                        <li>Add/manage students</li>
                        <li>View student progress</li>
                        <li>Generate reports</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h4>👨‍🎓 Student</h4>
                    <ul>
                        <li>Access courses</li>
                        <li>Track progress</li>
                        <li>Complete sections</li>
                        <li>Earn certificates</li>
                        <li>View history</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <!-- Admin Workflow -->
        <section class="guide-section">
            <h2>3. Admin Workflow</h2>
            
            <h3>Getting Started as Admin</h3>
            
            <div class="step">
                <strong>Step 1: Login</strong>
                <p>Login with your admin credentials at <code>/public/login.php</code></p>
            </div>
            
            <div class="step">
                <strong>Step 2: Create Organizations</strong>
                <p>Go to Organizations → Add New Organization</p>
                <p>Fill in organization details and save</p>
            </div>
            
            <div class="step">
                <strong>Step 3: Create Courses</strong>
                <p>Go to Courses → Create New Course</p>
                <p>Add course title, description, and instructor information</p>
            </div>
            
            <div class="step">
                <strong>Step 4: Assign Courses</strong>
                <p>Go to Courses → Select Course → Assign</p>
                <p>Select organizations to receive this course</p>
            </div>
            
            <div class="step">
                <strong>Step 5: Manage Users</strong>
                <p>Go to Users → Add New User</p>
                <p>Create admin, organization, or student accounts</p>
            </div>
            
            <h3>Admin Dashboard Links</h3>
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>URL</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dashboard</td>
                        <td><code>/public/admin/dashboard.php</code></td>
                        <td>Overview & quick actions</td>
                    </tr>
                    <tr>
                        <td>Organizations</td>
                        <td><code>/public/admin/organizations.php</code></td>
                        <td>Manage organizations</td>
                    </tr>
                    <tr>
                        <td>Courses</td>
                        <td><code>/public/admin/courses.php</code></td>
                        <td>Create & manage courses</td>
                    </tr>
                    <tr>
                        <td>Users</td>
                        <td><code>/public/admin/users.php</code></td>
                        <td>Manage all users</td>
                    </tr>
                </tbody>
            </table>
        </section>
        
        <!-- Organization Workflow -->
        <section class="guide-section">
            <h2>4. Organization Workflow</h2>
            
            <h3>As an Organization Admin</h3>
            
            <div class="step">
                <strong>Step 1: Login</strong>
                <p>Login at <code>/public/login.php</code></p>
                <p>You'll be redirected to your Organization Dashboard</p>
            </div>
            
            <div class="step">
                <strong>Step 2: Add Students</strong>
                <p>Go to Students → Add New Student</p>
                <p>Create student accounts for your organization</p>
            </div>
            
            <div class="step">
                <strong>Step 3: Monitor Progress</strong>
                <p>Go to Reports to see student progress</p>
                <p>View course completion rates and certificates</p>
            </div>
            
            <h3>Organization Dashboard Links</h3>
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dashboard</td>
                        <td>Overview of your organization</td>
                    </tr>
                    <tr>
                        <td>Students</td>
                        <td>Add & manage student accounts</td>
                    </tr>
                    <tr>
                        <td>My Courses</td>
                        <td>View assigned courses</td>
                    </tr>
                    <tr>
                        <td>Reports</td>
                        <td>Student progress & analytics</td>
                    </tr>
                </tbody>
            </table>
        </section>
        
        <!-- Student Workflow -->
        <section class="guide-section">
            <h2>5. Student Workflow</h2>
            
            <h3>As a Student</h3>
            
            <div class="step">
                <strong>Step 1: Login</strong>
                <p>Login at <code>/public/login.php</code></p>
                <p>You'll be redirected to your Student Dashboard</p>
            </div>
            
            <div class="step">
                <strong>Step 2: View Available Courses</strong>
                <p>Go to "My Courses" to see all available courses</p>
                <p>Your organization admin has assigned these courses</p>
            </div>
            
            <div class="step">
                <strong>Step 3: Start Learning</strong>
                <p>Click a course to begin learning</p>
                <p>View course chapters and sections</p>
            </div>
            
            <div class="step">
                <strong>Step 4: Complete Sections</strong>
                <p>Watch videos and complete exercises</p>
                <p>Click "Mark as Complete" to track progress</p>
            </div>
            
            <div class="step">
                <strong>Step 5: Earn Certificates</strong>
                <p>Complete 100% of course sections</p>
                <p>Your certificate will be automatically generated</p>
            </div>
            
            <h3>Student Dashboard Links</h3>
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dashboard</td>
                        <td>Progress overview & statistics</td>
                    </tr>
                    <tr>
                        <td>My Courses</td>
                        <td>All available courses</td>
                    </tr>
                    <tr>
                        <td>Certificates</td>
                        <td>View earned certificates</td>
                    </tr>
                    <tr>
                        <td>Profile</td>
                        <td>Update profile & password</td>
                    </tr>
                </tbody>
            </table>
        </section>
        
        <!-- Key Features -->
        <section class="guide-section">
            <h2>6. Key System Features</h2>
            
            <h3>Progress Tracking</h3>
            <ul>
                <li>Automatic progress calculation</li>
                <li>Section completion tracking</li>
                <li>Percentage-based progress display</li>
                <li>Progress persists across sessions</li>
            </ul>
            
            <h3>Certificate System</h3>
            <ul>
                <li>Automatic certificate generation on 100% completion</li>
                <li>Unique certificate numbers</li>
                <li>Permanent storage (never deleted)</li>
                <li>Certificate verification possible</li>
            </ul>
            
            <h3>Security</h3>
            <ul>
                <li>Password hashing with bcrypt</li>
                <li>Session timeout protection</li>
                <li>Role-based access control</li>
                <li>Audit logging of all major actions</li>
                <li>Organization data isolation</li>
            </ul>
            
            <h3>Reporting</h3>
            <ul>
                <li>Student progress statistics</li>
                <li>Organization completion rates</li>
                <li>Certificate tracking</li>
                <li>User activity logs</li>
            </ul>
        </section>
        
        <!-- Common Tasks -->
        <section class="guide-section">
            <h2>7. Common Tasks</h2>
            
            <h3>As Admin: Create a Course</h3>
            <ol>
                <li>Login to admin dashboard</li>
                <li>Click "Courses" → "Create New Course"</li>
                <li>Fill in course details (title, description, etc.)</li>
                <li>Click "Create Course"</li>
                <li>Course is now ready to assign to organizations</li>
            </ol>
            
            <h3>As Admin: Add an Organization</h3>
            <ol>
                <li>Login to admin dashboard</li>
                <li>Click "Organizations" → "Add New Organization"</li>
                <li>Fill in organization details</li>
                <li>Click "Create Organization"</li>
                <li>Organization admin can now login</li>
            </ol>
            
            <h3>As Organization: Add a Student</h3>
            <ol>
                <li>Login to organization dashboard</li>
                <li>Click "Students" → "Add New Student"</li>
                <li>Fill in student details and password</li>
                <li>Click "Add Student"</li>
                <li>Student can now login and access courses</li>
            </ol>
            
            <h3>As Student: Complete a Course</h3>
            <ol>
                <li>Login to student dashboard</li>
                <li>Click "My Courses" and select a course</li>
                <li>View course structure (chapters and sections)</li>
                <li>Complete all sections</li>
                <li>Certificate will be automatically issued</li>
            </ol>
        </section>
        
        <!-- Troubleshooting -->
        <section class="guide-section">
            <h2>8. Troubleshooting</h2>
            
            <h3>Database Won't Connect</h3>
            <ul>
                <li>Ensure MySQL is running in XAMPP</li>
                <li>Check database credentials in <code>config/database.php</code></li>
                <li>Verify database exists: <code>CREATE DATABASE thesteeperclimb;</code></li>
            </ul>
            
            <h3>Can't Login</h3>
            <ul>
                <li>Verify email address is correct</li>
                <li>Check that user account status is "active"</li>
                <li>Reset password if forgotten</li>
            </ul>
            
            <h3>Permission Denied</h3>
            <ul>
                <li>Check user role and organization assignment</li>
                <li>Verify courses are assigned to your organization</li>
                <li>Ensure user status is "active"</li>
            </ul>
            
            <h3>File Upload Issues</h3>
            <ul>
                <li>Check uploads folder permissions (755)</li>
                <li>Verify file type is allowed</li>
                <li>Check file size doesn't exceed limit</li>
            </ul>
        </section>
        
        <!-- Next Steps -->
        <section class="guide-section">
            <h2>9. Next Steps</h2>
            
            <p>Now that you have your platform set up, here's what to do next:</p>
            
            <ol>
                <li><strong>Create Your First Course</strong> - Add educational content</li>
                <li><strong>Add Organizations</strong> - Onboard client organizations</li>
                <li><strong>Invite Students</strong> - Have organizations add their students</li>
                <li><strong>Monitor Progress</strong> - Track student engagement and completion</li>
                <li><strong>Review Certificates</strong> - See issued certificates in reports</li>
            </ol>
            
            <p style="margin-top: 30px; padding: 20px; background: #f0f4ff; border-radius: 8px;">
                <strong>💡 Pro Tip:</strong> Start with a small pilot program with one organization to test the platform, then expand to additional organizations.
            </p>
        </section>
        
        <!-- Support -->
        <section class="guide-section">
            <h2>10. Support & Documentation</h2>
            
            <p>For more information:</p>
            
            <ul>
                <li>Read the <strong>README.md</strong> file for complete documentation</li>
                <li>Check the <strong>Database Schema</strong> in <code>setup/database.sql</code></li>
                <li>Review <strong>Configuration</strong> in <code>config/config.php</code></li>
                <li>Check <strong>Audit Logs</strong> in admin panel for system activity</li>
            </ul>
            
            <div style="margin-top: 30px; padding: 20px; background: #efe; border-radius: 8px; border: 1px solid #28a745;">
                <strong>✓ Ready to Get Started?</strong><br><br>
                <a href="http://localhost/thesteeperclimb/public/login.php" class="link-button">Go to Login Page</a>
            </div>
        </section>
    </div>
</body>
</html>
