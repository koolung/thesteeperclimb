# The Steeper Climb - Online Course Platform

A comprehensive online learning management system built with PHP and MySQL for Nancy MacLeod's educational programming platform.

## Project Overview

The Steeper Climb is an online course platform designed to support personal growth and self-empowerment through structured learning. The system features role-based access control with three main account types: Admin, Organization, and Student.

### Key Features

#### Admin Features
- ✓ Create and manage organization accounts
- ✓ Review, add, edit, delete organization accounts
- ✓ Create and manage courses with chapters, sections, and content
- ✓ Assign courses to specific organizations
- ✓ Manage all student accounts across organizations
- ✓ Review audit logs and system activity
- ✓ Send notifications to organizations

#### Organization Features
- ✓ Manage own organization profile
- ✓ View assigned courses
- ✓ Add, edit, and delete student accounts (organization-specific)
- ✓ View student progress and statistics
- ✓ Generate reports on student performance
- ✓ Track certificates issued
- ✓ No access to other organizations' data

#### Student Features
- ✓ Access courses available to their organization
- ✓ Track course progress (saves automatically)
- ✓ Complete course sections (videos, quizzes, assignments)
- ✓ Submit answers and receive feedback
- ✓ Earn certificates upon successful completion (70%+ score)
- ✓ View and download certificates
- ✓ Access profile and manage account settings

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache/XAMPP

## Installation & Setup

### Prerequisites
- XAMPP (or PHP 7.4+ with MySQL)
- Apache Web Server
- MySQL Server
- Composer (for PHPMailer - auto-install included)
- Internet connection (for email sending)

### Step 1: Install PHPMailer (Email Support)

PHPMailer is required for sending welcome emails to organizations.

**Option A (Automated)**:
- Double-click: `install-phpmailer.bat`

**Option B (Manual)**:
1. Open PowerShell in project folder
2. Run: `composer install`

See: `PHPMAILER_QUICK_START.md` for details

### Step 2: Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Navigate to the workspace setup folder
3. Run `setup/install.php` to create the database and tables
   - Visit: `http://localhost/thesteeperclimb/setup/install.php`

### Step 3: Create Admin Account

1. After database setup, run `setup/create-admin.php`
   - Visit: `http://localhost/thesteeperclimb/setup/create-admin.php`
2. Fill in the form with admin credentials
3. Click "Create Admin Account"

### Step 4: Access the Platform

1. **Admin Dashboard**: `http://localhost/thesteeperclimb/public/login.php`
   - Login with admin credentials
   - Create organizations and courses

2. **Organization Portal**: Same login, redirects based on role
   - Manage students and view courses

3. **Student Portal**: Same login, redirects based on role
   - Access courses and track progress

## Email Configuration

### SMTP Settings (Hostinger)
- **Host**: smtp.hostinger.com
- **Port**: 465 (SSL)
- **Username**: thesteeperclimb@bedfordwebservices.com
- **Password**: Configured in `src/Utils/Mailer.php`

### Features
- ✓ Automatic welcome emails when organizations are created
- ✓ Password setup links sent to organization email
- ✓ Modern, responsive HTML email templates
- ✓ Secure SSL/TLS encryption

### Troubleshooting
See: `EMAIL_TROUBLESHOOTING.md` for detailed troubleshooting guide

## Directory Structure

```
thesteeperclimb/
├── vendor/                  # PHPMailer (auto-installed via composer)
├── config/
│   ├── config.php           # Main configuration file
│   └── database.php         # Database connection
├── src/
│   ├── Auth/
│   │   └── Auth.php         # Authentication class
│   ├── Database/
│   │   └── Database.php     # Database helper
│   ├── Models/
│   │   ├── BaseModel.php    # Base model class
│   │   ├── UserModel.php    # User model
│   │   ├── CourseModel.php  # Course model
│   │   ├── OrganizationModel.php
│   │   ├── ProgressModel.php
│   │   └── CertificateModel.php
│   └── Utils/
│       ├── Utils.php        # Utility functions
│       └── Mailer.php       # Email sending (PHPMailer)
├── public/
│   ├── login.php            # Login page
│   ├── logout.php           # Logout handler
│   ├── unauthorized.php     # 403 error page
│   ├── admin/
│   │   ├── dashboard.php    # Admin dashboard
│   │   ├── organizations.php
│   │   ├── users.php
│   │   └── courses.php
│   ├── organization/
│   │   ├── dashboard.php    # Organization dashboard
│   │   ├── students.php
│   │   ├── courses.php
│   │   └── reports.php
│   └── student/
│       ├── dashboard.php    # Student dashboard
│       ├── courses.php
│       ├── course.php       # Course learning page
│       ├── certificates.php
│       └── profile.php
├── assets/
│   ├── css/
│   │   └── admin.css        # Styles
│   ├── js/
│   │   └── main.js
│   └── images/
├── uploads/
│   ├── certificates/        # Certificate files
│   └── videos/              # Course videos
├── setup/
│   ├── database.sql         # SQL schema
│   ├── install.php          # Database installer
│   └── create-admin.php     # Admin creation
└── README.md
```

## Database Schema

### Core Tables
- **users** - All user accounts (admin, organization, student)
- **organizations** - Organization/client accounts
- **courses** - Course information
- **chapters** - Course chapters
- **sections** - Course sections (lessons)
- **questions** - Quiz questions
- **question_options** - Multiple choice options

### Tracking Tables
- **student_progress** - Overall course progress
- **section_completion** - Individual section completion
- **student_answers** - Quiz answers
- **certificates** - Issued certificates

### Support Tables
- **organization_courses** - Course assignments to organizations
- **audit_logs** - System activity logs
- **notifications** - User notifications

## User Workflows

### Admin Workflow
1. Login with admin credentials
2. Navigate to Admin Dashboard
3. Create organizations
4. Create courses with structure
5. Assign courses to organizations
6. Manage user accounts
7. Monitor system activity

### Organization Workflow
1. Login with organization credentials
2. View assigned courses
3. Add students to organization
4. Manage student accounts
5. View student progress reports
6. Track certificate issuance

### Student Workflow
1. Login with student credentials
2. View available courses
3. Select and start a course
4. Complete sections
5. Submit answers to questions
6. Track progress
7. Receive certificate on completion
8. Download and view certificates

## Security Features

- Password hashing with bcrypt
- Session management with timeout
- Role-based access control (RBAC)
- Input validation and sanitization
- SQL injection prevention (PDO prepared statements)
- CSRF protection ready
- Audit logging
- Account status management

## Configuration

Edit `config/config.php` to customize:
- Database credentials
- Application URL
- Session timeout
- Password requirements
- Upload settings
- Certification threshold (70% default)

## Password Requirements

By default, passwords must contain:
- At least 8 characters
- One uppercase letter
- One number
- One special character

Edit `config/config.php` to change requirements.

## API Responses

The system uses JSON responses for API calls. Common status codes:
- `200 OK` - Successful operation
- `400 Bad Request` - Invalid input
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Not authorized
- `404 Not Found` - Resource not found
- `500 Server Error` - Internal server error

## Additional Features (Recommended Enhancements)

1. **Email Notifications**
   - Student enrollment notifications
   - Course assignment notifications
   - Certificate issuance emails
   - Progress reminders

2. **Advanced Reporting**
   - Student performance analytics
   - Course completion rates
   - Organization statistics
   - Engagement metrics

3. **Content Management**
   - Video hosting integration
   - Quiz auto-grading
   - Assignment submission system
   - Discussion forums

4. **Mobile Support**
   - Responsive design (already included)
   - Mobile app integration
   - Offline access capability

5. **Gamification**
   - Badges and achievements
   - Leaderboards
   - Progress streaks
   - Rewards system

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database is created

### Login Issues
- Check username and password
- Verify user account status is "active"
- Clear browser cookies/cache

### Permission Denied
- Verify user role is correct
- Check organization assignment for students
- Review course assignments

## Support & Contact

For issues or questions:
- Review database schema in `setup/database.sql`
- Check audit logs for errors
- Verify file permissions on uploads folder

## License

This platform is built for The Steeper Climb. All rights reserved.

## Database Backup

Regular backups are recommended:

```bash
mysqldump -u root -p thesteeperclimb > backup.sql
```

Restore from backup:

```bash
mysql -u root -p thesteeperclimb < backup.sql
```

## Future Enhancements

- [ ] API endpoints for third-party integration
- [ ] Mobile application
- [ ] Advanced analytics dashboard
- [ ] Automated email notifications
- [ ] Video streaming integration
- [ ] Discussion forums
- [ ] Peer-to-peer messaging
- [ ] Learning path recommendations
- [ ] Accessibility improvements (WCAG compliance)
- [ ] Multi-language support

## Version

**v1.0.0** - Initial Release

---

Built with ❤️ for The Steeper Climb's Mission of Personal Growth & Empowerment
