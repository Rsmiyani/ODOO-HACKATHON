<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$userId = $user['id'];
$role = $user['role'];

// Fetch requests based on role
try {
    if (hasAnyRole(['admin', 'manager'])) {
        // Admin and Manager see all requests
        $stmt = $conn->query("
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
                u.name as assigned_to_name,
                u.id as assigned_to_id,
                creator.name as created_by_name
            FROM maintenance_requests mr
            INNER JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            INNER JOIN users creator ON mr.created_by = creator.id
            ORDER BY 
                CASE mr.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                mr.created_at DESC
        ");
    } else {
        // Technicians see only their assigned requests
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
                e.equipment_name,
                e.serial_number,
                e.category,
                u.name as assigned_to_name,
                u.id as assigned_to_id,
                creator.name as created_by_name
            FROM maintenance_requests mr
            INNER JOIN equipment e ON mr.equipment_id = e.id
            LEFT JOIN users u ON mr.assigned_to = u.id
            INNER JOIN users creator ON mr.created_by = creator.id
            WHERE mr.assigned_to = ?
            ORDER BY 
                CASE mr.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                mr.created_at DESC
        ");
        $stmt->execute([$userId]);
    }

    $allRequests = $stmt->fetchAll();

    // Group requests by stage
    $requestsByStage = [
        'new' => [],
        'in_progress' => [],
        'repaired' => [],
        'scrap' => []
    ];

    foreach ($allRequests as $request) {
        $requestsByStage[$request['stage']][] = $request;
    }

} catch (PDOException $e) {
    error_log("Kanban error: " . $e->getMessage());
    $requestsByStage = [
        'new' => [],
        'in_progress' => [],
        'repaired' => [],
        'scrap' => []
    ];
}

// Check if request is overdue
function isOverdue($scheduledDate, $stage)
{
    if (!$scheduledDate || in_array($stage, ['repaired', 'scrap'])) {
        return false;
    }
    return strtotime($scheduledDate) < time();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban Board - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/kanban.css">
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

            <a href="kanban.php" class="nav-item active">
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
                <h1>Kanban Board</h1>
                <p class="dashboard-subtitle">Drag and drop to update request status</p>
            </div>
            <div class="header-actions">
                <?php if (canPerform('create_request')): ?>
                    <button class="btn btn-primary" onclick="window.location.href='create-request.php'">
                        <i class="fas fa-plus"></i>
                        New Request
                    </button>
                <?php endif; ?>
                <button class="btn btn-outline" onclick="refreshKanban()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </header>

        <!-- Kanban Board -->
        <div class="kanban-board">
            <!-- Column 1: New -->
            <div class="kanban-column" data-stage="new">
                <div class="column-header new-header">
                    <div class="column-title">
                        <i class="fas fa-inbox"></i>
                        <span>New</span>
                    </div>
                    <span class="column-count"><?php echo count($requestsByStage['new']); ?></span>
                </div>
                <div class="kanban-cards" id="column-new">
                    <?php foreach ($requestsByStage['new'] as $request): ?>
                        <?php
                        $isOverdue = isOverdue($request['scheduled_date'], $request['stage']);
                        ?>
                        <div class="kanban-card <?php echo $isOverdue ? 'overdue' : ''; ?>" draggable="true"
                            data-request-id="<?php echo $request['id']; ?>" data-stage="new">

                            <?php if ($isOverdue): ?>
                                <div class="overdue-badge">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Overdue
                                </div>
                            <?php endif; ?>

                            <div class="card-header">
                                <span class="card-id">#<?php echo $request['id']; ?></span>
                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </div>

                            <h3 class="card-title"><?php echo htmlspecialchars($request['subject']); ?></h3>

                            <div class="card-equipment">
                                <i class="fas fa-cog"></i>
                                <span><?php echo htmlspecialchars($request['equipment_name']); ?></span>
                            </div>

                            <?php if ($request['description']): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 100)); ?>
                                    <?php echo strlen($request['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-meta">
                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                    <i
                                        class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
                                    <?php echo ucfirst($request['request_type']); ?>
                                </span>
                                <?php if ($request['scheduled_date']): ?>
                                    <span class="card-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y', strtotime($request['scheduled_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <?php if ($request['assigned_to_name']): ?>
                                    <div class="card-assignee">
                                        <div class="assignee-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($request['assigned_to_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="card-assignee unassigned">
                                        <i class="fas fa-user-slash"></i>
                                        <span>Unassigned</span>
                                    </div>
                                <?php endif; ?>

                                <button class="card-action-btn" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($requestsByStage['new'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-inbox"></i>
                            <p>No new requests</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: In Progress -->
            <div class="kanban-column" data-stage="in_progress">
                <div class="column-header progress-header">
                    <div class="column-title">
                        <i class="fas fa-spinner"></i>
                        <span>In Progress</span>
                    </div>
                    <span class="column-count"><?php echo count($requestsByStage['in_progress']); ?></span>
                </div>
                <div class="kanban-cards" id="column-in_progress">
                    <?php foreach ($requestsByStage['in_progress'] as $request): ?>
                        <?php
                        $isOverdue = isOverdue($request['scheduled_date'], $request['stage']);
                        ?>
                        <div class="kanban-card <?php echo $isOverdue ? 'overdue' : ''; ?>" draggable="true"
                            data-request-id="<?php echo $request['id']; ?>" data-stage="in_progress">

                            <?php if ($isOverdue): ?>
                                <div class="overdue-badge">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Overdue
                                </div>
                            <?php endif; ?>

                            <div class="card-header">
                                <span class="card-id">#<?php echo $request['id']; ?></span>
                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </div>

                            <h3 class="card-title"><?php echo htmlspecialchars($request['subject']); ?></h3>

                            <div class="card-equipment">
                                <i class="fas fa-cog"></i>
                                <span><?php echo htmlspecialchars($request['equipment_name']); ?></span>
                            </div>

                            <?php if ($request['description']): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 100)); ?>
                                    <?php echo strlen($request['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-meta">
                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                    <i
                                        class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
                                    <?php echo ucfirst($request['request_type']); ?>
                                </span>
                                <?php if ($request['scheduled_date']): ?>
                                    <span class="card-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y', strtotime($request['scheduled_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <?php if ($request['assigned_to_name']): ?>
                                    <div class="card-assignee">
                                        <div class="assignee-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($request['assigned_to_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="card-assignee unassigned">
                                        <i class="fas fa-user-slash"></i>
                                        <span>Unassigned</span>
                                    </div>
                                <?php endif; ?>

                                <button class="card-action-btn" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($requestsByStage['in_progress'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-spinner"></i>
                            <p>No tasks in progress</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 3: Repaired -->
            <div class="kanban-column" data-stage="repaired">
                <div class="column-header repaired-header">
                    <div class="column-title">
                        <i class="fas fa-check-circle"></i>
                        <span>Repaired</span>
                    </div>
                    <span class="column-count"><?php echo count($requestsByStage['repaired']); ?></span>
                </div>
                <div class="kanban-cards" id="column-repaired">
                    <?php foreach ($requestsByStage['repaired'] as $request): ?>
                        <div class="kanban-card" draggable="true" data-request-id="<?php echo $request['id']; ?>"
                            data-stage="repaired">

                            <div class="card-header">
                                <span class="card-id">#<?php echo $request['id']; ?></span>
                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </div>

                            <h3 class="card-title"><?php echo htmlspecialchars($request['subject']); ?></h3>

                            <div class="card-equipment">
                                <i class="fas fa-cog"></i>
                                <span><?php echo htmlspecialchars($request['equipment_name']); ?></span>
                            </div>

                            <?php if ($request['description']): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 100)); ?>
                                    <?php echo strlen($request['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-meta">
                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                    <i
                                        class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
                                    <?php echo ucfirst($request['request_type']); ?>
                                </span>
                                <?php if ($request['scheduled_date']): ?>
                                    <span class="card-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y', strtotime($request['scheduled_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <?php if ($request['assigned_to_name']): ?>
                                    <div class="card-assignee">
                                        <div class="assignee-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($request['assigned_to_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="card-assignee unassigned">
                                        <i class="fas fa-user-slash"></i>
                                        <span>Unassigned</span>
                                    </div>
                                <?php endif; ?>

                                <button class="card-action-btn" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($requestsByStage['repaired'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-check-circle"></i>
                            <p>No repaired items</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 4: Scrap -->
            <div class="kanban-column" data-stage="scrap">
                <div class="column-header scrap-header">
                    <div class="column-title">
                        <i class="fas fa-trash"></i>
                        <span>Scrap</span>
                    </div>
                    <span class="column-count"><?php echo count($requestsByStage['scrap']); ?></span>
                </div>
                <div class="kanban-cards" id="column-scrap">
                    <?php foreach ($requestsByStage['scrap'] as $request): ?>
                        <div class="kanban-card" draggable="true" data-request-id="<?php echo $request['id']; ?>"
                            data-stage="scrap">

                            <div class="card-header">
                                <span class="card-id">#<?php echo $request['id']; ?></span>
                                <span class="badge badge-priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </div>

                            <h3 class="card-title"><?php echo htmlspecialchars($request['subject']); ?></h3>

                            <div class="card-equipment">
                                <i class="fas fa-cog"></i>
                                <span><?php echo htmlspecialchars($request['equipment_name']); ?></span>
                            </div>

                            <?php if ($request['description']): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 100)); ?>
                                    <?php echo strlen($request['description']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-meta">
                                <span class="badge badge-<?php echo $request['request_type']; ?>">
                                    <i
                                        class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
                                    <?php echo ucfirst($request['request_type']); ?>
                                </span>
                                <?php if ($request['scheduled_date']): ?>
                                    <span class="card-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y', strtotime($request['scheduled_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <?php if ($request['assigned_to_name']): ?>
                                    <div class="card-assignee">
                                        <div class="assignee-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($request['assigned_to_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="card-assignee unassigned">
                                        <i class="fas fa-user-slash"></i>
                                        <span>Unassigned</span>
                                    </div>
                                <?php endif; ?>

                                <button class="card-action-btn" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($requestsByStage['scrap'])): ?>
                        <div class="empty-column">
                            <i class="fas fa-trash"></i>
                            <p>No scrapped items</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/kanban.js"></script>
</body>

</html>