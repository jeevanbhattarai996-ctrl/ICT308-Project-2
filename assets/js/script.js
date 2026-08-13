document.addEventListener('DOMContentLoaded', function() {
    validateForms();
    initDatePicker();
});

function validateForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                const value = input.value.trim();
                
                if (input.type === 'email') {
                    if (!isValidEmail(value)) {
                        showFieldError(input, 'Please enter a valid email');
                        isValid = false;
                    } else {
                        clearFieldError(input);
                    }
                } else if (value === '') {
                    showFieldError(input, 'This field is required');
                    isValid = false;
                } else {
                    clearFieldError(input);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(field, message) {
    const parent = field.parentElement;
    
    let errorElement = parent.querySelector('.error-text');
    if (!errorElement) {
        errorElement = document.createElement('small');
        errorElement.className = 'error-text';
        parent.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    field.style.borderColor = '#e74c3c';
}

function clearFieldError(field) {
    const parent = field.parentElement;
    const errorElement = parent.querySelector('.error-text');
    
    if (errorElement) {
        errorElement.remove();
    }
    
    field.style.borderColor = '';
}

function initDatePicker() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        const minDate = input.getAttribute('min');
        if (minDate) {
            input.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const minDateObj = new Date(minDate);
                
                if (selectedDate < minDateObj) {
                    showFieldError(this, 'Date must be in the future');
                } else {
                    clearFieldError(this);
                }
            });
        }
    });
}

function confirmAction(message) {
    return confirm(message);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    
    return `${displayHour}:${minutes} ${ampm}`;
}
