-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `thesteeperclimb`;
USE `thesteeperclimb`;

-- Users/Accounts Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'organization', 'student') NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `organization_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL,
  `phone` VARCHAR(20) NULL,
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_organization_id (organization_id),
  INDEX idx_status (status)
);

-- Organizations Table
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `website` VARCHAR(255) NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(50) NULL,
  `postal_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) NULL,
  `contact_person` VARCHAR(255) NULL,
  `contact_email` VARCHAR(255) NULL,
  `contact_phone` VARCHAR(20) NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
);

-- Courses Table
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NULL,
  `instructor_name` VARCHAR(255) NULL,
  `instructor_bio` TEXT NULL,
  `thumbnail_url` VARCHAR(255) NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `difficulty_level` ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
  `duration_hours` INT NULL,
  `pass_percentage` INT DEFAULT 70,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_status (status),
  INDEX idx_created_by (created_by)
);

-- Course Chapters Table
CREATE TABLE IF NOT EXISTS `chapters` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `course_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `order` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_course_id (course_id),
  INDEX idx_order (`order`),
  UNIQUE KEY unique_course_order (course_id, `order`)
);

-- Sections Table
CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `chapter_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `type` ENUM('video', 'quiz', 'assignment', 'reading') NOT NULL DEFAULT 'video',
  `order` INT NOT NULL,
  `video_url` VARCHAR(255) NULL,
  `video_duration_seconds` INT NULL,
  `content` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
  INDEX idx_chapter_id (chapter_id),
  INDEX idx_type (type),
  INDEX idx_order (`order`),
  UNIQUE KEY unique_chapter_order (chapter_id, `order`)
);

-- Questions Table (for quizzes)
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `section_id` INT NOT NULL,
  `question_text` LONGTEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'true_false', 'short_answer', 'essay') NOT NULL,
  `order` INT NOT NULL,
  `points` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  INDEX idx_section_id (section_id),
  INDEX idx_order (`order`),
  UNIQUE KEY unique_section_order (section_id, `order`)
);

-- Question Options Table (for multiple choice and true/false)
CREATE TABLE IF NOT EXISTS `question_options` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `option_text` LONGTEXT NOT NULL,
  `is_correct` BOOLEAN NOT NULL DEFAULT FALSE,
  `order` INT NOT NULL,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_question_id (question_id),
  INDEX idx_order (`order`),
  UNIQUE KEY unique_question_order (question_id, `order`)
);

-- Course Organization Subscription
CREATE TABLE IF NOT EXISTS `organization_courses` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `organization_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `assigned_by` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_org_course (organization_id, course_id),
  INDEX idx_organization_id (organization_id),
  INDEX idx_course_id (course_id)
);

-- Student Course Progress Table
CREATE TABLE IF NOT EXISTS `student_progress` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `status` ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
  `progress_percentage` INT DEFAULT 0,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `last_accessed` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE KEY unique_student_course (student_id, course_id),
  INDEX idx_student_id (student_id),
  INDEX idx_course_id (course_id),
  INDEX idx_status (status)
);

-- Section Completion Table (tracks which sections a student has completed)
CREATE TABLE IF NOT EXISTS `section_completion` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `section_id` INT NOT NULL,
  `completed_at` DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  UNIQUE KEY unique_student_section (student_id, section_id),
  INDEX idx_student_id (student_id),
  INDEX idx_section_id (section_id)
);

-- Student Answers Table
CREATE TABLE IF NOT EXISTS `student_answers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `answer_text` LONGTEXT NULL,
  `selected_option_id` INT NULL,
  `is_correct` BOOLEAN NULL,
  `points_earned` INT NULL,
  `submitted_at` DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL,
  INDEX idx_student_id (student_id),
  INDEX idx_question_id (question_id),
  INDEX idx_submitted_at (submitted_at)
);

-- Certificates Table
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `certificate_number` VARCHAR(255) UNIQUE NOT NULL,
  `issued_date` DATETIME NOT NULL,
  `score_percentage` INT NOT NULL,
  `certificate_file_path` VARCHAR(255) NULL,
  `certificate_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
  INDEX idx_student_id (student_id),
  INDEX idx_course_id (course_id),
  INDEX idx_issued_date (issued_date),
  UNIQUE KEY unique_student_course_cert (student_id, course_id)
);

-- Audit Log Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NULL,
  `action` VARCHAR(255) NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` INT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_action (action),
  INDEX idx_created_at (created_at)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `related_entity_id` INT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at)
);
