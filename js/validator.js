/**
 * validator.js
 * Frontend validation helpers for Sunday School Management System.
 */

const FormValidator = {
    /**
     * Show error message for a specific field.
     */
    showError: function (inputElement, message) {
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
    hideError: function (inputElement) {
        inputElement.classList.remove('is-invalid');
        inputElement.style.borderColor = '#ddd';
        const parent = inputElement.parentNode;
        const existingError = parent.querySelector('.error-message');
        if (existingError) {
            parent.removeChild(existingError);
        }
    },

    /**
     * Validate Text field (Generic).
     */
    validateText: function (value, fieldName, min = 3, max = 255) {
        const val = value.trim();
        if (val === "") return `${fieldName} cannot be empty.`;
        if (val.length < min) return `${fieldName} must be at least ${min} characters.`;
        if (val.length > max) return `${fieldName} cannot exceed ${max} characters.`;
        if (!/[a-zA-Z]/.test(val)) return `${fieldName} must contain at least one letter.`;
        return true;
    },

    /**
    * Validate Title / Heading.
    * Rule: No numbers-only, no special-chars-only, at least one alphabet mandatory.
    */
    validateTitle: function (value, fieldName) {
        const val = value.trim();
        if (val === "") return `${fieldName} cannot be empty.`;

        // Check for at least one alphabet
        if (!/[a-zA-Z]/.test(val)) {
            return `${fieldName} must contain at least one alphabet.`;
        }

        // Check if it's only numbers (though regex above covers this, explicit check for clarity if needed)
        if (/^\d+$/.test(val)) {
            return `${fieldName} cannot correspond to only numbers.`;
        }

        // Check if it's only special chars
        if (/^[^a-zA-Z0-9]+$/.test(val)) {
            return `${fieldName} cannot contain only special characters.`;
        }

        return true;
    },

    /**
     * Validate Description field.
     * Rule: At least one alphabet, not empty.
     */
    validateDescription: function (value, fieldName, min = 10) {
        const val = value.trim();
        if (val === "") return `${fieldName} cannot be empty.`;
        if (val.length < min) return `${fieldName} must be at least ${min} characters.`;
        if (!/[a-zA-Z]/.test(val)) return `${fieldName} must contain at least one alphabet.`;
        return true;
    },

    /**
     * Validate Numeric field.
     */
    validateNumeric: function (value, fieldName, min = 0, max = null) {
        if (value === "") return `${fieldName} cannot be empty.`;
        const num = Number(value);
        if (isNaN(num)) return `${fieldName} must be a number.`;
        if (num < 0) return `${fieldName} cannot be negative.`;
        if (min !== null && num < min) return `${fieldName} must be at least ${min}.`;
        if (max !== null && num > max) return `${fieldName} cannot exceed ${max}.`;

        // Strict check: if input contains non-numeric chars (that aren't part of valid number format)
        if (/[^0-9.]/.test(value)) return `${fieldName} cannot contain alphabets or special characters.`;

        return true;
    },

    /**
     * Validate Email (Strict).
     */
    validateEmail: function (email) {
        const val = email.trim();
        if (val === "") return "Email cannot be empty.";
        // Strict Regex: name@domain.tld
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!re.test(val)) return "Invalid email format (e.g., name@example.com).";

        // Extra checks
        if (/^\d+$/.test(val)) return "Email cannot contain only numbers.";
        if (val.indexOf(' ') >= 0) return "Email cannot contain spaces.";

        return true;
    },

    /**
     * Check Email Availability via AJAX.
     * Returns a Promise.
     */
    checkEmailAvailability: async function (email) {
        try {
            const formData = new FormData();
            formData.append('email', email);
            const response = await fetch('../includes/check_email.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            return result; // { available: true/false, message: "..." }
        } catch (error) {
            console.error("Email check failed", error);
            return { available: false, message: "Error checking email availability." };
        }
    },

    /**
     * Validate Phone.
     */
    validatePhone: function (phone) {
        if (phone.trim() === "") return "Phone number cannot be empty.";
        if (!/^[0-9]{10}$/.test(phone)) return "Phone number must be exactly 10 digits.";
        return true;
    },

    /**
     * Validate Date.
     * @param {string} dateVal - YYYY-MM-DD
     * @param {string} dateType - 'past', 'future', 'any'
     */
    validateDate: function (dateVal, fieldName, dateType = 'any') {
        if (!dateVal) return `${fieldName} cannot be empty.`;

        const inputDate = new Date(dateVal);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Reset input date time to midnight for comparison
        inputDate.setHours(0, 0, 0, 0);

        if (dateType === 'future_only') { // Today or Future
            if (inputDate < today) return `${fieldName} cannot be in the past.`;
        } else if (dateType === 'past_only') { // Past or Today
            if (inputDate > today) return `${fieldName} cannot be in the future.`;
        }

        return true;
    },

    /**
     * Initialize validation on a form.
     * @param {string} formSelector CSS selector for the form.
     * @param {object} rules Mapping of field names to validation functions.
     * @param {boolean} liveValidation Enable/Disable live validation.
     */
    init: function (formSelector, rules, liveValidation = true) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        // Attach live listeners
        if (liveValidation) {
            for (const [fieldName, validateFn] of Object.entries(rules)) {
                const input = form.querySelector(`[name="${fieldName}"]`);
                if (!input) continue;

                // For text inputs, validate on input/blur
                input.addEventListener('input', async () => {
                    // For email, we might want to debounce or wait, but here we just call validation
                    // If it's the email field and logic includes async check, handle it separate?
                    // Simplified: Just run the sync validation here
                    const result = validateFn(input.value);
                    if (result !== true) {
                        this.showError(input, result);
                    } else {
                        this.hideError(input);
                    }
                });
            }
        }

        form.addEventListener('submit', async (e) => {
            // e.preventDefault(); // Controlled by caller usually, but here we preserve default unless error

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
                e.stopImmediatePropagation(); // Stop other listeners
                // Scroll to first error
                const firstError = form.querySelector('.error-message');
                if (firstError) {
                    firstError.parentNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.parentNode.querySelector('input, select, textarea').focus();
                }
            }
        });
    }
};
