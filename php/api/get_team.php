<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get team ID from query parameter
$teamId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$teamId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID is required']);
    exit();
}

try {
    // Fetch team details
    $stmt = $conn->prepare("SELECT id, team_name, description FROM maintenance_teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Team not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'team' => $team
    ]);

} catch (PDOException $e) {
    error_log("Get team error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>