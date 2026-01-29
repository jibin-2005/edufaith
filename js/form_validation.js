/**
 * Form Validation Library
 * Provides comprehensive client-side validation for all user forms
 */

const FormValidator = {
    // Validation rules
    rules: {
        fullname: {
            required: true,
            minLength: 2,
            maxLength: 100,
            pattern: /^[a-zA-Z\s\.\-']+$/,
            message: 'Name should only contain letters, spaces, dots, hyphens, and apostrophes'
        },
        email: {
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Please enter a valid email address'
        },
        password: {
            required: true,
            minLength: 6,
            maxLength: 50,
            message: 'Password must be at least 6 characters long'
        },
        phone: {
            required: false,
            pattern: /^[\d\s\-\+\(\)]{10,20}$/,
            message: 'Please enter a valid phone number (10-20 digits)'
        }
    },

    // Error message templates
    messages: {
        required: 'This field is required',
        minLength: 'Minimum {min} characters required',
        maxLength: 'Maximum {max} characters allowed',
        pattern: 'Invalid format'
    },

    /**
     * Show error message for a field
     */
    showError: function(field, message) {
        this.clearError(field);
        
        field.classList.add('is-invalid');
        field.style.borderColor = '#e74c3c';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.style.cssText = 'color: #e74c3c; font-size: 12px; margin-top: 5px; display: flex; align-items: center; gap: 5px;';
        errorDiv.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + message;
        
        field.parentNode.appendChild(errorDiv);
    },

    /**
     * Clear error message for a field
     */
    clearError: function(field) {
        field.classList.remove('is-invalid');
        field.style.borderColor = '';
        
        const existingError = field.parentNode.querySelector('.validation-error');
        if (existingError) {
            existingError.remove();
        }
    },

    /**
     * Show success state for a field
     */
    showSuccess: function(field) {
        this.clearError(field);
        field.classList.add('is-valid');
        field.style.borderColor = '#2ecc71';
    },

    /**
     * Validate a single field
     */
    validateField: function(field, rules) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        
        // Check required
        if (rules.required && !value) {
            this.showError(field, this.messages.required);
            return false;
        }
        
        // Skip other validations if field is empty and not required
        if (!value && !rules.required) {
            this.clearError(field);
            return true;
        }
        
        // Check min length
        if (rules.minLength && value.length < rules.minLength) {
            this.showError(field, this.messages.minLength.replace('{min}', rules.minLength));
            return false;
        }
        
        // Check max length
        if (rules.maxLength && value.length > rules.maxLength) {
            this.showError(field, this.messages.maxLength.replace('{max}', rules.maxLength));
            return false;
        }
        
        // Check pattern
        if (rules.pattern && !rules.pattern.test(value)) {
            this.showError(field, rules.message || this.messages.pattern);
            return false;
        }
        
        this.showSuccess(field);
        return true;
    },

    /**
     * Validate full name
     */
    validateFullName: function(field) {
        return this.validateField(field, this.rules.fullname);
    },

    /**
     * Validate email
     */
    validateEmail: function(field) {
        return this.validateField(field, this.rules.email);
    },

    /**
     * Validate password
     */
    validatePassword: function(field, isRequired = true) {
        const rules = {...this.rules.password, required: isRequired};
        return this.validateField(field, rules);
    },

    /**
     * Validate phone number
     */
    validatePhone: function(field, isRequired = false) {
        const rules = {...this.rules.phone, required: isRequired};
        return this.validateField(field, rules);
    },

    /**
     * Validate select dropdown (required)
     */
    validateSelect: function(field, message = 'Please select an option') {
        const value = field.value;
        if (!value || value === '') {
            this.showError(field, message);
            return false;
        }
        this.showSuccess(field);
        return true;
    },

    /**
     * Validate entire form
     */
    validateForm: function(form) {
        let isValid = true;
        
        // Find all inputs with validation attributes
        const fullnameField = form.querySelector('[name="fullname"], #fullname');
        const emailField = form.querySelector('[name="email"], #email');
        const passwordField = form.querySelector('[name="password"], #password');
        const roleField = form.querySelector('[name="role"], #role');
        const classField = form.querySelector('[name="class_id"], #class_id');
        
        // Validate each field if it exists
        if (fullnameField) {
            if (!this.validateFullName(fullnameField)) isValid = false;
        }
        
        if (emailField) {
            if (!this.validateEmail(emailField)) isValid = false;
        }
        
        if (passwordField && passwordField.closest('.form-group').style.display !== 'none') {
            // Only validate password if it's visible and has a value or is required
            const isRequired = passwordField.hasAttribute('required');
            if (!this.validatePassword(passwordField, isRequired)) isValid = false;
        }
        
        if (roleField && roleField.hasAttribute('required')) {
            if (!this.validateSelect(roleField, 'Please select a role')) isValid = false;
        }
        
        // Only validate class if it's visible and required (for students)
        if (classField) {
            const classGroup = classField.closest('.form-group, #class-group');
            const isVisible = classGroup && classGroup.style.display !== 'none';
            const isRequired = classField.hasAttribute('required');
            
            if (isVisible && isRequired) {
                if (!this.validateSelect(classField, 'Please select a class for the student')) isValid = false;
            }
        }
        
        return isValid;
    },

    /**
     * Initialize real-time validation on a form
     */
    initRealTimeValidation: function(form) {
        const self = this;
        
        // Full name field
        const fullnameField = form.querySelector('[name="fullname"], #fullname');
        if (fullnameField) {
            fullnameField.addEventListener('blur', function() {
                self.validateFullName(this);
            });
            fullnameField.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    self.validateFullName(this);
                }
            });
        }
        
        // Email field
        const emailField = form.querySelector('[name="email"], #email');
        if (emailField) {
            emailField.addEventListener('blur', function() {
                self.validateEmail(this);
            });
            emailField.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    self.validateEmail(this);
                }
            });
        }
        
        // Password field
        const passwordField = form.querySelector('[name="password"], #password');
        if (passwordField) {
            passwordField.addEventListener('blur', function() {
                self.validatePassword(this, this.hasAttribute('required'));
            });
            passwordField.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    self.validatePassword(this, this.hasAttribute('required'));
                }
            });
        }
        
        // Role field
        const roleField = form.querySelector('[name="role"], #role');
        if (roleField) {
            roleField.addEventListener('change', function() {
                if (this.hasAttribute('required')) {
                    self.validateSelect(this, 'Please select a role');
                }
            });
        }
        
        // Class field
        const classField = form.querySelector('[name="class_id"], #class_id');
        if (classField) {
            classField.addEventListener('change', function() {
                const classGroup = this.closest('.form-group, #class-group');
                const isVisible = classGroup && classGroup.style.display !== 'none';
                if (isVisible && this.hasAttribute('required')) {
                    self.validateSelect(this, 'Please select a class');
                }
            });
        }
        
        // Form submit validation
        form.addEventListener('submit', function(e) {
            if (!self.validateForm(this)) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                return false;
            }
        });
    },

    /**
     * Add validation styles to the page
     */
    addStyles: function() {
        if (document.getElementById('validation-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'validation-styles';
        styles.textContent = `
            .is-invalid {
                border-color: #e74c3c !important;
                background-color: #fff5f5 !important;
            }
            .is-valid {
                border-color: #2ecc71 !important;
            }
            .validation-error {
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .form-group input:focus,
            .form-group select:focus {
                box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            }
            .form-group input.is-invalid:focus {
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
            }
            .form-group input.is-valid:focus {
                box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
            }
        `;
        document.head.appendChild(styles);
    },

    /**
     * Initialize validation on page load
     */
    init: function() {
        this.addStyles();
        
        // Auto-initialize on forms with data-validate attribute or common form IDs
        const forms = document.querySelectorAll('form[data-validate], #addForm, form[method="POST"]');
        const self = this;
        
        forms.forEach(function(form) {
            self.initRealTimeValidation(form);
        });
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    FormValidator.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}
