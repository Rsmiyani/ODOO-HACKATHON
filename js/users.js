// User Management JavaScript

function showAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('user_id').value = '';
    document.getElementById('user_name').value = '';
    document.getElementById('user_email').value = '';
    document.getElementById('user_role').value = '';
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = true;
    document.getElementById('user_active').checked = true;

    // Show password as required for new user
    document.getElementById('password_required').style.display = 'inline';
    document.getElementById('password_hint').textContent = 'Minimum 6 characters';

    document.getElementById('userModal').classList.add('show');
}

function editUser(userData) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = userData.id;
    document.getElementById('user_name').value = userData.name;
    document.getElementById('user_email').value = userData.email;
    document.getElementById('user_role').value = userData.role;
    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = false;
    document.getElementById('user_active').checked = userData.is_active == 1;

    // Password optional for edit
    document.getElementById('password_required').style.display = 'none';
    document.getElementById('password_hint').textContent = 'Leave blank to keep existing password';

    document.getElementById('userModal').classList.add('show');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
}

function saveUser(event) {
    event.preventDefault();

    const userId = document.getElementById('user_id').value;
    const name = document.getElementById('user_name').value.trim();
    const email = document.getElementById('user_email').value.trim();
    const role = document.getElementById('user_role').value;
    const password = document.getElementById('user_password').value;
    const isActive = document.getElementById('user_active').checked ? 1 : 0;

    // Validation
    if (!name) {
        showNotification('Name is required', 'error');
        return;
    }

    if (!email) {
        showNotification('Email is required', 'error');
        return;
    }

    if (!role) {
        showNotification('Role is required', 'error');
        return;
    }

    if (!userId && !password) {
        showNotification('Password is required for new user', 'error');
        return;
    }

    if (password && password.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }

    const url = userId ? '../php/api/update_user.php' : '../php/api/create_user.php';
    const loader = showLoader(userId ? 'Updating user...' : 'Creating user...');

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: userId,
            name: name,
            email: email,
            role: role,
            password: password,
            is_active: isActive
        })
    })
        .then(response => response.json())
        .then(data => {
            hideLoader(loader);
            if (data.success) {
                showNotification(userId ? 'User updated successfully!' : 'User created successfully!', 'success');
                closeUserModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoader(loader);
            console.error('Error:', error);
            showNotification('Failed to save user', 'error');
        });
}

function toggleUserStatus(userId, newStatus, userName) {
    const action = newStatus ? 'activate' : 'deactivate';

    if (!confirm(`Are you sure you want to ${action} ${userName}?`)) {
        return;
    }

    const loader = showLoader(`${action.charAt(0).toUpperCase() + action.slice(1)}ing user...`);

    fetch('../php/api/toggle_user_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: userId,
            is_active: newStatus
        })
    })
        .then(response => response.json())
        .then(data => {
            hideLoader(loader);
            if (data.success) {
                showNotification(`User ${action}d successfully!`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoader(loader);
            console.error('Error:', error);
            showNotification('Failed to update user status', 'error');
        });
}

function confirmDeleteUser(userId, userName) {
    if (confirm(`Are you sure you want to DELETE ${userName}?\n\nThis action cannot be undone!`)) {
        deleteUser(userId);
    }
}

function deleteUser(userId) {
    const loader = showLoader('Deleting user...');

    fetch('../php/api/delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_id: userId })
    })
        .then(response => response.json())
        .then(data => {
            hideLoader(loader);
            if (data.success) {
                showNotification('User deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoader(loader);
            console.error('Error:', error);
            showNotification('Failed to delete user', 'error');
        });
}

// Utility functions
function showLoader(message) {
    const loader = document.createElement('div');
    loader.className = 'loader-overlay';
    loader.innerHTML = `
        <div class="loader-content">
            <i class="fas fa-spinner fa-spin"></i>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(loader);
    return loader;
}

function hideLoader(loader) {
    if (loader && loader.parentNode) {
        loader.parentNode.removeChild(loader);
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Close modal on outside click
window.addEventListener('click', function (event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
});
