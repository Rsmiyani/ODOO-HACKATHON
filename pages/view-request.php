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

// Fetch request details
try {
    $stmt = $conn->prepare("
        SELECT 
            mr.*,
            e.equipment_name,
            e.serial_number,
            e.category,
            e.department,
            e.location,
            e.assigned_to_employee,
            assigned.name as assigned_to_name,
            assigned.email as assigned_to_email,
            assigned.role as assigned_to_role,
            creator.name as created_by_name,
            creator.email as created_by_email,
            mt.team_name
        FROM maintenance_requests mr
        INNER JOIN equipment e ON mr.equipment_id = e.id
        LEFT JOIN users assigned ON mr.assigned_to = assigned.id
        INNER JOIN users creator ON mr.created_by = creator.id
        LEFT JOIN maintenance_teams mt ON e.maintenance_team_id = mt.id
        WHERE mr.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        $_SESSION['error'] = "Request not found.";
        header("Location: requests.php");
        exit();
    }

    // Check if technician has access
    if (hasRole('technician') && $request['assigned_to'] != $user['id']) {
        $_SESSION['error'] = "You don't have permission to view this request.";
        header("Location: requests.php");
        exit();
    }

    // Fetch request history
    $stmt = $conn->prepare("
        SELECT 
            rh.*,
            u.name as user_name
        FROM request_history rh
        LEFT JOIN users u ON rh.user_id = u.id
        WHERE rh.request_id = ?
        ORDER BY rh.created_at DESC
    ");
    $stmt->execute([$requestId]);
    $history = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("View request error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading request details.";
    header("Location: requests.php");
    exit();
}

// Check if overdue
$isOverdue = false;
if ($request['scheduled_date'] && !in_array($request['stage'], ['repaired', 'scrap'])) {
    $isOverdue = strtotime($request['scheduled_date']) < time();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request['id']; ?> - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/view-request.css">
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
                <h1>Request #<?php echo $request['id']; ?></h1>
                <p class="dashboard-subtitle"><?php echo htmlspecialchars($request['subject']); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.location.href='requests.php'">
                    <i class="fas fa-arrow-left"></i>
                    Back to List
                </button>
                <?php if (canPerform('edit_request')): ?>
                    <button class="btn btn-primary"
                        onclick="window.location.href='edit-request.php?id=<?php echo $request['id']; ?>'">
                        <i class="fas fa-edit"></i>
                        Edit
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Request Details -->
        <div class="request-details-container">
            <!-- Main Details Card -->
            <div class="details-main">
                <!-- Status Header -->
                <div class="status-header stage-<?php echo $request['stage']; ?>">
                    <div class="status-info">
                        <h2><?php echo htmlspecialchars($request['subject']); ?></h2>
                        <?php if ($isOverdue): ?>
                            <div class="overdue-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>This request is overdue!</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="status-badges">
                        <span class="badge badge-stage-<?php echo $request['stage']; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($request['stage'])); ?>
                        </span>
                        <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                            <?php echo ucfirst($request['priority']); ?> Priority
                        </span>
                        <span class="badge badge-<?php echo $request['request_type']; ?>">
                            <i
                                class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
                            <?php echo ucfirst($request['request_type']); ?>
                        </span>
                    </div>
                </div>

                <!-- Equipment Information -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i>
                        Equipment Information
                    </h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Equipment Name</span>
                            <span
                                class="detail-value"><strong><?php echo htmlspecialchars($request['equipment_name']); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Serial Number</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['serial_number']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Category</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['category']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['department']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value"><?php echo htmlspecialchars($request['location']); ?></span>
                        </div>
                        <?php if ($request['assigned_to_employee']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Assigned Employee</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($request['assigned_to_employee']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline btn-sm"
                        onclick="window.location.href='view-equipment.php?id=<?php echo $request['equipment_id']; ?>'">
                        <i class="fas fa-external-link-alt"></i>
                        View Equipment Details
                    </button>
                </div>

                <!-- Request Details -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-clipboard-list"></i>
                        Request Details
                    </h3>

                    <?php if ($request['description']): ?>
                        <div class="description-box">
                            <h4>Description</h4>
                            <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Request Type</span>
                            <span class="detail-value">
                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                    <?php echo ucfirst($request['request_type']); ?> Maintenance
                                </span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Priority Level</span>
                            <span class="detail-value">
                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Current Stage</span>
                            <span class="detail-value">
                                <span class="badge badge-stage-<?php echo $request['stage']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($request['stage'])); ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($request['scheduled_date']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Scheduled Date</span>
                                <span class="detail-value <?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('F d, Y \a\t h:i A', strtotime($request['scheduled_date'])); ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="overdue-text">(Overdue)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">Created Date</span>
                            <span class="detail-value">
                                <i class="fas fa-clock"></i>
                                <?php echo date('F d, Y \a\t h:i A', strtotime($request['created_at'])); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Updated</span>
                            <span class="detail-value">
                                <i class="fas fa-sync-alt"></i>
                                <?php echo date('F d, Y \a\t h:i A', strtotime($request['updated_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Team & Assignment -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>
                        Team & Assignment
                    </h3>
                    <div class="detail-grid">
                        <?php if ($request['team_name']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Maintenance Team</span>
                                <span class="detail-value">
                                    <i class="fas fa-users-cog"></i>
                                    <?php echo htmlspecialchars($request['team_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="detail-item">
                            <span class="detail-label">Assigned To</span>
                            <span class="detail-value">
                                <?php if ($request['assigned_to_name']): ?>
                                    <div class="user-info-inline">
                                        <i class="fas fa-user-circle"></i>
                                        <div>
                                            <strong><?php echo htmlspecialchars($request['assigned_to_name']); ?></strong>
                                            <small><?php echo htmlspecialchars($request['assigned_to_email']); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned yet</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Created By</span>
                            <span class="detail-value">
                                <div class="user-info-inline">
                                    <i class="fas fa-user"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($request['created_by_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($request['created_by_email']); ?></small>
                                    </div>
                                </div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar with History -->
            <aside class="details-sidebar">
                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Quick Actions</h3>
                    <div class="quick-actions-list">
                        <?php if (canPerform('update_my_requests') || canPerform('assign_request')): ?>
                            <?php if ($request['stage'] === 'new'): ?>
                                <button class="action-btn start-btn"
                                    onclick="updateStage(<?php echo $requestId; ?>, 'in_progress')">
                                    <i class="fas fa-play"></i>
                                    Start Working
                                </button>
                            <?php elseif ($request['stage'] === 'in_progress'): ?>
                                <button class="action-btn complete-btn"
                                    onclick="updateStage(<?php echo $requestId; ?>, 'repaired')">
                                    <i class="fas fa-check"></i>
                                    Mark as Repaired
                                </button>
                                <button class="action-btn scrap-btn" onclick="confirmScrap(<?php echo $requestId; ?>)">
                                    <i class="fas fa-trash"></i>
                                    Mark as Scrap
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <button class="action-btn" onclick="window.print()">
                            <i class="fas fa-print"></i>
                            Print Request
                        </button>
                    </div>
                </div>

                <!-- Activity History -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-history"></i>
                        Activity History
                    </h3>
                    <div class="history-timeline">
                        <?php if (empty($history)): ?>
                            <p class="text-muted">No activity recorded yet</p>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <div class="history-item">
                                    <div class="history-dot"></div>
                                    <div class="history-content">
                                        <p class="history-action">
                                            <strong><?php echo htmlspecialchars($item['action']); ?></strong>
                                            <?php if ($item['new_value']): ?>
                                                <span
                                                    class="history-value"><?php echo htmlspecialchars($item['new_value']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="history-meta">
                                            <span><?php echo htmlspecialchars($item['user_name'] ?? 'System'); ?></span>
                                            <span><?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?></span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/view-request.js"></script>
</body>

</html>