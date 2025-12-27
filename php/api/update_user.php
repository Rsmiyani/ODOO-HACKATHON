<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$userId = intval($data['user_id'] ?? 0);
$name = sanitizeInput($data['name'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
$role = sanitizeInput($data['role'] ?? '');
$password = $data['password'] ?? '';
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Validation
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit();
}

if (!in_array($role, ['admin', 'manager', 'technician'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

if (!empty($password) && strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

try {
    // Check if email already exists for other users
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Update user
    if (!empty($password)) {
        // Update with new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, password = ?, role = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, $hashedPassword, $role, $isActive, $userId]);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, role = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, $role, $isActive, $userId]);
    }

    error_log("User updated: ID #$userId by admin");

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);

} catch (PDOException $e) {
    error_log("Update user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>