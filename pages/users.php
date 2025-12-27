<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission - Only Admin can access
if (!hasRole('admin')) {
    $_SESSION['error'] = "You don't have permission to access user management.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();

// Get filter parameters
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filterRole = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Fetch users
try {
    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            u.is_active,
            u.created_at,
            COUNT(DISTINCT mr.id) as assigned_requests
        FROM users u
        LEFT JOIN maintenance_requests mr ON u.id = mr.assigned_to AND mr.stage IN ('new', 'in_progress')
        WHERE 1=1
    ";

    $params = [];

    // Add search filter
    if (!empty($searchQuery)) {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Add role filter
    if (!empty($filterRole)) {
        $sql .= " AND u.role = ?";
        $params[] = $filterRole;
    }

    // Add status filter
    if ($filterStatus !== '') {
        $isActive = ($filterStatus === 'active') ? 1 : 0;
        $sql .= " AND u.is_active = ?";
        $params[] = $isActive;
    }

    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Users list error: " . $e->getMessage());
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/users.css">
    <link rel="stylesheet" href="../css/modal.css">
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

            <a href="equipment.php" class="nav-item">
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

            <a href="users.php" class="nav-item active">
                <i class="fas fa-user-cog"></i>
                <span>User Management</span>
            </a>
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
                <h1>User Management</h1>
                <p class="dashboard-subtitle">Manage system users and permissions</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showAddUserModal()">
                    <i class="fas fa-user-plus"></i>
                    Add User
                </button>
            </div>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-overview">
            <?php
            $totalUsers = count($users);
            $activeUsers = count(array_filter($users, fn($u) => $u['is_active']));
            $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
            $managerCount = count(array_filter($users, fn($u) => $u['role'] === 'manager'));
            $technicianCount = count(array_filter($users, fn($u) => $u['role'] === 'technician'));
            ?>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $activeUsers; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $adminCount; ?></h3>
                    <p>Administrators</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $managerCount; ?></h3>
                    <p>Managers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $technicianCount; ?></h3>
                    <p>Technicians</p>
                </div>
            </div>
        </section>

        <!-- Filters Section -->
        <section class="filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by name or email..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <select name="role" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo $filterRole === 'manager' ? 'selected' : ''; ?>>Manager
                        </option>
                        <option value="technician" <?php echo $filterRole === 'technician' ? 'selected' : ''; ?>>
                            Technician</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive
                        </option>
                    </select>
                </div>

                <?php if ($searchQuery || $filterRole || $filterStatus): ?>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.location.href='users.php'">
                        <i class="fas fa-times"></i>
                        Clear
                    </button>
                <?php endif; ?>
            </form>
        </section>

        <!-- Users Table -->
        <section class="table-section">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>
                        <?php if ($searchQuery || $filterRole || $filterStatus): ?>
                            No users match your filters. Try adjusting your search criteria.
                        <?php else: ?>
                            Start by adding users to the system.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Active Requests</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $usr): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-small">
                                                <?php echo strtoupper(substr($usr['name'], 0, 2)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($usr['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                    <td>
                                        <span class="badge badge-role-<?php echo $usr['role']; ?>">
                                            <i class="fas fa-<?php
                                            echo $usr['role'] === 'admin' ? 'user-shield' :
                                                ($usr['role'] === 'manager' ? 'user-tie' : 'user-cog');
                                            ?>"></i>
                                            <?php echo ucfirst($usr['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usr['is_active']): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-circle"></i>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-circle"></i>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usr['assigned_requests'] > 0): ?>
                                            <span class="count-badge"><?php echo $usr['assigned_requests']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($usr['id'] != $user['id']): ?>
                                                <button class="btn-icon" onclick='editUser(<?php echo json_encode($usr); ?>)'
                                                    title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <?php if ($usr['is_active']): ?>
                                                    <button class="btn-icon btn-warning"
                                                        onclick="toggleUserStatus(<?php echo $usr['id']; ?>, 0, '<?php echo htmlspecialchars($usr['name']); ?>')"
                                                        title="Deactivate User">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-icon btn-success"
                                                        onclick="toggleUserStatus(<?php echo $usr['id']; ?>, 1, '<?php echo htmlspecialchars($usr['name']); ?>')"
                                                        title="Activate User">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <button class="btn-icon btn-danger"
                                                    onclick="confirmDeleteUser(<?php echo $usr['id']; ?>, '<?php echo htmlspecialchars($usr['name']); ?>')"
                                                    title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">(You)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="results-info">
                    Showing <?php echo count($users); ?> user(s)
                    <?php if ($searchQuery || $filterRole || $filterStatus): ?>
                        (filtered)
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="userModalTitle">Add User</h2>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="saveUser(event)">
                    <input type="hidden" id="user_id" value="">

                    <div class="form-group">
                        <label for="user_name">
                            <i class="fas fa-user"></i>
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" id="user_name" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label for="user_email">
                            <i class="fas fa-envelope"></i>
                            Email <span class="required">*</span>
                        </label>
                        <input type="email" id="user_email" required placeholder="user@example.com">
                    </div>

                    <div class="form-group">
                        <label for="user_role">
                            <i class="fas fa-user-tag"></i>
                            Role <span class="required">*</span>
                        </label>
                        <select id="user_role" required>
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technician</option>
                        </select>
                    </div>

                    <div class="form-group" id="password_group">
                        <label for="user_password">
                            <i class="fas fa-lock"></i>
                            Password <span class="required" id="password_required">*</span>
                        </label>
                        <input type="password" id="user_password" placeholder="Enter password">
                        <small class="form-hint" id="password_hint">Minimum 6 characters. Leave blank to keep existing
                            password.</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="user_active" checked>
                            Active User
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeUserModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/users.js"></script>
</body>

</html>