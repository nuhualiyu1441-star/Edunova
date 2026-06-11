-- ============================================
-- EDUNOVA LMS - COMPLETE DATABASE SCHEMA
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS edunova_lms;
USE edunova_lms;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'instructor') DEFAULT 'student',
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    bio TEXT,
    location VARCHAR(100),
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- ============================================
-- COURSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    description TEXT,
    category VARCHAR(100),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    image VARCHAR(255),
    instructor_id INT,
    price DECIMAL(10,2) DEFAULT 0,
    duration_hours INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    enrollments_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_level (level),
    FULLTEXT idx_search (title, description)
);

-- ============================================
-- MODULES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_order (order_number)
);

-- ============================================
-- LESSONS TABLE (with YouTube support)
-- ============================================
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    lesson_type ENUM('video', 'text', 'document', 'presentation', 'interactive', 'youtube') DEFAULT 'text',
    duration_minutes INT DEFAULT 0,
    order_number INT DEFAULT 0,
    video_url VARCHAR(500),
    youtube_url VARCHAR(500),
    file_path VARCHAR(500),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_status (status),
    INDEX idx_order (order_number)
);

-- ============================================
-- QUIZZES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    module_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    time_limit_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 70,
    total_points INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    INDEX idx_course (course_id),
    INDEX idx_status (status)
);

-- ============================================
-- QUIZ QUESTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(500),
    option_b VARCHAR(500),
    option_c VARCHAR(500),
    option_d VARCHAR(500),
    correct_answer CHAR(1),
    points INT DEFAULT 1,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id)
);

-- ============================================
-- ENROLLMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress_percent INT DEFAULT 0,
    completed_lessons INT DEFAULT 0,
    total_lessons INT DEFAULT 0,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    last_accessed DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_progress (progress_percent)
);

-- ============================================
-- LESSON PROGRESS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    watched_duration INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (enrollment_id, lesson_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_completed (completed)
);

-- ============================================
-- QUIZ ATTEMPTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    total_points INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    passed BOOLEAN DEFAULT FALSE,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_quiz (quiz_id)
);

-- ============================================
-- QUIZ ANSWERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer CHAR(1),
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_attempt (attempt_id)
);

-- ============================================
-- CERTIFICATES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(100) UNIQUE,
    pdf_path VARCHAR(500),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_code (certificate_code)
);

-- ============================================
-- USER NOTES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    title VARCHAR(200),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_lesson (user_id, lesson_id)
);

-- ============================================
-- USER BOOKMARKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NULL,
    course_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ============================================
-- ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Admin user (password: admin123)
INSERT IGNORE INTO users (full_name, username, email, password, role, status) 
VALUES ('Admin User', 'admin', 'admin@edunova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample student (password: student123)
INSERT IGNORE INTO users (full_name, username, email, password, role, status, profile_image) 
VALUES ('John Doe', 'johndoe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', 'uploads/profiles/default.jpg');

-- Sample courses
INSERT IGNORE INTO courses (id, title, slug, description, category, level, status, enrollments_count, rating) VALUES
(1, 'Web Development Fundamentals', 'web-development-fundamentals', 'Learn HTML, CSS, JavaScript and build responsive websites from scratch.', 'Development', 'beginner', 'published', 1250, 4.8),
(2, 'Python Programming Basics', 'python-programming-basics', 'Learn Python from scratch and build your programming skills.', 'Programming', 'beginner', 'published', 856, 4.7),
(3, 'UI/UX Design Essentials', 'ui-ux-design-essentials', 'Design beautiful and user-friendly interfaces.', 'Design', 'beginner', 'published', 642, 4.9),
(4, 'Database Management Systems', 'database-management-systems', 'Learn SQL and database management concepts.', 'Database', 'intermediate', 'published', 512, 4.6),
(5, 'Digital Marketing Basics', 'digital-marketing-basics', 'Learn digital marketing fundamentals and strategies.', 'Marketing', 'beginner', 'published', 421, 4.5);

-- Sample modules
INSERT IGNORE INTO modules (id, course_id, title, order_number) VALUES
(1, 1, 'Introduction to Web Development', 1),
(2, 1, 'HTML Basics', 2),
(3, 1, 'CSS Fundamentals', 3),
(4, 1, 'JavaScript Basics', 4),
(5, 2, 'Python Introduction', 1),
(6, 2, 'Python Variables and Data Types', 2);

-- Sample lessons with YouTube videos
INSERT IGNORE INTO lessons (id, module_id, title, content, lesson_type, duration_minutes, order_number, youtube_url, status) VALUES
(1, 1, 'What is Web Development?', 'Learn what web development is and how websites work', 'youtube', 15, 1, 'https://www.youtube.com/watch?v=zJSY8tbf_ys', 'published'),
(2, 1, 'How the Web Works', 'Understanding HTTP, servers, and browsers', 'youtube', 20, 2, 'https://www.youtube.com/watch?v=dh406O2v_1c', 'published'),
(3, 1, 'Setting Up Your Environment', 'Install VS Code and necessary tools', 'youtube', 25, 3, 'https://www.youtube.com/watch?v=WPqXP_kLzpo', 'published'),
(4, 2, 'HTML Introduction', 'Basic HTML structure and tags', 'youtube', 18, 1, 'https://www.youtube.com/watch?v=qz0aGYrrlhU', 'published'),
(5, 2, 'HTML Forms', 'Creating forms in HTML', 'youtube', 22, 2, 'https://www.youtube.com/watch?v=fNcJuPIZ2WE', 'published');

-- Sample enrollments
INSERT IGNORE INTO enrollments (user_id, course_id, progress_percent) VALUES
(2, 1, 75),
(2, 2, 60),
(2, 3, 40),
(2, 4, 25);

-- ============================================
-- VIEWS FOR EASY QUERIES
-- ============================================

-- User dashboard view
CREATE OR REPLACE VIEW user_dashboard AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.email,
    u.profile_image,
    COUNT(DISTINCT e.course_id) as enrolled_courses,
    SUM(lp.completed) as completed_lessons,
    COUNT(DISTINCT qa.id) as quizzes_taken,
    AVG(qa.percentage) as avg_quiz_score
FROM users u
LEFT JOIN enrollments e ON u.id = e.user_id
LEFT JOIN lesson_progress lp ON e.id = lp.enrollment_id AND lp.completed = 1
LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
WHERE u.role = 'student'
GROUP BY u.id;

-- Course analytics view
CREATE OR REPLACE VIEW course_analytics AS
SELECT 
    c.id,
    c.title,
    c.enrollments_count,
    COUNT(DISTINCT e.user_id) as active_students,
    AVG(e.progress_percent) as avg_progress,
    COUNT(DISTINCT m.id) as total_modules,
    COUNT(DISTINCT l.id) as total_lessons
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id
LEFT JOIN modules m ON c.id = m.course_id
LEFT JOIN lessons l ON m.id = l.module_id
WHERE c.status = 'published'
GROUP BY c.id;