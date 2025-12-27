<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$role = $user['role'];

// Get filter parameters
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filterStage = isset($_GET['stage']) ? sanitizeInput($_GET['stage']) : '';
$filterPriority = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';
$filterType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

// Fetch requests based on role
try {
    if (hasAnyRole(['admin', 'manager'])) {
        // Admin and Manager see all requests
        $sql = "
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
            WHERE 1=1
        ";
    } else {
        // Technicians see only their assigned requests
        $sql = "
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
        ";
    }

    $params = [];
    if (hasRole('technician')) {
        $params[] = $user['id'];
    }

    // Add search filter
    if (!empty($searchQuery)) {
        $sql .= " AND (mr.subject LIKE ? OR e.equipment_name LIKE ? OR mr.id = ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = intval($searchQuery);
    }

    // Add stage filter
    if (!empty($filterStage)) {
        $sql .= " AND mr.stage = ?";
        $params[] = $filterStage;
    }

    // Add priority filter
    if (!empty($filterPriority)) {
        $sql .= " AND mr.priority = ?";
        $params[] = $filterPriority;
    }

    // Add type filter
    if (!empty($filterType)) {
        $sql .= " AND mr.request_type = ?";
        $params[] = $filterType;
    }

    $sql .= " ORDER BY 
        CASE mr.stage
            WHEN 'new' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'repaired' THEN 3
            WHEN 'scrap' THEN 4
        END,
        CASE mr.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        mr.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Requests list error: " . $e->getMessage());
    $requests = [];
}

// Check if request is overdue
function isRequestOverdue($scheduledDate, $stage)
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
    <title><?php echo hasRole('technician') ? 'My Requests' : 'All Requests'; ?> - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/requests.css">
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
                <h1><?php echo hasRole('technician') ? 'My Requests' : 'All Maintenance Requests'; ?></h1>
                <p class="dashboard-subtitle">View and manage maintenance work orders</p>
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

        <!-- Filters Section -->
        <section class="filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by ID, subject, or equipment..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <select name="stage" onchange="this.form.submit()">
                        <option value="">All Stages</option>
                        <option value="new" <?php echo $filterStage === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="in_progress" <?php echo $filterStage === 'in_progress' ? 'selected' : ''; ?>>In
                            Progress</option>
                        <option value="repaired" <?php echo $filterStage === 'repaired' ? 'selected' : ''; ?>>Repaired
                        </option>
                        <option value="scrap" <?php echo $filterStage === 'scrap' ? 'selected' : ''; ?>>Scrap</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="priority" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <option value="urgent" <?php echo $filterPriority === 'urgent' ? 'selected' : ''; ?>>Urgent
                        </option>
                        <option value="high" <?php echo $filterPriority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filterPriority === 'medium' ? 'selected' : ''; ?>>Medium
                        </option>
                        <option value="low" <?php echo $filterPriority === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="type" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="corrective" <?php echo $filterType === 'corrective' ? 'selected' : ''; ?>>
                            Corrective</option>
                        <option value="preventive" <?php echo $filterType === 'preventive' ? 'selected' : ''; ?>>
                            Preventive</option>
                    </select>
                </div>

                <?php if ($searchQuery || $filterStage || $filterPriority || $filterType): ?>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.location.href='requests.php'">
                        <i class="fas fa-times"></i>
                        Clear
                    </button>
                <?php endif; ?>
            </form>
        </section>

        <!-- Requests Table -->
        <section class="table-section">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Requests Found</h3>
                    <p>
                        <?php if ($searchQuery || $filterStage || $filterPriority || $filterType): ?>
                            No requests match your filters. Try adjusting your search criteria.
                        <?php else: ?>
                            <?php echo hasRole('technician') ? 'No requests assigned to you yet.' : 'No maintenance requests in the system.'; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
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
                                <th>Scheduled</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php $isOverdue = isRequestOverdue($request['scheduled_date'], $request['stage']); ?>
                                <tr class="<?php echo $isOverdue ? 'overdue-row' : ''; ?>">
                                    <td>
                                        <strong>#<?php echo $request['id']; ?></strong>
                                    </td>
                                    <td>
                                        <div class="request-subject">
                                            <?php echo htmlspecialchars($request['subject']); ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="overdue-tag-small">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Overdue
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="equipment-cell">
                                            <strong><?php echo htmlspecialchars($request['equipment_name']); ?></strong>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($request['serial_number']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $request['request_type']; ?>">
                                            <i
                                                class="fas fa-<?php echo $request['request_type'] == 'preventive' ? 'calendar-check' : 'wrench'; ?>"></i>
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
                                        <?php if ($request['scheduled_date']): ?>
                                            <small><?php echo date('M d, Y H:i', strtotime($request['scheduled_date'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"
                                                onclick="window.location.href='view-request.php?id=<?php echo $request['id']; ?>'"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="results-info">
                    Showing <?php echo count($requests); ?> request(s)
                    <?php if ($searchQuery || $filterStage || $filterPriority || $filterType): ?>
                        (filtered)
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../js/dashboard.js"></script>
</body>

</html>