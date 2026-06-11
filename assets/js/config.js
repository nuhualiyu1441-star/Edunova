/**
 * Edunova Configuration
 * Centralized configuration for API endpoints and app settings
 */

const Config = {
  // API Configuration
  API: {
    BASE_URL: process.env.API_BASE_URL || 'http://localhost/edunova/api',
    TIMEOUT: 10000,
    RETRY_ATTEMPTS: 3
  },

  // Storage Keys
  STORAGE: {
    TOKEN: 'edunova_token',
    USER: 'edunova_user',
    ADMIN: 'edunova_admin'
  },

  // Routes
  ROUTES: {
    // Student routes
    STUDENT_LOGIN: 'pages/student/login.html',
    STUDENT_REGISTER: 'pages/student/register.html',
    STUDENT_DASHBOARD: 'student-dashboard.html',
    COURSES: 'courses.html',
    COURSE_DETAILS: 'course-details.html',

    // Admin routes
    ADMIN_LOGIN: 'pages/admin/login.html',
    ADMIN_DASHBOARD: 'pages/admin/dashboard.html'
  },

  // Toast Notification Types
  TOAST_TYPES: {
    SUCCESS: 'success',
    ERROR: 'error',
    INFO: 'info'
  },

  // User Roles
  ROLES: {
    ADMIN: 'admin',
    STUDENT: 'student'
  }
};

Object.freeze(Config);
