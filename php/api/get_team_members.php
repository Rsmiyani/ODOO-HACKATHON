<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teamId = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

if (!$teamId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID is required']);
    exit();
}

try {
    // Get team info
    $stmt = $conn->prepare("SELECT id, team_name, description FROM maintenance_teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Team not found']);
        exit();
    }

    // Get team members
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role
        FROM users u
        INNER JOIN team_members tm ON u.id = tm.user_id
        WHERE tm.team_id = ?
        ORDER BY u.name ASC
    ");
    $stmt->execute([$teamId]);
    $members = $stmt->fetchAll();

    // Get available users (not in this team)
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role
        FROM users u
        WHERE u.is_active = TRUE 
        AND u.id NOT IN (SELECT user_id FROM team_members WHERE team_id = ?)
        AND u.role IN ('technician', 'manager')
        ORDER BY u.name ASC
    ");
    $stmt->execute([$teamId]);
    $availableUsers = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'team' => $team,
        'members' => $members,
        'available_users' => $availableUsers
    ]);

} catch (PDOException $e) {
    error_log("Get team members error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>