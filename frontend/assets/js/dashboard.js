/**
 * dashboard.js - Gestion du tableau de bord principal
 */

class Dashboard {
    constructor() {
        this.charts = new DashboardCharts();
        this.refreshInterval = 60000; // 1 minute
        this.init();
    }

    /**
     * Initialisation du tableau de bord
     */
    async init() {
        this.initElements();
        this.initEventListeners();
        await this.loadDashboardData();
        this.charts.initializeCharts();
        this.startAutoRefresh();
    }

    /**
     * Initialise les références aux éléments DOM
     */
    initElements() {
        this.projectCountElement = document.getElementById('projectCount');
        this.taskCountElement = document.getElementById('taskCount');
        this.completedTasksElement = document.getElementById('completedTasks');
        this.overdueTasksElement = document.getElementById('overdueTasks');
        this.projectListContainer = document.getElementById('recentProjects');
        this.taskListContainer = document.getElementById('activeTasks');
    }

    /**
     * Initialise les écouteurs d'événements
     */
    initEventListeners() {
        // Filtres de projet
        document.getElementById('projectFilter')?.addEventListener('change', (e) => {
            this.filterProjects(e.target.value);
        });

        // Tri des tâches
        document.getElementById('taskSort')?.addEventListener('change', (e) => {
            this.sortTasks(e.target.value);
        });

        // Rafraîchissement manuel
        document.getElementById('refreshDashboard')?.addEventListener('click', () => {
            this.refreshDashboard();
        });
    }

    /**
     * Charge les données du tableau de bord depuis l'API
     */
    async loadDashboardData() {
        try {
            const response = await fetch('/api/dashboard/data', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            if (!response.ok) {
                throw new Error('Erreur de chargement des données');
            }

            const data = await response.json();
            this.updateDashboardStats(data.stats);
            this.updateRecentProjects(data.recentProjects);
            this.updateActiveTasks(data.activeTasks);
            this.updateProgressBars(data.progress);

        } catch (error) {
            console.error('Erreur:', error);
            this.showErrorMessage('Erreur de chargement du tableau de bord');
        }
    }

    /**
     * Met à jour les statistiques générales
     */
    updateDashboardStats(stats) {
        this.projectCountElement.textContent = stats.totalProjects;
        this.taskCountElement.textContent = stats.totalTasks;
        this.completedTasksElement.textContent = stats.completedTasks;
        this.overdueTasksElement.textContent = stats.overdueTasks;

        // Mise à jour des indicateurs de tendance
        this.updateTrendIndicators(stats.trends);
    }

    /**
     * Met à jour la liste des projets récents
     */
    updateRecentProjects(projects) {
        if (!this.projectListContainer) return;

        this.projectListContainer.innerHTML = projects.map(project => `
            <div class="project-card">
                <div class="project-card__header">
                    <h3>${project.title}</h3>
                    <span class="badge badge-${project.status}">${project.status}</span>
                </div>
                <div class="project-card__progress">
                    <div class="progress-bar">
                        <div class="progress-bar__fill" style="width: ${project.progress}%"></div>
                    </div>
                    <span>${project.progress}%</span>
                </div>
                <div class="project-card__footer">
                    <span>${project.tasks_count} tâches</span>
                    <span>${project.due_date}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Met à jour la liste des tâches actives
     */
    updateActiveTasks(tasks) {
        if (!this.taskListContainer) return;

        this.taskListContainer.innerHTML = tasks.map(task => `
            <div class="task-item">
                <div class="task-item__status">
                    <span class="priority-dot priority--${task.priority}"></span>
                </div>
                <div class="task-item__content">
                    <h4>${task.title}</h4>
                    <p>${task.project_name}</p>
                </div>
                <div class="task-item__meta">
                    <span>${task.due_date}</span>
                    <span>${task.assigned_to}</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Met à jour les barres de progression
     */
    updateProgressBars(progress) {
        Object.entries(progress).forEach(([key, value]) => {
            const progressBar = document.querySelector(`#progress-${key}`);
            if (progressBar) {
                progressBar.style.width = `${value}%`;
                progressBar.setAttribute('aria-valuenow', value);
            }
        });
    }

    /**
     * Filtre les projets affichés
     */
    filterProjects(status) {
        const projects = document.querySelectorAll('.project-card');
        projects.forEach(project => {
            const projectStatus = project.querySelector('.badge').textContent;
            project.style.display = status === 'all' || projectStatus === status ? 'block' : 'none';
        });
    }

    /**
     * Trie les tâches affichées
     */
    sortTasks(criterion) {
        const tasks = Array.from(this.taskListContainer.children);
        tasks.sort((a, b) => {
            // Logique de tri selon le critère
            return this.getTaskSortValue(a, criterion) - this.getTaskSortValue(b, criterion);
        });
        tasks.forEach(task => this.taskListContainer.appendChild(task));
    }

    /**
     * Rafraîchit automatiquement le tableau de bord
     */
    startAutoRefresh() {
        setInterval(() => this.refreshDashboard(), this.refreshInterval);
    }

    /**
     * Rafraîchit manuellement le tableau de bord
     */
    async refreshDashboard() {
        await this.loadDashboardData();
        await this.charts.updateCharts();
        this.showSuccessMessage('Tableau de bord mis à jour');
    }

    /**
     * Affiche un message d'erreur
     */
    showErrorMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        document.querySelector('.dashboard-header').appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    /**
     * Affiche un message de succès
     */
    showSuccessMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;
        document.querySelector('.dashboard-header').appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
}

// Initialisation du tableau de bord
document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new Dashboard();
});
