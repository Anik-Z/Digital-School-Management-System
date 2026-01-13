<?php
require_once 'db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Find user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        if ($user['role'] == 'student') {
            $_SESSION['class'] = $user['class'];
            $_SESSION['roll_number'] = $user['roll_number'];
            header("Location: ../student/dashboard.html");
        } elseif ($user['role'] == 'teacher') {
            $_SESSION['department'] = $user['department'];
            $_SESSION['subject'] = $user['subject'];
            header("Location: ../teacher/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Digital School Management System</title>
    <link rel="stylesheet" href="../Assets/css/login.css">
</head>
<body>
<header class="nav">
    <div class="brand">
        <img src="../Assets/images/logo.png" alt="Logo" class="logo-img">
        Digital School Management System
    </div>
    <div class="nav-buttons">
        <a href="../index.html" class="home-btn">Home</a>
    </div>
</header>

<div class="login-wrapper">
    <div class="login-card">
        
        <h1>Welcome Back</h1>
        <img src="../Assets/images/logo.png" alt="Logo" class="logo-img">
        <p class="subtitle">
            Digital School Management System
        </p>
        
        <?php if ($error): ?>
            <div class="error-message" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" 
                       value="<?php echo $_POST['email'] ?? ''; ?>" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="form-extra">
                <a href="forgetpassword.php" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">
                Login
            </button>
        </form>

        <p class="signup-text">
            Don't have an account?
            <a href="register.php">Create one</a>
        </p>

    </div>
</div>

</body>
</html>