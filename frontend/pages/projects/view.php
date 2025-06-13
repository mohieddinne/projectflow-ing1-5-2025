<?php
requirePermission('view_projects');
require_once __DIR__ . '/../../includes/functions.php';

$projectId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
if (!$projectId) {
    redirect('/projects');
}

$project = getProjectDetails($projectId);
if (!$project) {
    setFlashMessage('error', 'Projet non trouvé');
    redirect('/projects');
}

// Fonctions d'échappement
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function nl2brE($string) {
    return nl2br(e($string));
}
?>

<div class="page-container">
    <!-- En-tête du projet -->
    <div class="project-header">
        <div class="project-header__main">
            <h1><?= e($project['title']) ?></h1>
            <span class="status-badge status-<?= e($project['status']) ?>">
                <?= e(getStatusLabel($project['status'])) ?>
            </span>
        </div>

        <div class="project-header__actions">
            <?php if (hasPermission('edit_project')): ?>
                <a href="/projects/edit?id=<?= urlencode($projectId) ?>" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            <?php endif; ?>
            <?php if (hasPermission('create_task')): ?>
                <button class="btn btn-primary" data-modal="createTaskModal">
                    <i class="fas fa-plus"></i> Nouvelle tâche
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aperçu du projet -->
    <div class="project-overview">
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card__icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-card__content">
                    <h3><?= (int)$project['tasks_count'] ?></h3>
                    <span>Tâches totales</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-card__content">
                    <h3><?= (int)$project['completed_tasks'] ?></h3>
                    <span>Tâches terminées</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card__content">
                    <h3><?= count($project['members']) ?></h3>
                    <span>Membres</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-card__content">
                    <h3><?= (int)getDaysRemaining($project['end_date']) ?></h3>
                    <span>Jours restants</span>
                </div>
            </div>
        </div>

        <div class="progress-bar progress-bar--large">
            <progress value="<?= (int)$project['progress'] ?>" max="100" class="progress-bar__fill"></progress>
            <span class="progress-bar__text"><?= (int)$project['progress'] ?>%</span>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="project-content">
        <!-- Tableau Kanban des tâches -->
        <div class="kanban-board">
            <?php
            $statuses = [
                'todo' => 'À faire',
                'in_progress' => 'En cours',
                'review' => 'En révision',
                'completed' => 'Terminé'
            ];

            foreach ($statuses as $statusKey => $statusLabel): ?>
                <div class="kanban-column" data-status="<?= e($statusKey) ?>">
                    <h3><?= e($statusLabel) ?></h3>
                    <div class="task-list">
                        <?php if (!empty($project['tasks'][$statusKey]) && is_array($project['tasks'][$statusKey])): ?>
                            <?php foreach ($project['tasks'][$statusKey] as $task): ?>
                                <?php include 'components/task-card.php'; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-tasks">Aucune tâche</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informations détaillées -->
        <div class="project-details">
            <div class="card">
                <h2>Description</h2>
                <p class="project-description"><?= nl2brE($project['description']) ?></p>
            </div>

            <!-- Équipe -->
            <div class="card">
                <h2>Équipe</h2>
                <div class="team-members">
                    <?php foreach ($project['members'] as $member): ?>
                        <div class="team-member">
                            <img src="<?= e($member['avatar_url']) ?>" 
                                 alt="<?= e($member['name']) ?>" 
                                 class="avatar" loading="lazy" decoding="async">
                            <div class="team-member__info">
                                <strong><?= e($member['name']) ?></strong>
                                <span><?= e($member['role']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card">
                <h2>Activité récente</h2>
                <div class="activity-feed">
                    <?php foreach ($project['activities'] as $activity): ?>
                        <div class="activity-item">
                            <i class="<?= e(getActivityIcon($activity['type'])) ?>"></i>
                            <div class="activity-item__content">
                                <p><?= e(formatActivity($activity)) ?></p>
                                <small><?= e(formatTimeAgo($activity['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taskCards = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.kanban-column');

    taskCards.forEach(card => {
        card.setAttribute('draggable', true);
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
    });

    function handleDragStart(e) {
        e.target.classList.add('dragging');
        e.dataTransfer.setData('text/plain', e.target.dataset.taskId);
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
    }

    function handleDragOver(e) {
        e.preventDefault();
    }

    async function handleDrop(e) {
        e.preventDefault();
        const taskId = e.dataTransfer.getData('text/plain');
        const column = e.target.closest('.kanban-column');
        if (!column) return;
        const newStatus = column.dataset.status;

        try {
            const response = await fetch(`/api/tasks/${encodeURIComponent(taskId)}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify({ status: newStatus })
            });

            if (response.ok) {
                window.location.reload();
            } else {
                throw new Error('Erreur lors de la mise à jour du statut');
            }
        } catch (error) {
            showError(error.message);
        }
    }

    function showError(message) {
        if (window.app && typeof window.app.showNotification === 'function') {
            window.app.showNotification({ message, type: 'error' });
        } else {
            alert(message);
        }
    }
});
</script>