<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!canPerform('delete_equipment')) {
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
$equipmentId = intval($data['equipment_id'] ?? 0);

if (!$equipmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Equipment ID is required']);
    exit();
}

try {
    // Check if equipment has active maintenance requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM maintenance_requests 
        WHERE equipment_id = ? AND stage IN ('new', 'in_progress')
    ");
    $stmt->execute([$equipmentId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete equipment with active maintenance requests. Please complete or close all requests first.'
        ]);
        exit();
    }

    // Delete equipment
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$equipmentId]);

    echo json_encode([
        'success' => true,
        'message' => 'Equipment deleted successfully'
    ]);

} catch (PDOException $e) {
    error_log("Delete equipment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>