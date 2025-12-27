<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$equipmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$equipmentId) {
    $_SESSION['error'] = "Invalid equipment ID.";
    header("Location: equipment.php");
    exit();
}

// Fetch equipment details
try {
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            mt.team_name,
            u.name as default_technician_name
        FROM equipment e
        LEFT JOIN maintenance_teams mt ON e.maintenance_team_id = mt.id
        LEFT JOIN users u ON e.default_technician_id = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        $_SESSION['error'] = "Equipment not found.";
        header("Location: equipment.php");
        exit();
    }

    // Fetch maintenance request statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN stage IN ('new', 'in_progress') THEN 1 ELSE 0 END) as open_requests,
            SUM(CASE WHEN stage = 'repaired' THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN stage = 'scrap' THEN 1 ELSE 0 END) as scrapped_requests
        FROM maintenance_requests
        WHERE equipment_id = ?
    ");
    $stmt->execute([$equipmentId]);
    $stats = $stmt->fetch();

    // Fetch recent maintenance requests
    $stmt = $conn->prepare("
        SELECT 
            mr.id,
            mr.subject,
            mr.stage,
            mr.priority,
            mr.request_type,
            mr.scheduled_date,
            mr.created_at,
            u.name as assigned_to_name
        FROM maintenance_requests mr
        LEFT JOIN users u ON mr.assigned_to = u.id
        WHERE mr.equipment_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$equipmentId]);
    $recentRequests = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("View equipment error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading equipment details.";
    header("Location: equipment.php");
    exit();
}

// Check if warranty expired
$warrantyExpired = false;
if ($equipment['warranty_expiry']) {
    $warrantyExpired = strtotime($equipment['warranty_expiry']) < time();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($equipment['equipment_name']); ?> - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/view-equipment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
                <h1><?php echo htmlspecialchars($equipment['equipment_name']); ?></h1>
                <p class="dashboard-subtitle"><?php echo htmlspecialchars($equipment['serial_number']); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.location.href='equipment.php'">
                    <i class="fas fa-arrow-left"></i>
                    Back to List
                </button>
                <?php if (canPerform('edit_equipment')): ?>
                    <button class="btn btn-primary"
                        onclick="window.location.href='edit-equipment.php?id=<?php echo $equipment['id']; ?>'">
                        <i class="fas fa-edit"></i>
                        Edit
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Equipment Details -->
        <div class="equipment-details-container">
            <!-- Main Details -->
            <div class="details-main">
                <!-- Status Header -->
                <div class="status-header status-<?php echo $equipment['status']; ?>">
                    <div class="status-info">
                        <div class="equipment-icon-large">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($equipment['equipment_name']); ?></h2>
                            <p class="serial-large"><?php echo htmlspecialchars($equipment['serial_number']); ?></p>
                        </div>
                    </div>
                    <div class="status-badge-large">
                        <span class="badge-status status-<?php echo $equipment['status']; ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo str_replace('_', ' ', ucfirst($equipment['status'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Smart Button - Maintenance Requests -->
                <div class="smart-button-section">
                    <button class="smart-button-large" onclick="scrollToRequests()">
                        <div class="smart-button-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <div class="smart-button-content">
                            <h3>Maintenance Requests</h3>
                            <p>View all maintenance history for this equipment</p>
                        </div>
                        <div class="smart-button-badge">
                            <?php if ($stats['open_requests'] > 0): ?>
                                <span class="badge badge-danger"><?php echo $stats['open_requests']; ?> Open</span>
                            <?php endif; ?>
                            <span class="badge badge-info"><?php echo $stats['total_requests']; ?> Total</span>
                        </div>
                    </button>
                </div>

                <!-- Basic Information -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Equipment Name</span>
                            <span
                                class="detail-value"><strong><?php echo htmlspecialchars($equipment['equipment_name']); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Serial Number</span>
                            <span
                                class="detail-value"><?php echo htmlspecialchars($equipment['serial_number']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Category</span>
                            <span class="detail-value">
                                <span
                                    class="badge badge-info"><?php echo htmlspecialchars($equipment['category']); ?></span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="equipment-status status-<?php echo $equipment['status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo str_replace('_', ' ', ucfirst($equipment['status'])); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Location & Assignment -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Location & Assignment
                    </h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-value">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($equipment['department']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value">
                                <i class="fas fa-map-pin"></i>
                                <?php echo htmlspecialchars($equipment['location']); ?>
                            </span>
                        </div>
                        <?php if ($equipment['assigned_to_employee']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Assigned Employee</span>
                                <span class="detail-value">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($equipment['assigned_to_employee']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Maintenance Details -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-wrench"></i>
                        Maintenance Configuration
                    </h3>
                    <div class="detail-grid">
                        <?php if ($equipment['team_name']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Maintenance Team</span>
                                <span class="detail-value">
                                    <i class="fas fa-users-cog"></i>
                                    <?php echo htmlspecialchars($equipment['team_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($equipment['default_technician_name']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Default Technician</span>
                                <span class="detail-value">
                                    <i class="fas fa-user-cog"></i>
                                    <?php echo htmlspecialchars($equipment['default_technician_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Purchase & Warranty -->
                <?php if ($equipment['purchase_date'] || $equipment['warranty_expiry']): ?>
                    <div class="detail-section">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-check"></i>
                            Purchase & Warranty
                        </h3>
                        <div class="detail-grid">
                            <?php if ($equipment['purchase_date']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Purchase Date</span>
                                    <span class="detail-value">
                                        <i class="fas fa-shopping-cart"></i>
                                        <?php echo date('F d, Y', strtotime($equipment['purchase_date'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($equipment['warranty_expiry']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Warranty Expiry</span>
                                    <span class="detail-value <?php echo $warrantyExpired ? 'text-danger' : ''; ?>">
                                        <i class="fas fa-shield-alt"></i>
                                        <?php echo date('F d, Y', strtotime($equipment['warranty_expiry'])); ?>
                                        <?php if ($warrantyExpired): ?>
                                            <span class="expired-text">(Expired)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Maintenance Requests Section -->
                <div class="detail-section" id="maintenance-requests">
                    <div class="section-header-with-action">
                        <h3 class="section-title">
                            <i class="fas fa-clipboard-list"></i>
                            Maintenance Requests History
                        </h3>
                        <?php if (canPerform('create_request')): ?>
                            <button class="btn btn-primary btn-sm"
                                onclick="window.location.href='create-request.php?equipment_id=<?php echo $equipment['id']; ?>'">
                                <i class="fas fa-plus"></i>
                                New Request
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($recentRequests)): ?>
                        <div class="empty-message">
                            <i class="fas fa-clipboard"></i>
                            <p>No maintenance requests found for this equipment</p>
                        </div>
                    <?php else: ?>
                        <div class="requests-table">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Stage</th>
                                        <th>Assigned To</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <tr>
                                            <td><strong>#<?php echo $request['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                                    <?php echo ucfirst($request['request_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                                    <?php echo ucfirst($request['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-stage-<?php echo $request['stage']; ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($request['stage'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($request['assigned_to_name']): ?>
                                                    <div class="user-mini">
                                                        <i class="fas fa-user-circle"></i>
                                                        <span><?php echo htmlspecialchars($request['assigned_to_name']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn-icon"
                                                    onclick="window.location.href='view-request.php?id=<?php echo $request['id']; ?>'"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar with Stats -->
            <aside class="details-sidebar">
                <!-- Statistics Card -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-chart-pie"></i>
                        Statistics
                    </h3>
                    <div class="stats-list">
                        <div class="stat-item">
                            <div class="stat-icon blue">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $stats['total_requests']; ?></span>
                                <span class="stat-label">Total Requests</span>
                            </div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $stats['open_requests']; ?></span>
                                <span class="stat-label">Open Requests</span>
                            </div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $stats['completed_requests']; ?></span>
                                <span class="stat-label">Completed</span>
                            </div>
                        </div>

                        <?php if ($stats['scrapped_requests'] > 0): ?>
                            <div class="stat-item">
                                <div class="stat-icon gray">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <div class="stat-details">
                                    <span class="stat-value"><?php echo $stats['scrapped_requests']; ?></span>
                                    <span class="stat-label">Scrapped</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Quick Actions</h3>
                    <div class="quick-actions-list">
                        <?php if (canPerform('create_request')): ?>
                            <button class="action-btn"
                                onclick="window.location.href='create-request.php?equipment_id=<?php echo $equipment['id']; ?>&type=corrective'">
                                <i class="fas fa-wrench"></i>
                                Report Breakdown
                            </button>
                            <button class="action-btn"
                                onclick="window.location.href='create-request.php?equipment_id=<?php echo $equipment['id']; ?>&type=preventive'">
                                <i class="fas fa-calendar-plus"></i>
                                Schedule Maintenance
                            </button>
                        <?php endif; ?>

                        <?php if (canPerform('edit_equipment')): ?>
                            <button class="action-btn"
                                onclick="window.location.href='edit-equipment.php?id=<?php echo $equipment['id']; ?>'">
                                <i class="fas fa-edit"></i>
                                Edit Equipment
                            </button>
                        <?php endif; ?>

                        <button class="action-btn" onclick="window.print()">
                            <i class="fas fa-print"></i>
                            Print Details
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
        function scrollToRequests() {
            document.getElementById('maintenance-requests').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    </script>
</body>

</html>