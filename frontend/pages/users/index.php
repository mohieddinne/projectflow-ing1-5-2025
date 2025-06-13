<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Utilisateurs</h1>
        <?php if (hasPermission('create_user')): ?>
        <a href="/users/create" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Nouvel utilisateur
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
        <?= $_SESSION['flash']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); endif; ?>

    <!-- Filter Form -->
    <form id="filterForm" class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="role" class="form-label">Rôle</label>
                    <select name="role" id="role" class="form-select">
                        <option value="all">Tous les rôles</option>
                        <option value="admin">Administrateur</option>
                        <option value="manager">Manager</option>
                        <option value="user">Utilisateur</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="sort" class="form-label">Trier par</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="newest">Plus récents</option>
                        <option value="oldest">Plus anciens</option>
                        <option value="name">Nom</option>
                        <option value="role">Rôle</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Users Container -->
    <div id="usersContainer">
        <div class="text-center py-5">
            <span class="spinner-border"></span> Chargement des utilisateurs...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const usersContainer = document.getElementById('usersContainer');
    
    // User Manager Object
    const userManager = {
        // Fetch users from API
        fetchUsers: async function(params = {}) {
            const url = new URL('/backend/api/users.php', window.location.origin);
            
            // Add params to URL
            Object.entries(params).forEach(([key, value]) => {
                if (value) url.searchParams.append(key, value);
            });
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.status === 'success' && data.data) {
                    return data.data;
                } else {
                    throw new Error(data.message || 'Failed to fetch users');
                }
            } catch (error) {
                console.error('Error fetching users:', error);
                throw error;
            }
        },
        
        // Render users list
        renderUsers: function(users) {
            if (users.length === 0) {
                return `
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>Aucun utilisateur trouvé</h5>
                            <p class="text-muted">Aucun utilisateur avec les filtres sélectionnés n'a été trouvé.</p>
                            ${this.hasPermission('create_user') ? `
                            <a href="/users/create" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Créer un utilisateur
                            </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
            
            return `
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${users.map(user => this.renderUserRow(user)).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        },
        
        // Render single user row
        renderUserRow: function(user) {
            return `
                <tr>
                    <td>${this.escapeHtml(user.id)}</td>
                    <td>${this.escapeHtml(user.name)}</td>
                    <td>${this.escapeHtml(user.email)}</td>
                    <td>${this.renderRoleBadge(user.role)}</td>
                    <td>${this.formatDate(user.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            ${this.hasPermission('edit_user') ? `
                            <a href="/users/edit?id=${user.id}" class="btn btn-outline-secondary" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            ` : ''}
                            ${this.hasPermission('delete_user') ? `
                            <button type="button" class="btn btn-outline-danger" title="Supprimer"
                                onclick="userManager.deleteUser(${user.id}, '${this.escapeHtml(user.name)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        },
        
        // Role badge rendering
        renderRoleBadge: function(role) {
            const badges = {
                'admin': { class: 'bg-danger', label: 'Admin' },
                'manager': { class: 'bg-info text-dark', label: 'Manager' },
                'user': { class: 'bg-secondary', label: 'Utilisateur' }
            };
            
            const badge = badges[role] || { class: 'bg-light text-dark', label: role };
            return `<span class="badge ${badge.class}">${this.escapeHtml(badge.label)}</span>`;
        },
        
        // Delete user
        deleteUser: async function(id, name) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${name}" ?`)) return;
            
            try {
                const response = await fetch(`/backend/api/users.php?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    this.showAlert('Utilisateur supprimé avec succès!', 'success');
                    this.loadAndRenderUsers();
                } else {
                    throw new Error(data.message || 'Échec de la suppression');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                this.showAlert(`Erreur lors de la suppression: ${error.message}`, 'danger');
            }
        },
        
        // Load and render users based on form filters
        loadAndRenderUsers: async function() {
            const formData = new FormData(filterForm);
            const params = {
                role: formData.get('role') === 'all' ? '' : formData.get('role'),
                sort: formData.get('sort')
            };
            
            usersContainer.innerHTML = `
                <div class="text-center py-5">
                    <span class="spinner-border"></span> Chargement des utilisateurs...
                </div>
            `;
            
            try {
                const users = await this.fetchUsers(params);
                usersContainer.innerHTML = this.renderUsers(users);
            } catch (error) {
                usersContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur lors du chargement des utilisateurs: ${this.escapeHtml(error.message)}
                    </div>
                `;
            }
        },
        
        // Show alert message
        showAlert: function(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show mb-4`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alertDiv, container.children[1]);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        },
        
        // Helper functions
        escapeHtml: function(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        formatDate: function(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR');
        },
        
        hasPermission: function(permission) {
            // This should be replaced with actual permission check logic
            // For now, we'll assume it's available from PHP
            return true;
        }
    };
    
    // Initialize form with current values from URL
    function initFormFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('role')) {
            filterForm.querySelector('#role').value = urlParams.get('role');
        }
        
        if (urlParams.has('sort')) {
            filterForm.querySelector('#sort').value = urlParams.get('sort');
        }
    }
    
    // Form submission handler
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        userManager.loadAndRenderUsers();
        
        // Update URL without reloading the page
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        if (formData.get('role') !== 'all') {
            params.set('role', formData.get('role'));
        }
        
        if (formData.get('sort') !== 'newest') {
            params.set('sort', formData.get('sort'));
        }
        
        const newUrl = params.toString() ? `${window.location.pathname}?${params}` : window.location.pathname;
        window.history.pushState({}, '', newUrl);
    });
    
    // Form reset handler
    filterForm.addEventListener('reset', function() {
        setTimeout(() => {
            filterForm.dispatchEvent(new Event('submit'));
        }, 0);
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', function() {
        initFormFromUrl();
        userManager.loadAndRenderUsers();
    });
    
    // Initialize
    initFormFromUrl();
    userManager.loadAndRenderUsers();
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/footer.php'; ?>