// Teams Management JavaScript

function showAddTeamModal() {
    document.getElementById('teamModalTitle').textContent = 'Add Team';
    document.getElementById('team_id').value = '';
    document.getElementById('team_name').value = '';
    document.getElementById('team_description').value = '';
    document.getElementById('teamModal').classList.add('show');
}

function editTeam(teamId, teamName, description) {
    document.getElementById('teamModalTitle').textContent = 'Edit Team';
    document.getElementById('team_id').value = teamId;
    document.getElementById('team_name').value = teamName;
    document.getElementById('team_description').value = description;
    document.getElementById('teamModal').classList.add('show');
}

function closeTeamModal() {
    document.getElementById('teamModal').classList.remove('show');
}

function saveTeam(event) {
    event.preventDefault();

    const teamId = document.getElementById('team_id').value;
    const teamName = document.getElementById('team_name').value;
    const description = document.getElementById('team_description').value;

    const url = teamId ? '../php/api/update_team.php' : '../php/api/create_team.php';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            team_id: teamId,
            team_name: teamName,
            description: description
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(teamId ? 'Team updated successfully!' : 'Team created successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save team');
        });
}

function confirmDeleteTeam(teamId, teamName) {
    if (confirm(`Are you sure you want to delete "${teamName}"?\n\nThis will not delete team members, but will unassign them from this team.`)) {
        deleteTeam(teamId);
    }
}

function deleteTeam(teamId) {
    fetch('../php/api/delete_team.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ team_id: teamId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Team deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete team');
        });
}

function viewTeamDetails(teamId) {
    const modal = document.getElementById('membersModal');
    const modalBody = document.getElementById('membersModalBody');

    modal.classList.add('show');

    modalBody.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i>
            Loading team members...
        </div>
    `;

    fetch(`../php/api/get_team_members.php?team_id=${teamId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTeamMembers(data.team, data.members, data.available_users);
            } else {
                modalBody.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.message || 'Failed to load team members'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-times-circle"></i>
                    <p>Error loading team members</p>
                </div>
            `;
        });
}

function displayTeamMembers(team, members, availableUsers) {
    const modalBody = document.getElementById('membersModalBody');
    document.getElementById('membersModalTitle').textContent = `${team.team_name} - Members`;

    let html = '<div class="members-list">';

    if (members.length === 0) {
        html += `
            <div class="empty-message">
                <i class="fas fa-users-slash"></i>
                <p>No members in this team yet</p>
            </div>
        `;
    } else {
        members.forEach(member => {
            const initials = member.name.split(' ').map(n => n[0]).join('').toUpperCase();
            html += `
                <div class="member-card">
                    <div class="member-info">
                        <div class="member-avatar">${initials}</div>
                        <div class="member-details">
                            <h4>${escapeHtml(member.name)}</h4>
                            <p>${escapeHtml(member.email)} • ${member.role}</p>
                        </div>
                    </div>
                    <div class="member-actions">
                        <button onclick="removeMember(${team.id}, ${member.id}, '${escapeHtml(member.name)}')" title="Remove from team">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </div>
                </div>
            `;
        });
    }

    html += '</div>';

    // Add member section
    if (availableUsers.length > 0) {
        html += `
            <div class="add-member-section">
                <h3>Add Member</h3>
                <div class="add-member-form">
                    <select id="newMemberId">
                        <option value="">Select a user to add...</option>
                        ${availableUsers.map(user =>
            `<option value="${user.id}">${escapeHtml(user.name)} (${user.role})</option>`
        ).join('')}
                    </select>
                    <button class="btn btn-primary" onclick="addMember(${team.id})">
                        <i class="fas fa-user-plus"></i>
                        Add
                    </button>
                </div>
            </div>
        `;
    }

    modalBody.innerHTML = html;
}

function addMember(teamId) {
    const userId = document.getElementById('newMemberId').value;

    if (!userId) {
        alert('Please select a user');
        return;
    }

    fetch('../php/api/add_team_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            team_id: teamId,
            user_id: userId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                viewTeamDetails(teamId); // Refresh
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add member');
        });
}

function removeMember(teamId, userId, userName) {
    if (confirm(`Remove ${userName} from this team?`)) {
        fetch('../php/api/remove_team_member.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                team_id: teamId,
                user_id: userId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    viewTeamDetails(teamId); // Refresh
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove member');
            });
    }
}

function closeMembersModal() {
    document.getElementById('membersModal').classList.remove('show');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals on outside click
window.addEventListener('click', function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
});
