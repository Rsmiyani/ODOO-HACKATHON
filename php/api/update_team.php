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
$teamName = sanitizeInput($data['team_name'] ?? '');
$description = sanitizeInput($data['description'] ?? '');

if (!$teamId || empty($teamName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID and name are required']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE maintenance_teams SET team_name = ?, description = ? WHERE id = ?");
    $stmt->execute([$teamName, $description, $teamId]);

    echo json_encode([
        'success' => true,
        'message' => 'Team updated successfully'
    ]);

} catch (PDOException $e) {
    error_log("Update team error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>