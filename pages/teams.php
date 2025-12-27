<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission
if (!hasAnyRole(['admin', 'manager'])) {
    $_SESSION['error'] = "You don't have permission to view teams.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();

// Fetch all teams with member count
try {
    $stmt = $conn->query("
        SELECT 
            mt.id,
            mt.team_name,
            mt.description,
            mt.created_at,
            COUNT(DISTINCT tm.user_id) as member_count,
            COUNT(DISTINCT e.id) as equipment_count,
            COUNT(DISTINCT mr.id) as active_requests
        FROM maintenance_teams mt
        LEFT JOIN team_members tm ON mt.id = tm.team_id
        LEFT JOIN equipment e ON mt.id = e.maintenance_team_id
        LEFT JOIN maintenance_requests mr ON e.id = mr.equipment_id AND mr.stage IN ('new', 'in_progress')
        GROUP BY mt.id
        ORDER BY mt.team_name ASC
    ");
    $teams = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Teams list error: " . $e->getMessage());
    $teams = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Management - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/teams.css">
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

            <a href="teams.php" class="nav-item active">
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
                <h1>Teams Management</h1>
                <p class="dashboard-subtitle">Manage maintenance teams and members</p>
            </div>
            <div class="header-actions">
                <?php if (canPerform('add_team')): ?>
                    <button class="btn btn-primary" onclick="showAddTeamModal()">
                        <i class="fas fa-plus"></i>
                        Add Team
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Teams Grid -->
        <section class="teams-grid">
            <?php if (empty($teams)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Teams Found</h3>
                    <p>Start by creating your first maintenance team.</p>
                    <?php if (canPerform('add_team')): ?>
                        <button class="btn btn-primary" onclick="showAddTeamModal()">
                            <i class="fas fa-plus"></i>
                            Create First Team
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <div class="team-header">
                            <div class="team-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h3 class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                        </div>

                        <?php if ($team['description']): ?>
                            <p class="team-description"><?php echo htmlspecialchars($team['description']); ?></p>
                        <?php endif; ?>

                        <div class="team-stats">
                            <div class="stat-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <span class="stat-value"><?php echo $team['member_count']; ?></span>
                                    <span class="stat-label">Members</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <i class="fas fa-cog"></i>
                                <div>
                                    <span class="stat-value"><?php echo $team['equipment_count']; ?></span>
                                    <span class="stat-label">Equipment</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <i class="fas fa-tasks"></i>
                                <div>
                                    <span class="stat-value"><?php echo $team['active_requests']; ?></span>
                                    <span class="stat-label">Active</span>
                                </div>
                            </div>
                        </div>

                        <div class="team-actions">
                            <button class="btn btn-primary btn-block" onclick="viewTeamDetails(<?php echo $team['id']; ?>)">
                                <i class="fas fa-eye"></i>
                                View Members
                            </button>

                            <div class="action-buttons">
                                <?php if (canPerform('edit_team')): ?>
                                    <button class="btn-icon"
                                        onclick="editTeam(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>', '<?php echo htmlspecialchars($team['description'] ?? ''); ?>')"
                                        title="Edit Team">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if (canPerform('delete_team')): ?>
                                    <button class="btn-icon btn-danger"
                                        onclick="confirmDeleteTeam(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>')"
                                        title="Delete Team">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Add/Edit Team Modal -->
    <div id="teamModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2 id="teamModalTitle">Add Team</h2>
                <button class="modal-close" onclick="closeTeamModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="teamForm" onsubmit="saveTeam(event)">
                    <input type="hidden" id="team_id" value="">

                    <div class="form-group">
                        <label for="team_name">
                            <i class="fas fa-users"></i>
                            Team Name <span class="required">*</span>
                        </label>
                        <input type="text" id="team_name" required placeholder="e.g., Mechanical Team">
                    </div>

                    <div class="form-group">
                        <label for="team_description">
                            <i class="fas fa-align-left"></i>
                            Description
                        </label>
                        <textarea id="team_description" rows="3"
                            placeholder="Brief description of the team's responsibilities..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="closeTeamModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Team Members Modal -->
    <div id="membersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="membersModalTitle">Team Members</h2>
                <button class="modal-close" onclick="closeMembersModal()">&times;</button>
            </div>
            <div class="modal-body" id="membersModalBody">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
    <script src="../js/teams.js"></script>
</body>

</html>