// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password');
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
const passwordInput = document.getElementById('password');
if (passwordInput) {
    passwordInput.addEventListener('input', function () {
        checkPasswordStrength(this.value);
        validatePasswordRequirements(this.value);
    });
}

function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    if (!strengthDiv) return;

    let strength = 0;

    // Length checks
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;

    // Character variety checks
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    let className = '';
    let width = 0;

    if (strength <= 2) {
        className = 'strength-weak';
        width = 33;
    } else if (strength <= 4) {
        className = 'strength-medium';
        width = 66;
    } else {
        className = 'strength-strong';
        width = 100;
    }

    strengthDiv.innerHTML = `<div class="password-strength-bar ${className}" style="width: ${width}%"></div>`;
}

function validatePasswordRequirements(password) {
    const requirements = {
        'req-length': password.length >= 8,
        'req-upper': /[A-Z]/.test(password),
        'req-lower': /[a-z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-special': /[^a-zA-Z0-9]/.test(password)
    };

    for (let [id, isValid] of Object.entries(requirements)) {
        const element = document.getElementById(id);
        if (element) {
            if (isValid) {
                element.classList.add('valid');
                element.querySelector('i').classList.remove('fa-circle');
                element.querySelector('i').classList.add('fa-check-circle');
            } else {
                element.classList.remove('valid');
                element.querySelector('i').classList.remove('fa-check-circle');
                element.querySelector('i').classList.add('fa-circle');
            }
        }
    }
}

// Confirm password validation
const confirmPasswordInput = document.getElementById('confirm_password');
if (confirmPasswordInput && passwordInput) {
    confirmPasswordInput.addEventListener('input', function () {
        if (this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
}

// Form validation
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }

        // Check all password requirements
        const requirements = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^a-zA-Z0-9]/.test(password)
        };

        const allValid = Object.values(requirements).every(v => v === true);

        if (!allValid) {
            e.preventDefault();
            alert('Password does not meet all requirements!');
            return false;
        }
    });
}

// Auto-hide alerts after 8 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s, transform 0.3s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 8000);
    });
});
