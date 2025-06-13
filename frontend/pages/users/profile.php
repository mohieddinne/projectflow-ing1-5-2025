<?php
$userId = $_GET['id'] ?? getCurrentUserId();
$user = getUserDetails($userId);

if (!$user || (!hasPermission('view_all_profiles') && $userId !== getCurrentUserId())) {
    setFlashMessage('error', 'Accès non autorisé');
    redirect('/dashboard');
}
?>

<div class="page-container">
    <div class="page-header">
        <h1>Profil utilisateur</h1>
        <nav class="breadcrumb">
            <a href="/dashboard">Tableau de bord</a>
            <?php if (hasPermission('view_all_profiles')): ?>
                <a href="/users">Utilisateurs</a>
            <?php endif; ?>
            <span><?= htmlspecialchars($user['name']) ?></span>
        </nav>
    </div>

    <div class="profile-content">
        <!-- Informations principales -->
        <div class="profile-main card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                         alt="Photo de profil"
                         class="avatar avatar--large">
                    <?php if ($userId === getCurrentUserId()): ?>
                        <button class="btn btn-icon" data-modal="changeAvatarModal">
                            <i class="fas fa-camera"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <h2><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="profile-role"><?= htmlspecialchars($user['role_name']) ?></p>
                    <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <?php if ($userId === getCurrentUserId()): ?>
                    <button class="btn btn-secondary" data-modal="editProfileModal">
                        <i class="fas fa-edit"></i> Modifier le profil
                    </button>
                <?php endif; ?>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $user['projects_count'] ?></span>
                    <span class="stat-label">Projets</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $user['tasks_count'] ?></span>
                    <span class="stat-label">Tâches</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $user['completed_tasks'] ?></span>
                    <span class="stat-label">Tâches terminées</span>
                </div>
            </div>
        </div>

        <div class="profile-details">
            <!-- Projets actifs -->
            <div class="card">
                <h3>Projets actifs</h3>
                <div class="projects-list">
                    <?php foreach($user['active_projects'] as $project): ?>
                        <div class="project-item">
                            <div class="project-item__info">
                                <h4>
                                    <a href="/projects/view?id=<?= $project['id'] ?>">
                                        <?= htmlspecialchars($project['title']) ?>
                                    </a>
                                </h4>
                                <div class="progress-bar">
                                    <div class="progress-bar__fill" 
                                         style="width: <?= $project['progress'] ?>%">
                                    </div>
                                    <span class="progress-text"><?= $project['progress'] ?>%</span>
                                </div>
                            </div>
                            <span class="status-badge status-<?= $project['status'] ?>">
                                <?= getStatusLabel($project['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tâches récentes -->
            <div class="card">
                <h3>Tâches récentes</h3>
                <div class="tasks-list">
                    <?php foreach($user['recent_tasks'] as $task): ?>
                        <div class="task-item">
                            <div class="task-item__info">
                                <h4>
                                    <a href="/tasks/view?id=<?= $task['id'] ?>">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </a>
                                </h4>
                                <small>
                                    Projet : 
                                    <a href="/projects/view?id=<?= $task['project_id'] ?>">
                                        <?= htmlspecialchars($task['project_title']) ?>
                                    </a>
                                </small>
                            </div>
                            <span class="due-date <?= isOverdue($task['due_date']) ? 'overdue' : '' ?>">
                                <?= formatDate($task['due_date']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card">
                <h3>Activité récente</h3>
                <div class="activity-feed">
                    <?php foreach($user['recent_activities'] as $activity): ?>
                        <div class="activity-item">
                            <i class="<?= getActivityIcon($activity['type']) ?>"></i>
                            <div class="activity-item__content">
                                <p><?= formatActivity($activity) ?></p>
                                <small><?= formatTimeAgo($activity['created_at']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php if ($userId === getCurrentUserId()): ?>
    <!-- Modal de modification du profil -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <h2>Modifier le profil</h2>
            <form id="editProfileForm" class="form" method="POST" action="/api/users/update">
                <div class="form-group">
                    <label for="name" class="form-label">Nom complet</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-input" 
                           value="<?= htmlspecialchars($user['name']) ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="userEmail" class="form-label">Email</label>
                    <input type="email" 
                           id="userEmail" 
                           name="email" 
                           class="form-input" 
                           value="<?= htmlspecialchars($user['email']) ?>" 
                           required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de changement d'avatar -->
    <div id="changeAvatarModal" class="modal">
        <div class="modal-content">
            <h2>Changer la photo de profil</h2>
            <form id="avatarForm" class="form" method="POST" action="/api/users/avatar" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatarFile" class="form-label">Sélectionner une image</label>
                    <input type="file" 
                           id="avatarFile" 
                           name="avatar" 
                           class="form-input" 
                           accept="image/*" 
                           required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Télécharger
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des formulaires
    const editProfileForm = document.getElementById('editProfileForm');
    const avatarForm = document.getElementById('avatarForm');

    editProfileForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            const data = await response.json();

            if (response.ok) {
                showSuccess('Profil mis à jour avec succès');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showError(error.message);
        }
    });

    avatarForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            const data = await response.json();

            if (response.ok) {
                showSuccess('Photo de profil mise à jour');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showError(error.message);
        }
    });

    function showSuccess(message) {
        window.app.showNotification({ message, type: 'success' });
    }

    function showError(message) {
        window.app.showNotification({ message, type: 'error' });
    }
});
</script>
