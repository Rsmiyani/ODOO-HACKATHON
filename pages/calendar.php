<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission (only admin and manager)
if (!hasAnyRole(['admin', 'manager'])) {
    $_SESSION['error'] = "You don't have permission to view calendar.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();

// Fetch all maintenance teams for filter
try {
    $stmt = $conn->query("SELECT id, team_name FROM maintenance_teams ORDER BY team_name ASC");
    $teams = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Calendar error: " . $e->getMessage());
    $teams = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Calendar - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/calendar.css">
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

            <a href="calendar.php" class="nav-item active">
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
                <h1>Maintenance Calendar</h1>
                <p class="dashboard-subtitle">Schedule and manage preventive maintenance</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='create-request.php?type=preventive'">
                    <i class="fas fa-plus"></i>
                    Schedule Maintenance
                </button>
            </div>
        </header>

        <!-- Calendar Controls -->
        <section class="calendar-controls">
            <div class="month-navigation">
                <button class="btn-nav" id="prevMonth">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 id="currentMonth">Loading...</h2>
                <button class="btn-nav" id="nextMonth">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="btn btn-outline btn-sm" id="todayBtn">
                    <i class="fas fa-calendar-day"></i>
                    Today
                </button>
            </div>

            <div class="calendar-filters">
                <select id="teamFilter" class="filter-select">
                    <option value="">All Teams</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>">
                            <?php echo htmlspecialchars($team['team_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="typeFilter" class="filter-select">
                    <option value="">All Types</option>
                    <option value="preventive">Preventive</option>
                    <option value="corrective">Corrective</option>
                </select>

                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="new">New</option>
                    <option value="in_progress">In Progress</option>
                    <option value="repaired">Repaired</option>
                </select>
            </div>
        </section>

        <!-- Calendar Legend -->
        <section class="calendar-legend">
            <div class="legend-item">
                <span class="legend-color preventive"></span>
                <span>Preventive Maintenance</span>
            </div>
            <div class="legend-item">
                <span class="legend-color corrective"></span>
                <span>Corrective Maintenance</span>
            </div>
            <div class="legend-item">
                <span class="legend-color overdue"></span>
                <span>Overdue</span>
            </div>
            <div class="legend-item">
                <span class="legend-color completed"></span>
                <span>Completed</span>
            </div>
        </section>

        <!-- Calendar Grid -->
        <section class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-day-name">Sunday</div>
                <div class="calendar-day-name">Monday</div>
                <div class="calendar-day-name">Tuesday</div>
                <div class="calendar-day-name">Wednesday</div>
                <div class="calendar-day-name">Thursday</div>
                <div class="calendar-day-name">Friday</div>
                <div class="calendar-day-name">Saturday</div>
            </div>

            <div class="calendar-grid" id="calendarGrid">
                <!-- Calendar days will be generated by JavaScript -->
                <div class="loading-calendar">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading calendar...</p>
                </div>
            </div>
        </section>

        <!-- Event Details Sidebar -->
        <aside class="event-sidebar" id="eventSidebar">
            <div class="sidebar-header">
                <h3 id="selectedDate">Events</h3>
                <button class="close-sidebar" onclick="closeSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="sidebar-content" id="sidebarContent">
                <p class="empty-message">Select a date to view events</p>
            </div>
        </aside>
    </main>

    <!-- Event Detail Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Maintenance Details</h2>
                <button class="modal-close" onclick="closeEventModal()">&times;</button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Event details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/calendar.js"></script>
</body>

</html>