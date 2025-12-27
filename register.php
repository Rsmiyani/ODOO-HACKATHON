<?php
require_once 'php/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize inputs
        $name = sanitizeInput($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = sanitizeInput($_POST['role'] ?? 'user');

        // Validation Rules
        if (empty($name)) {
            $errors[] = "Name is required";
        } elseif (strlen($name) < 3) {
            $errors[] = "Name must be at least 3 characters";
        } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            $errors[] = "Name can only contain letters and spaces";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Password validation (comprehensive)
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        } elseif (!preg_match("/[a-z]/", $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        } elseif (!preg_match("/[0-9]/", $password)) {
            $errors[] = "Password must contain at least one number";
        } elseif (!preg_match("/[^a-zA-Z0-9]/", $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Validate role
        if (!in_array($role, ['admin', 'manager', 'technician', 'user'])) {
            $errors[] = "Invalid role selected";
        }

        // Check if email already exists
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $errors[] = "Email already registered. Please login instead.";
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again.";
            }
        }

        // Register user if no errors
        if (empty($errors)) {
            try {
                // Hash password using PASSWORD_DEFAULT (bcrypt - more compatible)
                // Argon2id might not be available on all systems
                if (defined('PASSWORD_ARGON2ID')) {
                    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                }

                // Generate verification token
                $verificationToken = bin2hex(random_bytes(32));

                // Insert user into database
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, role, verification_token, email_verified, is_active) 
                    VALUES (?, ?, ?, ?, ?, FALSE, TRUE)
                ");

                $stmt->execute([$name, $email, $hashedPassword, $role, $verificationToken]);

                // Log successful registration
                error_log("New user registered: " . $email . " (Role: " . $role . ")");

                $success = "Registration successful! You can now login with your credentials.";

                // In production, send verification email here
                // sendVerificationEmail($email, $verificationToken);

            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GearGuard</title>
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
                <h2>Create Account</h2>
                <p>Join us to manage your maintenance operations</p>
            </div>

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

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <p><?php echo htmlspecialchars($success); ?></p>
                        <a href="login.php" class="btn-link">Login Now →</a>
                    </div>
                </div>
            <?php else: ?>

                <form method="POST" action="" class="auth-form" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i>
                            Full Name
                        </label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required
                            autocomplete="name">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required
                            autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-user-tag"></i>
                            Role
                        </label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo (!isset($_POST['role']) || $_POST['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="technician" <?php echo (isset($_POST['role']) && $_POST['role'] == 'technician') ? 'selected' : ''; ?>>Technician</option>
                            <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" placeholder="Create a strong password"
                                required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Confirm your password" required autocomplete="new-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <p><strong>Password must contain:</strong></p>
                        <ul>
                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="req-upper"><i class="fas fa-circle"></i> One uppercase letter</li>
                            <li id="req-lower"><i class="fas fa-circle"></i> One lowercase letter</li>
                            <li id="req-number"><i class="fas fa-circle"></i> One number</li>
                            <li id="req-special"><i class="fas fa-circle"></i> One special character</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>

            <?php endif; ?>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
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