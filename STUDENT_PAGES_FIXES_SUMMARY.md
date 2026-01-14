# Student Pages Fixes - Comprehensive Summary

## Problem Identified
After the database refactoring that consolidated organizations and users tables, the student pages were not showing courses. The issue was:

1. Students had no way to know which organization they belonged to
2. The ProgressModel wasn't filtering courses by the student's organization
3. Students could theoretically access any course, not just those assigned to their organization

## Root Cause
The original refactoring removed the `organization_id` column from the users table entirely, but students MUST have an `organization_id` to identify which organization they belong to and which courses they can access.

## Database Schema Changes

### File: `setup/database.sql`
**Change**: Added `organization_id` column back to users table (for students only)

```sql
`organization_id` INT NULL,
INDEX idx_organization_id (organization_id),
FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE
```

**Why**: Students need to track which organization they belong to. This is an INT reference to the user ID of the organization user (role='organization').

### File: `setup/migration_add_organization_to_students.sql` (NEW)
**Creation**: Migration script to add the column to existing databases

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS organization_id INT NULL;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_organization_id (organization_id);
ALTER TABLE users ADD CONSTRAINT FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE;
```

## Code Changes

### Auth Layer

**File**: `src/Auth/Auth.php`
**Method**: `register()`

**Before**:
```php
public static function register($email, $password, $first_name, $last_name, $role = ROLE_STUDENT)
```

**After**:
```php
public static function register($email, $password, $first_name, $last_name, $role = ROLE_STUDENT, $organization_id = null)
```

**Changes**:
- Added optional `$organization_id` parameter
- When creating a student (`$role === ROLE_STUDENT`), includes `organization_id` in the INSERT statement
- Maintains backward compatibility for other roles (admin, organization)

**Impact**: Organizations can now create students that belong to them

---

### Model Layer

**File**: `src/Models/ProgressModel.php`
**Method**: `getStudentCourses()`

**Before**:
```php
public function getStudentCourses($student_id, $status = null) {
    $sql = "SELECT sp.*, c.title, c.description, c.difficulty_level 
            FROM student_progress sp
            INNER JOIN courses c ON sp.course_id = c.id
            WHERE sp.student_id = ?";
```

**After**:
```php
public function getStudentCourses($student_id, $status = null) {
    $sql = "SELECT sp.*, c.id as course_id, c.title, c.description, c.difficulty_level, c.duration_hours, c.status as course_status
            FROM student_progress sp
            INNER JOIN courses c ON sp.course_id = c.id
            INNER JOIN users u ON u.id = ?
            INNER JOIN organization_courses oc ON oc.organization_id = u.organization_id AND oc.course_id = c.id
            WHERE sp.student_id = ?";
```

**Changes**:
- Now joins with `users` table to get the student's organization_id
- Adds constraint: course must be in organization_courses for student's organization
- Returns additional fields: `course_id`, `duration_hours`, `course_status`
- Only shows courses assigned to student's organization

**Impact**: Students now only see courses assigned to their organization

---

**File**: `src/Models/UserModel.php`
**Methods**: `findStudentsByOrganization()` and `countStudentsByOrganization()`

**Before**:
```php
public function findStudentsByOrganization($organization_id, $limit = null, $offset = 0) {
    $sql = "SELECT * FROM users 
            WHERE role = ? AND status = ?";  // NO organization filter!
```

**After**:
```php
public function findStudentsByOrganization($organization_id, $limit = null, $offset = 0) {
    $sql = "SELECT * FROM users 
            WHERE role = ? AND status = ? AND organization_id = ?";  // NOW filters by organization
```

**Impact**: Organizations only see their own students in management pages

---

### Student Pages

**File**: `public/student/courses.php`
**Change**: Updated field reference from `$course['status']` to `$course['course_status']`

```php
// Before
Status: <strong><?php echo ucfirst($course['status']); ?></strong>

// After
Status: <strong><?php echo ucfirst($course['course_status']); ?></strong>
```

**Why**: ProgressModel now returns `course_status` to distinguish from student_progress status

---

**File**: `public/student/dashboard.php`
**Changes**:
1. Removed undefined `$organization` variable reference
2. Updated field reference from `$course['status']` to `$course['course_status']`
3. Fixed inline import formatting (removed blank line)

**Before**:
```php
<small><?php echo htmlspecialchars($organization['name'] ?? 'Organization'); ?></small>
Status: <strong><?php echo ucfirst($course['status']); ?></strong>
```

**After**:
```php
<!-- removed undefined $organization line -->
Status: <strong><?php echo ucfirst($course['course_status']); ?></strong>
```

**Why**: 
- Organization name not needed/available on student dashboard
- Match ProgressModel field name changes

---

**File**: `public/student/course.php`
**Change**: Updated course access validation

**Before**:
```php
$stmt = $pdo->prepare(
    "SELECT oc.id FROM organization_courses oc
     WHERE oc.course_id = ?"
);
$stmt->execute([$course_id]);
```

**After**:
```php
$stmt = $pdo->prepare(
    "SELECT oc.id FROM organization_courses oc
     INNER JOIN users u ON u.id = ?
     WHERE oc.course_id = ? AND oc.organization_id = u.organization_id"
);
$stmt->execute([$user['id'], $course_id]);
```

**Why**: Verify the course is assigned to THIS student's organization, not just any organization

---

### Organization Pages

**File**: `public/organization/students.php`
**Change**: Pass organization_id when creating students

**Before**:
```php
Auth::register($email, $password, $first_name, $last_name, ROLE_STUDENT);
```

**After**:
```php
Auth::register($email, $password, $first_name, $last_name, ROLE_STUDENT, $user['id']);
```

**Why**: When an organization creates a student, that student must be assigned to that organization

---

**File**: `public/organization/reports.php`
**Change**: Filter student progress by organization

**Before**:
```php
$stmt->execute([ROLE_STUDENT]);  // Gets ALL students
```

**After**:
```php
$stmt->execute([ROLE_STUDENT, $user['id']]);  // Gets only this organization's students
```

**With WHERE clause change**:
```sql
WHERE u.role = ? AND u.organization_id = ?
```

**Why**: Organization should only see reports for their own students

---

## Course Visibility Flow

### Before Fix
1. Student logs in
2. ProgressModel.getStudentCourses() returns ALL courses with student_progress records
3. Student sees courses from ANY organization
4. **PROBLEM**: Data breach possibility - wrong access control

### After Fix
1. Student logs in
2. Student has organization_id = 5 (for example)
3. ProgressModel.getStudentCourses() joins with organization_courses
4. Only courses where organization_courses.organization_id = 5 are returned
5. Student can only access their organization's courses
6. **SECURE**: Proper access control enforced at database level

## Data Model

### Users Table Structure (Updated)
```
id (INT)
email (VARCHAR)
password_hash (VARCHAR)
first_name (VARCHAR)
last_name (VARCHAR)
role (ENUM: admin, organization, student)
status (ENUM: active, inactive, suspended)
phone (VARCHAR)
organization_id (INT, FK to users.id) -- ⭐ NEW: For students only
organization_name (VARCHAR) -- For organizations only
organization_description (TEXT) -- For organizations only
... [other org fields] ...
created_by (INT, FK to users.id)
```

### Relationships
- User (organization, role='organization') → has many organization_courses
- organization_courses → links to courses
- User (student, role='student') → has organization_id pointing to organization
- Student courses = organization_courses where organization_id = student.organization_id

## Migration Instructions

### Step 1: Update Database Schema
Run the migration SQL:
```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS organization_id INT NULL;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_organization_id (organization_id);
ALTER TABLE users ADD CONSTRAINT FOREIGN KEY (organization_id) REFERENCES users(id) ON DELETE CASCADE;
```

### Step 2: Update Existing Student Records
If you have existing students, manually assign them to organizations:
```sql
-- Example: Assign all existing students to organization with ID 2
UPDATE users SET organization_id = 2 WHERE role = 'student' AND organization_id IS NULL;
```

### Step 3: Test the Flow
1. Login as organization user
2. Go to Students page
3. Add a new student
4. Student should be created with organization_id set
5. Login as that student
6. Go to My Courses
7. Should only see courses assigned to their organization

## Files Modified
1. ✅ `setup/database.sql` - Added organization_id column
2. ✅ `setup/migration_add_organization_to_students.sql` - NEW migration file
3. ✅ `src/Auth/Auth.php` - Added organization_id parameter to register()
4. ✅ `src/Models/ProgressModel.php` - Updated getStudentCourses() with org filtering
5. ✅ `src/Models/UserModel.php` - Updated methods to filter by organization_id
6. ✅ `public/student/courses.php` - Fixed field reference
7. ✅ `public/student/dashboard.php` - Fixed field references and removed undefined vars
8. ✅ `public/student/course.php` - Updated access validation
9. ✅ `public/organization/students.php` - Pass organization_id on creation
10. ✅ `public/organization/reports.php` - Filter by organization_id

## Testing Checklist

- [ ] Database migration runs without errors
- [ ] Admin can create organizations
- [ ] Organization can create students (students appear in their list)
- [ ] Student appears with correct organization_id in database
- [ ] Student can login
- [ ] Student sees only courses assigned to their organization
- [ ] Student cannot access courses from other organizations
- [ ] Organization reports show only their students
- [ ] Organization can manage their students
- [ ] New student creation assigns correct organization_id
- [ ] Existing students (if any) have organization_id set correctly

## Backward Compatibility

✅ All changes are backward compatible:
- Auth::register() still works without organization_id parameter
- For non-student roles, organization_id is ignored
- Existing queries still work, just filtered differently
- No breaking changes to public API
