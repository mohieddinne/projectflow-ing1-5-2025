/**
 * ProjectFlow - Main JavaScript file
 * Ce fichier contient les fonctionnalités JavaScript principales de l'application
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('ProjectFlow application initialized');
    
    // Initialiser les composants Bootstrap
    initBootstrapComponents();
    
    // Gérer la déconnexion
    setupLogout();
    
    // Gérer les filtres de période sur le tableau de bord
    setupPeriodFilter();
    
    // Gérer les confirmations de suppression
    setupDeleteConfirmations();
    
    // Gérer les notifications
    setupNotifications();
    
    // Gérer le mode sombre
    setupDarkMode();
});

/**
 * Initialise les composants Bootstrap
 */
function initBootstrapComponents() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialiser les toasts
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });
}

/**
 * Configure le bouton de déconnexion
 */
function setupLogout() {
    const logoutBtn = document.querySelector('.logout-btn');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Afficher une confirmation
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                // Envoyer une requête de déconnexion
                fetch('/backend/api/auth/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer les données utilisateur du localStorage
                        localStorage.removeItem('user');
                        
                        // Rediriger vers la page de connexion
                        window.location.href = '/login';
                    } else {
                        showToast('Erreur lors de la déconnexion', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showToast('Erreur lors de la déconnexion', 'danger');
                });
            }
        });
    }
}

/**
 * Configure le filtre de période sur le tableau de bord
 */
function setupPeriodFilter() {
    const periodFilter = document.getElementById('periodFilter');
    
    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            window.location.href = `/dashboard?period=${this.value}`;
        });
    }
}

/**
 * Configure les confirmations de suppression
 */
function setupDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-btn, [data-confirm]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Configure le système de notifications
 */
function setupNotifications() {
    const notificationBtn = document.querySelector('.notification-btn');
    
    if (notificationBtn) {
        // Charger les notifications
        loadNotifications();
        
        // Actualiser les notifications toutes les minutes
        setInterval(loadNotifications, 60000);
    }
}

/**
 * Charge les notifications depuis l'API
 */
function loadNotifications() {
    const notificationBtn = document.querySelector('.notification-btn');
    const notificationBadge = document.querySelector('.notification-badge');
    const notificationList = document.querySelector('.notification-list');
    
    if (notificationBtn && notificationList) {
        fetch('/backend/api/notifications/get.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le badge
                if (notificationBadge) {
                    const unreadCount = data.notifications.filter(n => !n.read).length;
                    notificationBadge.textContent = unreadCount;
                    notificationBadge.style.display = unreadCount > 0 ? 'block' : 'none';
                }
                
                // Mettre à jour la liste
                if (data.notifications.length > 0) {
                    notificationList.innerHTML = '';
                    
                    data.notifications.forEach(notification => {
                        const item = document.createElement('li');
                        item.innerHTML = `
                            <a class="dropdown-item ${notification.read ? 'read' : 'unread'}" href="${notification.link || '#'}">
                                <div class="notification-icon">
                                    <i class="${getNotificationIcon(notification.type)}"></i>
                                </div>
                                <div class="notification-content">
                                    <p>${notification.message}</p>
                                    <small>${formatTimeAgo(notification.created_at)}</small>
                                </div>
                            </a>
                        `;
                        notificationList.appendChild(item);
                    });
                } else {
                    notificationList.innerHTML = '<li class="dropdown-item text-center">Aucune notification</li>';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}

/**
 * Retourne l'icône correspondant au type de notification
 */
function getNotificationIcon(type) {
    switch (type) {
        case 'task_assigned':
            return 'fas fa-tasks text-primary';
        case 'comment_added':
            return 'fas fa-comment text-info';
        case 'deadline_approaching':
            return 'fas fa-clock text-warning';
        case 'project_completed':
            return 'fas fa-check-circle text-success';
        default:
            return 'fas fa-bell text-secondary';
    }
}

/**
 * Configure le mode sombre
 */
function setupDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    if (darkModeToggle) {
        // Vérifier la préférence enregistrée
        const darkMode = localStorage.getItem('darkMode') === 'true';
        
        // Appliquer le mode sombre si nécessaire
        if (darkMode) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Gérer le changement de mode
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'false');
            }
        });
    }
}

/**
 * Affiche un toast (notification)
 */
function showToast(message, type = 'info') {
    // Créer le conteneur de toasts s'il n'existe pas
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Créer le toast
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Ajouter le toast au conteneur
    toastContainer.appendChild(toastEl);
    
    // Initialiser et afficher le toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Supprimer le toast du DOM après sa disparition
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

/**
 * Formate une date en "il y a X temps"
 */
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) {
        return 'à l\'instant';
    } else if (diffMin < 60) {
        return `il y a ${diffMin} minute${diffMin > 1 ? 's' : ''}`;
    } else if (diffHour < 24) {
        return `il y a ${diffHour} heure${diffHour > 1 ? 's' : ''}`;
    } else if (diffDay < 30) {
        return `il y a ${diffDay} jour${diffDay > 1 ? 's' : ''}`;
    } else {
        // Formater la date pour les dates plus anciennes
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    }
}

/**
 * Formate un nombre avec séparateur de milliers
 */
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

/**
 * Formate une durée en heures et minutes
 */
function formatDuration(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours === 0) {
        return `${mins} min`;
    } else if (mins === 0) {
        return `${hours} h`;
    } else {
        return `${hours} h ${mins} min`;
    }
}

/**
 * Vérifie si une date est dépassée
 */
function isOverdue(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    
    // Comparer uniquement les dates (sans l'heure)
    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const nowOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    return dateOnly < nowOnly;
}

/**
 * Formate une date au format français
 */
function formatDate(dateString, includeTime = false) {
    const date = new Date(dateString);
    
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleDateString('fr-FR', options);
}
