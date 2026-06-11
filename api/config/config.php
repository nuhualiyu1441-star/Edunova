<?php
/**
 * Edunova API - Configuration File
 * Database connection and settings
 */

// Environment
define('ENV', 'development');
define('DEBUG', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'edunova_lms');
define('DB_PORT', 3306);

// API Configuration
define('API_BASE_URL', 'http://localhost/edunova/api');
define('API_TIMEOUT', 10);
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB

// JWT Configuration
define('JWT_SECRET', 'your_jwt_secret_key_here_change_in_production');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 86400); // 24 hours

// File Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PROFILE_IMAGES_DIR', UPLOAD_DIR . 'profile_images/');
define('COURSE_MATERIALS_DIR', UPLOAD_DIR . 'course_materials/');
define('ASSIGNMENTS_DIR', UPLOAD_DIR . 'assignments/');

// Allowed MIME Types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);

// CORS Configuration
define('ALLOWED_ORIGINS', ['http://localhost', 'http://localhost:8000', 'http://localhost/edunova']);
define('ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);

// Pagination
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Session Configuration
define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'edunova_session');

// Email Configuration (optional)
define('MAIL_HOST', 'smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USER', 'your_email_here');
define('MAIL_PASS', 'your_password_here');
define('MAIL_FROM', 'noreply@edunova.com');

// Create upload directories if they don't exist
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(PROFILE_IMAGES_DIR)) mkdir(PROFILE_IMAGES_DIR, 0755, true);
if (!is_dir(COURSE_MATERIALS_DIR)) mkdir(COURSE_MATERIALS_DIR, 0755, true);
if (!is_dir(ASSIGNMENTS_DIR)) mkdir(ASSIGNMENTS_DIR, 0755, true);
