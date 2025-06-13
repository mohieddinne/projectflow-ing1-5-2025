/**
 * charts.js - Gestion des graphiques pour le tableau de bord
 * Utilise Chart.js pour les visualisations
 */

class DashboardCharts {
    constructor() {
        this.charts = {};
        this.colors = {
            primary: '#1976d2',
            success: '#4caf50',
            warning: '#ff9800',
            danger: '#f44336',
            gray: '#757575'
        };
    }

    /**
     * Initialise tous les graphiques du tableau de bord
     */
    async initializeCharts() {
        await this.loadChartData();
        this.initProjectProgress();
        this.initTaskDistribution();
        this.initTimelineChart();
        this.initUserActivity();
    }

    /**
     * Charge les données pour les graphiques depuis l'API
     */
    async loadChartData() {
        try {
            const response = await fetch('/api/dashboard/stats');
            this.chartData = await response.json();
        } catch (error) {
            console.error('Erreur de chargement des données:', error);
            this.chartData = this.getDefaultData();
        }
    }

    /**
     * Graphique de progression des projets
     */
    initProjectProgress() {
        const ctx = document.getElementById('projectProgressChart');
        if (!ctx) return;

        this.charts.projectProgress = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Complété', 'En cours', 'En attente', 'En retard'],
                datasets: [{
                    data: [
                        this.chartData.projectStats.completed,
                        this.chartData.projectStats.inProgress,
                        this.chartData.projectStats.pending,
                        this.chartData.projectStats.overdue
                    ],
                    backgroundColor: [
                        this.colors.success,
                        this.colors.primary,
                        this.colors.warning,
                        this.colors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Progression des Projets'
                    }
                }
            }
        });
    }

    /**
     * Graphique de distribution des tâches
     */
    initTaskDistribution() {
        const ctx = document.getElementById('taskDistributionChart');
        if (!ctx) return;

        this.charts.taskDistribution = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: this.chartData.taskStats.users,
                datasets: [{
                    label: 'Tâches assignées',
                    data: this.chartData.taskStats.counts,
                    backgroundColor: this.colors.primary
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribution des Tâches par Utilisateur'
                    }
                }
            }
        });
    }

    /**
     * Graphique de timeline des projets
     */
    initTimelineChart() {
        const ctx = document.getElementById('timelineChart');
        if (!ctx) return;

        this.charts.timeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.chartData.timeline.dates,
                datasets: [{
                    label: 'Tâches complétées',
                    data: this.chartData.timeline.completed,
                    borderColor: this.colors.success,
                    fill: false
                }, {
                    label: 'Nouvelles tâches',
                    data: this.chartData.timeline.new,
                    borderColor: this.colors.primary,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Activité du Projet'
                    }
                }
            }
        });
    }

    /**
     * Graphique d'activité utilisateur
     */
    initUserActivity() {
        const ctx = document.getElementById('userActivityChart');
        if (!ctx) return;

        this.charts.userActivity = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Création', 'Modification', 'Commentaires', 'Validation', 'Révision'],
                datasets: [{
                    label: 'Activité utilisateur',
                    data: this.chartData.userActivity,
                    backgroundColor: 'rgba(25, 118, 210, 0.2)',
                    borderColor: this.colors.primary,
                    pointBackgroundColor: this.colors.primary
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Répartition des Activités'
                    }
                }
            }
        });
    }

    /**
     * Met à jour les graphiques avec de nouvelles données
     */
    async updateCharts() {
        await this.loadChartData();
        Object.values(this.charts).forEach(chart => {
            chart.update();
        });
    }

    /**
     * Données par défaut en cas d'erreur
     */
    getDefaultData() {
        return {
            projectStats: {
                completed: 0,
                inProgress: 0,
                pending: 0,
                overdue: 0
            },
            taskStats: {
                users: [],
                counts: []
            },
            timeline: {
                dates: [],
                completed: [],
                new: []
            },
            userActivity: [0, 0, 0, 0, 0]
        };
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    const dashboardCharts = new DashboardCharts();
    dashboardCharts.initializeCharts();

    // Rafraîchissement automatique toutes les 5 minutes
    setInterval(() => dashboardCharts.updateCharts(), 300000);
});
