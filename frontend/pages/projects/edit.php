<?php
// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get project ID
$projectId = (int)($_GET['id'] ?? 0);
if ($projectId <= 0) {
    header("Location: /projects?error=invalid_id");
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1>Edit Project</h1>
        <nav class="breadcrumb">
            <a href="/dashboard">Dashboard</a>
            <a href="/projects">Projects</a>
            <span id="project-title-breadcrumb">Loading...</span>
            <span>Edit</span>
        </nav>
    </div>

    <div id="error-container" class="alert alert-danger d-none"></div>
    <div id="loading-indicator" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p>Loading project data...</p>
    </div>

    <div id="edit-form-container" class="d-none">
        <!-- Form will be injected here by JavaScript -->
    </div>
</div>

<script>
// Utility functions
const utils = {
    escapeHtml: (unsafe) => {
        if (!unsafe) return '';
        return unsafe.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },
    
    showError: (message) => {
        const errorContainer = document.getElementById('error-container');
        errorContainer.textContent = message;
        errorContainer.classList.remove('d-none');
        window.scrollTo(0, 0);
    },
    
    handleApiError: (error) => {
        console.error('API Error:', error);
        utils.showError(error.message || 'An error occurred');
    }
};

class ProjectEditor {
    constructor(projectId) {
        this.projectId = projectId;
        this.init();
    }
    
    async init() {
        try {
            // Load all required data in parallel
            const [project, managers, users] = await Promise.all([
                this.fetchProject(),
                this.fetchManagers(),
                this.fetchUsers()
            ]);
            
            this.renderForm(project, managers, users);
            this.setupEventHandlers(project);
            
            // Hide loader and show form
            document.getElementById('loading-indicator').classList.add('d-none');
            document.getElementById('edit-form-container').classList.remove('d-none');
        } catch (error) {
            utils.handleApiError(error);
        }
    }
    
    async fetchProject() {
        const response = await fetch(`/backend/api/projects.php?id=${this.projectId}`);
        if (!response.ok) throw new Error('Failed to load project');
        
        const data = await response.json();
        if (!data.success || !data.data?.data) throw new Error('Invalid project data');
        
        return data.data.data;
    }
    
    async fetchManagers() {
        const response = await fetch('/backend/api/users.php?role=manager');
        
        if (!response.ok) throw new Error('Failed to load managers');
        
        const data = await response.json();
        // if (data.status !== 'success' || !Array.isArray(data.data)) throw new Error('Invalid managers data');
        
        return data.data;
    }
    
    async fetchUsers() {
        const response = await fetch('/backend/api/users.php');
        
        if (!response.ok) throw new Error('Failed to load users');
        
        const data = await response.json();        
        return data.data;
    }
    
    renderForm(project, managers, users) {
        // Update breadcrumb
        document.getElementById('project-title-breadcrumb').innerHTML = 
            `<a href="/projects/view?id=${project.id}">${utils.escapeHtml(project.title)}</a>`;
        
        // Render form HTML
        const formContainer = document.getElementById('edit-form-container');
        formContainer.innerHTML = `
            <form id="editProjectForm" class="card p-4">
                <input type="hidden" name="id" value="${project.id}">
                
                <!-- Project Info Section -->
                <div class="form-section mb-4">
                    <h2 class="mb-3">Project Information</h2>
                    
                    <div class="form-group mb-3">
                        <label class="form-label required">Title</label>
                        <input type="text" name="title" class="form-control" 
                               value="${utils.escapeHtml(project.title)}" required minlength="3">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label required">Description</label>
                        <textarea name="description" class="form-control" rows="4" required>${utils.escapeHtml(project.description)}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label required">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="${project.start_date}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label required">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="${project.end_date}" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Section -->
                <div class="form-section mb-4">
                    <h2 class="mb-3">Project Status</h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    ${['planning', 'in_progress', 'active', 'on_hold', 'completed', 'cancelled']
                                        .map(status => `
                                            <option value="${status}" ${project.status === status ? 'selected' : ''}>
                                                ${status.replace('_', ' ').toUpperCase()}
                                            </option>
                                        `).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Progress</label>
                                <input type="range" name="progress" class="form-range"
                                       min="0" max="100" value="${project.progress_percentage || 0}">
                                <div class="d-flex justify-content-between">
                                    <small>0%</small>
                                    <span class="progress-value">${project.progress_percentage || 0}%</span>
                                    <small>100%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Team Section -->
                <div class="form-section mb-4">
                    <h2 class="mb-3">Project Team</h2>
                    
                    <div class="form-group mb-3">
                        <label class="form-label required">Project Manager</label>
                        <select id="projectManager" name="manager_id" class="form-control" required>
                            ${managers.map(manager => `
                                <option value="${manager.id}" ${project.manager_id == manager.id ? 'selected' : ''}>
                                    ${utils.escapeHtml(manager.name)}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Team Members</label>
                        <select name="members[]" class="form-control" multiple>
                            ${users.map(user => `
                                <option value="${user.id}" ${project.members?.includes(user.id) ? 'selected' : ''}>
                                    ${utils.escapeHtml(user.name)}
                                </option>
                            `).join('')}
                        </select>
                        <small class="text-muted">Hold CTRL to select multiple members</small>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions d-flex justify-content-between mt-4">
                    <button type="button" id="deleteProjectBtn" class="btn btn-danger">
                        Delete Project
                    </button>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        `;
        
        // Initialize progress bar interaction
        const progressInput = formContainer.querySelector('input[name="progress"]');
        const progressValue = formContainer.querySelector('.progress-value');
        if (progressInput && progressValue) {
            progressInput.addEventListener('input', () => {
                progressValue.textContent = `${progressInput.value}%`;
            });
        }
    }
    
    setupEventHandlers(project) {
        const form = document.getElementById('editProjectForm');
        const deleteBtn = document.getElementById('deleteProjectBtn');
        
        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const formData = new FormData(form);
                const data = {
                    id: this.projectId,
                    title: formData.get('title'),
                    description: formData.get('description'),
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    status: formData.get('status'),
                    progress_percentage: parseInt(formData.get('progress')) || 0,
                    manager_id: parseInt(formData.get('manager_id')),
                    members: Array.from(formData.getAll('members[]')).map(Number)
                };
                
                const response = await fetch(`/backend/api/projects.php?id=${this.projectId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Update failed');
                }
                
                window.location.href = `/projects`;
            } catch (error) {
                utils.handleApiError(error);
            }
        });
        
        // Delete project
        deleteBtn.addEventListener('click', async () => {
            if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                try {
                    const response = await fetch(`/backend/api/projects.php?id=${this.projectId}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        }
                    });
                    
                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.message || 'Delete failed');
                    }
                    
                    window.location.href = '/projects';
                } catch (error) {
                    utils.handleApiError(error);
                }
            }
        });
    }
}

// Initialize the editor when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProjectEditor(<?= $projectId ?>);
});
</script>