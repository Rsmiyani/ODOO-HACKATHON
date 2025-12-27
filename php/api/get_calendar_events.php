<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasAnyRole(['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Get filter parameters
$teamFilter = isset($_GET['team']) ? intval($_GET['team']) : 0;
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Build query
    $sql = "
        SELECT 
            mr.id,
            mr.subject,
            mr.description,
            mr.stage,
            mr.priority,
            mr.request_type,
            mr.scheduled_date,
            mr.created_at,
            e.equipment_name,
            e.serial_number,
            e.category,
            e.maintenance_team_id,
            u.name as assigned_to_name,
            mt.team_name
        FROM maintenance_requests mr
        INNER JOIN equipment e ON mr.equipment_id = e.id
        LEFT JOIN users u ON mr.assigned_to = u.id
        LEFT JOIN maintenance_teams mt ON e.maintenance_team_id = mt.id
        WHERE mr.scheduled_date IS NOT NULL
    ";

    $params = [];

    // Add filters
    if ($teamFilter > 0) {
        $sql .= " AND e.maintenance_team_id = ?";
        $params[] = $teamFilter;
    }

    if (!empty($typeFilter)) {
        $sql .= " AND mr.request_type = ?";
        $params[] = $typeFilter;
    }

    if (!empty($statusFilter)) {
        $sql .= " AND mr.stage = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY mr.scheduled_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);

} catch (PDOException $e) {
    error_log("Get calendar events error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>