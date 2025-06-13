<?php
/**
 * Template pour l'affichage d'une liste de tâches
 * @param array $tasks Liste des tâches
 * @param bool $showProject Afficher le projet associé
 * @param bool $showActions Afficher les actions
 */
?>

<div class="task-list" data-sortable="<?= $sortable ?? false ?>">
    <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <img src="/assets/images/empty-tasks.svg" alt="Aucune tâche">
            <p>Aucune tâche à afficher</p>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <div class="task-item" data-task-id="<?= $task['id'] ?>" draggable="<?= $sortable ?? false ?>">
                <!-- État et priorité -->
                <div class="task-status">
                    <div class="status-indicator">
                        <input type="checkbox" 
                               class="task-checkbox"
                               <?= $task['status'] === 'completed' ? 'checked' : '' ?>
                               <?= hasPermission('update_task') ? '' : 'disabled' ?>>
                        <span class="priority-dot priority-<?= $task['priority'] ?>" 
                              title="Priorité <?= getPriorityLabel($task['priority']) ?>">
                        </span>
                    </div>
                </div>

                <!-- Informations principales -->
                <div class="task-content">
                    <div class="task-header">
                        <h4 class="task-title">
                            <a href="/tasks/view?id=<?= $task['id'] ?>">
                                <?= htmlspecialchars($task['title']) ?>
                            </a>
                        </h4>
                        <?php if ($showProject ?? true): ?>
                            <a href="/projects/view?id=<?= $task['project_id'] ?>" 
                               class="project-link">
                                <?= htmlspecialchars($task['project_title']) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="task-description">
                        <?= htmlspecialchars(truncateText($task['description'], 100)) ?>
                    </p>

                    <!-- Méta-informations -->
                    <div class="task-meta">
                        <div class="task-assignees">
                            <?php foreach(array_slice($task['assigned_to'], 0, 3) as $assignee): ?>
                                <img src="<?= $assignee['avatar_url'] ?>" 
                                     alt="<?= htmlspecialchars($assignee['name']) ?>"
                                     title="<?= htmlspecialchars($assignee['name']) ?>"
                                     class="avatar avatar--xs">
                            <?php endforeach; ?>
                            <?php if (count($task['assigned_to']) > 3): ?>
                                <span class="avatar avatar--more">
                                    +<?= count($task['assigned_to']) - 3 ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="task-stats">
                            <?php if ($task['comments_count'] > 0): ?>
                                <span class="stat-item" title="Commentaires">
                                    <i class="fas fa-comment"></i>
                                    <?= $task['comments_count'] ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($task['attachments_count'] > 0): ?>
                                <span class="stat-item" title="Pièces jointes">
                                    <i class="fas fa-paperclip"></i>
                                    <?= $task['attachments_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Progression et échéance -->
                <div class="task-progress">
                    <div class="progress-bar">
                        <div class="progress-bar__fill" 
                             style="width: <?= $task['progress'] ?>%">
                        </div>
                    </div>
                    <span class="due-date <?= isOverdue($task['due_date']) ? 'overdue' : '' ?>">
                        <?= formatDate($task['due_date']) ?>
                    </span>
                </div>

                <!-- Actions -->
                <?php if ($showActions ?? true): ?>
                    <div class="task-actions">
                        <?php if (hasPermission('edit_task')): ?>
                            <button class="btn btn-icon" 
                                    title="Modifier"
                                    onclick="window.location.href='/tasks/edit?id=<?= $task['id'] ?>'">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-icon">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="/tasks/view?id=<?= $task['id'] ?>" class="dropdown-item">
                                    <i class="fas fa-eye"></i> Voir détails
                                </a>
                                <?php if (hasPermission('update_task')): ?>
                                    <button class="dropdown-item" onclick="changeTaskStatus(<?= $task['id'] ?>)">
                                        <i class="fas fa-exchange-alt"></i> Changer le statut
                                    </button>
                                <?php endif; ?>
                                <?php if (hasPermission('delete_task')): ?>
                                    <button class="dropdown-item text-danger" 
                                            onclick="confirmDeleteTask(<?= $task['id'] ?>)">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des cases à cocher
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.closest('.task-item').dataset.taskId;
            const newStatus = this.checked ? 'completed' : 'in_progress';
            
            updateTaskStatus(taskId, newStatus);
        });
    });

    // Gestion du tri par glisser-déposer
    if (document.querySelector('.task-list[data-sortable="true"]')) {
        initSortable();
    }
});

function updateTaskStatus(taskId, status) {
    fetch(`/api/tasks/${taskId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({ status })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message);
        }
        showNotification('Statut mis à jour', 'success');
    })
    .catch(error => {
        showNotification(error.message, 'error');
    });
}

function confirmDeleteTask(taskId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        fetch(`/api/tasks/delete/${taskId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-task-id="${taskId}"]`).remove();
                showNotification('Tâche supprimée', 'success');
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            showNotification(error.message, 'error');
        });
    }
}

function initSortable() {
    let draggedItem = null;

    document.querySelectorAll('.task-item[draggable="true"]').forEach(item => {
        item.addEventListener('dragstart', function() {
            draggedItem = this;
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            updateTaskOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(this.parentElement, e.clientY);
            if (draggedItem !== this) {
                if (afterElement) {
                    this.parentElement.insertBefore(draggedItem, afterElement);
                } else {
                    this.parentElement.appendChild(draggedItem);
                }
            }
        });
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.task-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateTaskOrder() {
    const taskIds = Array.from(document.querySelectorAll('.task-item'))
        .map(item => item.dataset.taskId);

    fetch('/api/tasks/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({ order: taskIds })
    })
    .catch(error => {
        showNotification('Erreur lors de la mise à jour de l\'ordre', 'error');
    });
}
</script>
