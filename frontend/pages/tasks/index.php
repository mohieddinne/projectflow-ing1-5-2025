<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/header.php'; ?>

<div class="container mt-4">
    <h1 class="mb-4">Tâches</h1>
    <a href="/tasks/create" class="btn btn-primary mb-3">
        <i class="fas fa-plus"></i> Nouvelle tâche
    </a>
    <form id="filterForm" class="form-inline mb-4">
        <label for="status" class="mr-2">Statut:</label>
        <select id="status" name="status" class="form-control mr-3">
            <option value="">Tous</option>
            <option value="todo">À faire</option>
            <option value="in_progress">En cours</option>
            <option value="review">En révision</option>
            <option value="done">Terminé</option>
        </select>

        <label for="priority" class="mr-2">Priorité:</label>
        <select id="priority" name="priority" class="form-control mr-3">
            <option value="">Toutes</option>
            <option value="urgent">Urgente</option>
            <option value="high">Haute</option>
            <option value="medium">Moyenne</option>
            <option value="low">Basse</option>
        </select>

        <label for="sort" class="mr-2">Trier par:</label>
        <select id="sort" name="sort" class="form-control">
            <option value="deadline">Date d'échéance</option>
            <option value="title">Titre</option>
            <option value="priority">Priorité</option>
            <option value="status">Statut</option>
            <option value="created">Date de création</option>
        </select>
    </form>

    <div class="row" id="tasksContainer">
        <!-- Les tâches seront injectées ici par JS -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const prioritySelect = document.getElementById('priority');
    const sortSelect = document.getElementById('sort');
    const tasksContainer = document.getElementById('tasksContainer');
    
    // Créer un objet pour gérer les tâches
    const taskManager = {
        fetchAndDisplayTasks: function() {
            const status = statusSelect.value;
            const priority = prioritySelect.value;
            const sort = sortSelect.value;

            const url = new URL('/backend/api/tasks.php', window.location.origin);
            if (status) url.searchParams.append('status', status);
            if (priority) url.searchParams.append('priority', priority);
            if (sort) url.searchParams.append('sort', sort);

            tasksContainer.innerHTML = `<div class="col-12 text-center py-5"><span class="spinner-border"></span> Chargement...</div>`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data && data.data.length > 0) {
                        this.renderTasks(data.data);
                    } else {
                        tasksContainer.innerHTML = `
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <h5>Aucune tâche trouvée</h5>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    tasksContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-danger text-center">Erreur lors du chargement des tâches.</div>
                        </div>
                    `;
                });
        },
        
        renderTasks: function(tasks) {
            tasksContainer.innerHTML = tasks.map(task => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <a href="/tasks/view?id=${task.id}">
                                    ${this.escapeHtml(task.title)}
                                </a>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">${this.truncateText(task.description, 150)}</p>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Projet: ${this.escapeHtml(task.project_title || '')}</small>
                                    <small>Assigné à: ${this.escapeHtml(task.assigned_to_name || 'Non assignée')}</small>
                                </div>
                                <div>
                                    <span class="badge ${this.getStatusBadgeClass(task.status)}">${this.getStatusLabel(task.status)}</span>
                                    <span class="badge ${this.getPriorityBadgeClass(task.priority)}">${this.getPriorityLabel(task.priority)}</span>
                                </div>
                                <div>
                                    <small>Date d'échéance: ${this.formatDate(task.due_date)}</small>
                                    ${this.isOverdue(task.due_date, task.status) ? `<span class="text-danger fw-bold ms-2"><i class="fas fa-exclamation-circle"></i> En retard</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group" role="group">
                                <a href="/tasks/edit?id=${task.id}" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-task-id="${task.id}" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Ajouter les écouteurs d'événements aux boutons de suppression
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const taskId = e.currentTarget.getAttribute('data-task-id');
                    this.deleteTask(taskId);
                });
            });
        },
        
        // Helpers JS
        escapeHtml: function(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        truncateText: function(text, length = 100) {
            if (!text) return '';
            return text.length > length ? text.slice(0, length) + '...' : text;
        },
        
        formatDate: function(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('fr-FR');
        },
        
        isOverdue: function(dateStr, status) {
            if (!dateStr || status === 'done') return false;
            const date = new Date(dateStr);
            const today = new Date();
            today.setHours(0,0,0,0);
            return date < today;
        },
        
        getStatusBadgeClass: function(status) {
            switch (status) {
                case 'todo': return 'bg-secondary';
                case 'in_progress': return 'bg-primary';
                case 'review': return 'bg-info';
                case 'done': return 'bg-success';
                default: return 'bg-secondary';
            }
        },
        
        getStatusLabel: function(status) {
            switch (status) {
                case 'todo': return 'À faire';
                case 'in_progress': return 'En cours';
                case 'review': return 'En révision';
                case 'done': return 'Terminé';
                default: return 'Inconnu';
            }
        },
        
        getPriorityBadgeClass: function(priority) {
            switch (priority) {
                case 'low': return 'bg-success';
                case 'medium': return 'bg-warning';
                case 'high': return 'bg-danger';
                case 'urgent': return 'bg-danger text-white fw-bold';
                default: return 'bg-secondary';
            }
        },
        
        getPriorityLabel: function(priority) {
            switch (priority) {
                case 'low': return 'Basse';
                case 'medium': return 'Moyenne';
                case 'high': return 'Haute';
                case 'urgent': return 'Urgente';
                default: return 'Inconnue';
            }
        },
        
        // Fonction de suppression
        deleteTask: function(id) {
            if (!confirm('Voulez-vous vraiment supprimer cette tâche ?')) return;
            
            fetch(`/backend/api/tasks.php?id=${id}`, {
                method: 'DELETE',
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Rafraîchir la liste des tâches après suppression
                    this.fetchAndDisplayTasks();
                    
                    // Afficher une notification
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        Tâche supprimée avec succès !
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container').prepend(alertDiv);
                    
                    // Fermer automatiquement après 3 secondes
                    setTimeout(() => {
                        alertDiv.classList.remove('show');
                        setTimeout(() => alertDiv.remove(), 150);
                    }, 3000);
                } else {
                    alert('Erreur lors de la suppression : ' + (data.message || ''));
                }
            })
            .catch(() => alert('Erreur lors de la suppression.'));
        }
    };

    // Charger au démarrage
    taskManager.fetchAndDisplayTasks();

    // Écouter les changements sur les filtres
    statusSelect.addEventListener('change', () => taskManager.fetchAndDisplayTasks());
    prioritySelect.addEventListener('change', () => taskManager.fetchAndDisplayTasks());
    sortSelect.addEventListener('change', () => taskManager.fetchAndDisplayTasks());
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/footer.php'; ?>