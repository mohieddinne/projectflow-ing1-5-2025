<?php
/**
 * Template pour l'affichage des statistiques
 * @param array $stats Les données statistiques
 * @param string $period La période sélectionnée (week|month|quarter|year)
 */
?>

<div class="statistics-widget">
    <!-- En-tête avec sélecteur de période -->
    <div class="widget-header">
        <h3><?= $stats['title'] ?? 'Statistiques' ?></h3>
        <select class="period-selector" onchange="updateStats(this.value)">
            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Cette semaine</option>
            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Ce mois</option>
            <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Ce trimestre</option>
            <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Cette année</option>
        </select>
    </div>

    <!-- Indicateurs clés -->
    <div class="key-metrics">
        <div class="metric-card">
            <div class="metric-icon bg-primary">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value"><?= $stats['total_tasks'] ?></span>
                <span class="metric-label">Tâches totales</span>
                <span class="metric-trend <?= $stats['tasks_trend'] >= 0 ? 'up' : 'down' ?>">
                    <?= abs($stats['tasks_trend']) ?>%
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value"><?= $stats['completion_rate'] ?>%</span>
                <span class="metric-label">Taux de complétion</span>
                <div class="progress-mini">
                    <div class="progress-bar" style="width: <?= $stats['completion_rate'] ?>%"></div>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="metric-data">
                <span class="metric-value"><?= formatDuration($stats['avg_completion_time']) ?></span>
                <span class="metric-label">Temps moyen</span>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="stats-charts">
        <!-- Graphique d'évolution -->
        <div class="chart-container">
            <canvas id="progressChart"></canvas>
        </div>

        <!-- Répartition par statut -->
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- Tableau récapitulatif -->
    <div class="stats-table">
        <table>
            <thead>
                <tr>
                    <th>Statut</th>
                    <th>Nombre</th>
                    <th>Pourcentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($stats['status_distribution'] as $status => $data): ?>
                    <tr>
                        <td>
                            <span class="status-dot status-<?= $status ?>"></span>
                            <?= getStatusLabel($status) ?>
                        </td>
                        <td><?= $data['count'] ?></td>
                        <td><?= number_format($data['percentage'], 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration des graphiques
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    const statusCtx = document.getElementById('statusChart').getContext('2d');

    // Graphique d'évolution
    new Chart(progressCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($stats['progress_labels']) ?>,
            datasets: [{
                label: 'Progression',
                data: <?= json_encode($stats['progress_data']) ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Graphique de répartition
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(<?= json_encode($stats['status_distribution']) ?>).map(getStatusLabel),
            datasets: [{
                data: Object.values(<?= json_encode($stats['status_distribution']) ?>).map(d => d.count),
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 206, 86)',
                    'rgb(75, 192, 192)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

// Mise à jour des statistiques
function updateStats(period) {
    fetch(`/api/statistics?period=${period}`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        }
    })
    .then(response => response.json())
    .then(data => {
        // Mise à jour des métriques
        updateMetrics(data);
        // Mise à jour des graphiques
        updateCharts(data);
        // Mise à jour du tableau
        updateTable(data.status_distribution);
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour des statistiques:', error);
    });
}

function updateMetrics(data) {
    // Mise à jour des indicateurs clés
    document.querySelectorAll('.metric-value').forEach(el => {
        const key = el.dataset.key;
        if (data[key] !== undefined) {
            el.textContent = key.includes('time') ? formatDuration(data[key]) : data[key];
        }
    });
}

function updateCharts(data) {
    // Mise à jour des graphiques via Chart.js
    const charts = Chart.getChart('progressChart');
    if (charts) {
        charts.data.labels = data.progress_labels;
        charts.data.datasets[0].data = data.progress_data;
        charts.update();
    }
}

function updateTable(distribution) {
    const tbody = document.querySelector('.stats-table tbody');
    tbody.innerHTML = '';
    
    Object.entries(distribution).forEach(([status, data]) => {
        tbody.innerHTML += `
            <tr>
                <td>
                    <span class="status-dot status-${status}"></span>
                    ${getStatusLabel(status)}
                </td>
                <td>${data.count}</td>
                <td>${data.percentage.toFixed(1)}%</td>
            </tr>
        `;
    });
}
</script>
