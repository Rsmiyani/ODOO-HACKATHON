// Kanban Board Drag and Drop Functionality

let draggedCard = null;

// Initialize drag and drop
document.addEventListener('DOMContentLoaded', function () {
    initializeDragAndDrop();
});

function initializeDragAndDrop() {
    const cards = document.querySelectorAll('.kanban-card');
    const columns = document.querySelectorAll('.kanban-cards');

    // Add drag events to cards
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    // Add drop events to columns
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    draggedCard = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');

    // Remove drag-over class from all columns
    document.querySelectorAll('.kanban-cards').forEach(column => {
        column.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    // Add visual feedback
    this.classList.add('drag-over');

    // Get the card being dragged over
    const afterElement = getDragAfterElement(this, e.clientY);

    if (afterElement == null) {
        this.appendChild(draggedCard);
    } else {
        this.insertBefore(draggedCard, afterElement);
    }
}

function handleDragLeave(e) {
    if (e.target.classList.contains('kanban-cards')) {
        e.target.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    if (draggedCard) {
        const requestId = draggedCard.getAttribute('data-request-id');
        const oldStage = draggedCard.getAttribute('data-stage');
        const newStage = this.closest('.kanban-column').getAttribute('data-stage');

        if (oldStage !== newStage) {
            // Update in database
            updateRequestStage(requestId, newStage, oldStage);
        }
    }
}

function getDragAfterElement(column, y) {
    const draggableElements = [...column.querySelectorAll('.kanban-card:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateRequestStage(requestId, newStage, oldStage) {
    // Show loading
    showToast('Updating...', 'info');

    fetch('../php/api/update_request_stage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            request_id: requestId,
            stage: newStage
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update card's data attribute
                if (draggedCard) {
                    draggedCard.setAttribute('data-stage', newStage);
                }

                // Update column counts
                updateColumnCounts();

                // Show success message
                showToast('Request moved to ' + formatStageName(newStage), 'success');

                // If moved to scrap, show warning
                if (newStage === 'scrap') {
                    showToast('Equipment marked as scrapped', 'error');
                }
            } else {
                // Revert the move
                showToast('Error: ' + data.message, 'error');
                revertCardMove(requestId, oldStage);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to update request', 'error');
            revertCardMove(requestId, oldStage);
        });
}

function revertCardMove(requestId, oldStage) {
    const card = document.querySelector(`[data-request-id="${requestId}"]`);
    const oldColumn = document.querySelector(`#column-${oldStage}`);

    if (card && oldColumn) {
        oldColumn.appendChild(card);
        updateColumnCounts();
    }
}

function updateColumnCounts() {
    const columns = document.querySelectorAll('.kanban-column');

    columns.forEach(column => {
        const stage = column.getAttribute('data-stage');
        const cardsContainer = column.querySelector('.kanban-cards');
        const cards = cardsContainer.querySelectorAll('.kanban-card');
        const countBadge = column.querySelector('.column-count');
        const emptyState = cardsContainer.querySelector('.empty-column');

        // Update count
        if (countBadge) {
            countBadge.textContent = cards.length;
        }

        // Show/hide empty state
        if (cards.length === 0) {
            if (!emptyState) {
                const emptyDiv = createEmptyState(stage);
                cardsContainer.appendChild(emptyDiv);
            }
        } else {
            if (emptyState) {
                emptyState.remove();
            }
        }
    });
}

function createEmptyState(stage) {
    const emptyDiv = document.createElement('div');
    emptyDiv.className = 'empty-column';

    const icons = {
        'new': 'inbox',
        'in_progress': 'spinner',
        'repaired': 'check-circle',
        'scrap': 'trash'
    };

    const messages = {
        'new': 'No new requests',
        'in_progress': 'No tasks in progress',
        'repaired': 'No repaired items',
        'scrap': 'No scrapped items'
    };

    emptyDiv.innerHTML = `
        <i class="fas fa-${icons[stage]}"></i>
        <p>${messages[stage]}</p>
    `;

    return emptyDiv;
}

function formatStageName(stage) {
    const names = {
        'new': 'New',
        'in_progress': 'In Progress',
        'repaired': 'Repaired',
        'scrap': 'Scrap'
    };
    return names[stage] || stage;
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast show ' + type;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function viewRequest(requestId) {
    window.location.href = 'view-request.php?id=' + requestId;
}

function refreshKanban() {
    showToast('Refreshing...', 'info');
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(() => {
    // In production, use AJAX to refresh data without page reload
    console.log('Auto-refresh check...');
}, 30000);
