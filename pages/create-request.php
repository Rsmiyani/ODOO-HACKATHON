    <?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission
if (!canPerform('create_request')) {
    $_SESSION['error'] = "You don't have permission to create requests.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();
$errors = [];
$success = "";

// Get request type from URL parameter
$requestType = isset($_GET['type']) ? $_GET['type'] : 'corrective';

// Fetch all equipment for dropdown
try {
    $stmt = $conn->query("
        SELECT id, equipment_name, serial_number, category, maintenance_team_id, default_technician_id
        FROM equipment 
        WHERE status != 'scrapped'
        ORDER BY equipment_name ASC
    ");
    $equipmentList = $stmt->fetchAll();
    
    // Fetch all teams
    $stmt = $conn->query("SELECT id, team_name FROM maintenance_teams ORDER BY team_name ASC");
    $teamsList = $stmt->fetchAll();
    
    // Fetch all technicians (for managers/admins)
    if (hasAnyRole(['admin', 'manager'])) {
        $stmt = $conn->query("
            SELECT id, name, role 
            FROM users 
            WHERE role IN ('technician', 'manager') AND is_active = TRUE
            ORDER BY name ASC
        ");
        $techniciansList = $stmt->fetchAll();
    } else {
        $techniciansList = [];
    }
    
} catch(PDOException $e) {
    error_log("Create request error: " . $e->getMessage());
    $equipmentList = [];
    $teamsList = [];
    $techniciansList = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize inputs
        $subject = sanitizeInput($_POST['subject']);
        $description = sanitizeInput($_POST['description']);
        $equipmentId = intval($_POST['equipment_id']);
        $requestType = sanitizeInput($_POST['request_type']);
        $priority = sanitizeInput($_POST['priority']);
        $scheduledDate = $_POST['scheduled_date'] ?? null;
        $assignedTo = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        
        // Validation
        if (empty($subject)) {
            $errors[] = "Subject is required";
        } elseif (strlen($subject) < 5) {
            $errors[] = "Subject must be at least 5 characters";
        }
        
        if (empty($equipmentId)) {
            $errors[] = "Equipment selection is required";
        }
        
        if (!in_array($requestType, ['corrective', 'preventive'])) {
            $errors[] = "Invalid request type";
        }
        
        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
            $errors[] = "Invalid priority level";
        }
        
        // Preventive maintenance requires scheduled date
        if ($requestType === 'preventive' && empty($scheduledDate)) {
            $errors[] = "Scheduled date is required for preventive maintenance";
        }
        
        // Validate scheduled date format
        if (!empty($scheduledDate)) {
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDate);
            if (!$dateTime) {
                $errors[] = "Invalid date format";
            }
        }
        
        // Create request if no errors
        if (empty($errors)) {
            try {
                // If no technician assigned and user is technician, assign to self
                if (!$assignedTo && hasRole('technician')) {
                    $assignedTo = $user['id'];
                }
                
                // Convert scheduled date to MySQL format
                $scheduledDateFormatted = null;
                if (!empty($scheduledDate)) {
                    $scheduledDateFormatted = date('Y-m-d H:i:s', strtotime($scheduledDate));
                }
                
                // Insert request
                $stmt = $conn->prepare("
                    INSERT INTO maintenance_requests 
                    (subject, description, equipment_id, request_type, priority, stage, scheduled_date, assigned_to, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'new', ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $subject,
                    $description,
                    $equipmentId,
                    $requestType,
                    $priority,
                    $scheduledDateFormatted,
                    $assignedTo,
                    $user['id']
                ]);
                
                $requestId = $conn->lastInsertId();
                
                // Log in request history
                $stmt = $conn->prepare("
                    INSERT INTO request_history (request_id, user_id, action, new_value, created_at) 
                    VALUES (?, ?, 'created', 'Request created', NOW())
                ");
                $stmt->execute([$requestId, $user['id']]);
                
                // Update equipment status to under_maintenance if corrective
                if ($requestType === 'corrective') {
                    $stmt = $conn->prepare("UPDATE equipment SET status = 'under_maintenance' WHERE id = ?");
                    $stmt->execute([$equipmentId]);
                }
                
                error_log("New maintenance request created: ID #$requestId by " . $user['email']);
                
                $_SESSION['success'] = "Maintenance request created successfully!";
                header("Location: kanban.php");
                exit();
                
            } catch(PDOException $e) {
                error_log("Create request database error: " . $e->getMessage());
                $errors[] = "Failed to create request. Please try again.";
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
    <title>Create Maintenance Request - GearGuard</title>
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
            
            <?php if (canPerform('view_all_requests') || canPerform('view_my_requests')): ?>
            <a href="requests.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span><?php echo hasRole('technician') ? 'My Requests' : 'All Requests'; ?></span>
            </a>
            <?php endif; ?>
            
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
                <h1>Create Maintenance Request</h1>
                <p class="dashboard-subtitle">Report equipment issues or schedule maintenance</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.location.href='kanban.php'">
                    <i class="fas fa-arrow-left"></i>
                    Back to Kanban
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
                
                <form method="POST" action="" class="request-form" id="requestForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Request Type Selection -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-clipboard-list"></i>
                            Request Type
                        </h3>
                        
                        <div class="request-type-selector">
                            <label class="type-option <?php echo $requestType === 'corrective' ? 'active' : ''; ?>">
                                <input type="radio" name="request_type" value="corrective" 
                                       <?php echo $requestType === 'corrective' ? 'checked' : ''; ?> required>
                                <div class="type-card">
                                    <i class="fas fa-wrench"></i>
                                    <h4>Corrective Maintenance</h4>
                                    <p>Emergency repair or breakdown</p>
                                </div>
                            </label>
                            
                            <label class="type-option <?php echo $requestType === 'preventive' ? 'active' : ''; ?>">
                                <input type="radio" name="request_type" value="preventive" 
                                       <?php echo $requestType === 'preventive' ? 'checked' : ''; ?> required>
                                <div class="type-card">
                                    <i class="fas fa-calendar-check"></i>
                                    <h4>Preventive Maintenance</h4>
                                    <p>Scheduled routine checkup</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
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
                                    placeholder="Brief description of the issue (e.g., 'Leaking Oil')"
                                    value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                                    required
                                    minlength="5"
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
                                    placeholder="Detailed description of the problem or maintenance needed..."
                                ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipment Selection with Auto-Fill -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-cogs"></i>
                            Equipment Details
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="equipment_id">
                                    <i class="fas fa-cog"></i>
                                    Select Equipment <span class="required">*</span>
                                </label>
                                <select id="equipment_id" name="equipment_id" required>
                                    <option value="">-- Choose Equipment --</option>
                                    <?php foreach($equipmentList as $equipment): ?>
                                        <option 
                                            value="<?php echo $equipment['id']; ?>"
                                            data-team-id="<?php echo $equipment['maintenance_team_id']; ?>"
                                            data-technician-id="<?php echo $equipment['default_technician_id']; ?>"
                                            data-category="<?php echo htmlspecialchars($equipment['category']); ?>"
                                            <?php echo (isset($_POST['equipment_id']) && $_POST['equipment_id'] == $equipment['id']) ? 'selected' : ''; ?>
                                        >
                                            <?php echo htmlspecialchars($equipment['equipment_name']); ?> 
                                            (<?php echo htmlspecialchars($equipment['serial_number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="equipment_category">
                                    <i class="fas fa-tag"></i>
                                    Category
                                </label>
                                <input 
                                    type="text" 
                                    id="equipment_category" 
                                    readonly
                                    placeholder="Auto-filled when equipment is selected"
                                    class="readonly-field"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="maintenance_team">
                                    <i class="fas fa-users"></i>
                                    Maintenance Team
                                </label>
                                <input 
                                    type="text" 
                                    id="maintenance_team" 
                                    readonly
                                    placeholder="Auto-filled from equipment"
                                    class="readonly-field"
                                >
                                <input type="hidden" id="team_id" name="team_id">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assignment & Scheduling -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Assignment & Scheduling
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="priority">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Priority <span class="required">*</span>
                                </label>
                                <select id="priority" name="priority" required>
                                    <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>
                                        Low Priority
                                    </option>
                                    <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] == 'medium') ? 'selected' : ''; ?>>
                                        Medium Priority
                                    </option>
                                    <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>
                                        High Priority
                                    </option>
                                    <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'selected' : ''; ?>>
                                        Urgent
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="scheduledDateGroup">
                                <label for="scheduled_date">
                                    <i class="fas fa-clock"></i>
                                    Scheduled Date & Time 
                                    <span class="required" id="dateRequired" style="display: <?php echo $requestType === 'preventive' ? 'inline' : 'none'; ?>;">*</span>
                                </label>
                                <input 
                                    type="datetime-local" 
                                    id="scheduled_date" 
                                    name="scheduled_date"
                                    value="<?php echo isset($_POST['scheduled_date']) ? $_POST['scheduled_date'] : ''; ?>"
                                    min="<?php echo date('Y-m-d\TH:i'); ?>"
                                >
                            </div>
                            
                            <?php if (hasAnyRole(['admin', 'manager'])): ?>
                            <div class="form-group full-width">
                                <label for="assigned_to">
                                    <i class="fas fa-user"></i>
                                    Assign to Technician
                                </label>
                                <select id="assigned_to" name="assigned_to">
                                    <option value="">-- Auto-assign or Choose Later --</option>
                                    <?php foreach($techniciansList as $technician): ?>
                                        <option 
                                            value="<?php echo $technician['id']; ?>"
                                            <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $technician['id']) ? 'selected' : ''; ?>
                                        >
                                            <?php echo htmlspecialchars($technician['name']); ?> 
                                            (<?php echo ucfirst($technician['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">Leave empty to auto-assign based on equipment's default technician</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="window.location.href='kanban.php'">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Create Request
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
