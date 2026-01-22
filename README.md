# 🎓 Digital School Management System

This project is a role-based academic management and performance monitoring system designed to improve learning outcomes. It supports students, teachers, and administrators with secure authentication, profile management, and role-based dashboards. Structured data and performance analysis help track progress and enhance academic efficiency.

---

## 📋 Table of Contents

- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [User Roles & Credentials](#user-roles--credentials)
- [Future Enhancements](#future-enhancements)
- [Contributing](#contributing)

---

## ✨ Features

### 👨‍🎓 Student Portal

- **Personal Dashboard**: View performance metrics, assignments, and risk status
- **Goal Tracker**: Set, track, and manage academic goals with progress visualization
- **Assessment Submission**: Submit assignments with file upload support
- **Performance Analytics**: View detailed performance trends and subject-wise scores

### 👨‍🏫 Teacher Portal

- **Academic Dashboard**: Overview of all classes, students, and assessments
- **Assessment Management**: Create, assign, grade, and manage assessments (Quiz, Exam, Assignment, Project, etc.)
- **Student Risk Monitoring**: Real-time color-coded risk indicators (🟢 Green, 🟡 Yellow, 🔴 Red)
- **Intervention Logging**: Document and track interventions for at-risk students

### 👨‍💼 Admin Portal

- **System Dashboard**: Comprehensive analytics and system-wide metrics
- **User Management**: Full CRUD operations for students and teachers
- **Policy Manager**: Define performance evaluation rules and criteria

### 🎯 Key Highlights

- **Real-time Analytics**: Live dashboards with performance trends
- **Intervention Tracking**: Complete CRUD system for tracking student support actions
- **Multi-role Access Control**: Separate portals for Students, Teachers, and Admins
- **Secure Authentication**: Password-protected access with session management

## 🛠️ Technologies Used

### Backend

- **PHP 8.0+**: Server-side scripting with procedural approach
- **MySQL 5.7+**: Relational database management
- **MySQLi**: Procedural database interface

### Frontend

- **HTML5 & CSS3**: Semantic markup and modern styling
- **JavaScript (ES6+)**: Client-side interactivity
- **Chart.js**: Data visualization and analytics charts

### Design

- **Inter Font Family**: Modern, clean typography
- **Glass-morphism UI**: Frosted glass effects with backdrop filters
- **Gradient Accents**: Vibrant color gradients for visual appeal

📥 Installation

Prerequisites

- **XAMPP**: Apache server with PHP 8.0+ and MySQL 5.7+
- **Web Browser**: Chrome, Firefox, Safari, or Edge (latest versions)
- **Text Editor**: VS Code, Sublime Text, or any code editor (optional)

### Step-by-Step Installation

1. **Clone the Repository**

   ```bash
   git clone https://github.com/Anik-Z/Digital-School-Management-System.git
   cd Digital-School-Management-System
   ```

2. **Move to Server Directory**

   ```bash
   # For XAMPP (Windows/Mac)
   cp -r . C:/xampp/htdocs/student_system/

   # For LAMP (Linux)
   sudo cp -r . /var/www/html/student_system/
   ```

3. **Start Apache & MySQL**
   - Open XAMPP/WAMP Control Panel
   - Start Apache and MySQL services

4. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `Digital-School-Management-System`
   - Import the SQL schema (see [Database Setup](#database-setup))

5. **Configure Database Connection**
   - Update database credentials in all PHP files:

   ```php
   $host = 'localhost';
   $dbname = 'Digital-School-Management-System';
   $username = 'root';      // Your MySQL username
   $password = '';          // Your MySQL password
   ```

6. **Access the System**
   - Open browser and navigate to: `http://localhost/Digital_school_management_system/`

---

## 💾 Database Setup

### Create the Database

````sql
-- 1. CREATE DATABASE
CREATE DATABASE IF NOT EXISTS digital_school_management_system;
USE digital_school_management_system;

-- 2. CREATE USERS TABLE (Your original)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student','teacher') NOT NULL,
    class VARCHAR(50) DEFAULT NULL,
    roll_number VARCHAR(50) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    subject VARCHAR(100) DEFAULT NULL,
    risk_status ENUM('Green','Yellow','Red') DEFAULT 'Green',
    security_question VARCHAR(100) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. CREATE REMEMBER TOKENS TABLE
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. CREATE CLASSES TABLE
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    teacher_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. CREATE ASSESSMENTS TABLE (Most Important)
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject VARCHAR(100) NOT NULL,
    type ENUM('Quiz','Assignment','Project','Exam','Class Test','Presentation','Lab Report','Essay') NOT NULL,
    total_marks INT NOT NULL,
    duration INT DEFAULT NULL,
    due_date DATE NOT NULL,
    assigned_to_all BOOLEAN DEFAULT 0,
    assigned_to_student INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to_student) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. CREATE ASSESSMENT_SUBMISSIONS TABLE
CREATE TABLE IF NOT EXISTS assessment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    answer_text TEXT,
    file_path VARCHAR(500),
    submission_date DATETIME NOT NULL,
    obtained_marks INT DEFAULT NULL,
    feedback TEXT,
    status ENUM('Submitted','Graded') DEFAULT 'Submitted',
    graded_by INT DEFAULT NULL,
    graded_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. CREATE PERFORMANCE TABLE
CREATE TABLE IF NOT EXISTS performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    assignment_name VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. CREATE GOALS TABLE
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    deadline DATE NOT NULL,
    progress INT DEFAULT 0,
    status ENUM('In Progress','Completed') DEFAULT 'In Progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 9. CREATE POLICIES TABLE
CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    criteria TEXT NOT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. CREATE NOTICES TABLE
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM('All','Students','Teachers') DEFAULT 'All',
    priority ENUM('High','Medium','Low') DEFAULT 'Medium',
    expiry_date DATE DEFAULT NULL,
    status ENUM('Active','Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. CREATE INTERVENTIONS TABLE
CREATE TABLE IF NOT EXISTS interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    intervention_date DATE NOT NULL,
    status ENUM('Pending','In Progress','Resolved') DEFAULT 'Pending',
    follow_up_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

### Insert Sample Data (Optional)
```sql
-- Sample Admin (for testing)
-- You'll need to create an admin authentication system

-- Sample Student
INSERT INTO students (name, email, password, risk_status)
VALUES ('Alex Johnson', 'alex@student.com', '$2y$10$encrypted_password_here', 'Green');

-- Sample Teacher
INSERT INTO teachers (name, email, subject, password)
VALUES ('John Smith', 'john@teacher.com', 'Mathematics', '$2y$10$encrypted_password_here');

-- Sample Class
INSERT INTO classes (name, teacher_id) VALUES ('Grade 10A', 1);
````

---

### Common Workflows

**For Teachers:**

1. Create Assessment → Assign to Students
2. Students Submit → Teacher Grades
3. Monitor Risk Status → Log Interventions
4. Track Performance Analytics

**For Students:**

1. View Assigned Assessments
2. Submit Work (Text + Files)
3. Track Performance & Goals
4. View Feedback & Grades

**For Admins:**

1. Manage Users (Add/Edit/Delete)
2. Create Classes & Assign Teachers
3. Define Evaluation Policies
4. Publish System-wide Notices

---

## 🔐 User Roles & Credentials

### Default Test Accounts

**Student Account:**

- Email: `student@demo.com`
- Password: `student123`
- Access: Student Portal

**Teacher Account:**

- Email: `teacher@demo.com`
- Password: `teacher123`
- Access: Teacher Portal

**Admin Account:**

- Email: `admin@gmail.com`
- Password: `admin123`
- Access: Admin Portal

## 🔮 Future Enhancements

      **Real-time Notifications**: WebSocket integration for live updates
      **Email System**: Automated email notifications for assignments and interventions
      **Mobile App**: React Native mobile application
      **AI-Powered Insights**: Machine learning for predictive analytics
      **Parent Portal**: Allow parents to monitor student progress
      **Video Conferencing**: Integrated virtual classroom
      **Attendance System**: QR code-based attendance tracking
      **Grade Calculator**: Automatic GPA calculation
      **Export Reports**: PDF/Excel report generation
      **Multi-language Support**: Internationalization (i18n)
      **Dark Mode**: Theme switching capability
      **API Integration**: RESTful API for third-party integrations

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. **Fork the Repository**
2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit Your Changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push to Branch**
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open a Pull Request**

### Contribution Guidelines

- Follow PSR-12 coding standards for PHP
- Write clear, descriptive commit messages
- Add comments for complex logic
- Test thoroughly before submitting
- Update documentation for new features

---

## 📝 License

This project is licensed under the **MIT License**

### Technologies & Resources

- [Chart.js](https://www.chartjs.org/) - Beautiful JavaScript charts
- [Google Fonts - Inter](https://fonts.google.com/specimen/Inter) - Modern typography
- [Shields.io](https://shields.io/) - README badges
- [PHP Manual](https://www.php.net/manual/en/) - PHP documentation
- [MySQL Documentation](https://dev.mysql.com/doc/) - Database reference
