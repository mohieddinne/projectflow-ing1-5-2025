/**
 * tasks.js - Gestion des tâches
 * Implémente les fonctionnalités CRUD et interactions pour les tâches
 */

class TaskManager {
    constructor() {
        this.baseUrl = '/api/tasks';
        this.currentProjectId = null;
        this.draggedTask = null;
        this.init();
    }

    /**
     * Initialisation du gestionnaire de tâches
     */
    init() {
        this.initializeEventListeners();
        this.initializeDragAndDrop();
        this.currentProjectId = this.getProjectIdFromUrl();
    }

    /**
     * Initialise les écouteurs d'événements
     */
    initializeEventListeners() {
        // Formulaire de création de tâche
        const createTaskForm = document.getElementById('createTaskForm');
        if (createTaskForm) {
            createTaskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createTask(new FormData(createTaskForm));
            });
        }

        // Filtres de tâches
        document.getElementById('taskFilter')?.addEventListener('change', (e) => {
            this.filterTasks(e.target.value);
        });

        // Recherche de tâches
        document.getElementById('taskSearch')?.addEventListener('input', (e) => {
            this.searchTasks(e.target.value);
        });

        // Changement de statut
        document.querySelectorAll('.task-status-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const taskId = e.target.closest('.task-item').dataset.taskId;
                this.updateTaskStatus(taskId, e.target.value);
            });
        });
    }

    /**
     * Initialise le drag and drop pour les tâches
     */
    initializeDragAndDrop() {
        const taskColumns = document.querySelectorAll('.task-column');
        
        taskColumns.forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
                column.classList.add('drag-over');
            });

            column.addEventListener('dragleave', () => {
                column.classList.remove('drag-over');
            });

            column.addEventListener('drop', (e) => {
                e.preventDefault();
                column.classList.remove('drag-over');
                const taskId = this.draggedTask.dataset.taskId;
                const newStatus = column.dataset.status;
                this.updateTaskStatus(taskId, newStatus);
            });
        });

        document.querySelectorAll('.task-item').forEach(task => {
            task.setAttribute('draggable', true);
            task.addEventListener('dragstart', (e) => {
                this.draggedTask = task;
                task.classList.add('dragging');
            });

            task.addEventListener('dragend', () => {
                task.classList.remove('dragging');
            });
        });
    }

    /**
     * Crée une nouvelle tâche
     */
    async createTask(formData) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            const data = await response.json();

            if (response.ok) {
                this.showSuccessMessage('Tâche créée avec succès');
                this.refreshTaskList();
                this.closeModal('createTaskModal');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Met à jour le statut d'une tâche
     */
    async updateTaskStatus(taskId, newStatus) {
        try {
            const response = await fetch(`${this.baseUrl}/${taskId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify({ status: newStatus })
            });

            if (!response.ok) throw new Error('Erreur de mise à jour du statut');

            this.showSuccessMessage('Statut mis à jour');
            this.updateTaskElement(taskId, newStatus);
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Assigne une tâche à un utilisateur
     */
    async assignTask(taskId, userId) {
        try {
            const response = await fetch(`${this.baseUrl}/${taskId}/assign`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify({ user_id: userId })
            });

            if (!response.ok) throw new Error('Erreur d\'assignation');

            this.showSuccessMessage('Tâche assignée avec succès');
            this.refreshTaskList();
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Filtre les tâches selon différents critères
     */
    filterTasks(criteria) {
        const tasks = document.querySelectorAll('.task-item');
        tasks.forEach(task => {
            const matches = this.matchesFilterCriteria(task, criteria);
            task.style.display = matches ? 'flex' : 'none';
        });
    }

    /**
     * Recherche dans les tâches
     */
    searchTasks(query) {
        const tasks = document.querySelectorAll('.task-item');
        const searchTerm = query.toLowerCase();

        tasks.forEach(task => {
            const title = task.querySelector('.task-title').textContent.toLowerCase();
            const description = task.querySelector('.task-description').textContent.toLowerCase();
            const isVisible = title.includes(searchTerm) || description.includes(searchTerm);
            task.style.display = isVisible ? 'flex' : 'none';
        });
    }

    /**
     * Utilitaires
     */
    getProjectIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('project_id');
    }

    showSuccessMessage(message) {
        window.app.showNotification({ message, type: 'success' });
    }

    showErrorMessage(message) {
        window.app.showNotification({ message, type: 'error' });
    }

    refreshTaskList() {
        window.location.reload();
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.taskManager = new TaskManager();
});
