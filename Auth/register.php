<?php
require_once 'db_connection.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $security_question = $_POST['securityQuestion'] ?? '';
    $security_answer = trim($_POST['answer'] ?? '');

    $class = $_POST['class'] ?? '';
    $roll = $_POST['roll'] ?? '';
    $department = $_POST['department'] ?? '';
    $subject = $_POST['subject'] ?? '';

    if ($full_name === "")
        $error = "Full name required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = "Invalid email";
    elseif (strlen($password) < 6)
        $error = "Password minimum 6 characters";
    elseif ($role === "")
        $error = "Select role";
    elseif ($security_question === "")
        $error = "Select security question";
    elseif ($security_answer === "")
        $error = "Security answer required";
    elseif ($role === "student" && $class === "")
        $error = "Class required";
    elseif ($role === "teacher" && $department === "")
        $error = "Department required";

    else {

        $full_name = mysqli_real_escape_string($conn, $full_name);
        $email = mysqli_real_escape_string($conn, $email);
        $security_question = mysqli_real_escape_string($conn, $security_question);
        $security_answer = mysqli_real_escape_string($conn, $security_answer);
        $class = mysqli_real_escape_string($conn, $class);
        $roll = mysqli_real_escape_string($conn, $roll);
        $department = mysqli_real_escape_string($conn, $department);
        $subject = mysqli_real_escape_string($conn, $subject);
        $role = mysqli_real_escape_string($conn, $role);

        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");

        if (mysqli_num_rows($check) > 0) {
            $error = "Email already exists";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users 
            (full_name,email,password,role,class,roll_number,department,subject,security_question,security_answer)
            VALUES
            ('$full_name','$email','$hashed','$role','$class','$roll','$department','$subject','$security_question','$security_answer')";

            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful";
                $_POST = [];
            } else {
                $error = mysqli_error($conn);
            }
        }
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
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
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
                <input type="text" id="securityAnswer" name="answer" placeholder="Ex: Dog, Atif Aslam" value="<?php echo isset($_POST['answer']) ? htmlspecialchars($_POST['answer']) : ''; ?>" required>
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
                    <input type="text" name="class" value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" name="roll" value="<?php echo isset($_POST['roll']) ? htmlspecialchars($_POST['roll']) : ''; ?>">
                </div>
            </div>

            <div id="teacher-fields" class="extra-fields" 
                 style="display: <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label>Department *</label>
                    <input type="text" name="department" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
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