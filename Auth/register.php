<?php
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $security_question = $_POST['securityQuestion'] ?? '';
    $security_answer = trim($_POST['answer'] ?? '');
    
    // Additional fields based on role
    if ($role == 'student') {
        $class = $_POST['class'] ?? '';
        $roll_number = $_POST['roll'] ?? '';
        $department = $subject = null;
    } else {
        $department = $_POST['department'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $class = $roll_number = null;
    }
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (empty($security_question)) {
        $errors[] = "Please select a security question";
    }
    
    if (empty($security_answer)) {
        $errors[] = "Security answer is required";
    }
    
    if ($role == 'student' && empty($class)) {
        $errors[] = "Class is required for students";
    }
    
    if ($role == 'teacher' && empty($department)) {
        $errors[] = "Department is required for teachers";
    }
    
    // If no validation errors
    if (empty($errors)) {
        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        
        if ($checkEmail->rowCount() > 0) {
            $error = "Email already registered. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $sql = "INSERT INTO users (full_name, email, password, role, class, roll_number, department, subject, security_question, security_answer) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            try {
                $stmt->execute([
                    $full_name, 
                    $email, 
                    $hashed_password, 
                    $role, 
                    $class, 
                    $roll_number, 
                    $department, 
                    $subject, 
                    $security_question, 
                    $security_answer
                ]);
                
                $success = "Account created successfully! You can now <a href='login.php'>login</a>.";
                // Clear form
                $_POST = array();
                
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again. Error: " . $e->getMessage();
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up | Digital School Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Assets/css/signup.css">
    <style>
        .error-message {
            background-color: #ffe6e6;
            border: 1px solid #ff9999;
            color: #cc0000;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .success-message {
            background-color: #e6ffe6;
            border: 1px solid #99ff99;
            color: #006600;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .form-hint {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="signup-container">
    <div class="signup-card">
        <h2>Create Your Account</h2>
        <p class="subtitle">Join the Digital School Management System</p>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
                <small class="form-hint">Must be at least 6 characters long</small>
            </div>

            <div class="form-group">
                <label for="securityQuestion">Security Question *</label>
                <select id="securityQuestion" name="securityQuestion" required>
                    <option value="">-- Choose Question --</option>
                    <option value="pet" <?php echo (isset($_POST['securityQuestion']) && $_POST['securityQuestion'] == 'pet') ? 'selected' : ''; ?>>What is your pet's name?</option>
                    <option value="singer" <?php echo (isset($_POST['securityQuestion']) && $_POST['securityQuestion'] == 'singer') ? 'selected' : ''; ?>>Who is your favorite singer?</option>
                </select>
                <small class="form-hint">Select a question for password recovery</small>
            </div>

            <div class="form-group">
                <label for="securityAnswer">Security Answer *</label>
                <input type="text" id="securityAnswer" name="answer" placeholder="Ex: Dog, Atif Aslam" value="<?php echo htmlspecialchars($_POST['answer'] ?? ''); ?>" required>
                <small class="form-hint">Enter the answer to your security question</small>
            </div>

            <div class="role-box">
                <label class="role-title">Register As *</label>
                <div class="role-options">
                    <label>
                        <input type="radio" name="role" value="student" onclick="toggleFields()" required 
                               <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'checked' : ''; ?>>
                        Student
                    </label>
                    <label>
                        <input type="radio" name="role" value="teacher" onclick="toggleFields()" required
                               <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'checked' : ''; ?>>
                        Teacher
                    </label>
                </div>
            </div>

            <div id="student-fields" class="extra-fields" 
                 style="display: <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>Class *</label>
                    <input type="text" name="class" value="<?php echo htmlspecialchars($_POST['class'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" name="roll" value="<?php echo htmlspecialchars($_POST['roll'] ?? ''); ?>">
                </div>
            </div>

            <div id="teacher-fields" class="extra-fields" 
                 style="display: <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>Department *</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn">Create Account</button>

            <p class="login-link">
                Already have an account?
                <a href="login.php">Login</a>
            </p>
        </form>
    </div>
</div>

<script>
    const roleRadios = document.querySelectorAll('input[name="role"]');
    const studentFields = document.getElementById('student-fields');
    const teacherFields = document.getElementById('teacher-fields');

    // Function to toggle fields
    function toggleFields() {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'none';

        const selectedRole = document.querySelector('input[name="role"]:checked');
        if (selectedRole) {
            if (selectedRole.value === 'student') {
                studentFields.style.display = 'block';
            } 
            else if (selectedRole.value === 'teacher') {
                teacherFields.style.display = 'block';
            }
        }
    }

    // Add event listeners
    roleRadios.forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>
</body>
</html>