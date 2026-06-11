# Edunova - Smart Learning System

A complete Learning Management System (LMS) with PHP backend and modern frontend.

## Features

- 🔐 User Authentication (Login/Register)
- 📚 Course Management
- 🎥 YouTube Video Integration
- 📝 Quizzes & Assessments
- 📊 Progress Tracking
- 📎 File Uploads (Profile Images, Assignments)
- 📝 User Notes & Bookmarks
- 📜 Certificates Generation
- 👨‍💼 Admin Dashboard

## Installation

1. Copy all files to `C:\xampp\htdocs\edunova\`
2. Start XAMPP (Apache & MySQL)
3. Import `database.sql` in phpMyAdmin
4. Update database credentials in `api/config/database.php`
5. Create upload directories:
   ```bash
   mkdir uploads/profile_images
   mkdir uploads/assignments
   mkdir uploads/course_materials