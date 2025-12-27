<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gearguard_db');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/gearguard'); // Change this to your project folder name

// Create connection with PDO
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Security settings for sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// CSRF Token functions
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS Protection
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($role)
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Check if user has any of the specified roles
function hasAnyRole($roles)
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Require specific role(s)
function requireRole($roles)
{
    requireLogin();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['user_role'], $roles)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: " . BASE_URL . "/pages/dashboard.php");
        exit();
    }
}

// Check if user can perform action
function canPerform($action)
{
    if (!isLoggedIn()) {
        return false;
    }

    $role = $_SESSION['user_role'];

    $permissions = [
        'admin' => [
            'view_all_equipment',
            'add_equipment',
            'edit_equipment',
            'delete_equipment',
            'view_all_requests',
            'create_request',
            'assign_request',
            'edit_request',
            'delete_request',
            'view_all_teams',
            'add_team',
            'edit_team',
            'delete_team',
            'view_reports',
            'manage_users'
        ],
        'manager' => [
            'view_all_equipment',
            'add_equipment',
            'edit_equipment',
            'view_all_requests',
            'create_request',
            'assign_request',
            'edit_request',
            'view_all_teams',
            'add_team',
            'view_reports'
        ],
        'technician' => [
            'view_all_equipment',
            'view_my_requests',
            'update_my_requests',
            'create_request',
            'view_my_team'
        ],
        'user' => [
            'create_request',
            'view_my_requests'
        ]
    ];

    return isset($permissions[$role]) && in_array($action, $permissions[$role]);
}

// Get current user info
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

// Get user's role display name
function getRoleDisplayName($role)
{
    $roleNames = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'technician' => 'Technician',
        'user' => 'User'
    ];
    return $roleNames[$role] ?? ucfirst($role);
}

// Get role badge color
function getRoleBadgeColor($role)
{
    $colors = [
        'admin' => 'red',
        'manager' => 'blue',
        'technician' => 'green',
        'user' => 'purple'
    ];
    return $colors[$role] ?? 'gray';
}

// Helper functions
function asset($path)
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function url($path)
{
    return BASE_URL . '/' . ltrim($path, '/');
}
?>