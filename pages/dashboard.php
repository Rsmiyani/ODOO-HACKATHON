<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$role = $user['role'];

// Fetch dashboard statistics based on role
try {
    // Admin & Manager see all data
    if (hasAnyRole(['admin', 'manager'])) {
        // Total Equipment Count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM equipment WHERE status != 'scrapped'");
        $totalEquipment = $stmt->fetch()['count'];

        // All Pending Requests
        $stmt = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE stage IN ('new', 'in_progress')");
        $pendingRequests = $stmt->fetch()['count'];

        // All Completed Today
        $stmt = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE stage = 'repaired' AND DATE(updated_at) = CURDATE()");
        $completedToday = $stmt->fetch()['count'];

        // All Overdue Requests
        $stmt = $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE scheduled_date < NOW() AND stage IN ('new', 'in_progress')");
        $overdueRequests = $stmt->fetch()['count'];

        // Recent Maintenance Requests (Last 10)
        $stmt = $conn->query("
            SELECT 
                mr.id,
                mr.subject,
                mr.stage,
                mr.priority,
                mr.request_type,
                mr.scheduled_date,
                mr.created_at,
                e.equipment_name,
                e.serial_number,
                u.name as assigned_to_name,
                creator.name as created_by_name
            FROM maintenance_requests mr
            INNER JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            INNER JOIN users creator ON mr.created_by = creator.id
            ORDER BY mr.created_at DESC
            LIMIT 10
        ");
        $recentRequests = $stmt->fetchAll();

    } elseif (hasRole('user')) {
        // Regular users see only their own created requests
        $userId = $user['id'];

        // Hide equipment count for users
        $totalEquipment = 0;

        // Requests created by user
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE created_by = ? AND stage IN ('new', 'in_progress')
        ");
        $stmt->execute([$userId]);
        $pendingRequests = $stmt->fetch()['count'];

        // Completed requests by user
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE created_by = ? AND stage = 'repaired' AND DATE(updated_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $completedToday = $stmt->fetch()['count'];

        // Overdue Requests created by user
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE created_by = ? AND scheduled_date < NOW() AND stage IN ('new', 'in_progress')
        ");
        $stmt->execute([$userId]);
        $overdueRequests = $stmt->fetch()['count'];

        // Recent Requests created by user
        $stmt = $conn->prepare("
            SELECT 
                mr.id,
                mr.subject,
                mr.stage,
                mr.priority,
                mr.request_type,
                mr.scheduled_date,
                mr.created_at,
                e.equipment_name,
                e.serial_number,
                u.name as assigned_to_name,
                creator.name as created_by_name
            FROM maintenance_requests mr
            INNER JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            INNER JOIN users creator ON mr.created_by = creator.id
            WHERE mr.created_by = ?
            ORDER BY mr.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentRequests = $stmt->fetchAll();
    } else {
        // Technicians see only their assigned data
        $userId = $user['id'];

        // Equipment assigned to technician's team
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.id) as count 
            FROM equipment e
            WHERE e.default_technician_id = ? AND e.status != 'scrapped'
        ");
        $stmt->execute([$userId]);
        $totalEquipment = $stmt->fetch()['count'];

        // Pending Requests assigned to technician
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE assigned_to = ? AND stage IN ('new', 'in_progress')
        ");
        $stmt->execute([$userId]);
        $pendingRequests = $stmt->fetch()['count'];

        // Completed Today by technician
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE assigned_to = ? AND stage = 'repaired' AND DATE(updated_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $completedToday = $stmt->fetch()['count'];

        // Overdue Requests assigned to technician
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_requests 
            WHERE assigned_to = ? AND scheduled_date < NOW() AND stage IN ('new', 'in_progress')
        ");
        $stmt->execute([$userId]);
        $overdueRequests = $stmt->fetch()['count'];

        // Recent Requests assigned to technician
        $stmt = $conn->prepare("
            SELECT 
                mr.id,
                mr.subject,
                mr.stage,
                mr.priority,
                mr.request_type,
                mr.scheduled_date,
                mr.created_at,
                e.equipment_name,
                e.serial_number,
                u.name as assigned_to_name,
                creator.name as created_by_name
            FROM maintenance_requests mr
            INNER JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            INNER JOIN users creator ON mr.created_by = creator.id
            WHERE mr.assigned_to = ?
            ORDER BY mr.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentRequests = $stmt->fetchAll();
    }

    // Equipment by Status (for all roles)
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM equipment 
        GROUP BY status
    ");
    $equipmentByStatus = $stmt->fetchAll();

    // Requests by Stage (role-based)
    if (hasAnyRole(['admin', 'manager'])) {
        $stmt = $conn->query("
            SELECT stage, COUNT(*) as count 
            FROM maintenance_requests 
            GROUP BY stage
        ");
    } elseif (hasRole('user')) {
        $stmt = $conn->prepare("
            SELECT stage, COUNT(*) as count 
            FROM maintenance_requests 
            WHERE created_by = ?
            GROUP BY stage
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $conn->prepare("
            SELECT stage, COUNT(*) as count 
            FROM maintenance_requests 
            WHERE assigned_to = ?
            GROUP BY stage
        ");
        $stmt->execute([$userId]);
    }
    $requestsByStage = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalEquipment = $pendingRequests = $completedToday = $overdueRequests = 0;
    $recentRequests = [];
    $equipmentByStatus = [];
    $requestsByStage = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
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
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

            <?php if (canPerform('view_all_equipment')): ?>
                <a href="equipment.php" class="nav-item">
                    <i class="fas fa-cogs"></i>
                    <span>Equipment</span>
                </a>
            <?php endif; ?>

            <?php if (canPerform('view_all_requests') || canPerform('view_my_requests')): ?>
                <a href="kanban.php" class="nav-item">
                    <i class="fas fa-columns"></i>
                    <span>Kanban Board</span>
                </a>
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
                <h1>
                    <?php
                    if (hasRole('admin')) {
                        echo 'Admin Dashboard';
                    } elseif (hasRole('manager')) {
                        echo 'Manager Dashboard';
                    } else {
                        echo 'My Dashboard';
                    }
                    ?>
                </h1>
                <p class="dashboard-subtitle">
                    <?php
                    if (hasRole('technician')) {
                        echo 'View your assigned maintenance tasks';
                    } elseif (hasRole('user')) {
                        echo 'View and manage your maintenance requests';
                    } else {
                        echo 'Overview of maintenance operations';
                    }
                    ?>
                </p>
            </div>
            <div class="header-actions">
                <?php if (canPerform('create_request')): ?>
                    <button class="btn btn-primary" onclick="window.location.href='create-request.php'">
                        <i class="fas fa-plus"></i>
                        New Request
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $totalEquipment; ?></h3>
                    <p><?php echo hasRole('user') ? 'Requests Created' : (hasRole('technician') ? 'My Equipment' : 'Total Equipment'); ?>
                    </p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $pendingRequests; ?></h3>
                    <p><?php echo hasRole('technician') ? 'My Pending Tasks' : 'Pending Requests'; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $completedToday; ?></h3>
                    <p>Completed Today</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $overdueRequests; ?></h3>
                    <p>Overdue <?php echo hasRole('technician') ? 'Tasks' : 'Requests'; ?></p>
                </div>
            </div>
        </section>

        <!-- Quick Actions (Role-based) -->
        <section class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <?php if (canPerform('add_equipment')): ?>
                    <a href="equipment.php?action=add" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Equipment</span>
                    </a>
                <?php endif; ?>

                <?php if (canPerform('create_request')): ?>
                    <a href="create-request.php?type=corrective" class="action-card">
                        <i class="fas fa-wrench"></i>
                        <span>Report Breakdown</span>
                    </a>
                    <a href="create-request.php?type=preventive" class="action-card">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Schedule Maintenance</span>
                    </a>
                <?php endif; ?>

                <?php if (canPerform('add_team')): ?>
                    <a href="teams.php?action=add" class="action-card">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Team Member</span>
                    </a>
                <?php endif; ?>

                <?php if (hasRole('technician')): ?>
                    <a href="kanban.php" class="action-card">
                        <i class="fas fa-tasks"></i>
                        <span>View My Tasks</span>
                    </a>
                <?php endif; ?>

                <?php if (canPerform('view_reports')): ?>
                    <a href="reports.php" class="action-card">
                        <i class="fas fa-chart-line"></i>
                        <span>View Reports</span>
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Requests -->
            <section class="content-section">
                <div class="section-header">
                    <h2>
                        <?php echo hasRole('technician') ? 'My Recent Tasks' : 'Recent Maintenance Requests'; ?>
                    </h2>
                    <a href="requests.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Equipment</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Stage</th>
                                <?php if (!hasRole('technician')): ?>
                                    <th>Assigned To</th>
                                <?php endif; ?>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRequests)): ?>
                                <tr>
                                    <td colspan="<?php echo hasRole('technician') ? '8' : '9'; ?>" class="text-center">
                                        <?php echo hasRole('technician') ? 'No tasks assigned to you' : 'No maintenance requests found'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['subject']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="equipment-name">
                                                <?php echo htmlspecialchars($request['equipment_name']); ?>
                                            </span>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($request['serial_number']); ?></small>
                                        </td>
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
                                        <?php if (!hasRole('technician')): ?>
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
                                        <?php endif; ?>
                                        <td>
                                            <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn-icon"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Charts Section -->
            <aside class="sidebar-content">
                <!-- Equipment Status Chart -->
                <?php if (!empty($equipmentByStatus)): ?>
                    <div class="chart-card">
                        <h3>Equipment Status</h3>
                        <div class="status-list">
                            <?php
                            $statusIcons = [
                                'active' => ['icon' => 'check-circle', 'color' => 'green'],
                                'under_maintenance' => ['icon' => 'wrench', 'color' => 'orange'],
                                'scrapped' => ['icon' => 'times-circle', 'color' => 'red']
                            ];
                            foreach ($equipmentByStatus as $status):
                                $statusData = $statusIcons[$status['status']] ?? ['icon' => 'circle', 'color' => 'gray'];
                                ?>
                                <div class="status-item">
                                    <i
                                        class="fas fa-<?php echo $statusData['icon']; ?> status-icon <?php echo $statusData['color']; ?>"></i>
                                    <span
                                        class="status-label"><?php echo str_replace('_', ' ', ucfirst($status['status'])); ?></span>
                                    <span class="status-count"><?php echo $status['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Request Stage Chart -->
                <?php if (!empty($requestsByStage)): ?>
                    <div class="chart-card">
                        <h3><?php echo hasRole('technician') ? 'My Task Status' : 'Request Stages'; ?></h3>
                        <div class="status-list">
                            <?php
                            $stageIcons = [
                                'new' => ['icon' => 'plus-circle', 'color' => 'blue'],
                                'in_progress' => ['icon' => 'spinner', 'color' => 'orange'],
                                'repaired' => ['icon' => 'check-circle', 'color' => 'green'],
                                'scrap' => ['icon' => 'trash', 'color' => 'red']
                            ];
                            foreach ($requestsByStage as $stage):
                                $stageData = $stageIcons[$stage['stage']] ?? ['icon' => 'circle', 'color' => 'gray'];
                                ?>
                                <div class="status-item">
                                    <i
                                        class="fas fa-<?php echo $stageData['icon']; ?> status-icon <?php echo $stageData['color']; ?>"></i>
                                    <span
                                        class="status-label"><?php echo str_replace('_', ' ', ucfirst($stage['stage'])); ?></span>
                                    <span class="status-count"><?php echo $stage['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <script src="../js/dashboard.js"></script>
</body>

</html>