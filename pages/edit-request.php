<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$requestId) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: requests.php");
    exit();
}

$errors = [];

// Fetch request details
try {
    $stmt = $conn->prepare("SELECT * FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $_SESSION['error'] = "Request not found.";
        header("Location: requests.php");
        exit();
    }
    
    // Check permission
    if (!canPerform('edit_request') && $request['created_by'] != $user['id']) {
        $_SESSION['error'] = "You don't have permission to edit this request.";
        header("Location: view-request.php?id=$requestId");
        exit();
    }
    
    // Fetch equipment list
    $stmt = $conn->query("
        SELECT id, equipment_name, serial_number, category, 
               maintenance_team_id, default_technician_id
        FROM equipment 
        WHERE status != 'scrapped'
        ORDER BY equipment_name ASC
    ");
    $equipmentList = $stmt->fetchAll();
    
    // Fetch teams
    $stmt = $conn->query("SELECT id, team_name FROM maintenance_teams ORDER BY team_name ASC");
    $teams = $stmt->fetchAll();
    
    // Fetch technicians
    $stmt = $conn->query("
        SELECT id, name 
        FROM users 
        WHERE role IN ('technician', 'manager') AND is_active = TRUE 
        ORDER BY name ASC
    ");
    $technicians = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Edit request error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading request details.";
    header("Location: requests.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $equipmentId = intval($_POST['equipment_id']);
        $subject = sanitizeInput($_POST['subject']);
        $description = sanitizeInput($_POST['description']);
        $requestType = sanitizeInput($_POST['request_type']);
        $priority = sanitizeInput($_POST['priority']);
        $scheduledDate = $_POST['scheduled_date'] ?? null;
        $assignedTo = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $durationHours = isset($_POST['duration_hours']) ? floatval($_POST['duration_hours']) : null;
        
        // Validation
        if (!$equipmentId) {
            $errors[] = "Equipment is required";
        }
        
        if (empty($subject)) {
            $errors[] = "Subject is required";
        }
        
        if (!in_array($requestType, ['corrective', 'preventive'])) {
            $errors[] = "Invalid request type";
        }
        
        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
            $errors[] = "Invalid priority";
        }
        
        // Update request if no errors
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE maintenance_requests SET
                        equipment_id = ?,
                        subject = ?,
                        description = ?,
                        request_type = ?,
                        priority = ?,
                        scheduled_date = ?,
                        assigned_to = ?,
                        duration_hours = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $equipmentId,
                    $subject,
                    $description,
                    $requestType,
                    $priority,
                    $scheduledDate,
                    $assignedTo,
                    $durationHours,
                    $requestId
                ]);
                
                // Log history
                $stmt = $conn->prepare("
                    INSERT INTO request_history (request_id, action, user_id, created_at)
                    VALUES (?, 'Request updated', ?, NOW())
                ");
                $stmt->execute([$requestId, $user['id']]);
                
                error_log("Request updated: ID #$requestId by " . $user['email']);
                
                $_SESSION['success'] = "Request updated successfully!";
                header("Location: view-request.php?id=$requestId");
                exit();
                
            } catch(PDOException $e) {
                error_log("Update request database error: " . $e->getMessage());
                $errors[] = "Failed to update request. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Request #<?php echo $request['id']; ?> - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>GearGuard</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if (canPerform('view_all_equipment')): ?>
            <a href="equipment.php" class="nav-item">
                <i class="fas fa-cogs"></i>
                <span>Equipment</span>
            </a>
            <?php endif; ?>
            
            <a href="kanban.php" class="nav-item">
                <i class="fas fa-columns"></i>
                <span>Kanban Board</span>
            </a>
            
            <a href="requests.php" class="nav-item active">
                <i class="fas fa-clipboard-list"></i>
                <span><?php echo hasRole('technician') ? 'My Requests' : 'All Requests'; ?></span>
            </a>
            
            <?php if (hasAnyRole(['admin', 'manager'])): ?>
            <a href="calendar.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Calendar</span>
            </a>
            <a href="teams.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Teams</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasRole('admin')): ?>
            <a href="users.php" class="nav-item">
                <i class="fas fa-user-cog"></i>
                <span>User Management</span>
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="user-role">
                        <span class="role-badge role-<?php echo getRoleBadgeColor($user['role']); ?>">
                            <?php echo getRoleDisplayName($user['role']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h1>Edit Request #<?php echo $request['id']; ?></h1>
                <p class="dashboard-subtitle">Modify maintenance request details</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.location.href='view-request.php?id=<?php echo $requestId; ?>'">
                    <i class="fas fa-arrow-left"></i>
                    Back to Details
                </button>
            </div>
        </header>
        
        <!-- Form Section -->
        <section class="form-section">
            <div class="form-container">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <ul>
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="request-form" id="editRequestForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Equipment Selection -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-cog"></i>
                            Equipment Information
                        </h3>
                        
                        <div class="form-group">
                            <label for="equipment_id">
                                <i class="fas fa-wrench"></i>
                                Select Equipment <span class="required">*</span>
                            </label>
                            <select id="equipment_id" name="equipment_id" required onchange="loadEquipmentDetails()">
                                <option value="">-- Select Equipment --</option>
                                <?php foreach($equipmentList as $equip): ?>
                                    <option 
                                        value="<?php echo $equip['id']; ?>"
                                        data-category="<?php echo htmlspecialchars($equip['category']); ?>"
                                        data-team-id="<?php echo $equip['maintenance_team_id']; ?>"
                                        data-tech-id="<?php echo $equip['default_technician_id']; ?>"
                                        <?php echo $request['equipment_id'] == $equip['id'] ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($equip['equipment_name']) . ' (' . htmlspecialchars($equip['serial_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="equipment-info-display" id="equipmentInfo" style="display: none;">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Category:</span>
                                    <span class="info-value" id="equipmentCategory">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Details -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Request Details
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="subject">
                                    <i class="fas fa-heading"></i>
                                    Subject <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="subject" 
                                    name="subject" 
                                    value="<?php echo htmlspecialchars($request['subject']); ?>"
                                    placeholder="e.g., Motor overheating issue"
                                    required
                                >
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    rows="4"
                                    placeholder="Provide detailed information about the maintenance request..."
                                ><?php echo htmlspecialchars($request['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="request_type">
                                    <i class="fas fa-tag"></i>
                                    Request Type <span class="required">*</span>
                                </label>
                                <select id="request_type" name="request_type" required>
                                    <option value="corrective" <?php echo $request['request_type'] === 'corrective' ? 'selected' : ''; ?>>Corrective (Repair)</option>
                                    <option value="preventive" <?php echo $request['request_type'] === 'preventive' ? 'selected' : ''; ?>>Preventive (Routine)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Priority <span class="required">*</span>
                                </label>
                                <select id="priority" name="priority" required>
                                    <option value="low" <?php echo $request['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $request['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $request['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $request['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="scheduled_date">
                                    <i class="fas fa-calendar-alt"></i>
                                    Scheduled Date
                                </label>
                                <input 
                                    type="datetime-local" 
                                    id="scheduled_date" 
                                    name="scheduled_date"
                                    value="<?php echo $request['scheduled_date'] ? date('Y-m-d\TH:i', strtotime($request['scheduled_date'])) : ''; ?>"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="duration_hours">
                                    <i class="fas fa-clock"></i>
                                    Duration (Hours)
                                </label>
                                <input 
                                    type="number" 
                                    id="duration_hours" 
                                    name="duration_hours"
                                    step="0.5"
                                    min="0"
                                    value="<?php echo $request['duration_hours'] ?? ''; ?>"
                                    placeholder="e.g., 2.5"
                                >
                                <small class="form-hint">Time spent on this maintenance task</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assignment -->
                    <?php if (canPerform('assign_request')): ?>
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-user-cog"></i>
                            Assignment
                        </h3>
                        
                        <div class="form-group">
                            <label for="assigned_to">
                                <i class="fas fa-user"></i>
                                Assign to Technician
                            </label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">-- Select Technician --</option>
                                <?php foreach($technicians as $tech): ?>
                                    <option 
                                        value="<?php echo $tech['id']; ?>"
                                        <?php echo $request['assigned_to'] == $tech['id'] ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($tech['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Submit Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="window.location.href='view-request.php?id=<?php echo $requestId; ?>'">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Request
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
    
    <script src="../js/dashboard.js"></script>
    <script src="../js/create-request.js"></script>
</body>
</html>
