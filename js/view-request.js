// View Request Page JavaScript

/**
 * Update the stage of a maintenance request
 * @param {number} requestId - The ID of the request to update
 * @param {string} newStage - The new stage (new, in_progress, repaired, scrap)
 */
function updateStage(requestId, newStage) {
    // Prevent double-clicking
    if (window.isUpdating) {
        return;
    }
    window.isUpdating = true;

    // Show loading state
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Prepare the request data
    const data = {
        request_id: requestId,
        stage: newStage
    };

    // If marking as repaired, could optionally ask for duration
    // For now, we'll just update the stage

    // Send AJAX request
    fetch('../php/api/update_request_stage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Success!', data.message || 'Request updated successfully', 'success');

                // Reload the page after a short delay to show updated state
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Show error message
                showNotification('Error', data.message || 'Failed to update request', 'error');

                // Restore button state
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                window.isUpdating = false;
            }
        })
        .catch(error => {
            console.error('Error updating stage:', error);
            showNotification('Error', 'An error occurred while updating the request', 'error');

            // Restore button state
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            window.isUpdating = false;
        });
}

/**
 * Confirm before marking equipment as scrap (irreversible action)
 * @param {number} requestId - The ID of the request
 */
function confirmScrap(requestId) {
    const confirmed = confirm(
        'Are you sure you want to mark this equipment as SCRAP?\n\n' +
        'This action will:\n' +
        '• Mark the request as scrapped\n' +
        '• Update the equipment status to "scrapped"\n' +
        '• This action cannot be undone!\n\n' +
        'Do you want to continue?'
    );

    if (confirmed) {
        updateStage(requestId, 'scrap');
    }
}

/**
 * Show notification toast message
 * @param {string} title - Notification title
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, warning, info)
 */
function showNotification(title, message, type = 'info') {
    // Check if notification container exists, create if not
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    // Icon based on type
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    else if (type === 'error') icon = 'fa-exclamation-circle';
    else if (type === 'warning') icon = 'fa-exclamation-triangle';

    notification.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="notification-content">
            <strong>${title}</strong>
            <p>${message}</p>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add to container
    container.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add notification styles dynamically if not in CSS
if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
        }

        .notification i.fas:first-child {
            font-size: 24px;
        }

        .notification-success {
            border-left: 4px solid #10b981;
        }
        .notification-success i.fas:first-child {
            color: #10b981;
        }

        .notification-error {
            border-left: 4px solid #ef4444;
        }
        .notification-error i.fas:first-child {
            color: #ef4444;
        }

        .notification-warning {
            border-left: 4px solid #f59e0b;
        }
        .notification-warning i.fas:first-child {
            color: #f59e0b;
        }

        .notification-info {
            border-left: 4px solid #3b82f6;
        }
        .notification-info i.fas:first-child {
            color: #3b82f6;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .notification-content p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }

        .notification-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            transition: color 0.2s;
        }

        .notification-close:hover {
            color: #374151;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        @media (max-width: 640px) {
            .notification-container {
                left: 20px;
                right: 20px;
            }
            
            .notification {
                min-width: auto;
                max-width: 100%;
            }
        }
    `;
    document.head.appendChild(style);
}
