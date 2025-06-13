<?php
session_start();
$pageTitle = "Créer un nouvel utilisateur";
require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Créer un nouvel utilisateur</h1>
        <a href="/users" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div id="alertContainer"></div>

    <div class="card">
        <div class="card-body">
            <form id="userForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirmation <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="admin">Administrateur</option>
                            <option value="manager">Manager</option>
                            <option value="user">Utilisateur</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer l'utilisateur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE_URL = 'http://localhost:8000/backend/api';
    const USERS_API_URL = `${API_BASE_URL}/users.php`;
    
    const userForm = document.getElementById('userForm');
    const alertContainer = document.getElementById('alertContainer');
    
    // Fonctions utilitaires
    function showAlert(message, type) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Valider les données du formulaire
    function validateForm() {
        let isValid = true;
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;
        
        // Réinitialiser les erreurs
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        // Validation des champs requis
        const requiredFields = ['name', 'email', 'password', 'password_confirmation', 'role'];
        requiredFields.forEach(field => {
            const element = document.getElementById(field);
            if (!element.value.trim()) {
                element.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        // Validation de l'email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const email = document.getElementById('email').value;
        if (email && !emailRegex.test(email)) {
            document.getElementById('email').classList.add('is-invalid');
            isValid = false;
        }
        
        // Validation du mot de passe
        if (password || passwordConfirmation) {
            if (password !== passwordConfirmation) {
                document.getElementById('password').classList.add('is-invalid');
                document.getElementById('password_confirmation').classList.add('is-invalid');
                showAlert('Les mots de passe ne correspondent pas', 'danger');
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById('password').classList.add('is-invalid');
                showAlert('Le mot de passe doit contenir au moins 6 caractères', 'danger');
                isValid = false;
            }
        }
        
        return isValid;
    }

    // Gérer la soumission du formulaire
    async function handleSubmit(e) {
        e.preventDefault();
        
        // Validation du formulaire
        if (!validateForm()) {
            return;
        }
        
        const submitBtn = userForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création...';

            // Préparation des données
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value,
            };
            
            // Envoi à l'API
            const response = await fetch(USERS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                showAlert('Utilisateur créé avec succès!', 'success');
                userForm.reset();
                
                // Redirection optionnelle
                // setTimeout(() => window.location.href = `/users/edit?id=${data.data.id}`, 1500);
            } else {
                // Affichage détaillé des erreurs
                const errorMessage = data.message || 'Échec de la création de l\'utilisateur';
                const detailedError = data.error ? ` (${data.error})` : '';
                throw new Error(`${errorMessage}${detailedError}`);
            }
        } catch (error) {
            console.error('Erreur création utilisateur:', error);
            showAlert(`Erreur lors de la création: ${error.message}`, 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Créer l\'utilisateur';
        }
    }

    // Initialisation
    userForm.addEventListener('submit', handleSubmit);
    
    // Validation en temps réel
    document.getElementById('email').addEventListener('blur', validateForm);
    document.getElementById('password_confirmation').addEventListener('blur', function() {
        const password = document.getElementById('password').value;
        const passwordConfirmation = this.value;
        
        if (password && password !== passwordConfirmation) {
            this.classList.add('is-invalid');
            document.getElementById('password').classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            document.getElementById('password').classList.remove('is-invalid');
        }
    });
});
</script>

<style>
/* Styles identiques à edit.php */
.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.alert {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
    padding: 1rem;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
    transition: all 0.3s ease;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-primary:disabled {
    background-color: #a0aec0;
    border-color: #a0aec0;
    cursor: not-allowed;
}

.card {
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: none;
    margin-top: 1rem;
}

.card-body {
    padding: 1.5rem;
}

.spinner-border {
    vertical-align: middle;
    margin-right: 0.5rem;
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/footer.php'; ?>