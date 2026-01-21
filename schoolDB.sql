/*schoolDB.sql*/
CREATE DATABASE IF NOT EXISTS digital_school_management_system;
USE digital_school_management_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    role ENUM('student','teacher') NOT NULL,

    class VARCHAR(50) DEFAULT NULL,
    roll_number VARCHAR(50) DEFAULT NULL,

    department VARCHAR(100) DEFAULT NULL,
    subject VARCHAR(100) DEFAULT NULL,

    security_question VARCHAR(100) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
/*creating table for remember me tokens*/

CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
/*=====================Queries for Admin==================================*/
-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    subject VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    teacher_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- Update students table
ALTER TABLE students ADD COLUMN class_id INT NULL;
ALTER TABLE students ADD FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL;

-- Policies table
CREATE TABLE policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    criteria TEXT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notices table
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM('All', 'Students', 'Teachers') DEFAULT 'All',
    priority ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    status ENUM('Active', 'Expired') DEFAULT 'Active',
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
/*=====================Queries for Student ==================================*/
/*creating table for goal_tracker*/
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    deadline DATE NOT NULL,
    progress INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'In Progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
/*creating table for performance*/
CREATE TABLE performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    assignment_name VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
/*creating table for Assessment_submission*/
CREATE TABLE assessment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    answer_text TEXT NULL,
    file_path VARCHAR(255) NULL,
    submission_date DATETIME NOT NULL,
    status ENUM('Submitted', 'Graded') DEFAULT 'Submitted',
    obtained_marks INT NULL,
    feedback TEXT NULL,
    graded_by INT NULL,
    graded_at DATETIME NULL,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id),
    UNIQUE KEY unique_submission (assessment_id, student_id)
);
/*creating table for Assessment*/
CREATE TABLE assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    type ENUM('Quiz', 'Assignment', 'Project', 'Exam') NOT NULL,
    total_marks INT NOT NULL,
    duration INT NULL,
    due_date DATE NOT NULL,
    assigned_to_student INT NULL,
    assigned_to_all TINYINT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    risk_status ENUM('Green','Yellow','Red') DEFAULT 'Green',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

/*---------------Queris for Teacher=====================================*/
CREATE TABLE teachers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    intervention_date DATE NOT NULL,
    status ENUM('Pending','In Progress','Resolved') DEFAULT 'Pending',
    follow_up_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id)
) ENGINE=InnoDB;



