<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!canPerform('add_team')) {
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

$teamName = sanitizeInput($data['team_name'] ?? '');
$description = sanitizeInput($data['description'] ?? '');

if (empty($teamName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team name is required']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO maintenance_teams (team_name, description, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$teamName, $description]);

    echo json_encode([
        'success' => true,
        'message' => 'Team created successfully',
        'team_id' => $conn->lastInsertId()
    ]);

} catch (PDOException $e) {
    error_log("Create team error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>