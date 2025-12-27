// Equipment Management JavaScript

// View maintenance requests for specific equipment
function viewMaintenanceRequests(equipmentId) {
    const modal = document.getElementById('maintenanceModal');
    const modalBody = document.getElementById('modalBody');

    // Show modal
    modal.classList.add('show');

    // Show loading
    modalBody.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Loading maintenance requests...
        </div>
    `;

    // Fetch requests for this equipment
    fetch(`../php/api/get_equipment_requests.php?equipment_id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMaintenanceRequests(data.requests, data.equipment);
            } else {
                modalBody.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.message || 'Failed to load requests'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-times-circle"></i>
                    <p>Error loading maintenance requests</p>
                </div>
            `;
        });
}

// Display maintenance requests in modal
function displayMaintenanceRequests(requests, equipment) {
    const modalBody = document.getElementById('modalBody');

    if (requests.length === 0) {
        modalBody.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Maintenance Requests</h3>
                <p>This equipment has no maintenance history yet.</p>
            </div>
        `;
        return;
    }

    let html = `
        <div class="equipment-info-bar">
            <h3>${escapeHtml(equipment.equipment_name)}</h3>
            <p>${escapeHtml(equipment.serial_number)}</p>
        </div>
        <div class="requests-list">
    `;

    requests.forEach(request => {
        const isOverdue = request.is_overdue;
        html += `
            <div class="request-item ${isOverdue ? 'overdue' : ''}">
                <div class="request-header">
                    <span class="request-id">#${request.id}</span>
                    <span class="badge badge-stage-${request.stage}">
                        ${formatStageName(request.stage)}
                    </span>
                </div>
                <h4 class="request-title">${escapeHtml(request.subject)}</h4>
                <div class="request-meta">
                    <span class="badge badge-${request.request_type}">
                        <i class="fas fa-${request.request_type === 'preventive' ? 'calendar-check' : 'wrench'}"></i>
                        ${request.request_type}
                    </span>
                    <span class="badge badge-priority-${request.priority}">
                        ${request.priority}
                    </span>
                    ${request.scheduled_date ? `
                        <span class="request-date">
                            <i class="fas fa-clock"></i>
                            ${formatDate(request.scheduled_date)}
                        </span>
                    ` : ''}
                </div>
                ${request.assigned_to_name ? `
                    <div class="request-assignee">
                        <i class="fas fa-user"></i>
                        Assigned to: ${escapeHtml(request.assigned_to_name)}
                    </div>
                ` : ''}
                <div class="request-actions">
                    <button class="btn-sm btn-primary" onclick="viewRequestDetail(${request.id})">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    modalBody.innerHTML = html;
}

// Close modal
function closeModal() {
    const modal = document.getElementById('maintenanceModal');
    modal.classList.remove('show');
}

// Close modal when clicking outside
window.addEventListener('click', function (event) {
    const modal = document.getElementById('maintenanceModal');
    if (event.target === modal) {
        closeModal();
    }
});

// View request detail
function viewRequestDetail(requestId) {
    window.location.href = `view-request.php?id=${requestId}`;
}

// Confirm delete equipment
function confirmDelete(equipmentId, equipmentName) {
    if (confirm(`Are you sure you want to delete "${equipmentName}"?\n\nThis action cannot be undone.`)) {
        deleteEquipment(equipmentId);
    }
}

// Delete equipment
function deleteEquipment(equipmentId) {
    fetch('../php/api/delete_equipment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ equipment_id: equipmentId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Equipment deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete equipment');
        });
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatStageName(stage) {
    return stage.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
