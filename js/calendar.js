// Calendar JavaScript for Maintenance Scheduling

let currentDate = new Date();
let selectedDate = null;
let allEvents = [];
let filteredEvents = [];

// Initialize calendar
document.addEventListener('DOMContentLoaded', function () {
    initializeCalendar();
    setupEventListeners();
});

function initializeCalendar() {
    renderCalendar(currentDate);
    loadEvents();
}

function setupEventListeners() {
    // Month navigation
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });

    document.getElementById('todayBtn').addEventListener('click', () => {
        currentDate = new Date();
        renderCalendar(currentDate);
    });

    // Filters
    document.getElementById('teamFilter').addEventListener('change', applyFilters);
    document.getElementById('typeFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
}

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();

    // Update month header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const calendarGrid = document.getElementById('calendarGrid');
    calendarGrid.innerHTML = '';

    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const dayElement = createDayElement(day, new Date(year, month - 1, day), true);
        calendarGrid.appendChild(dayElement);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayDate = new Date(year, month, day);
        const dayElement = createDayElement(day, dayDate, false);
        calendarGrid.appendChild(dayElement);
    }

    // Next month days
    const totalCells = calendarGrid.children.length;
    const remainingCells = 42 - totalCells; // 6 rows × 7 days
    for (let day = 1; day <= remainingCells; day++) {
        const dayElement = createDayElement(day, new Date(year, month + 1, day), true);
        calendarGrid.appendChild(dayElement);
    }

    // Populate events
    populateEvents();
}

function createDayElement(dayNumber, date, otherMonth) {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day';

    if (otherMonth) {
        dayElement.classList.add('other-month');
    }

    // Check if today
    const today = new Date();
    if (date.toDateString() === today.toDateString()) {
        dayElement.classList.add('today');
    }

    // Store date
    dayElement.dataset.date = date.toISOString().split('T')[0];

    dayElement.innerHTML = `
        <div class="day-number">${dayNumber}</div>
        <div class="day-events"></div>
    `;

    // Click event to show day events
    dayElement.addEventListener('click', () => showDayEvents(date, dayElement));

    return dayElement;
}

function loadEvents() {
    const teamFilter = document.getElementById('teamFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    fetch(`../php/api/get_calendar_events.php?team=${teamFilter}&type=${typeFilter}&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allEvents = data.events;
                filteredEvents = allEvents;
                populateEvents();
            } else {
                console.error('Failed to load events:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading events:', error);
        });
}

function applyFilters() {
    loadEvents();
}

function populateEvents() {
    const days = document.querySelectorAll('.calendar-day');

    days.forEach(day => {
        const dateStr = day.dataset.date;
        const eventsContainer = day.querySelector('.day-events');
        eventsContainer.innerHTML = '';

        // Find events for this day
        const dayEvents = filteredEvents.filter(event => {
            const eventDate = event.scheduled_date.split(' ')[0];
            return eventDate === dateStr;
        });

        // Display up to 3 events, then show "+X more"
        const maxVisible = 3;
        dayEvents.slice(0, maxVisible).forEach(event => {
            const eventDot = createEventDot(event);
            eventsContainer.appendChild(eventDot);
        });

        if (dayEvents.length > maxVisible) {
            const moreCount = document.createElement('div');
            moreCount.className = 'event-count';
            moreCount.textContent = `+${dayEvents.length - maxVisible} more`;
            eventsContainer.appendChild(moreCount);
        }
    });
}

function createEventDot(event) {
    const dot = document.createElement('div');

    // Determine class based on status and date
    let eventClass = event.request_type;
    if (event.stage === 'repaired') {
        eventClass = 'completed';
    } else if (isOverdue(event.scheduled_date) && event.stage !== 'repaired') {
        eventClass = 'overdue';
    }

    dot.className = `event-dot ${eventClass}`;
    dot.textContent = event.subject;
    dot.title = `${event.equipment_name} - ${event.subject}`;

    // Click to show details
    dot.addEventListener('click', (e) => {
        e.stopPropagation();
        showEventDetails(event);
    });

    return dot;
}

function isOverdue(scheduledDate) {
    const now = new Date();
    const scheduled = new Date(scheduledDate);
    return scheduled < now;
}

function showDayEvents(date, dayElement) {
    // Remove previous selection
    document.querySelectorAll('.calendar-day.selected').forEach(el => {
        el.classList.remove('selected');
    });

    // Add selection to clicked day
    dayElement.classList.add('selected');

    selectedDate = date;
    const dateStr = date.toISOString().split('T')[0];

    // Find events for this day
    const dayEvents = filteredEvents.filter(event => {
        const eventDate = event.scheduled_date.split(' ')[0];
        return eventDate === dateStr;
    });

    // Open sidebar
    const sidebar = document.getElementById('eventSidebar');
    sidebar.classList.add('open');

    // Update sidebar header
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('selectedDate').textContent = date.toLocaleDateString('en-US', options);

    // Populate sidebar content
    const sidebarContent = document.getElementById('sidebarContent');

    if (dayEvents.length === 0) {
        sidebarContent.innerHTML = `
            <div class="empty-message">
                <i class="fas fa-calendar-times"></i>
                <p>No maintenance scheduled for this day</p>
            </div>
        `;
    } else {
        let html = '';
        dayEvents.forEach(event => {
            let eventClass = event.request_type;
            if (event.stage === 'repaired') {
                eventClass = 'completed';
            } else if (isOverdue(event.scheduled_date) && event.stage !== 'repaired') {
                eventClass = 'overdue';
            }

            html += `
                <div class="sidebar-event ${eventClass}" onclick="showEventDetails(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                    <div class="event-time">
                        <i class="fas fa-clock"></i>
                        ${formatTime(event.scheduled_date)}
                    </div>
                    <div class="event-title">${escapeHtml(event.subject)}</div>
                    <div class="event-equipment">
                        <i class="fas fa-cog"></i>
                        ${escapeHtml(event.equipment_name)}
                    </div>
                    <div class="event-meta">
                        <span class="badge badge-${event.request_type}">${event.request_type}</span>
                        <span class="badge badge-priority-${event.priority}">${event.priority}</span>
                        <span class="badge badge-stage-${event.stage}">${formatStageName(event.stage)}</span>
                    </div>
                </div>
            `;
        });
        sidebarContent.innerHTML = html;
    }
}

function closeSidebar() {
    document.getElementById('eventSidebar').classList.remove('open');
    document.querySelectorAll('.calendar-day.selected').forEach(el => {
        el.classList.remove('selected');
    });
}

function showEventDetails(event) {
    const modal = document.getElementById('eventModal');
    const modalBody = document.getElementById('eventModalBody');

    let statusClass = event.request_type;
    if (event.stage === 'repaired') {
        statusClass = 'completed';
    } else if (isOverdue(event.scheduled_date) && event.stage !== 'repaired') {
        statusClass = 'overdue';
    }

    modalBody.innerHTML = `
        <div class="event-detail-card">
            <div class="event-detail-header ${statusClass}">
                <h3>${escapeHtml(event.subject)}</h3>
                <span class="badge badge-stage-${event.stage}">${formatStageName(event.stage)}</span>
            </div>
            
            <div class="event-detail-body">
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-cog"></i>
                        Equipment
                    </div>
                    <div class="detail-value">
                        <strong>${escapeHtml(event.equipment_name)}</strong>
                        <br><small>${escapeHtml(event.serial_number)}</small>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-alt"></i>
                        Scheduled
                    </div>
                    <div class="detail-value">
                        ${formatDateTime(event.scheduled_date)}
                        ${isOverdue(event.scheduled_date) && event.stage !== 'repaired' ?
            '<span class="overdue-tag"><i class="fas fa-exclamation-triangle"></i> OVERDUE</span>' : ''}
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-tag"></i>
                        Type & Priority
                    </div>
                    <div class="detail-value">
                        <span class="badge badge-${event.request_type}">${event.request_type}</span>
                        <span class="badge badge-priority-${event.priority}">${event.priority}</span>
                    </div>
                </div>
                
                ${event.assigned_to_name ? `
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-user"></i>
                            Assigned To
                        </div>
                        <div class="detail-value">
                            ${escapeHtml(event.assigned_to_name)}
                        </div>
                    </div>
                ` : ''}
                
                ${event.description ? `
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-align-left"></i>
                            Description
                        </div>
                        <div class="detail-value">
                            ${escapeHtml(event.description)}
                        </div>
                    </div>
                ` : ''}
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-info-circle"></i>
                        Request ID
                    </div>
                    <div class="detail-value">
                        #${event.id}
                    </div>
                </div>
            </div>
            
            <div class="event-detail-actions">
                <button class="btn btn-outline" onclick="closeEventModal()">
                    <i class="fas fa-times"></i>
                    Close
                </button>
                <button class="btn btn-primary" onclick="window.location.href='view-request.php?id=${event.id}'">
                    <i class="fas fa-eye"></i>
                    View Full Details
                </button>
            </div>
        </div>
    `;

    modal.classList.add('show');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('show');
}

// Close modal when clicking outside
window.addEventListener('click', function (event) {
    const modal = document.getElementById('eventModal');
    if (event.target === modal) {
        closeEventModal();
    }
});

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatStageName(stage) {
    return stage.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
