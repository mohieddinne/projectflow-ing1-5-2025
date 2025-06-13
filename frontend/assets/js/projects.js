/**
 * projects.js - Gestion des projets
 * Implémente les fonctionnalités CRUD et interactions pour les projets
 */

class ProjectManager {
    constructor() {
        this.baseUrl = '/api/projects';
        this.currentProject = null;
        this.initializeEventListeners();
    }

    /**
     * Initialise les écouteurs d'événements
     */
    initializeEventListeners() {
        // Formulaire de création de projet
        const createForm = document.getElementById('createProjectForm');
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createProject(new FormData(createForm));
            });
        }

        // Filtres et recherche
        document.getElementById('projectFilter')?.addEventListener('change', (e) => {
            this.filterProjects(e.target.value);
        });

        document.getElementById('projectSearch')?.addEventListener('input', (e) => {
            this.searchProjects(e.target.value);
        });

        // Tri des projets
        document.getElementById('projectSort')?.addEventListener('change', (e) => {
            this.sortProjects(e.target.value);
        });
    }

    /**
     * Crée un nouveau projet
     */
    async createProject(formData) {
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
                this.showSuccessMessage('Projet créé avec succès');
                this.refreshProjectList();
                this.closeModal('createProjectModal');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Charge les détails d'un projet
     */
    async loadProjectDetails(projectId) {
        try {
            const response = await fetch(`${this.baseUrl}/${projectId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            if (!response.ok) throw new Error('Erreur de chargement du projet');

            const project = await response.json();
            this.currentProject = project;
            this.displayProjectDetails(project);
            this.loadProjectTasks(projectId);
            this.loadProjectMembers(projectId);
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Met à jour un projet
     */
    async updateProject(projectId, data) {
        try {
            const response = await fetch(`${this.baseUrl}/${projectId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) throw new Error('Erreur de mise à jour');

            this.showSuccessMessage('Projet mis à jour avec succès');
            this.refreshProjectList();
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Supprime un projet
     */
    async deleteProject(projectId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) return;

        try {
            const response = await fetch(`${this.baseUrl}/${projectId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            if (!response.ok) throw new Error('Erreur de suppression');

            this.showSuccessMessage('Projet supprimé avec succès');
            this.refreshProjectList();
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    /**
     * Affiche les détails d'un projet
     */
    displayProjectDetails(project) {
        const container = document.getElementById('projectDetails');
        if (!container) return;

        container.innerHTML = `
            <div class="project-header">
                <h2>${project.title}</h2>
                <span class="badge badge-${project.status}">${project.status}</span>
            </div>
            <div class="project-info">
                <p>${project.description}</p>
                <div class="project-meta">
                    <span>Début: ${project.start_date}</span>
                    <span>Fin: ${project.end_date}</span>
                    <span>Progression: ${project.progress}%</span>
                </div>
            </div>
            <div class="project-progress">
                <div class="progress-bar">
                    <div class="progress-bar__fill" style="width: ${project.progress}%"></div>
                </div>
            </div>
        `;
    }

    /**
     * Filtre les projets
     */
    filterProjects(status) {
        const projects = document.querySelectorAll('.project-card');
        projects.forEach(project => {
            const projectStatus = project.dataset.status;
            project.style.display = status === 'all' || status === projectStatus ? 'block' : 'none';
        });
    }

    /**
     * Recherche dans les projets
     */
    searchProjects(query) {
        const projects = document.querySelectorAll('.project-card');
        const searchTerm = query.toLowerCase();

        projects.forEach(project => {
            const title = project.querySelector('.project-title').textContent.toLowerCase();
            const description = project.querySelector('.project-description').textContent.toLowerCase();
            const isVisible = title.includes(searchTerm) || description.includes(searchTerm);
            project.style.display = isVisible ? 'block' : 'none';
        });
    }

    /**
     * Trie les projets
     */
    sortProjects(criterion) {
        const container = document.querySelector('.projects-grid');
        const projects = Array.from(container.children);

        projects.sort((a, b) => {
            const valueA = this.getSortValue(a, criterion);
            const valueB = this.getSortValue(b, criterion);
            return valueA > valueB ? 1 : -1;
        });

        projects.forEach(project => container.appendChild(project));
    }

    /**
     * Utilitaires
     */
    showSuccessMessage(message) {
        window.app.showNotification({ message, type: 'success' });
    }

    showErrorMessage(message) {
        window.app.showNotification({ message, type: 'error' });
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }

    refreshProjectList() {
        window.location.reload();
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.projectManager = new ProjectManager();
});
