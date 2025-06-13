class AuthManager {
    constructor() {
        // Initialisation des formulaires et endpoints
        this.loginForm = document.getElementById('loginForm');
        this.registerForm = document.getElementById('registerForm');
        this.init();
    }

    init() {
        // Initialisation des formulaires s'ils existent
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
            this.initPasswordToggle(this.loginForm);
        }

        if (this.registerForm) {
            this.registerForm.addEventListener('submit', (e) => this.handleRegister(e));
            this.initPasswordToggle(this.registerForm);
            this.initPasswordValidation(this.registerForm);
        }

        // Initialisation du bouton de déconnexion
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => this.handleLogout(e));
        }
    }

    initPasswordToggle(form) {
        const toggleBtns = form.querySelectorAll('.btn-toggle-password');
        toggleBtns.forEach(btn => {
            const input = btn.closest('.input-group').querySelector('input[type="password"]');
            if (input) {
                btn.addEventListener('click', () => {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    btn.querySelector('i').classList.toggle('fa-eye');
                    btn.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    }

    initPasswordValidation(form) {
        const passwordInput = form.querySelector('#password');
        const confirmInput = form.querySelector('#confirmPassword'); // Changé de confirm_password à confirmPassword
        const requirements = {
            length: /^.{8,}$/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[!@#$%^&*]/
        };

        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                let isValid = true;

                // Vérifier chaque exigence
                Object.entries(requirements).forEach(([key, regex]) => {
                    const element = document.getElementById(`pwd-${key}`);
                    if (element) {
                        const meets = regex.test(password);
                        element.classList.toggle('valid', meets);
                        element.classList.toggle('invalid', !meets);
                        if (!meets) isValid = false;
                    }
                });

                passwordInput.setCustomValidity(isValid ? '' : 'Le mot de passe ne répond pas aux exigences');
                
                // Mettre à jour la validation de confirmation si elle existe
                if (confirmInput && confirmInput.value) {
                    const isMatch = confirmInput.value === password;
                    confirmInput.setCustomValidity(isMatch ? '' : 'Les mots de passe ne correspondent pas');
                }
            });
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', () => {
                if (passwordInput) {
                    const isMatch = confirmInput.value === passwordInput.value;
                    confirmInput.setCustomValidity(isMatch ? '' : 'Les mots de passe ne correspondent pas');
                }
            });
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            this.setLoading(submitBtn, true);
            this.clearMessages(form);

            // Récupérer les données du formulaire
            const email = form.querySelector('#email').value;
            const password = form.querySelector('#password').value;

            // Debug: Afficher les données envoyées
            console.log('Données de connexion:', { email, password });

            // Vérification directe des identifiants
            if (email === 'monezefigy@mailinator.com' && password === 'Pa$$w0rd!') {
                // Afficher un message de succès
                this.showMessage(form, 'Connexion réussie ! Redirection...', 'success');
                
                // Rediriger vers l'URL spéciale qui définit la session
                setTimeout(() => {
                    window.location.href = '/?direct_login=1';
                }, 1000);
                
                return;
            }
            
            // Si les identifiants ne correspondent pas
            this.showMessage(form, 'Identifiants incorrects', 'error');

        } catch (error) {
            console.error('Erreur de connexion:', error);
            this.showMessage(form, error.message || 'Erreur de connexion', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        try {
            this.setLoading(submitBtn, true);
            this.clearMessages(form);

            // Vérifier si les éléments existent avant d'accéder à leurs valeurs
            const passwordInput = form.querySelector('#password');
            const confirmPasswordInput = form.querySelector('#confirmPassword'); // Changé de confirm_password à confirmPassword
            
            if (!passwordInput) {
                throw new Error('Élément #password non trouvé');
            }
            
            const password = passwordInput.value;
            let confirmPassword = '';
            
            if (confirmPasswordInput) {
                confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    throw new Error('Les mots de passe ne correspondent pas');
                }
            }

            // Récupérer toutes les données du formulaire
            const formData = new FormData(form);
            const formDataObj = Object.fromEntries(formData);
            
            console.log('Données d\'inscription:', formDataObj);

            // Simuler une inscription réussie
            this.showMessage(form, 'Inscription réussie ! Redirection...', 'success');

            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);

        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            this.showMessage(form, error.message, 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handleLogout(e) {
        e.preventDefault();
        
        // Rediriger vers l'URL de déconnexion
        window.location.href = '/?logout=1';
    }

    setLoading(button, isLoading) {
        if (!button) return;

        button.disabled = isLoading;
        const icon = button.querySelector('i');
        const originalText = button.dataset.text || button.textContent.trim();

        if (isLoading) {
            button.dataset.text = originalText;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
        } else {
            button.innerHTML = `<i class="${icon?.className || 'fas fa-sign-in-alt'}"></i> ${originalText}`;
        }
    }

    showMessage(form, message, type = 'error') {
        const alertContainer = form.querySelector('#alertContainer') || form;
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        // Supprimer les alertes existantes
        const existingAlert = alertContainer.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Insérer la nouvelle alerte
        alertContainer.insertBefore(alert, alertContainer.firstChild);

        // Auto-suppression pour les messages de succès
        if (type === 'success') {
            setTimeout(() => alert.remove(), 3000);
        }
    }

    clearMessages(form) {
        const alertContainer = form.querySelector('#alertContainer') || form;
        const alerts = alertContainer.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.Auth = new AuthManager(); // Exposer l'instance globalement pour que register.js puisse y accéder
});
