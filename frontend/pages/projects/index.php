<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/header.php'; ?>

<div class="container mt-4">
  <h1 class="mb-4">Projets</h1>
  <a href="/projects/create" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Créer un projet
  </a>
  
  <form id="filterForm" class="form-inline mb-4">
    <label for="status" class="mr-2">Statut:</label>
    <select id="status" name="status" class="form-control mr-3">
      <option value="">Tous</option>
      <option value="planning">Planification</option>
      <option value="in_progress">En cours</option>
      <option value="active">Actif</option>
      <option value="on_hold">En attente</option>
      <option value="completed">Terminé</option>
      <option value="cancelled">Annulé</option>
    </select>

    <label for="sort" class="mr-2">Trier par:</label>
    <select id="sort" name="sort" class="form-control">
      <option value="latest">Les plus récents</option>
      <option value="oldest">Les plus anciens</option>
      <option value="progress_desc">Progression (décroissant)</option>
      <option value="progress_asc">Progression (croissant)</option>
    </select>
  </form>

  <div class="row" id="projects-container">
    <!-- Les projets seront injectés ici par JS -->
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const statusSelect = document.getElementById('status');
  const sortSelect = document.getElementById('sort');
  const projectsContainer = document.getElementById('projects-container');

  function fetchAndDisplayProjects() {
    const status = statusSelect.value;
    const sort = sortSelect.value;

    const url = new URL('/backend/api/projects.php', window.location.origin);
    if (status) url.searchParams.append('status', status);
    if (sort) url.searchParams.append('sort', sort);

    projectsContainer.innerHTML = `<div class="col-12 text-center py-5"><span class="spinner-border"></span> Chargement...</div>`;

    fetch(url)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.data && data.data.data.length > 0) {
          renderProjects(data.data.data);
        } else {
          projectsContainer.innerHTML = `
            <div class="col-12">
              <div class="card">
                <div class="card-body text-center py-5">
                  <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                  <h5>Aucun projet trouvé</h5>
                </div>
              </div>
            </div>
          `;
        }
      })
      .catch(() => {
        projectsContainer.innerHTML = `
          <div class="col-12">
            <div class="alert alert-danger text-center">Erreur lors du chargement des projets.</div>
          </div>
        `;
      });
  }

  function renderProjects(projects) {
    projectsContainer.innerHTML = projects.map(project => `
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 border-${getStatusColor(project.status)}">
          <div class="card-header bg-${getStatusColor(project.status)}-light">
            <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
              <a href="/projects/view?id=${project.id}" class="text-decoration-none text-reset">
                ${escapeHtml(project.title)}
              </a>
              <span class="badge bg-${getStatusColor(project.status)}">
                ${getStatusText(project.status)}
              </span>
            </h5>
          </div>
          <div class="card-body">
            <p class="card-text">${truncateText(project.description, 150)}</p>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <small>Date début: ${formatDate(project.start_date)}</small>
                <small>Date fin: ${formatDate(project.end_date)}</small>
              </div>
              ${isOverdue(project.end_date) ? `<div class="alert alert-danger py-1">Projet en retard</div>` : ''}
            </div>
            <div class="progress mb-3">
              <div class="progress-bar bg-${getStatusColor(project.status)}" 
                   role="progressbar" 
                   style="width: ${project.progress || 0}%" 
                   aria-valuenow="${project.progress || 0}" 
                   aria-valuemin="0" 
                   aria-valuemax="100">
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="btn-group" role="group">
              <a href="/projects/edit?id=${project.id}" class="btn btn-sm btn-outline-secondary" title="Modifier">
                <i class="fas fa-edit"></i>
              </a>
              <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="deleteProject(${project.id})">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  }

  // Helper functions
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  function truncateText(text, length = 100) {
    if (!text) return '';
    return text.length > length ? text.slice(0, length) + '...' : text;
  }
  
  function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR');
  }
  
  function isOverdue(dateStr) {
    if (!dateStr) return false;
    const date = new Date(dateStr);
    const today = new Date();
    today.setHours(0,0,0,0);
    return date < today;
  }
  
  function getStatusText(status) {
    const statusMap = {
      planning: 'Planification',
      in_progress: 'En cours',
      active: 'Actif',
      on_hold: 'En attente',
      completed: 'Terminé',
      cancelled: 'Annulé'
    };
    return statusMap[status] || status;
  }
  
  function getStatusColor(status) {
    const colorMap = {
      planning: 'secondary',
      in_progress: 'info',
      active: 'primary',
      on_hold: 'warning',
      completed: 'success',
      cancelled: 'danger'
    };
    return colorMap[status] || 'secondary';
  }

  // Fonction de suppression
  window.deleteProject = function(id) {
    if (!confirm('Voulez-vous vraiment supprimer ce projet ?')) return;

    fetch(`/backend/api/projects.php?id=${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Projet supprimé avec succès.');
        fetchAndDisplayProjects();
      } else {
        alert('Erreur lors de la suppression : ' + (data.message || ''));
      }
    })
    .catch(() => alert('Erreur lors de la suppression.'));
  }

  // Charger au démarrage
  fetchAndDisplayProjects();

  // Écouter les changements sur les filtres
  statusSelect.addEventListener('change', fetchAndDisplayProjects);
  sortSelect.addEventListener('change', fetchAndDisplayProjects);
});
</script>

<style>
/* Styles pour les couleurs de statut */
.bg-secondary-light {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.bg-info-light {
  background-color: #e7f6f8;
  border-bottom: 1px solid #b6e0f3;
}
.bg-primary-light {
  background-color: #e7f1fd;
  border-bottom: 1px solid #b3d1ff;
}
.bg-warning-light {
  background-color: #fff8e6;
  border-bottom: 1px solid #ffeeba;
}
.bg-success-light {
  background-color: #e8f5e9;
  border-bottom: 1px solid #c3e6cb;
}
.bg-danger-light {
  background-color: #fde8e8;
  border-bottom: 1px solid #f5c6cb;
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/includes/footer.php'; ?>