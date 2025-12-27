<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!canPerform('delete_team')) {
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

if (!$teamId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID is required']);
    exit();
}

try {
    // Delete team members first
    $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->execute([$teamId]);

    // Delete team
    $stmt = $conn->prepare("DELETE FROM maintenance_teams WHERE id = ?");
    $stmt->execute([$teamId]);

    echo json_encode([
        'success' => true,
        'message' => 'Team deleted successfully'
    ]);

} catch (PDOException $e) {
    error_log("Delete team error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>