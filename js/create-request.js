// Create Request Form with Auto-Fill Logic

// Equipment data mapping
let equipmentData = {};

// Initialize form
document.addEventListener('DOMContentLoaded', function () {
    initializeEquipmentData();
    initializeRequestTypeToggle();
    initializeAutoFill();
});

// Store equipment data from select options
function initializeEquipmentData() {
    const equipmentSelect = document.getElementById('equipment_id');

    if (equipmentSelect) {
        const options = equipmentSelect.querySelectorAll('option');

        options.forEach(option => {
            if (option.value) {
                equipmentData[option.value] = {
                    teamId: option.getAttribute('data-team-id'),
                    technicianId: option.getAttribute('data-technician-id'),
                    category: option.getAttribute('data-category')
                };
            }
        });
    }
}

// Request type toggle handler
function initializeRequestTypeToggle() {
    const typeOptions = document.querySelectorAll('.type-option');
    const scheduledDateInput = document.getElementById('scheduled_date');
    const dateRequired = document.getElementById('dateRequired');

    typeOptions.forEach(option => {
        option.addEventListener('click', function () {
            // Remove active class from all options
            typeOptions.forEach(opt => opt.classList.remove('active'));

            // Add active class to clicked option
            this.classList.add('active');

            // Get selected type
            const radio = this.querySelector('input[type="radio"]');
            const requestType = radio.value;

            // Handle scheduled date requirement
            if (requestType === 'preventive') {
                scheduledDateInput.required = true;
                dateRequired.style.display = 'inline';
            } else {
                scheduledDateInput.required = false;
                dateRequired.style.display = 'none';
            }
        });
    });
}

// Auto-fill logic when equipment is selected
function initializeAutoFill() {
    const equipmentSelect = document.getElementById('equipment_id');
    const categoryInput = document.getElementById('equipment_category');
    const teamInput = document.getElementById('maintenance_team');
    const teamIdInput = document.getElementById('team_id');
    const assignedToSelect = document.getElementById('assigned_to');

    if (!equipmentSelect) return;

    equipmentSelect.addEventListener('change', function () {
        const equipmentId = this.value;

        if (!equipmentId) {
            // Clear fields if no equipment selected
            clearAutoFillFields();
            return;
        }

        // Get equipment data
        const data = equipmentData[equipmentId];

        if (!data) return;

        // Auto-fill category
        if (categoryInput && data.category) {
            categoryInput.value = data.category;
            animateAutoFill(categoryInput);
        }

        // Fetch and auto-fill team name
        if (data.teamId) {
            fetchTeamName(data.teamId, teamInput, teamIdInput);
        }

        // Auto-fill technician if available
        if (assignedToSelect && data.technicianId) {
            assignedToSelect.value = data.technicianId;
            animateAutoFill(assignedToSelect);
        }
    });
}

// Fetch team name from API
function fetchTeamName(teamId, teamInput, teamIdInput) {
    if (!teamId) return;

    fetch(`../php/api/get_team.php?id=${teamId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.team) {
                teamInput.value = data.team.team_name;
                teamIdInput.value = teamId;
                animateAutoFill(teamInput);
            }
        })
        .catch(error => {
            console.error('Error fetching team:', error);
        });
}

// Clear auto-fill fields
function clearAutoFillFields() {
    const categoryInput = document.getElementById('equipment_category');
    const teamInput = document.getElementById('maintenance_team');
    const teamIdInput = document.getElementById('team_id');
    const assignedToSelect = document.getElementById('assigned_to');

    if (categoryInput) categoryInput.value = '';
    if (teamInput) teamInput.value = '';
    if (teamIdInput) teamIdInput.value = '';
    if (assignedToSelect) assignedToSelect.value = '';
}

// Animate auto-filled field
function animateAutoFill(element) {
    element.classList.add('auto-filled');
    setTimeout(() => {
        element.classList.remove('auto-filled');
    }, 600);
}

// Form validation before submit
const requestForm = document.getElementById('requestForm');
if (requestForm) {
    requestForm.addEventListener('submit', function (e) {
        const subject = document.getElementById('subject').value;
        const equipmentId = document.getElementById('equipment_id').value;
        const requestType = document.querySelector('input[name="request_type"]:checked').value;
        const scheduledDate = document.getElementById('scheduled_date').value;

        // Validate subject length
        if (subject.length < 5) {
            e.preventDefault();
            alert('Subject must be at least 5 characters long');
            return false;
        }

        // Validate equipment selection
        if (!equipmentId) {
            e.preventDefault();
            alert('Please select an equipment');
            return false;
        }

        // Validate scheduled date for preventive maintenance
        if (requestType === 'preventive' && !scheduledDate) {
            e.preventDefault();
            alert('Scheduled date is required for preventive maintenance');
            return false;
        }

        // Validate date is in future
        if (scheduledDate) {
            const selectedDate = new Date(scheduledDate);
            const now = new Date();

            if (selectedDate < now) {
                e.preventDefault();
                alert('Scheduled date must be in the future');
                return false;
            }
        }
    });
}
