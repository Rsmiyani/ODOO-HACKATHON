<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get equipment ID
$equipmentId = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

if (!$equipmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Equipment ID is required']);
    exit();
}

try {
    // Get equipment details
    $stmt = $conn->prepare("
        SELECT equipment_name, serial_number 
        FROM equipment 
        WHERE id = ?
    ");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit();
    }

    // Get all maintenance requests for this equipment
    $stmt = $conn->prepare("
        SELECT 
            mr.id,
            mr.subject,
            mr.description,
            mr.stage,
            mr.priority,
            mr.request_type,
            mr.scheduled_date,
            mr.created_at,
            u.name as assigned_to_name,
            CASE 
                WHEN mr.scheduled_date < NOW() AND mr.stage IN ('new', 'in_progress') 
                THEN TRUE 
                ELSE FALSE 
            END as is_overdue
        FROM maintenance_requests mr
        LEFT JOIN users u ON mr.assigned_to = u.id
        WHERE mr.equipment_id = ?
        ORDER BY 
            CASE mr.stage
                WHEN 'new' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'repaired' THEN 3
                WHEN 'scrap' THEN 4
            END,
            mr.created_at DESC
    ");
    $stmt->execute([$equipmentId]);
    $requests = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'equipment' => $equipment,
        'requests' => $requests,
        'count' => count($requests)
    ]);

} catch (PDOException $e) {
    error_log("Get equipment requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>