<?php
/**
 * Template pour l'affichage d'une carte de projet
 * @param array $project Les données du projet
 */
?>

<div class="project-card" data-project-id="<?= $project['id'] ?>">
    <div class="project-card__header">
        <!-- Statut et actions -->
        <div class="project-card__status">
            <span class="status-badge status-<?= $project['status'] ?>">
                <?= getStatusLabel($project['status']) ?>
            </span>
            <?php if ($project['priority'] === 'high'): ?>
                <span class="priority-badge">
                    <i class="fas fa-star" title="Priorité haute"></i>
                </span>
            <?php endif; ?>
        </div>

        <div class="project-card__actions">
            <?php if (hasPermission('edit_project')): ?>
                <button class="btn btn-icon" 
                        title="Modifier" 
                        onclick="window.location.href='/projects/edit?id=<?= $project['id'] ?>'">
                    <i class="fas fa-edit"></i>
                </button>
            <?php endif; ?>
            
            <div class="dropdown">
                <button class="btn btn-icon" title="Plus d'options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="/projects/view?id=<?= $project['id'] ?>" class="dropdown-item">
                        <i class="fas fa-eye"></i> Voir détails
                    </a>
                    <?php if (hasPermission('create_task')): ?>
                        <a href="/tasks/create?project_id=<?= $project['id'] ?>" class="dropdown-item">
                            <i class="fas fa-plus"></i> Nouvelle tâche
                        </a>
                    <?php endif; ?>
                    <?php if (hasPermission('delete_project')): ?>
                        <button class="dropdown-item text-danger" 
                                onclick="confirmDeleteProject(<?= $project['id'] ?>)">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="project-card__body">
        <!-- Titre et description -->
        <h3 class="project-card__title">
            <a href="/projects/view?id=<?= $project['id'] ?>">
                <?= htmlspecialchars($project['title']) ?>
            </a>
        </h3>
        <p class="project-card__description">
            <?= htmlspecialchars(truncateText($project['description'], 120)) ?>
        </p>

        <!-- Progression -->
        <div class="project-progress">
            <div class="progress-bar">
                <div class="progress-bar__fill" 
                     style="width: <?= $project['progress'] ?>%">
                </div>
                <span class="progress-text"><?= $project['progress'] ?>%</span>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="project-stats">
            <div class="stat-item" title="Tâches">
                <i class="fas fa-tasks"></i>
                <span><?= $project['tasks_count'] ?></span>
            </div>
            <div class="stat-item" title="Tâches terminées">
                <i class="fas fa-check-circle"></i>
                <span><?= $project['completed_tasks'] ?></span>
            </div>
            <div class="stat-item" title="Commentaires">
                <i class="fas fa-comments"></i>
                <span><?= $project['comments_count'] ?></span>
            </div>
        </div>
    </div>

    <div class="project-card__footer">
        <!-- Dates -->
        <div class="project-dates">
            <span class="date-item" title="Date de début">
                <i class="fas fa-calendar-alt"></i>
                <?= formatDate($project['start_date']) ?>
            </span>
            <span class="date-separator">→</span>
            <span class="date-item <?= isOverdue($project['end_date']) ? 'overdue' : '' ?>" 
                  title="Date de fin">
                <?= formatDate($project['end_date']) ?>
            </span>
        </div>

        <!-- Équipe -->
        <div class="project-team">
            <?php foreach(array_slice($project['members'], 0, 3) as $member): ?>
                <img src="<?= $member['avatar_url'] ?>" 
                     alt="<?= htmlspecialchars($member['name']) ?>"
                     title="<?= htmlspecialchars($member['name']) ?>"
                     class="avatar avatar--small">
            <?php endforeach; ?>
            <?php if (count($project['members']) > 3): ?>
                <span class="avatar avatar--more">
                    +<?= count($project['members']) - 3 ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fonction de confirmation de suppression
function confirmDeleteProject(projectId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
        fetch(`/api/projects/delete/${projectId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-project-id="${projectId}"]`).remove();
                showNotification('Projet supprimé avec succès', 'success');
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            showNotification(error.message, 'error');
        });
    }
}

// Initialisation des dropdowns
document.querySelectorAll('.dropdown').forEach(dropdown => {
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
    });
});

// Fermeture des dropdowns au clic extérieur
document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown.active').forEach(dropdown => {
        dropdown.classList.remove('active');
    });
});
</script>
