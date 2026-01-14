<?php
/**
 * Application Configuration
 */

// Application settings
define('APP_NAME', 'The Steeper Climb - Online Course Platform');
define('APP_URL', 'http://localhost/thesteeperclimb');
define('APP_ENV', 'development'); // development or production

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_TIMEOUT', 1800); // 30 minutes of inactivity

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('CERTIFICATE_DIR', UPLOAD_DIR . '/certificates');
define('VIDEO_DIR', UPLOAD_DIR . '/videos');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Password requirements
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_UPPERCASE', true);
define('REQUIRE_NUMBERS', true);
define('REQUIRE_SPECIAL_CHARS', true);

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_ORGANIZATION', 'organization');
define('ROLE_STUDENT', 'student');

// Account status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');

// Course status
define('COURSE_DRAFT', 'draft');
define('COURSE_PUBLISHED', 'published');
define('COURSE_ARCHIVED', 'archived');

// Progress tracking
define('PROGRESS_NOT_STARTED', 'not_started');
define('PROGRESS_IN_PROGRESS', 'in_progress');
define('PROGRESS_COMPLETED', 'completed');

// Certification
define('CERTIFICATION_THRESHOLD', 70); // 70% passing grade

// Email settings (if needed)
define('MAIL_FROM', 'noreply@thesteeperclimb.com');
define('MAIL_FROM_NAME', 'The Steeper Climb');

// Include database configuration
require_once __DIR__ . '/database.php';
?>
