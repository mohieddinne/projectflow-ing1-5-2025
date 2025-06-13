<?php
// frontend/pages/projects/create.php

if (!function_exists('requirePermission')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
requirePermission('create_project');

$pageTitle = "Créer un projet";

// Récupération des données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Créer un nouveau projet</h1>
        <a href="/projects" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="alert alert-danger d-none" id="globalErrorAlert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <span id="globalErrorMessage"></span>
    </div>

    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($formErrors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form id="projectForm" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="title" class="form-label">Titre du projet <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" placeholder="Nom du projet" required
                       value="<?= htmlspecialchars($formData['title'] ?? '') ?>">
                <div class="invalid-feedback">Veuillez saisir un titre pour le projet.</div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                <select class="form-select" id="status" required>
                    <option value="">Sélectionnez un statut</option>
                    <option value="planning" <?= ($formData['status'] ?? '') === 'planning' ? 'selected' : '' ?>>Planification</option>
                    <option value="in_progress" <?= ($formData['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                    <option value="completed" <?= ($formData['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Terminé</option>
                    <option value="on_hold" <?= ($formData['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>En attente</option>
                </select>
                <div class="invalid-feedback">Veuillez sélectionner un statut.</div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" rows="3" placeholder="Décrivez le projet..." required><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            <div class="invalid-feedback">Veuillez saisir une description.</div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="start_date" required
                       value="<?= htmlspecialchars($formData['start_date'] ?? '') ?>">
                <div class="invalid-feedback">Veuillez sélectionner une date de début.</div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="end_date" required
                       value="<?= htmlspecialchars($formData['end_date'] ?? '') ?>">
                <div class="invalid-feedback">Veuillez sélectionner une date de fin.</div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="progress" class="form-label">Progression</label>
            <div class="d-flex align-items-center gap-3">
                <input type="range" class="form-range flex-grow-1" id="progress" min="0" max="100" step="5" 
                       value="<?= htmlspecialchars($formData['progress_percentage'] ?? 0) ?>">
                <span class="progress-value fw-bold"><?= htmlspecialchars($formData['progress_percentage'] ?? 0) ?>%</span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="manager_id" class="form-label">Manager responsable <span class="text-danger">*</span></label>
                <select class="form-select" id="manager_id" required>
                    <option value="">Chargement des managers...</option>
                </select>
                <div class="invalid-feedback">Veuillez sélectionner un manager.</div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="members" class="form-label">Membres de l'équipe</label>
                <select class="form-select" id="members" multiple style="height: 150px;">
                    <option value="">Chargement des membres...</option>
                </select>
                <div class="alert alert-warning mt-2 d-none" id="membersError">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="membersErrorMessage"></span>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save me-2"></i>Créer le projet
            </button>
        </div>
        
        <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 0 ?>">
    </form>

    Panneau de débogage
    <div class="card mt-5">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-bug me-2"></i>
                <button class="btn btn-sm btn-outline-primary float-end" id="toggleDebug">
                </button>
            </h5>
        </div>
        <div class="card-body d-none" id="debugPanel">
            <div class="mb-3">
                <label class="form-label">Dernière requête API :</label>
                <pre id="lastRequest" class="bg-dark text-white p-3 rounded"></pre>
            </div>
            <div>
                <label class="form-label">Dernière réponse API :</label>
                <pre id="lastResponse" class="bg-dark text-white p-3 rounded"></pre>
            </div>
            <div>
                <label class="form-label">Journal des erreurs :</label>
                <pre id="errorLog" class="bg-dark text-white p-3 rounded"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Configuration API
    const API_BASE_URL = 'http://localhost:8000/backend/api';
    const PROJECTS_API_URL = `${API_BASE_URL}/projects.php`;
    const USERS_API_URL = `${API_BASE_URL}/users.php`;
    
    // Éléments UI
    // const debugPanel = document.getElementById('debugPanel');
    // const toggleDebug = document.getElementById('toggleDebug');
    const lastRequest = document.getElementById('lastRequest');
    const lastResponse = document.getElementById('lastResponse');
    const errorLog = document.getElementById('errorLog');
    const globalErrorAlert = document.getElementById('globalErrorAlert');
    const globalErrorMessage = document.getElementById('globalErrorMessage');
    const membersError = document.getElementById('membersError');
    const membersErrorMessage = document.getElementById('membersErrorMessage');
    
    // Journal des erreurs
    const errorLogs = [];
    
    // Fonctions utilitaires
    function logError(message, details = {}) {
        const timestamp = new Date().toISOString();
        const entry = {timestamp, message, details};
        errorLogs.push(entry);
        updateErrorLog();
        
        console.error(`${timestamp}: ${message}`, details);
    }
    
    function updateErrorLog() {
        const logContent = errorLogs.map(entry => 
            `[${entry.timestamp}] ${entry.message}\n${JSON.stringify(entry.details, null, 2)}`
        ).join('\n\n');
        errorLog.textContent = logContent;
    }
    
    function showGlobalError(message) {
        globalErrorMessage.textContent = message;
        globalErrorAlert.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function hideGlobalError() {
        globalErrorAlert.classList.add('d-none');
    }
    
    function showMembersError(message) {
        membersErrorMessage.textContent = message;
        membersError.classList.remove('d-none');
    }
    
    function hideMembersError() {
        membersError.classList.add('d-none');
    }
    
    function showDebugInfo(request, response) {
        lastRequest.textContent = JSON.stringify(request, null, 2);
        lastResponse.textContent = JSON.stringify(response, null, 2);
    }
    
    // Toggle debug panel
    toggleDebug.addEventListener('click', () => {
        const isVisible = !debugPanel.classList.contains('d-none');
        toggleDebug.textContent = 'Masquer';
    });
    
    // Gestion de la progression
    const progressSlider = document.getElementById('progress');
    const progressValue = document.querySelector('.progress-value');
    
    progressSlider.addEventListener('input', function() {
        progressValue.textContent = `${this.value}%`;
    });
    
    // Chargement des managers
    async function loadManagers() {
        const select = document.getElementById('manager_id');
        try {
            select.disabled = true;
            select.innerHTML = '<option value="">Chargement en cours...</option>';
            
            const response = await fetch(`${USERS_API_URL}?role=manager`);
            const responseData = await response.json();
            
            // Journalisation pour débogage
            logError('Réponse API pour les managers', {
                url: `${USERS_API_URL}?role=manager`,
                status: response.status,
                data: responseData
            });
        
            select.innerHTML = '<option value="">Sélectionnez un manager</option>';
            responseData.data.forEach(user => {
                const selected = user.id == <?= json_encode($formData['manager_id'] ?? 'null') ?>;
                const option = new Option(`${user.name} (${user.email})`, user.id);
                option.selected = selected;
                select.add(option);
            });
            
            logError('Chargement des managers réussi', {
                count: responseData.data.length
            });
        } catch (error) {
            logError('Erreur chargement managers', {
                error: error.message
            });
            
            showGlobalError('Erreur lors du chargement des managers');
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        } finally {
            select.disabled = false;
        }
    }
    
    // Chargement des membres d'équipe - CORRECTION DE L'ERREUR
    async function loadTeamMembers() {
        const select = document.getElementById('members');
        try {
            select.disabled = true;
            select.innerHTML = '<option value="">Chargement en cours...</option>';
            hideMembersError();
            
            // 1. Vérifier l'URL de l'API
            const url = `${USERS_API_URL}?active=1`;
            logError('Chargement des membres - URL', {url});
            
            const response = await fetch(url);
            const responseData = await response.json();
            
            // 2. Journaliser la réponse complète
            logError('Réponse API pour les membres', {
                url,
                status: response.status,
                data: responseData
            });
            
            
            select.innerHTML = '';
            responseData.data.forEach(user => {
                const option = new Option(`${user.name} (${user.email})`, user.id);
                select.add(option);
            });
            
            // 4. Pré-sélection si données existantes
            <?php if (!empty($formData['members'])): ?>
            const members = <?= json_encode($formData['members']) ?>;
            members.forEach(memberId => {
                const option = Array.from(select.options).find(opt => opt.value == memberId);
                if (option) option.selected = true;
            });
            <?php endif; ?>
            
            logError('Chargement des membres réussi', {
                count: responseData.data.length
            });
        } catch (error) {
            logError('Erreur chargement membres', {
                error: error.message,
                stack: error.stack
            });
            
            // Afficher l'erreur spécifique pour les membres
            showMembersError(`Erreur lors du chargement des membres: ${error.message}`);
            
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        } finally {
            select.disabled = false;
        }
    }
    
    // Validation des dates
    function setupDateValidation() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const today = new Date().toISOString().split('T')[0];

        startDate.min = today;
        endDate.min = today;

        function validateDates() {
            if (startDate.value && endDate.value) {
                if (new Date(endDate.value) < new Date(startDate.value)) {
                    endDate.setCustomValidity('La date de fin doit être postérieure au début');
                    endDate.classList.add('is-invalid');
                } else {
                    endDate.setCustomValidity('');
                    endDate.classList.remove('is-invalid');
                }
            }
        }

        startDate.addEventListener('change', function() {
            endDate.min = startDate.value;
            validateDates();
        });

        endDate.addEventListener('change', validateDates);
    }
    
    // Validation du formulaire
    function setupFormValidation() {
        const form = document.getElementById('projectForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideGlobalError();
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            try {
                submitBtn.disabled = true;
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création en cours...';
                
                // Préparation des données
                const formData = {
                    title: document.getElementById('title').value.trim(),
                    description: document.getElementById('description').value.trim(),
                    status: document.getElementById('status').value,
                    start_date: document.getElementById('start_date').value,
                    end_date: document.getElementById('end_date').value,
                    manager_id: parseInt(document.getElementById('manager_id').value),
                    progress_percentage: parseFloat(progressSlider.value) || 0,
                    created_by: <?= $_SESSION['user_id'] ?? 1 ?>
                };
                
                // Envoi à l'API
                const response = await fetch(PROJECTS_API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                // Debug: Enregistrer la réponse
                showDebugInfo({
                    url: PROJECTS_API_URL,
                    method: 'POST',
                    body: formData
                }, {
                    status: response.status,
                    data: result
                });
                
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Erreur lors de la création du projet');
                }
                
                // Succès - redirection
                window.location.href = '/projects';
                
            } catch (error) {
                logError('Erreur création projet', {
                    error: error.message,
                    stack: error.stack
                });
                
                showGlobalError(`Échec de la création : ${error.message}`);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
    
    // Initialisation
    try {
        // Chargement parallèle avec gestion d'erreur indépendante
        await Promise.allSettled([
            loadManagers(),
            loadTeamMembers()
        ]);
        
        setupDateValidation();
        setupFormValidation();
        
    } catch (error) {
        logError('Erreur initialisation', {
            error: error.message,
            stack: error.stack
        });
        
        showGlobalError('Erreur lors du chargement initial: ' + error.message);
    }
});
</script>

<style>
.progress-value {
    min-width: 40px;
    text-align: center;
}

#membersError {
    font-size: 0.9rem;
    padding: 0.5rem;
}

#debugPanel pre {
    max-height: 300px;
    overflow-y: auto;
}

.loading-indicator {
    display: inline-block;
    margin-left: 10px;
}
</style>

<?php
include_once __DIR__ . '/../../includes/footer.php';