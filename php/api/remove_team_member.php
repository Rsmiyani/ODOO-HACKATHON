<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!canPerform('edit_team')) {
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

$teamId = intval($data['team_id'] ?? 0);
$userId = intval($data['user_id'] ?? 0);

if (!$teamId || !$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID and User ID are required']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
    $stmt->execute([$teamId, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Member removed successfully'
    ]);

} catch (PDOException $e) {
    error_log("Remove team member error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>