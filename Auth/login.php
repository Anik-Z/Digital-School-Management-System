<?php
session_start(); 
require_once 'db_connection.php';

$error = "";

define("ADMIN_EMAIL", "admin@school.com");
define("ADMIN_PASSWORD", "admin123");


if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {

    $cookie_data = base64_decode($_COOKIE['remember_me']);
    $parts = explode('|', $cookie_data);

    if (count($parts) === 2) {

        $user_id = mysqli_real_escape_string($conn, $parts[0]);
        $token = mysqli_real_escape_string($conn, $parts[1]);

        $hashedToken = hash('sha256', $token);

        $sql = "
            SELECT u.*
            FROM users u
            INNER JOIN remember_tokens rt ON u.id = rt.user_id
            WHERE u.id = '$user_id'
              AND rt.token = '$hashedToken'
              AND rt.expires > NOW()
        ";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'student') {
                $_SESSION['class'] = $user['class'];
                $_SESSION['roll_number'] = $user['roll_number'];
                header("Location: ../student/dashboard.php");
                exit();
            }

            if ($user['role'] === 'teacher') {
                $_SESSION['department'] = $user['department'];
                $_SESSION['subject'] = $user['subject'];
                header("Location: ../teacher/dashboard.php");
                exit();
            }

        } else {
            setcookie("remember_me", "", time() - 3600, "/");
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    $email = $_POST['email'];
    $password = $_POST['password'];
 
    if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: ../Admin/dashboard.php");
        exit;
    } else {
        $error = "Invalid Admin Credentials";
    }

    if ($result && mysqli_num_rows($result) === 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            /* ===== REMEMBER ME ===== */
            if ($remember) {

                $token = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $token);
                $expires = date("Y-m-d H:i:s", time() + (7 * 24 * 60 * 60));

                $uid = $user['id'];

                mysqli_query($conn, "DELETE FROM remember_tokens WHERE user_id='$uid'");

                mysqli_query(
                    $conn,
                    "INSERT INTO remember_tokens (user_id, token, expires)
                     VALUES ('$uid', '$hashedToken', '$expires')"
                );

                $cookieValue = base64_encode($uid . "|" . $token);
                setcookie("remember_me", $cookieValue, time() + (7 * 24 * 60 * 60), "/");
            }

            /* ===== ROLE REDIRECT ===== */
            if ($user['role'] === 'student') {
                $_SESSION['class'] = $user['class'];
                $_SESSION['roll_number'] = $user['roll_number'];
                header("Location: ../student/dashboard.php");
                exit();
            }

            if ($user['role'] === 'teacher') {
                $_SESSION['department'] = $user['department'];
                $_SESSION['subject'] = $user['subject'];
                header("Location: ../teacher/dashboard.php");
                exit();
            }

        } else {
            $error = "Invalid email or password";
        }

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
    <style>
        .remember-me {
            display: flex;
            align-items: center;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }
    </style>
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
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <!-- REMEMBER ME CHECKBOX -->
            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Remember me</label>
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