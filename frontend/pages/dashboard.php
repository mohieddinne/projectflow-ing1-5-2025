<?php
if (!function_exists('requireAuthentication')) {
    require_once __DIR__ . '/../includes/functions.php';
}
requireAuthentication();
$user = getCurrentUser();
$pageTitle = "Tableau de bord";

// Fonctions utilitaires pour le formatage côté PHP (pour les tâches urgentes si besoin)
include_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ... (gardez le même CSS que précédemment) ... */
</style>

<div class="page-container">
    <div class="page-header">
        <h1>Tableau de bord</h1>
        <div class="date-filter">
            <select id="periodFilter" class="form-select">
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="quarter">Ce trimestre</option>
            </select>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-overview" id="statsOverview">
        <!-- Les cartes stats seront injectées ici -->
    </div>

    <div class="dashboard-content">
        <!-- Graphiques -->
        <div class="charts-section">
            <div class="card">
                <h2>Progression des projets</h2>
                <canvas id="projectsProgress"></canvas>
            </div>
            <div class="card">
                <h2>Répartition des tâches</h2>
                <canvas id="tasksDistribution"></canvas>
            </div>
        </div>

        <!-- Activités récentes -->
        <div class="recent-activities card">
            <h2>Activités récentes</h2>
            <div class="activity-timeline" id="activityTimeline">
                <!-- Les activités seront injectées ici -->
            </div>
        </div>

        <!-- Tâches urgentes -->
        <div class="urgent-tasks card">
            <h2>Tâches urgentes</h2>
            <div class="tasks-list" id="urgentTasksList">
                <!-- Les tâches urgentes seront injectées ici -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const API_URL = 'http://localhost:8000/backend/api/dashboard.php';

function getActivityIcon(type) {
    switch (type) {
        case 'task_created': return 'fas fa-plus-circle text-success';
        case 'task_completed': return 'fas fa-check-circle text-success';
        case 'task_updated': return 'fas fa-edit text-info';
        case 'task_assigned': return 'fas fa-user-plus text-primary';
        case 'comment_added': return 'fas fa-comment text-info';
        case 'project_created': return 'fas fa-folder-plus text-warning';
        case 'project_completed': return 'fas fa-flag-checkered text-success';
        default: return 'fas fa-bell text-secondary';
    }
}
function formatActivity(activity) {
    switch (activity.type) {
        case 'task_created':
            return `<strong>${activity.user_name}</strong> a créé la tâche <strong>${activity.task_title}</strong> dans le projet <strong>${activity.project_title}</strong>`;
        case 'task_completed':
            return `<strong>${activity.user_name}</strong> a terminé la tâche <strong>${activity.task_title}</strong> dans le projet <strong>${activity.project_title}</strong>`;
        case 'task_updated':
            return `<strong>${activity.user_name}</strong> a mis à jour la tâche <strong>${activity.task_title}</strong>`;
        case 'task_assigned':
            return `<strong>${activity.user_name}</strong> a assigné la tâche <strong>${activity.task_title}</strong> à <strong>${activity.assigned_to}</strong>`;
        case 'comment_added':
            return `<strong>${activity.user_name}</strong> a commenté sur la tâche <strong>${activity.task_title}</strong>: "${activity.comment}"`;
        case 'project_created':
            return `<strong>${activity.user_name}</strong> a créé le projet <strong>${activity.project_title}</strong>`;
        case 'project_completed':
            return `<strong>${activity.user_name}</strong> a marqué le projet <strong>${activity.project_title}</strong> comme terminé`;
        default:
            return "Activité inconnue";
    }
}
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return "à l'instant";
    if (diff < 3600) return `${Math.floor(diff/60)} minute${Math.floor(diff/60)>1?'s':''} plus tôt`;
    if (diff < 86400) return `${Math.floor(diff/3600)} heure${Math.floor(diff/3600)>1?'s':''} plus tôt`;
    if (diff < 2592000) return `${Math.floor(diff/86400)} jour${Math.floor(diff/86400)>1?'s':''} plus tôt`;
    if (diff < 31536000) return `${Math.floor(diff/2592000)} mois plus tôt`;
    return `${Math.floor(diff/31536000)} an${Math.floor(diff/31536000)>1?'s':''} plus tôt`;
}
function formatDate(dateString) {
    const d = new Date(dateString);
    return d.toLocaleDateString('fr-FR');
}
function isOverdue(dateString) {
    const due = new Date(dateString);
    const now = new Date();
    now.setHours(0,0,0,0);
    return due < now;
}
function formatDuration(minutes) {
    if (!minutes) return '0h';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h + 'h' + (m ? (' ' + m + 'min') : '');
}

let projectsChart, tasksChart;

function renderStats(stats) {
    document.getElementById('statsOverview').innerHTML = `    
        <div class="stat-card">
            <div class="stat-card__icon bg-warning">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-card__content">
                <h3>${stats.pending_tasks}</h3>
                <span>Tâches en cours</span>
            </div>
            <div class="stat-card__trend ${stats.tasks_trend >= 0 ? 'up' : 'down'}">
                <i class="fas ${stats.tasks_trend >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'}"></i>
                ${Math.abs(stats.tasks_trend)}%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon bg-info">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-card__content">
                <h3>${stats.completed_tasks}</h3>
                <span>Tâches terminées</span>
            </div>
            <div class="stat-card__trend neutral">
                <i class="fas fa-check"></i>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon bg-success">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-card__content">
                <h3>${stats.completion_rate}%</h3>
                <span>Taux d'avancement</span>
            </div>
            <div class="stat-card__trend ${stats.completion_rate >= 75 ? 'up' : (stats.completion_rate >= 50 ? 'neutral' : 'down')}">
                <i class="fas ${stats.completion_rate >= 75 ? 'fa-arrow-up' : (stats.completion_rate >= 50 ? 'fa-minus' : 'fa-arrow-down')}"></i>
            </div>
        </div>
    `;
}

function renderActivities(activities) {
    const timeline = document.getElementById('activityTimeline');
    
    if (!activities || !activities.length) {
        timeline.innerHTML = '<div class="text-center py-4 text-muted">Aucune activité récente</div>';
        return;
    }
    
    timeline.innerHTML = activities.map(activity => {
        // Déterminer le type d'entité et l'icône correspondante
        const entityType = activity.entity_type || 'unknown';
        const icon = getActivityIcon(entityType);
        const description = activity.description || 'Activité inconnue';
        
        return `
        <div class="activity-item d-flex mb-3">
            <div class="activity-icon me-3">
                <i class="${icon} fa-lg"></i>
            </div>
            <div class="activity-content flex-grow-1">
                <p class="mb-1">${description}</p>
                <div class="d-flex justify-content-between">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>
                        ${activity.user_name || 'Utilisateur inconnu'}
                    </small>
                    <small class="text-muted">${formatTimeAgo(activity.created_at)}</small>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function getActivityIcon(entityType) {
    const icons = {
        'user': 'fas fa-user-plus text-primary',
        'project': 'fas fa-folder-plus text-success',
        'task': 'fas fa-tasks text-info',
        'default': 'fas fa-circle-question text-secondary'
    };
    
    return icons[entityType] || icons.default;
}

function formatTimeAgo(timestamp) {
    if (!timestamp) return 'Date inconnue';
    
    const now = new Date();
    const date = new Date(timestamp);
    const diffMs = now - date;
    
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffMinutes < 1) return "à l'instant";
    if (diffMinutes < 60) return `il y a ${diffMinutes} min`;
    if (diffHours < 24) return `il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
    if (diffDays === 1) return 'hier';
    if (diffDays < 7) return `il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
    
    return date.toLocaleDateString('fr-FR');
}

function renderUrgentTasks(tasks) {
    const list = document.getElementById('urgentTasksList');
    if (!tasks.length) {
        list.innerHTML = '<p>Aucune tâche urgente.</p>';
        return;
    }
    list.innerHTML = tasks.map(task => `
        <div class="task-item">
            <div class="task-status">
                <span class="status-dot status-${task.status}"></span>
            </div>
            <div class="task-info">
                <h4>
                    <a href="/tasks/view?id=${task.id}">
                        ${task.title}
                    </a>
                </h4>
                <small>
                    Projet : 
                    <a href="/projects/view?id=${task.project_id}">
                        ${task.project_title}
                    </a>
                </small>
            </div>
            <div class="task-due">
                <span class="due-date ${isOverdue(task.due_date) ? 'overdue' : ''}">
                    ${formatDate(task.due_date)}
                </span>
            </div>
        </div>
    `).join('');
}

function renderCharts(projects, tasks) {
    // Projets
    const projectsCtx = document.getElementById('projectsProgress').getContext('2d');
    if (projectsChart) projectsChart.destroy();
    projectsChart = new Chart(projectsCtx, {
        type: 'bar',
        data: {
            labels: projects.labels,
            datasets: [{
                label: 'Progression (%)',
                data: projects.progress,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    // Tâches
    const tasksCtx = document.getElementById('tasksDistribution').getContext('2d');
    if (tasksChart) tasksChart.destroy();
    // Convert tasks.distribution object to array in the correct order
    const distributionArray = [
        tasks.distribution.todo ?? 0,
        tasks.distribution.in_progress ?? 0,
        tasks.distribution.review ?? 0,
        tasks.distribution.completed ?? 0
    ];
    tasksChart = new Chart(tasksCtx, {
        type: 'doughnut',
        data: {
            labels: ['À faire', 'En cours', 'En révision', 'Terminées'],
            datasets: [{
                data: distributionArray,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(54, 162, 235, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function loadDashboard() {
    fetch(API_URL)
        .then(res => res.json())
        .then(json => {
            if (json.status !== 'success') throw new Error('Erreur API');
            const data = json.data;
            renderStats(data.stats);
            renderCharts(data.projects, data.tasks);
            renderActivities(data.activities);
            renderUrgentTasks(data.urgent_tasks);
        })
        .catch(() => {
            document.getElementById('statsOverview').innerHTML = '<p>Erreur de chargement des statistiques.</p>';
            document.getElementById('activityTimeline').innerHTML = '';
            document.getElementById('urgentTasksList').innerHTML = '';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    const urlParams = new URLSearchParams(window.location.search);
    const period = urlParams.get('period') || 'week';
    document.getElementById('periodFilter').value = period;
    loadDashboard(period);

    // Filtre de période
    document.getElementById('periodFilter').addEventListener('change', function() {
        window.location.href = `/dashboard?period=${this.value}`;
    });
});
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>
