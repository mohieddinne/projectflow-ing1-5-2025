<?php
// frontend/pages/tasks/edit.php

if (!function_exists('requirePermission')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
requirePermission('edit_task');

// Récupérer l'ID de la tâche depuis l'URL
$taskId = $_GET['id'] ?? 0;
if (!$taskId) {
    header('Location: /tasks');
    exit;
}

$pageTitle = "Modifier la tâche";

// Récupération des données précédentes en session
$formData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modifier la tâche #<?= htmlspecialchars($taskId) ?></h1>
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
                            <option value="todo">À faire</option>
                            <option value="in_progress">En cours</option>
                            <option value="review">En révision</option>
                            <option value="done">Terminé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="priority" class="form-label">Priorité</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low">Basse</option>
                            <option value="medium">Moyenne</option>
                            <option value="high">Haute</option>
                            <option value="urgent">Urgente</option>
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
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">Non assignée</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <button type="button" class="btn btn-danger" id="deleteBtn">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
        
        <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 1 ?>">
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Charger les données de la tâche
    await loadTaskData(<?= $taskId ?>);
    
    // Charger les projets
    await loadProjects();
    
    // Configurer la validation du formulaire
    setupFormValidation();
    
    // Gestion du bouton de suppression
    document.getElementById('deleteBtn').addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
            deleteTask(<?= $taskId ?>);
        }
    });
});

// Charger les données de la tâche
async function loadTaskData(taskId) {
    try {
        const response = await fetch(`http://localhost:8000/backend/api/tasks.php?id=${taskId}`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status !== 'success' || !result.data) {
            throw new Error('Données de tâche non disponibles');
        }
        
        const task = result.data;
        task.taskId = taskId; // Ajout de taskId à l'objet data
        
        // Remplir le formulaire avec les données de la tâche
        document.getElementById('title').value = task.title || '';
        document.getElementById('description').value = task.description || '';
        document.getElementById('status').value = task.status || 'todo';
        document.getElementById('priority').value = task.priority || 'medium';
        document.getElementById('due_date').value = task.due_date || '';
        document.querySelector('input[name="created_by"]').value = task.created_by || <?= $_SESSION['user_id'] ?? 1 ?>;
        
        // Charger les membres après avoir défini le projet
        if (task.project_id) {
            await loadProjectMembers(task.project_id);
            document.getElementById('assigned_to').value = task.assigned_to || '';
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        alert('Impossible de charger les données de la tâche');
    }
}

// Charger la liste des projets
async function loadProjects() {
    const projectSelect = document.getElementById('project_id');
    projectSelect.disabled = true;
    projectSelect.innerHTML = '<option value="">Chargement des projets...</option>';

    try {
        const response = await fetch('http://localhost:8000/backend/api/projects.php');
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const apiResult = await response.json();

        // L'API retourne { success: true, data: { status: 'success', data: [...] } }
        let projects = [];
        if (
            apiResult &&
            apiResult.success &&
            apiResult.data &&
            apiResult.data.status === 'success' &&
            Array.isArray(apiResult.data.data)
        ) {
            projects = apiResult.data.data;
        }

        projectSelect.innerHTML = '<option value="">Sélectionnez un projet</option>';

        projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.title || `Projet #${project.id}`;
            projectSelect.appendChild(option);
        });

        // Sélectionner le projet de la tâche après chargement
        try {
            const taskResponse = await fetch(`http://localhost:8000/backend/api/tasks.php?id=${<?= $taskId ?>}`);
            if (taskResponse.ok) {
                const taskData = await taskResponse.json();
                if (taskData.status === 'success' && taskData.data) {
                    projectSelect.value = taskData.data.project_id || '';
                }
            }
        } catch (e) {
            // ignore
        }

        projectSelect.disabled = false;
    } catch (error) {
        console.error('Erreur:', error);
        projectSelect.innerHTML = '<option value="">Erreur de chargement</option>';
    }
}

// Charger les membres d'un projet
async function loadProjectMembers(projectId) {
    const assigneeSelect = document.getElementById('assigned_to');
    assigneeSelect.disabled = true;
    assigneeSelect.innerHTML = '<option value="">Chargement des utilisateurs...</option>';

    try {
        const response = await fetch('http://localhost:8000/backend/api/users.php?active=1');
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        assigneeSelect.innerHTML = '<option value="">Non assignée</option>';

        if (data && data.status === 'success' && Array.isArray(data.data)) {
            data.data.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name || `Utilisateur #${user.id}`;
                assigneeSelect.appendChild(option);
            });
        }

        assigneeSelect.disabled = false;
    } catch (error) {
        console.error('Erreur:', error);
        assigneeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
    }
}

/**
 * Configuration de la validation du formulaire
 */
function setupFormValidation() {
    const form = document.getElementById('taskForm');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // Récupérer l'ID de la tâche depuis le champ caché ou l'URL
        const taskId = <?= json_encode($taskId) ?>;

        const formData = {
            id: taskId,
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            status: document.getElementById('status').value,
            priority: document.getElementById('priority').value,
            due_date: document.getElementById('due_date').value || null,
            project_id: parseInt(document.getElementById('project_id').value),
            assigned_to: document.getElementById('assigned_to').value ?
                parseInt(document.getElementById('assigned_to').value) : null,
            created_by: <?= json_encode($_SESSION['user_id'] ?? 1) ?>
        };

        try {
            const response = await fetch('http://localhost:8000/backend/api/tasks.php?id=' + encodeURIComponent(taskId), {
                method: 'PUT',
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
                throw new Error(result.message || 'Erreur lors de la mise à jour de la tâche');
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
}

// Supprimer une tâche
async function deleteTask(taskId) {
    try {
        const response = await fetch(`http://localhost:8000/backend/api/tasks.php?id=${taskId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status !== 'success') {
            throw new Error(result.message || 'Erreur lors de la suppression');
        }
        
        window.location.href = '/tasks';
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression: ' + error.message);
    }
}
</script>

<?php
include_once __DIR__ . '/../../includes/footer.php';
?>