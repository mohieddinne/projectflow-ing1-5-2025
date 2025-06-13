<?php
// frontend/pages/tasks/create.php

if (!function_exists('requirePermission')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
requirePermission('create_task');

$pageTitle = "Créer une tâche";

// Récupération des données précédentes en session
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Créer une nouvelle tâche</h1>
        <a href="/tasks" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
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

    <form id="taskForm" class="needs-validation" novalidate>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informations de base</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               required 
                               minlength="3" 
                               maxlength="100"
                               value="<?= htmlspecialchars($formData['title'] ?? '') ?>">
                        <div class="invalid-feedback">Le titre doit contenir entre 3 et 100 caractères</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="project_id" class="form-label">Projet <span class="text-danger">*</span></label>
                        <select id="project_id" 
                                name="project_id" 
                                class="form-control" 
                                required>
                            <option value="">Chargement des projets...</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un projet</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="3"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Détails de la tâche</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Statut</label>
                        <select id="status" name="status" class="form-control">
                            <option value="todo" <?= ($formData['status'] ?? 'todo') === 'todo' ? 'selected' : '' ?>>À faire</option>
                            <option value="in_progress" <?= ($formData['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                            <option value="review" <?= ($formData['status'] ?? '') === 'review' ? 'selected' : '' ?>>En révision</option>
                            <option value="done" <?= ($formData['status'] ?? '') === 'done' ? 'selected' : '' ?>>Terminé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="priority" class="form-label">Priorité</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low" <?= ($formData['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Basse</option>
                            <option value="medium" <?= ($formData['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="high" <?= ($formData['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Haute</option>
                            <option value="urgent" <?= ($formData['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">Date d'échéance</label>
                        <input type="date" 
                               id="due_date" 
                               name="due_date" 
                               class="form-control"
                               value="<?= htmlspecialchars($formData['due_date'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assignée à</label>
                        <select id="assigned_to" name="assigned_to" class="form-control" disabled>
                            <option value="">Sélectionnez d'abord un projet</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Créer la tâche
            </button>
        </div>
        
        <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 0 ?>">
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    setupFormValidation();
});

async function loadProjects() {
    const projectSelect = document.getElementById('project_id');
    projectSelect.disabled = true;
    projectSelect.innerHTML = '<option value="">Chargement des projets...</option>';

    try {
        const response = await fetch('http://localhost:8000/backend/api/projects.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La réponse n\'est pas au format JSON');
        }
        
        const result = await response.json();

        // Adaptation à la nouvelle structure de réponse
        if (
            !result ||
            typeof result !== 'object' ||
            !result.success ||
            !result.data ||
            result.data.status !== 'success' ||
            !Array.isArray(result.data.data)
        ) {
            throw new Error('Structure de données invalide');
        }

        const projects = result.data.data;

        projectSelect.innerHTML = '<option value="">Sélectionnez un projet</option>';
        
        projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.title || `Projet #${project.id}`;
            
            if (project.id == <?= json_encode($formData['project_id'] ?? 'null') ?>) {
                option.selected = true;
                loadProjectMembers(project.id);
            }
            
            projectSelect.appendChild(option);
        });
        
        projectSelect.disabled = false;
    } catch (error) {
        console.error('Erreur:', error);
        projectSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        
        // Afficher un message d'erreur plus clair
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-3';
        errorDiv.textContent = 'Impossible de charger les projets. Veuillez réessayer.';
        document.querySelector('.container').prepend(errorDiv);
    }
}

async function loadProjectMembers(projectId) {
    const assigneeSelect = document.getElementById('assigned_to');

    // On ignore projectId, on charge tous les utilisateurs actifs
    assigneeSelect.disabled = true;
    assigneeSelect.innerHTML = '<option value="">Chargement des utilisateurs...</option>';

    try {
        const response = await fetch('http://localhost:8000/backend/api/users.php?active=1');
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        assigneeSelect.innerHTML = '<option value="">Non assignée</option>';

        if (data && Array.isArray(data.data)) {
            data.data.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name || `Utilisateur #${user.id}`;

                if (user.id == <?= json_encode($formData['assigned_to'] ?? 'null') ?>) {
                    option.selected = true;
                }

                assigneeSelect.appendChild(option);
            });
        }

        assigneeSelect.disabled = false;
    } catch (error) {
        console.error('Erreur:', error);
        assigneeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
    }
}

function setupFormValidation() {
    const form = document.getElementById('taskForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        const formData = {
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            status: document.getElementById('status').value,
            priority: document.getElementById('priority').value,
            due_date: document.getElementById('due_date').value || null,
            project_id: parseInt(document.getElementById('project_id').value),
            assigned_to: document.getElementById('assigned_to').value ? 
                        parseInt(document.getElementById('assigned_to').value) : null,
            created_by: <?= $_SESSION['user_id'] ?? 1 ?>
        };
        
        try {
            const response = await fetch('http://localhost:8000/backend/api/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                throw new Error(errorData?.message || `Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.status !== 'success') {
                throw new Error(result.message || 'Erreur lors de la création de la tâche');
            }
            
            window.location.href = '/tasks';
        } catch (error) {
            console.error('Erreur:', error);
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-3';
            errorDiv.textContent = 'Erreur: ' + error.message;
            
            const existingAlert = document.querySelector('.alert.alert-danger');
            if (existingAlert) {
                existingAlert.replaceWith(errorDiv);
            } else {
                form.prepend(errorDiv);
            }
        }
    });
    
    // Gestion du changement de projet
    document.getElementById('project_id').addEventListener('change', function() {
        loadProjectMembers(this.value);
    });
}
</script>

<?php
include_once __DIR__ . '/../../includes/footer.php';
?>