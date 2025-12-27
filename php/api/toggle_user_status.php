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
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$isActive, $userId]);

    $action = $isActive ? 'activated' : 'deactivated';
    error_log("User $action: ID #$userId by admin");

    echo json_encode([
        'success' => true,
        'message' => "User $action successfully"
    ]);

} catch (PDOException $e) {
    error_log("Toggle user status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>