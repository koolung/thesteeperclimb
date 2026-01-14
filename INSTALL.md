# The Steeper Climb - Installation & Setup Guide

## Overview

This is a comprehensive online course platform built for Nancy MacLeod's educational initiatives. The system supports three main user roles: Admin, Organization, and Student, with complete course management, progress tracking, and certificate functionality.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Initial Configuration](#initial-configuration)
4. [Verification](#verification)
5. [Using the Platform](#using-the-platform)
6. [Troubleshooting](#troubleshooting)

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **Disk Space**: Minimum 100MB
- **RAM**: Minimum 512MB

### Local Development
- XAMPP 7.4+ (recommended)
- Modern web browser (Chrome, Firefox, Safari, Edge)
- File manager or FTP client

### PHP Extensions Required
- MySQLi
- PDO
- PDO MySQL
- JSON
- Filter
- Sessions

## Installation Steps

### Step 1: Verify System Requirements

1. Open your browser and navigate to:
   ```
   http://localhost/thesteeperclimb/verify.php
   ```

2. Check that all requirements are marked as "OK"

3. If any items are missing, install them before proceeding

### Step 2: Set Up the Database

1. Visit:
   ```
   http://localhost/thesteeperclimb/setup/install.php
   ```

2. Click the "Create Database" button

3. Wait for confirmation that the database and tables have been created

4. You should see a success message with a link to create the admin account

### Step 3: Create Admin Account

1. Click the link or navigate to:
   ```
   http://localhost/thesteeperclimb/setup/create-admin.php
   ```

2. Fill in the following information:
   - **First Name**: Your first name
   - **Last Name**: Your last name
   - **Email**: Your admin email address
   - **Password**: A strong password (must contain uppercase, number, and special character)
   - **Confirm Password**: Repeat your password

3. Click "Create Admin Account"

4. You should see a success message

### Step 4: Access the Platform

1. Navigate to:
   ```
   http://localhost/thesteeperclimb/public/login.php
   ```

2. Login with your admin credentials

3. You will be directed to the Admin Dashboard

## Initial Configuration

### Configure Database Connection

1. Edit `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'thesteeperclimb');
   ```

2. Save the file

### Configure Application Settings

1. Edit `config/config.php` to customize:
   - Application URL
   - Session timeout
   - Password requirements
   - Certificate passing threshold (default: 70%)
   - Upload settings

## Verification

### Check Installation

1. Visit `http://localhost/thesteeperclimb/verify.php` to verify all requirements

2. Visit `http://localhost/thesteeperclimb` to see the home page

3. Attempt to login with your admin credentials

## Using the Platform

### First Time Setup Checklist

- [ ] Create at least one organization
- [ ] Create at least one course with chapters and sections
- [ ] Assign the course to the organization
- [ ] Create a student account in the organization
- [ ] Test the student workflow by accessing a course

### Main URLs

**Admin Interface**
- Dashboard: `http://localhost/thesteeperclimb/public/admin/dashboard.php`
- Organizations: `http://localhost/thesteeperclimb/public/admin/organizations.php`
- Courses: `http://localhost/thesteeperclimb/public/admin/courses.php`
- Users: `http://localhost/thesteeperclimb/public/admin/users.php`

**Organization Interface**
- Dashboard: `http://localhost/thesteeperclimb/public/organization/dashboard.php`
- Students: `http://localhost/thesteeperclimb/public/organization/students.php`
- Courses: `http://localhost/thesteeperclimb/public/organization/courses.php`
- Reports: `http://localhost/thesteeperclimb/public/organization/reports.php`

**Student Interface**
- Dashboard: `http://localhost/thesteeperclimb/public/student/dashboard.php`
- Courses: `http://localhost/thesteeperclimb/public/student/courses.php`
- Certificates: `http://localhost/thesteeperclimb/public/student/certificates.php`
- Profile: `http://localhost/thesteeperclimb/public/student/profile.php`

**Common**
- Login: `http://localhost/thesteeperclimb/public/login.php`
- Logout: `http://localhost/thesteeperclimb/public/logout.php`

### First Course Creation Workflow

1. **Login as Admin**
   - Go to `http://localhost/thesteeperclimb/public/login.php`
   - Enter your admin credentials

2. **Create an Organization**
   - Click "Organizations" in the sidebar
   - Click "+ Add Organization"
   - Fill in organization details
   - Click "Create Organization"

3. **Create a Course**
   - Click "Courses" in the sidebar
   - Click "+ Create New Course"
   - Fill in course details:
     - Title (required)
     - Description
     - Instructor name
     - Difficulty level
     - Duration
   - Click "Create Course"

4. **Add Course Structure** (Optional in v1.0)
   - Courses can be managed later to add:
     - Chapters
     - Sections
     - Videos
     - Quizzes

5. **Assign Course to Organization**
   - Find your course in the courses list
   - Click "Assign"
   - Check the organization you created
   - Click "Update Assignments"

6. **Create a Student in the Organization**
   - Go to "Organizations"
   - Click on your organization (or use organization login)
   - Go to "Students"
   - Click "+ Add New Student"
   - Fill in student details
   - Click "Add Student"

7. **Test Student Login**
   - Logout from admin (click "Logout")
   - Login with the student credentials you just created
   - You should see the course in "My Courses"
   - Click the course to view its structure

### Organization Workflow

**As an Organization Admin:**

1. Login with organization credentials
2. Go to "Students" to add/manage student accounts
3. Go to "My Courses" to see assigned courses
4. Go to "Reports" to see student progress

### Student Workflow

**As a Student:**

1. Login with student credentials
2. View "My Courses" dashboard
3. Click a course to start learning
4. Complete sections (each section marked complete)
5. Progress is automatically tracked
6. Upon 100% completion, a certificate is automatically issued
7. View certificates in "Certificates" section

## Troubleshooting

### Database Connection Errors

**Problem**: "Database connection failed"

**Solutions**:
1. Ensure MySQL is running in XAMPP Control Panel
2. Verify database credentials in `config/database.php`
3. Check that the database name is `thesteeperclimb`
4. Try accessing phpMyAdmin to test database connection

### Login Issues

**Problem**: Cannot login with correct credentials

**Solutions**:
1. Verify the email address is exactly correct
2. Check that the user account status is "active" (not "inactive" or "suspended")
3. Clear browser cookies and try again
4. Check that you're using the correct role (admin, organization, student)

### Permission Denied

**Problem**: Getting 403 "Unauthorized" error

**Solutions**:
1. Verify your user role matches the page you're accessing
2. Check that organization ID is set correctly for students/organizations
3. Verify course is assigned to your organization
4. Check user account status is "active"

### File Upload Issues

**Problem**: Cannot upload videos or files

**Solutions**:
1. Check uploads directory exists: `thesteeperclimb/uploads/`
2. Ensure uploads directory is writable (chmod 755)
3. Verify file size doesn't exceed limit (default: 5MB)
4. Check file type is allowed

### Session Timeout

**Problem**: Getting logged out frequently

**Solutions**:
1. Edit `config/config.php`
2. Increase `SESSION_TIMEOUT` value
3. Save and try again

## File Structure

```
thesteeperclimb/
├── config/               # Configuration files
│   ├── config.php       # Main settings
│   └── database.php     # Database connection
├── src/                 # Source code
│   ├── Auth/            # Authentication
│   ├── Models/          # Data models
│   └── Utils/           # Utilities
├── public/              # Public web files
│   ├── login.php        # Login page
│   ├── admin/           # Admin interface
│   ├── organization/    # Organization interface
│   └── student/         # Student interface
├── assets/              # CSS, JS, images
├── uploads/             # User uploads
│   ├── certificates/
│   └── videos/
├── setup/               # Installation files
│   ├── database.sql     # Database schema
│   ├── install.php      # Database setup
│   └── create-admin.php # Admin creation
├── README.md            # Full documentation
├── QUICKSTART.php       # Quick start guide
├── INSTALL.md           # This file
├── verify.php           # System verification
└── index.php            # Home page
```

## Database Schema

The database includes these main tables:

- **users** - All user accounts
- **organizations** - Client organizations
- **courses** - Course definitions
- **chapters** - Course chapters
- **sections** - Course sections/lessons
- **questions** - Quiz questions
- **student_progress** - Course progress tracking
- **section_completion** - Individual section completion
- **certificates** - Issued certificates
- **audit_logs** - System activity logs
- **notifications** - User notifications

Full schema is in `setup/database.sql`

## Security Features

- Password hashing with bcrypt
- Role-based access control (RBAC)
- Session management with timeout
- SQL injection prevention (PDO prepared statements)
- Input validation and sanitization
- Account status management (active/inactive/suspended)
- Audit logging of all major actions
- Organization data isolation

## Password Policy

Default password requirements:
- Minimum 8 characters
- At least one uppercase letter
- At least one number
- At least one special character

Edit `config/config.php` to change these requirements.

## Backup & Restore

### Backup Database

```bash
mysqldump -u root -p thesteeperclimb > backup.sql
```

### Restore Database

```bash
mysql -u root -p thesteeperclimb < backup.sql
```

## Support & Help

1. **Quick Start Guide**: Visit `http://localhost/thesteeperclimb/QUICKSTART.php`
2. **README**: Open `README.md` in your editor
3. **Admin Dashboard Help**: Look for help icons (?) in the interface
4. **Database Issues**: Check `setup/database.sql` for schema details

## Next Steps

After installation:

1. **Explore the Admin Dashboard** - Familiarize yourself with the interface
2. **Create Test Data** - Create a test organization, course, and student
3. **Test Workflows** - Go through the student experience
4. **Customize Settings** - Update application settings in `config/config.php`
5. **Add Real Data** - Start adding real organizations and courses
6. **Enable Notifications** (Optional) - Configure email notifications
7. **Regular Backups** - Set up automated database backups

## Version Information

- **Version**: 1.0.0
- **Release Date**: 2026
- **PHP Required**: 7.4+
- **MySQL Required**: 5.7+

## License

Built for The Steeper Climb - All Rights Reserved

---

**Need Help?**
- Check the README.md for full documentation
- Review QUICKSTART.php for workflows
- Run verify.php to check system requirements
- Check audit logs in admin panel for error details

**Thank you for using The Steeper Climb platform!**
