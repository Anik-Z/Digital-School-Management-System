/*schoolDB.sql*/
CREATE DATABASE IF NOT EXISTS digital_school;
USE digital_school;

/*Creating users table*/
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') NOT NULL,
    class VARCHAR(50),
    roll_number VARCHAR(50),
    department VARCHAR(100),
    subject VARCHAR(100),
    security_question VARCHAR(255) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

/*input data of the users*/
INSERT INTO users (full_name, email, password, role, class, roll_number, security_question, security_answer) 
VALUES 
('John Student', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '10th Grade', 'S001', 'pet', 'Max');

-- Insert sample teacher (password: teacher123)
INSERT INTO users (full_name, email, password, role, department, subject, security_question, security_answer) 
VALUES 
('Jane Teacher', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Mathematics', 'Algebra', 'singer', 'Adele');