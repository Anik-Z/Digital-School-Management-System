<?php
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $security_question = $_POST['securityQuestion'];
    $security_answer = trim($_POST['answer']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    
    // Validate
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check user and security answer
        $sql = "SELECT * FROM users WHERE email = ? AND security_question = ? AND security_answer = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email, $security_question, $security_answer]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt->execute([$hashed_password, $email])) {
                $success = "Password reset successfully! You can now login with your new password.";
            } else {
                $error = "Password reset failed. Please try again.";
            }
        } else {
            $error = "Invalid email, security question, or answer";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Password Recovery - Academic Performance System</title>
    <link rel="stylesheet" href="../Assets/css/forget.css" />
  </head>
  <body class="auth-page">
    <main>
      <div class="auth-container">
        <div class="auth-card">
          <div class="auth-header">
            <h2>Password Recovery</h2>
            <p>Reset your account password</p>
          </div>
          
          <?php if ($error): ?>
            <div class="error-message" style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $error; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($success): ?>
            <div class="success-message" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $success; ?>
            </div>
          <?php endif; ?>

          <form class="auth-form" method="post" action="">
            <!-- Email Field -->
            <div class="form-group">
              <label for="recoveryEmail">Email Address</label>
              <input
                type="email"
                id="recoveryEmail"
                name="email"
                placeholder="name@university.edu"
                value="<?php echo $_POST['email'] ?? ''; ?>"
                required
              />
              <small class="form-hint"
                >Enter the email associated with your account</small
              >
            </div>

            <!-- Security Question -->
            <div class="form-group">
              <label for="securityQuestion">Security Question</label>
              <select id="securityQuestion" name="securityQuestion" required>
                <option value="">-- Choose Question --</option>
                <option value="pet" <?php echo (isset($_POST['securityQuestion']) && $_POST['securityQuestion'] == 'pet') ? 'selected' : ''; ?>>What is your pet Name?</option>
                <option value="singer" <?php echo (isset($_POST['securityQuestion']) && $_POST['securityQuestion'] == 'singer') ? 'selected' : ''; ?>>Who is your Favourite Singer?</option>
              </select>
            </div>

            <!-- Security Answer -->
            <div class="form-group">
              <label for="recoveryAnswer">Type your Answer</label>
              <input
                type="text"
                id="recoveryAnswer"
                name="answer"
                placeholder="Ex: Dog, Atif Aslam"
                value="<?php echo $_POST['answer'] ?? ''; ?>"
                required
              />
              <small class="form-hint"
                >Enter the answer to your security question</small
              >
            </div>

            <!-- New Password -->
            <div class="form-group">
              <label for="recoveryPass">Reset Your Password</label>
              <input
                type="password"
                id="recoveryPass"
                name="password"
                placeholder="Enter New Password"
                required
              />
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
              <label for="confirmPass">Confirm New Password</label>
              <input
                type="password"
                id="confirmPass"
                name="confirmPassword"
                placeholder="Confirm New Password"
                required
              />
              <small class="form-hint"
                >Re-enter your new password to confirm</small
              >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
              Reset Password
            </button>
          </form>

          <div class="auth-footer">
            <p>
              Remember your password?
              <a href="login.php" class="text-link">Back to login</a>
            </p>
          </div>
        </div>

        <div class="auth-info">
          <div class="info-card">
            <h3>Security Information</h3>
            <p>For security purposes, password recovery requires:</p>
            <ul>
              <li>Account role verification</li>
              <li>Security question response</li>
              <li>Email confirmation</li>
            </ul>

            <div class="security-tip">
              <h4>Need immediate assistance?</h4>
              <p>Contact the IT Help Desk:</p>
              <p><strong>Email:</strong> helpaiub@aiub.edu</p>
              <p>Available Sunday-Friday, 8AM-6PM</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </body>
</html>