<?php
require_once '../../php/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = intval($data['request_id'] ?? 0);
$newStage = sanitizeInput($data['stage'] ?? '');
$durationHours = isset($data['duration_hours']) ? floatval($data['duration_hours']) : null;

if (!$requestId || !$newStage) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request ID and stage are required']);
    exit();
}

if (!in_array($newStage, ['new', 'in_progress', 'repaired', 'scrap'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid stage']);
    exit();
}

try {
    $conn->beginTransaction();

    // Get current request details
    $stmt = $conn->prepare("SELECT equipment_id, stage FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }

    $user = getCurrentUser();

    // Update request stage
    if ($newStage === 'repaired' && $durationHours !== null) {
        $stmt = $conn->prepare("
            UPDATE maintenance_requests 
            SET stage = ?, duration_hours = ?, completed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newStage, $durationHours, $requestId]);
    } else {
        $stmt = $conn->prepare("
            UPDATE maintenance_requests 
            SET stage = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newStage, $requestId]);
    }

    // If moving to scrap, update equipment status
    if ($newStage === 'scrap') {
        $stmt = $conn->prepare("UPDATE equipment SET status = 'scrapped' WHERE id = ?");
        $stmt->execute([$request['equipment_id']]);

        // Log history
        $stmt = $conn->prepare("
            INSERT INTO request_history (request_id, action, new_value, user_id, created_at)
            VALUES (?, 'Equipment marked as scrap', 'Scrapped', ?, NOW())
        ");
        $stmt->execute([$requestId, $user['id']]);
    }

    // Log stage change
    $stmt = $conn->prepare("
        INSERT INTO request_history (request_id, action, new_value, user_id, created_at)
        VALUES (?, 'Stage changed', ?, ?, NOW())
    ");
    $stmt->execute([$requestId, ucfirst(str_replace('_', ' ', $newStage)), $user['id']]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request stage updated successfully'
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Update request stage error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>