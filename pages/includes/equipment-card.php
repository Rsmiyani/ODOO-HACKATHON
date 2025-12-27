<div class="equipment-card">
    <div class="equipment-header">
        <div class="equipment-icon">
            <i class="fas fa-cog"></i>
        </div>
        <div class="equipment-title">
            <h3><?php echo htmlspecialchars($item['equipment_name']); ?></h3>
            <p class="serial-number"><?php echo htmlspecialchars($item['serial_number']); ?></p>
        </div>
        <span class="equipment-status status-<?php echo $item['status']; ?>">
            <i class="fas fa-circle"></i>
            <?php echo str_replace('_', ' ', ucfirst($item['status'])); ?>
        </span>
    </div>

    <div class="equipment-details">
        <div class="detail-row">
            <span class="detail-icon"><i class="fas fa-tag"></i></span>
            <span class="detail-text"><?php echo htmlspecialchars($item['category']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-icon"><i class="fas fa-building"></i></span>
            <span class="detail-text"><?php echo htmlspecialchars($item['department']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-icon"><i class="fas fa-map-pin"></i></span>
            <span class="detail-text"><?php echo htmlspecialchars($item['location']); ?></span>
        </div>
        <?php if ($item['assigned_to_employee']): ?>
            <div class="detail-row">
                <span class="detail-icon"><i class="fas fa-user"></i></span>
                <span class="detail-text"><?php echo htmlspecialchars($item['assigned_to_employee']); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($item['team_name']): ?>
            <div class="detail-row">
                <span class="detail-icon"><i class="fas fa-users-cog"></i></span>
                <span class="detail-text"><?php echo htmlspecialchars($item['team_name']); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Smart Button - Maintenance Requests -->
    <div class="equipment-actions">
        <button class="smart-button <?php echo $item['open_requests'] > 0 ? 'has-open' : ''; ?>"
            onclick="window.location.href='view-equipment.php?id=<?php echo $item['id']; ?>'">
            <span class="button-content">
                <i class="fas fa-wrench"></i>
                <span>Maintenance</span>
            </span>
            <?php if ($item['open_requests'] > 0): ?>
                <span class="request-badge badge-danger"><?php echo $item['open_requests']; ?></span>
            <?php else: ?>
                <span class="request-badge"><?php echo $item['total_requests']; ?></span>
            <?php endif; ?>
        </button>

        <div class="action-buttons">
            <button class="btn-icon" onclick="window.location.href='view-equipment.php?id=<?php echo $item['id']; ?>'"
                title="View Details">
                <i class="fas fa-eye"></i>
            </button>

            <?php if (canPerform('edit_equipment')): ?>
                <button class="btn-icon" onclick="window.location.href='edit-equipment.php?id=<?php echo $item['id']; ?>'"
                    title="Edit Equipment">
                    <i class="fas fa-edit"></i>
                </button>
            <?php endif; ?>

            <?php if (canPerform('delete_equipment')): ?>
                <button class="btn-icon btn-danger"
                    onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['equipment_name']); ?>')"
                    title="Delete Equipment">
                    <i class="fas fa-trash"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>  