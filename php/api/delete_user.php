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

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

// Prevent deleting yourself
$currentUser = getCurrentUser();
if ($userId == $currentUser['id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
    exit();
}

try {
    // Check if user has assigned requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM maintenance_requests 
        WHERE assigned_to = ? AND stage IN ('new', 'in_progress')
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete user with active assigned requests. Please reassign or complete them first.'
        ]);
        exit();
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    error_log("User deleted: ID #$userId by admin");

    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);

} catch (PDOException $e) {
    error_log("Delete user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>