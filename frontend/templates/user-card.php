<?php
/**
 * Template pour l'affichage d'une carte utilisateur
 * @param array $user Les données de l'utilisateur
 * @param bool $showActions Afficher les actions (optionnel)
 */
?>

<div class="user-card" data-user-id="<?= $user['id'] ?>">
    <!-- En-tête avec avatar et statut -->
    <div class="user-card__header">
        <div class="user-avatar">
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                 alt="Avatar de <?= htmlspecialchars($user['name']) ?>"
                 class="avatar avatar--medium">
            <span class="status-indicator <?= $user['is_online'] ? 'online' : 'offline' ?>"></span>
        </div>

        <?php if ($showActions ?? true): ?>
            <div class="user-actions">
                <div class="dropdown">
                    <button class="btn btn-icon" title="Options">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="/users/profile?id=<?= $user['id'] ?>" class="dropdown-item">
                            <i class="fas fa-user"></i> Voir profil
                        </a>
                        <?php if (hasPermission('manage_users')): ?>
                            <button class="dropdown-item" onclick="editUserRole(<?= $user['id'] ?>)">
                                <i class="fas fa-user-cog"></i> Modifier le rôle
                            </button>
                            <?php if ($user['id'] !== getCurrentUserId()): ?>
                                <button class="dropdown-item text-danger" 
                                        onclick="confirmDeactivateUser(<?= $user['id'] ?>)">
                                    <i class="fas fa-user-slash"></i> Désactiver le compte
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Informations principales -->
    <div class="user-card__body">
        <h3 class="user-name">
            <?= htmlspecialchars($user['name']) ?>
        </h3>
        <span class="user-role"><?= htmlspecialchars($user['role_name']) ?></span>
        <span class="user-email"><?= htmlspecialchars($user['email']) ?></span>

        <!-- Statistiques utilisateur -->
        <div class="user-stats">
            <div class="stat-item" title="Projets actifs">
                <i class="fas fa-project-diagram"></i>
                <span><?= $user['active_projects_count'] ?></span>
            </div>
            <div class="stat-item" title="Tâches en cours">
                <i class="fas fa-tasks"></i>
                <span><?= $user['pending_tasks_count'] ?></span>
            </div>
            <div class="stat-item" title="Tâches terminées">
                <i class="fas fa-check-circle"></i>
                <span><?= $user['completed_tasks_count'] ?></span>
            </div>
        </div>

        <!-- Dernière activité -->
        <?php if ($user['last_activity']): ?>
            <div class="user-activity">
                <small>Dernière activité :</small>
                <p><?= formatActivity($user['last_activity']) ?></p>
                <span class="activity-time">
                    <?= formatTimeAgo($user['last_activity']['timestamp']) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pied de carte avec tags et badges -->
    <div class="user-card__footer">
        <?php if (!empty($user['skills'])): ?>
            <div class="user-skills">
                <?php foreach(array_slice($user['skills'], 0, 3) as $skill): ?>
                    <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
                <?php if (count($user['skills']) > 3): ?>
                    <span class="skill-tag more">+<?= count($user['skills']) - 3 ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($user['badges'])): ?>
            <div class="user-badges">
                <?php foreach($user['badges'] as $badge): ?>
                    <span class="badge" title="<?= htmlspecialchars($badge['description']) ?>">
                        <i class="<?= $badge['icon'] ?>"></i>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des dropdowns
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
});

// Modification du rôle utilisateur
function editUserRole(userId) {
    const roles = <?= json_encode(getRolesList()) ?>;
    const currentRole = document.querySelector(`[data-user-id="${userId}"] .user-role`).textContent;
    
    const newRole = prompt('Sélectionnez un nouveau rôle :', currentRole);
    if (newRole && roles.includes(newRole)) {
        updateUserRole(userId, newRole);
    }
}

function updateUserRole(userId, role) {
    fetch(`/api/users/${userId}/role`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({ role })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-user-id="${userId}"] .user-role`).textContent = role;
            showNotification('Rôle mis à jour avec succès', 'success');
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        showNotification(error.message, 'error');
    });
}

// Désactivation du compte utilisateur
function confirmDeactivateUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir désactiver ce compte utilisateur ?')) {
        fetch(`/api/users/${userId}/deactivate`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-user-id="${userId}"]`).remove();
                showNotification('Compte désactivé avec succès', 'success');
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            showNotification(error.message, 'error');
        });
    }
}
</script>
