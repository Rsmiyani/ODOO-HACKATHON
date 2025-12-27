<?php
require_once '../php/config.php';

// Check if user is logged in
requireLogin();

// Check permission
if (!hasAnyRole(['admin', 'manager'])) {
    $_SESSION['error'] = "You don't have permission to view reports.";
    header("Location: dashboard.php");
    exit();
}

$user = getCurrentUser();

// Fetch statistics for reports
try {
    // Total counts
    $stmt = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE status != 'scrapped'");
    $totalEquipment = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests");
    $totalRequests = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE stage IN ('new', 'in_progress')");
    $openRequests = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE stage = 'repaired'");
    $completedRequests = $stmt->fetch()['total'];

    // Requests per Team
    $stmt = $conn->query("
        SELECT 
            COALESCE(mt.team_name, 'Unassigned') as team_name,
            COUNT(mr.id) as request_count
        FROM maintenance_requests mr
        INNER JOIN equipment e ON mr.equipment_id = e.id
        LEFT JOIN maintenance_teams mt ON e.maintenance_team_id = mt.id
        GROUP BY mt.id, mt.team_name
        ORDER BY request_count DESC
    ");
    $requestsPerTeam = $stmt->fetchAll();

    // Requests per Equipment Category
    $stmt = $conn->query("
        SELECT 
            e.category,
            COUNT(mr.id) as request_count
        FROM maintenance_requests mr
        INNER JOIN equipment e ON mr.equipment_id = e.id
        GROUP BY e.category
        ORDER BY request_count DESC
    ");
    $requestsPerCategory = $stmt->fetchAll();

    // Requests by Type
    $stmt = $conn->query("
        SELECT 
            request_type,
            COUNT(*) as count
        FROM maintenance_requests
        GROUP BY request_type
    ");
    $requestsByType = $stmt->fetchAll();

    // Requests by Priority
    $stmt = $conn->query("
        SELECT 
            priority,
            COUNT(*) as count
        FROM maintenance_requests
        GROUP BY priority
        ORDER BY 
            CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
    ");
    $requestsByPriority = $stmt->fetchAll();

    // Requests by Stage
    $stmt = $conn->query("
        SELECT 
            stage,
            COUNT(*) as count
        FROM maintenance_requests
        GROUP BY stage
        ORDER BY 
            CASE stage
                WHEN 'new' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'repaired' THEN 3
                WHEN 'scrap' THEN 4
            END
    ");
    $requestsByStage = $stmt->fetchAll();

    // Monthly Trends (Last 6 months)
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM maintenance_requests
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyTrends = $stmt->fetchAll();

    // Average resolution time
    $stmt = $conn->query("
        SELECT 
            AVG(duration_hours) as avg_duration
        FROM maintenance_requests
        WHERE duration_hours IS NOT NULL AND stage = 'repaired'
    ");
    $avgDuration = $stmt->fetch()['avg_duration'];

    // Equipment by Status
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM equipment
        GROUP BY status
    ");
    $equipmentByStatus = $stmt->fetchAll();

    // Top 10 Equipment with Most Requests
    $stmt = $conn->query("
        SELECT 
            e.equipment_name,
            e.serial_number,
            COUNT(mr.id) as request_count
        FROM equipment e
        LEFT JOIN maintenance_requests mr ON e.id = mr.equipment_id
        GROUP BY e.id
        ORDER BY request_count DESC
        LIMIT 10
    ");
    $topEquipment = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Reports error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - GearGuard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

            <a href="reports.php" class="nav-item active">
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
                <h1>Reports & Analytics</h1>
                <p class="dashboard-subtitle">Comprehensive maintenance insights and statistics</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    Print Report
                </button>
            </div>
        </header>

        <!-- Summary Statistics -->
        <section class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalEquipment; ?></h3>
                    <p>Total Equipment</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalRequests; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $openRequests; ?></h3>
                    <p>Open Requests</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $completedRequests; ?></h3>
                    <p>Completed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $avgDuration ? number_format($avgDuration, 1) . 'h' : 'N/A'; ?></h3>
                    <p>Avg. Resolution Time</p>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="charts-section">
            <!-- Row 1: Requests per Team & Category -->
            <div class="chart-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-users-cog"></i>
                            Requests per Maintenance Team
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestsPerTeamChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-tag"></i>
                            Requests per Equipment Category
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestsPerCategoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 2: Request Type & Priority -->
            <div class="chart-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-wrench"></i>
                            Requests by Type
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestsByTypeChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-exclamation-triangle"></i>
                            Requests by Priority
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestsByPriorityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 3: Monthly Trends & Stage Distribution -->
            <div class="chart-row">
                <div class="chart-card chart-card-wide">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-chart-line"></i>
                            Monthly Request Trends (Last 6 Months)
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-tasks"></i>
                            Requests by Stage
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestsByStageChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 4: Equipment Status -->
            <div class="chart-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-circle"></i>
                            Equipment by Status
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="equipmentByStatusChart"></canvas>
                    </div>
                </div>

                <!-- Top Equipment Table -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>
                            <i class="fas fa-trophy"></i>
                            Top 10 Equipment (Most Requests)
                        </h3>
                    </div>
                    <div class="table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Equipment</th>
                                    <th>Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1;
                                foreach ($topEquipment as $equip): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rank++; ?></strong></td>
                                        <td>
                                            <div class="equipment-name">
                                                <?php echo htmlspecialchars($equip['equipment_name']); ?>
                                                <small><?php echo htmlspecialchars($equip['serial_number']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="count-badge"><?php echo $equip['request_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
        // Chart.js Configuration
        const chartColors = {
            blue: '#3b82f6',
            orange: '#f59e0b',
            green: '#10b981',
            red: '#ef4444',
            purple: '#8b5cf6',
            yellow: '#eab308',
            gray: '#6b7280',
            pink: '#ec4899'
        };

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        };

        // 1. Requests per Team - Bar Chart
        const requestsPerTeamData = <?php echo json_encode($requestsPerTeam); ?>;
        new Chart(document.getElementById('requestsPerTeamChart'), {
            type: 'bar',
            data: {
                labels: requestsPerTeamData.map(item => item.team_name),
                datasets: [{
                    label: 'Number of Requests',
                    data: requestsPerTeamData.map(item => item.request_count),
                    backgroundColor: chartColors.blue,
                    borderColor: chartColors.blue,
                    borderWidth: 1
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // 2. Requests per Category - Pie Chart
        const requestsPerCategoryData = <?php echo json_encode($requestsPerCategory); ?>;
        const categoryColors = [chartColors.blue, chartColors.orange, chartColors.green, chartColors.purple, chartColors.yellow, chartColors.pink];
        new Chart(document.getElementById('requestsPerCategoryChart'), {
            type: 'pie',
            data: {
                labels: requestsPerCategoryData.map(item => item.category),
                datasets: [{
                    data: requestsPerCategoryData.map(item => item.request_count),
                    backgroundColor: categoryColors.slice(0, requestsPerCategoryData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: chartOptions
        });

        // 3. Requests by Type - Doughnut Chart
        const requestsByTypeData = <?php echo json_encode($requestsByType); ?>;
        new Chart(document.getElementById('requestsByTypeChart'), {
            type: 'doughnut',
            data: {
                labels: requestsByTypeData.map(item => item.request_type.charAt(0).toUpperCase() + item.request_type.slice(1)),
                datasets: [{
                    data: requestsByTypeData.map(item => item.count),
                    backgroundColor: [chartColors.orange, chartColors.blue],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: chartOptions
        });

        // 4. Requests by Priority - Horizontal Bar Chart
        const requestsByPriorityData = <?php echo json_encode($requestsByPriority); ?>;
        const priorityColors = {
            'urgent': chartColors.red,
            'high': chartColors.orange,
            'medium': chartColors.yellow,
            'low': chartColors.green
        };
        new Chart(document.getElementById('requestsByPriorityChart'), {
            type: 'bar',
            data: {
                labels: requestsByPriorityData.map(item => item.priority.charAt(0).toUpperCase() + item.priority.slice(1)),
                datasets: [{
                    label: 'Number of Requests',
                    data: requestsByPriorityData.map(item => item.count),
                    backgroundColor: requestsByPriorityData.map(item => priorityColors[item.priority]),
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // 5. Monthly Trends - Line Chart
        const monthlyTrendsData = <?php echo json_encode($monthlyTrends); ?>;
        new Chart(document.getElementById('monthlyTrendsChart'), {
            type: 'line',
            data: {
                labels: monthlyTrendsData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Requests Created',
                    data: monthlyTrendsData.map(item => item.count),
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // 6. Requests by Stage - Polar Area Chart
        const requestsByStageData = <?php echo json_encode($requestsByStage); ?>;
        const stageColors = {
            'new': chartColors.blue,
            'in_progress': chartColors.orange,
            'repaired': chartColors.green,
            'scrap': chartColors.gray
        };
        new Chart(document.getElementById('requestsByStageChart'), {
            type: 'polarArea',
            data: {
                labels: requestsByStageData.map(item => item.stage.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
                datasets: [{
                    data: requestsByStageData.map(item => item.count),
                    backgroundColor: requestsByStageData.map(item => stageColors[item.stage] + '80'),
                    borderColor: requestsByStageData.map(item => stageColors[item.stage]),
                    borderWidth: 2
                }]
            },
            options: chartOptions
        });

        // 7. Equipment by Status - Doughnut Chart
        const equipmentByStatusData = <?php echo json_encode($equipmentByStatus); ?>;
        const statusColors = {
            'active': chartColors.green,
            'under_maintenance': chartColors.orange,
            'scrapped': chartColors.gray
        };
        new Chart(document.getElementById('equipmentByStatusChart'), {
            type: 'doughnut',
            data: {
                labels: equipmentByStatusData.map(item => item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
                datasets: [{
                    data: equipmentByStatusData.map(item => item.count),
                    backgroundColor: equipmentByStatusData.map(item => statusColors[item.status]),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: chartOptions
        });
    </script>
</body>

</html>