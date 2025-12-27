<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission
if (!canPerform('view_all_equipment')) {
    $_SESSION['error'] = "You don't have permission to view equipment.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();

// Get filter and group by parameters
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filterCategory = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$groupBy = isset($_GET['group_by']) ? sanitizeInput($_GET['group_by']) : '';

// Fetch equipment with request counts
try {
    $sql = "
        SELECT 
            e.*,
            mt.team_name,
            u.name as technician_name,
            COUNT(DISTINCT mr.id) as total_requests,
            SUM(CASE WHEN mr.stage IN ('new', 'in_progress') THEN 1 ELSE 0 END) as open_requests
        FROM equipment e
        LEFT JOIN maintenance_teams mt ON e.maintenance_team_id = mt.id
        LEFT JOIN users u ON e.default_technician_id = u.id
        LEFT JOIN maintenance_requests mr ON e.id = mr.equipment_id
        WHERE 1=1
    ";

    $params = [];

    // Add search filter
    if (!empty($searchQuery)) {
        $sql .= " AND (e.equipment_name LIKE ? OR e.serial_number LIKE ? OR e.category LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Add category filter
    if (!empty($filterCategory)) {
        $sql .= " AND e.category = ?";
        $params[] = $filterCategory;
    }

    // Add status filter
    if (!empty($filterStatus)) {
        $sql .= " AND e.status = ?";
        $params[] = $filterStatus;
    }

    $sql .= " GROUP BY e.id";

    // Add ordering based on group by
    if ($groupBy) {
        switch ($groupBy) {
            case 'department':
                $sql .= " ORDER BY e.department ASC, e.equipment_name ASC";
                break;
            case 'category':
                $sql .= " ORDER BY e.category ASC, e.equipment_name ASC";
                break;
            case 'employee':
                $sql .= " ORDER BY e.assigned_to_employee ASC, e.equipment_name ASC";
                break;
            case 'team':
                $sql .= " ORDER BY mt.team_name ASC, e.equipment_name ASC";
                break;
            default:
                $sql .= " ORDER BY e.equipment_name ASC";
        }
    } else {
        $sql .= " ORDER BY e.equipment_name ASC";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $equipment = $stmt->fetchAll();

    // Group equipment if grouping is enabled
    $groupedEquipment = [];
    if ($groupBy && !empty($equipment)) {
        foreach ($equipment as $item) {
            $groupKey = '';
            switch ($groupBy) {
                case 'department':
                    $groupKey = $item['department'];
                    break;
                case 'category':
                    $groupKey = $item['category'];
                    break;
                case 'employee':
                    $groupKey = $item['assigned_to_employee'] ?: 'Unassigned';
                    break;
                case 'team':
                    $groupKey = $item['team_name'] ?: 'No Team';
                    break;
            }

            if (!isset($groupedEquipment[$groupKey])) {
                $groupedEquipment[$groupKey] = [];
            }
            $groupedEquipment[$groupKey][] = $item;
        }
        ksort($groupedEquipment);
    }

    // Fetch categories for filter
    $stmt = $conn->query("SELECT DISTINCT category FROM equipment ORDER BY category ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Equipment list error: " . $e->getMessage());
    $equipment = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/equipment.css">
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
                <h1>Equipment Management</h1>
                <p class="dashboard-subtitle">Track and manage all company assets</p>
            </div>
            <div class="header-actions">
                <?php if (canPerform('add_equipment')): ?>
                    <button class="btn btn-primary" onclick="window.location.href='add-equipment.php'">
                        <i class="fas fa-plus"></i>
                        Add Equipment
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
                        <input type="text" name="search" placeholder="Search equipment..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="under_maintenance" <?php echo $filterStatus === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="scrapped" <?php echo $filterStatus === 'scrapped' ? 'selected' : ''; ?>>Scrapped
                        </option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-layer-group"></i>
                        Group By:
                    </label>
                    <select name="group_by" onchange="this.form.submit()" class="group-by-select">
                        <option value="">No Grouping</option>
                        <option value="department" <?php echo $groupBy === 'department' ? 'selected' : ''; ?>>Department
                        </option>
                        <option value="category" <?php echo $groupBy === 'category' ? 'selected' : ''; ?>>Category
                        </option>
                        <option value="employee" <?php echo $groupBy === 'employee' ? 'selected' : ''; ?>>Employee
                        </option>
                        <option value="team" <?php echo $groupBy === 'team' ? 'selected' : ''; ?>>Maintenance Team
                        </option>
                    </select>
                </div>

                <?php if ($searchQuery || $filterCategory || $filterStatus || $groupBy): ?>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.location.href='equipment.php'">
                        <i class="fas fa-times"></i>
                        Clear
                    </button>
                <?php endif; ?>
            </form>
        </section>

        <!-- Equipment Display -->
        <section class="equipment-section">
            <?php if (empty($equipment)): ?>
                <div class="empty-state">
                    <i class="fas fa-cogs"></i>
                    <h3>No Equipment Found</h3>
                    <p>
                        <?php if ($searchQuery || $filterCategory || $filterStatus): ?>
                            No equipment matches your filters. Try adjusting your search criteria.
                        <?php else: ?>
                            Start by adding your first equipment to the system.
                        <?php endif; ?>
                    </p>
                    <?php if (canPerform('add_equipment')): ?>
                        <button class="btn btn-primary" onclick="window.location.href='add-equipment.php'">
                            <i class="fas fa-plus"></i>
                            Add First Equipment
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if ($groupBy && !empty($groupedEquipment)): ?>
                    <!-- Grouped View -->
                    <?php foreach ($groupedEquipment as $groupName => $items): ?>
                        <div class="equipment-group">
                            <div class="group-header">
                                <h3>
                                    <i class="fas fa-<?php
                                    echo $groupBy === 'department' ? 'building' :
                                        ($groupBy === 'category' ? 'tag' :
                                            ($groupBy === 'employee' ? 'user' : 'users-cog'));
                                    ?>"></i>
                                    <?php echo htmlspecialchars($groupName); ?>
                                </h3>
                                <span class="group-count"><?php echo count($items); ?> equipment</span>
                            </div>

                            <div class="equipment-grid">
                                <?php foreach ($items as $item): ?>
                                    <?php include 'includes/equipment-card.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Regular Grid View -->
                    <div class="equipment-grid">
                        <?php foreach ($equipment as $item): ?>
                            <?php include 'includes/equipment-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="results-info">
                    Showing <?php echo count($equipment); ?> equipment
                    <?php if ($searchQuery || $filterCategory || $filterStatus): ?>
                        (filtered)
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/equipment.js"></script>
</body>

</html>