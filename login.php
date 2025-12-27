<?php
require_once 'php/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

$errors = [];
$info = "";

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $info = "You have been logged out successfully.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        // Validation
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($errors)) {
            try {
                // Get user data
                $stmt = $conn->prepare("
                    SELECT id, name, email, password, role, is_active, 
                           failed_attempts, locked_until 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // Check if user exists
                if (!$user) {
                    logLoginAttempt(null, $email, false);
                    $errors[] = "Invalid email or password";
                }
                // Check if account is active
                elseif (!$user['is_active']) {
                    $errors[] = "Your account has been deactivated. Please contact administrator.";
                }
                // Check if account is locked
                elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
                    $errors[] = "Account temporarily locked due to multiple failed login attempts. Try again in $remainingTime minutes.";
                }
                // Verify password
                elseif (password_verify($password, $user['password'])) {
                    // Password is correct - clear failed attempts
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET failed_attempts = 0, locked_until = NULL, last_login = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$user['id']]);

                    // Log successful login
                    logLoginAttempt($user['id'], $email, true);

                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();

                    // Handle "Remember Me" functionality
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                    }

                    // Redirect to dashboard
                    header("Location: pages/dashboard.php");
                    exit();
                } else {
                    // Password incorrect - record failed attempt
                    $newFailedAttempts = $user['failed_attempts'] + 1;
                    $lockUntil = null;

                    // Lock account after 5 failed attempts for 15 minutes
                    if ($newFailedAttempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    }

                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET failed_attempts = ?, locked_until = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$newFailedAttempts, $lockUntil, $user['id']]);

                    logLoginAttempt($user['id'], $email, false);

                    $attemptsLeft = 5 - $newFailedAttempts;
                    if ($attemptsLeft > 0) {
                        $errors[] = "Invalid email or password. You have $attemptsLeft attempts remaining.";
                    } else {
                        $errors[] = "Too many failed login attempts. Your account has been locked for 15 minutes.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $errors[] = "An error occurred during login. Please try again.";
            }
        }
    }
}

// Function to log login attempts
function logLoginAttempt($userId, $email, $success)
{
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (user_id, email, ip_address, user_agent, success) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $email,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $success ? 1 : 0
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GearGuard</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>GearGuard</span>
                </div>
                <h2>Welcome Back</h2>
                <p>Login to access your dashboard</p>
            </div>

            <?php if (!empty($info)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <p><?php echo htmlspecialchars($info); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required
                        autocomplete="email" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required
                            autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <a href="index.html" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>

</html>