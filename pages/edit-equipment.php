<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission
if (!canPerform('edit_equipment')) {
    $_SESSION['error'] = "You don't have permission to edit equipment.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();
$equipmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$equipmentId) {
    $_SESSION['error'] = "Invalid equipment ID.";
    header("Location: equipment.php");
    exit();
}

$errors = [];

// Fetch equipment details
try {
    $stmt = $conn->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch();
    
    if (!$equipment) {
        $_SESSION['error'] = "Equipment not found.";
        header("Location: equipment.php");
        exit();
    }
    
    // Fetch teams and technicians for dropdowns
    $stmt = $conn->query("SELECT id, team_name FROM maintenance_teams ORDER BY team_name ASC");
    $teams = $stmt->fetchAll();
    
    $stmt = $conn->query("
        SELECT id, name 
        FROM users 
        WHERE role IN ('technician', 'manager') AND is_active = TRUE 
        ORDER BY name ASC
    ");
    $technicians = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Edit equipment error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading equipment details.";
    header("Location: equipment.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize inputs
        $equipmentName = sanitizeInput($_POST['equipment_name']);
        $serialNumber = sanitizeInput($_POST['serial_number']);
        $category = sanitizeInput($_POST['category']);
        $department = sanitizeInput($_POST['department']);
        $location = sanitizeInput($_POST['location']);
        $assignedToEmployee = sanitizeInput($_POST['assigned_to_employee'] ?? '');
        $maintenanceTeamId = isset($_POST['maintenance_team_id']) ? intval($_POST['maintenance_team_id']) : null;
        $defaultTechnicianId = isset($_POST['default_technician_id']) ? intval($_POST['default_technician_id']) : null;
        $purchaseDate = $_POST['purchase_date'] ?? null;
        $warrantyExpiry = $_POST['warranty_expiry'] ?? null;
        $status = sanitizeInput($_POST['status']);
        
        // Validation
        if (empty($equipmentName)) {
            $errors[] = "Equipment name is required";
        }
        
        if (empty($serialNumber)) {
            $errors[] = "Serial number is required";
        } else {
            // Check if serial number already exists for other equipment
            $stmt = $conn->prepare("SELECT id FROM equipment WHERE serial_number = ? AND id != ?");
            $stmt->execute([$serialNumber, $equipmentId]);
            if ($stmt->fetch()) {
                $errors[] = "Serial number already exists";
            }
        }
        
        if (empty($category)) {
            $errors[] = "Category is required";
        }
        
        if (empty($department)) {
            $errors[] = "Department is required";
        }
        
        if (empty($location)) {
            $errors[] = "Location is required";
        }
        
        if (!in_array($status, ['active', 'under_maintenance', 'scrapped'])) {
            $errors[] = "Invalid status";
        }
        
        // Update equipment if no errors
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE equipment SET
                        equipment_name = ?,
                        serial_number = ?,
                        category = ?,
                        department = ?,
                        location = ?,
                        assigned_to_employee = ?,
                        maintenance_team_id = ?,
                        default_technician_id = ?,
                        purchase_date = ?,
                        warranty_expiry = ?,
                        status = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $equipmentName,
                    $serialNumber,
                    $category,
                    $department,
                    $location,
                    $assignedToEmployee,
                    $maintenanceTeamId,
                    $defaultTechnicianId,
                    $purchaseDate,
                    $warrantyExpiry,
                    $status,
                    $equipmentId
                ]);
                
                error_log("Equipment updated: ID #$equipmentId by " . $user['email']);
                
                $_SESSION['success'] = "Equipment updated successfully!";
                header("Location: view-equipment.php?id=$equipmentId");
                exit();
                
            } catch(PDOException $e) {
                error_log("Update equipment database error: " . $e->getMessage());
                $errors[] = "Failed to update equipment. Please try again.";
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
    <title>Edit Equipment - GearGuard</title>
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
            
            <a href="equipment.php" class="nav-item active">
                <i class="fas fa-cogs"></i>
                <span>Equipment</span>
            </a>
            
            <a href="kanban.php" class="nav-item">
                <i class="fas fa-columns"></i>
                <span>Kanban Board</span>
            </a>
            
            <a href="requests.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>All Requests</span>
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
                <h1>Edit Equipment</h1>
                <p class="dashboard-subtitle"><?php echo htmlspecialchars($equipment['equipment_name']); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.location.href='view-equipment.php?id=<?php echo $equipmentId; ?>'">
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
                
                <form method="POST" action="" class="request-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Basic Information -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="equipment_name">
                                    <i class="fas fa-cog"></i>
                                    Equipment Name <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="equipment_name" 
                                    name="equipment_name" 
                                    value="<?php echo htmlspecialchars($equipment['equipment_name']); ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="serial_number">
                                    <i class="fas fa-barcode"></i>
                                    Serial Number <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="serial_number" 
                                    name="serial_number" 
                                    value="<?php echo htmlspecialchars($equipment['serial_number']); ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="category">
                                    <i class="fas fa-tag"></i>
                                    Category <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="category" 
                                    name="category" 
                                    value="<?php echo htmlspecialchars($equipment['category']); ?>"
                                    required
                                    list="categoryList"
                                >
                                <datalist id="categoryList">
                                    <option value="Production Machinery">
                                    <option value="Office Equipment">
                                    <option value="IT Equipment">
                                    <option value="Vehicles">
                                    <option value="Tools">
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">
                                    <i class="fas fa-circle"></i>
                                    Status <span class="required">*</span>
                                </label>
                                <select id="status" name="status" required>
                                    <option value="active" <?php echo $equipment['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="under_maintenance" <?php echo $equipment['status'] === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                    <option value="scrapped" <?php echo $equipment['status'] === 'scrapped' ? 'selected' : ''; ?>>Scrapped</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location & Assignment -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Location & Assignment
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="department">
                                    <i class="fas fa-building"></i>
                                    Department <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="department" 
                                    name="department" 
                                    value="<?php echo htmlspecialchars($equipment['department']); ?>"
                                    required
                                    list="departmentList"
                                >
                                <datalist id="departmentList">
                                    <option value="Production">
                                    <option value="IT">
                                    <option value="Admin">
                                    <option value="HR">
                                    <option value="Finance">
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">
                                    <i class="fas fa-map-pin"></i>
                                    Location <span class="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="location" 
                                    name="location" 
                                    value="<?php echo htmlspecialchars($equipment['location']); ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="assigned_to_employee">
                                    <i class="fas fa-user"></i>
                                    Assigned to Employee
                                </label>
                                <input 
                                    type="text" 
                                    id="assigned_to_employee" 
                                    name="assigned_to_employee" 
                                    value="<?php echo htmlspecialchars($equipment['assigned_to_employee'] ?? ''); ?>"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Details -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-wrench"></i>
                            Maintenance Details
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="maintenance_team_id">
                                    <i class="fas fa-users"></i>
                                    Maintenance Team
                                </label>
                                <select id="maintenance_team_id" name="maintenance_team_id">
                                    <option value="">-- Select Team --</option>
                                    <?php foreach($teams as $team): ?>
                                        <option value="<?php echo $team['id']; ?>"
                                                <?php echo $equipment['maintenance_team_id'] == $team['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="default_technician_id">
                                    <i class="fas fa-user-cog"></i>
                                    Default Technician
                                </label>
                                <select id="default_technician_id" name="default_technician_id">
                                    <option value="">-- Select Technician --</option>
                                    <?php foreach($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>"
                                                <?php echo $equipment['default_technician_id'] == $tech['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purchase & Warranty -->
                    <div class="form-card">
                        <h3 class="form-card-title">
                            <i class="fas fa-calendar-check"></i>
                            Purchase & Warranty Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="purchase_date">
                                    <i class="fas fa-shopping-cart"></i>
                                    Purchase Date
                                </label>
                                <input 
                                    type="date" 
                                    id="purchase_date" 
                                    name="purchase_date"
                                    value="<?php echo $equipment['purchase_date']; ?>"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="warranty_expiry">
                                    <i class="fas fa-shield-alt"></i>
                                    Warranty Expiry
                                </label>
                                <input 
                                    type="date" 
                                    id="warranty_expiry" 
                                    name="warranty_expiry"
                                    value="<?php echo $equipment['warranty_expiry']; ?>"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="window.location.href='view-equipment.php?id=<?php echo $equipmentId; ?>'">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Equipment
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
    
    <script src="../js/dashboard.js"></script>
</body>
</html>
