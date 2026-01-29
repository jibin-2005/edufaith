/**
 * validator.js
 * Frontend validation helpers for Sunday School Management System.
 */

const FormValidator = {
    /**
     * Show error message for a specific field.
     */
    showError: function(inputElement, message) {
        // Remove existing error message
        this.hideError(inputElement);
        
        inputElement.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '4px';
        errorDiv.innerText = message;
        inputElement.parentNode.appendChild(errorDiv);
        inputElement.style.borderColor = '#e74c3c';
    },

    /**
     * Hide error message for a specific field.
     */
    hideError: function(inputElement) {
        inputElement.classList.remove('is-invalid');
        inputElement.style.borderColor = '#ddd';
        const parent = inputElement.parentNode;
        const existingError = parent.querySelector('.error-message');
        if (existingError) {
            parent.removeChild(existingError);
        }
    },

    /**
     * Validate Text field.
     */
    validateText: function(value, fieldName, min = 3, max = 255) {
        const val = value.trim();
        if (val === "") return `${fieldName} cannot be empty.`;
        if (val.length < min) return `${fieldName} must be at least ${min} characters.`;
        if (val.length > max) return `${fieldName} cannot exceed ${max} characters.`;
        if (!/[a-zA-Z]/.test(val)) return `${fieldName} must contain at least one letter.`;
        return true;
    },

    /**
     * Validate Description field.
     */
    validateDescription: function(value, fieldName, min = 10) {
        const val = value.trim();
        if (val === "") return `${fieldName} cannot be empty.`;
        if (val.length < min) return `${fieldName} must be at least ${min} characters.`;
        if (!/[a-zA-Z]/.test(val)) return `${fieldName} must contain meaningful text.`;
        return true;
    },

    /**
     * Validate Numeric field.
     */
    validateNumeric: function(value, fieldName, min = 0, max = null) {
        if (value === "") return `${fieldName} cannot be empty.`;
        const num = Number(value);
        if (isNaN(num)) return `${fieldName} must be a number.`;
        if (num < 0) return `${fieldName} cannot be negative.`;
        if (min !== null && num < min) return `${fieldName} must be at least ${min}.`;
        if (max !== null && num > max) return `${fieldName} cannot exceed ${max}.`;
        return true;
    },

    /**
     * Validate Email.
     */
    validateEmail: function(email) {
        if (email.trim() === "") return "Email cannot be empty.";
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(email.toLowerCase())) return "Invalid email format.";
        return true;
    },

    /**
     * Validate Phone.
     */
    validatePhone: function(phone) {
        if (phone.trim() === "") return "Phone number cannot be empty.";
        if (!/^[0-9]{10}$/.test(phone)) return "Phone number must be exactly 10 digits.";
        return true;
    },

    /**
     * Initialize validation on a form.
     * @param {string} formSelector CSS selector for the form.
     * @param {object} rules Mapping of field names to validation functions.
     */
    init: function(formSelector, rules) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            let hasErrors = false;
            
            for (const [fieldName, validateFn] of Object.entries(rules)) {
                const input = form.querySelector(`[name="${fieldName}"]`);
                if (!input) continue;

                const result = validateFn(input.value);
                if (result !== true) {
                    this.showError(input, result);
                    hasErrors = true;
                } else {
                    this.hideError(input);
                }
            }

            if (hasErrors) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
};
