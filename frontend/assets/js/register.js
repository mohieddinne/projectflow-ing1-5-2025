// frontend/assets/js/register.js

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const togglePassword = document.querySelector('.btn-toggle-password');
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');

    // Configuration de la force du mot de passe
    const passwordStrengthConfig = {
        minLength: 8,
        minScore: 3,
        patterns: {
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            numbers: /[0-9]/,
            special: /[^A-Za-z0-9]/
        }
    };

    // Initialisation des gestionnaires d'événements
    initializeEventListeners();

    function initializeEventListeners() {
        // Toggle password visibility
        togglePassword?.addEventListener('click', () => {
            Auth.togglePasswordVisibility(passwordInput, togglePassword);
        });

        // Password strength check
        passwordInput?.addEventListener('input', () => {
            const strength = checkPasswordStrength(passwordInput.value);
            updatePasswordStrength(strength);
        });

        // Form submission
        form?.addEventListener('submit', handleSubmit);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription en cours...';

        try {
            const response = await Auth.handleFormSubmit(form, (data) => {
                Auth.showSuccess(form, 'Inscription réussie ! Redirection...');
                setTimeout(() => {
                    window.location.href = data.redirect || '/login';
                }, 2000);
            });
        } catch (error) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> S\'inscrire';
        }
    }

    function validateForm() {
        clearErrors();
        let isValid = true;

        // Validate name
        const name = document.getElementById('fullName').value.trim();
        if (name.length < 3) {
            showError('nameError', 'Le nom doit contenir au moins 3 caractères');
            isValid = false;
        }

        // Validate email
        const email = document.getElementById('email').value.trim();
        if (!isValidEmail(email)) {
            showError('emailError', 'Veuillez entrer une adresse email valide');
            isValid = false;
        }

        // Validate password
        const password = passwordInput.value;
        if (checkPasswordStrength(password) < passwordStrengthConfig.minScore) {
            showError('passwordError', 'Le mot de passe n\'est pas assez fort');
            isValid = false;
        }

        // Validate password confirmation
        if (password !== confirmPasswordInput.value) {
            showError('confirmPasswordError', 'Les mots de passe ne correspondent pas');
            isValid = false;
        }

        // Validate terms
        if (!document.getElementById('terms').checked) {
            showError('termsError', 'Vous devez accepter les conditions d\'utilisation');
            isValid = false;
        }

        return isValid;
    }

    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= passwordStrengthConfig.minLength) strength++;
        if (passwordStrengthConfig.patterns.uppercase.test(password)) strength++;
        if (passwordStrengthConfig.patterns.lowercase.test(password)) strength++;
        if (passwordStrengthConfig.patterns.numbers.test(password)) strength++;
        if (passwordStrengthConfig.patterns.special.test(password)) strength++;
        
        return strength;
    }

    function updatePasswordStrength(strength) {
        const percentage = (strength / 5) * 100;
        strengthBar.style.width = `${percentage}%`;
        
        const strengthLevels = {
            0: { text: 'Très faible', color: '#ff4444' },
            1: { text: 'Faible', color: '#FF8800' },
            2: { text: 'Moyen', color: '#ffbb33' },
            3: { text: 'Bon', color: '#00C851' },
            4: { text: 'Fort', color: '#007E33' },
            5: { text: 'Excellent', color: '#00695C' }
        };
        
        const level = strengthLevels[strength];
        strengthBar.style.backgroundColor = level.color;
        strengthText.textContent = level.text;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function clearErrors() {
        const errorElements = document.querySelectorAll('.form-error');
        errorElements.forEach(element => {
            element.textContent = '';
            element.style.display = 'none';
        });
    }
});
