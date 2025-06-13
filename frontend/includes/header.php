<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'ProjectFlow'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/frontend/assets/css/style.css">
    
    <!-- Dashboard CSS - chargé sur toutes les pages pour simplifier -->
    <link rel="stylesheet" href="/frontend/assets/css/dashboard.css">
    
    <!-- Styles améliorés -->
    <style>
        /* Styles généraux */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Navbar améliorée */
        .navbar {
            padding: 0.8rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #2563eb, #1e40af) !important;
        }
        
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand img {
            filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.3));
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .nav-link {
            padding: 0.8rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 3px;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .nav-link i {
            margin-right: 8px;
            font-size: 0.9em;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.5rem;
            min-width: 200px;
        }
        
        .dropdown-item {
            padding: 0.7rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f0f5ff;
            transform: translateX(3px);
        }
        
        .dropdown-item i {
            margin-right: 10px;
            color: #4b5563;
            width: 20px;
            text-align: center;
        }
        
        /* Container principal */
        .page-container {
            padding: 30px;
            margin-top: 80px;
            background-color: #f8f9fc;
            min-height: calc(100vh - 80px);
        }
        
        /* En-tête de page */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-header h1 {
            font-weight: 600;
            color: #1e3a8a;
            font-size: 1.8rem;
            margin: 0;
        }
        
        /* Cartes de statistiques */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .stat-card__icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card__icon i {
            font-size: 24px;
            color: #fff;
        }
        
        .stat-card__content {
            flex-grow: 1;
        }
        
        .stat-card__content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: #1e3a8a;
        }
        
        .stat-card__content span {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .stat-card__trend {
            margin-left: auto;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .stat-card__trend.up {
            color: #10b981;
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        .stat-card__trend.down {
            color: #ef4444;
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        .stat-card__trend i {
            margin-right: 5px;
        }
        
        /* Contenu du tableau de bord */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .card h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        /* Graphiques */
        .charts-section {
            grid-column: span 2;
        }
        
        /* Activités récentes */
        .activity-timeline {
            margin-top: 20px;
        }
        
        .activity-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .activity-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex-grow: 1;
        }
        
        .activity-content p {
            margin-bottom: 5px;
            color: #4b5563;
            font-size: 0.95rem;
        }
        
        .activity-content p strong {
            color: #1e3a8a;
            font-weight: 600;
        }
        
        .activity-content small {
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        /* Tâches urgentes */
        .tasks-list {
            margin-top: 20px;
        }
        
        .task-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }
        
        .task-item:hover {
            background-color: #f9fafb;
            padding-left: 10px;
            border-radius: 8px;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .status-todo {
            background-color: #9ca3af;
        }
        
        .status-in_progress {
            background-color: #3b82f6;
        }
        
        .status-review {
            background-color: #06b6d4;
        }
        
        .status-completed {
            background-color: #10b981;
        }
        
        .task-info {
            flex-grow: 1;
        }
        
        .task-info h4 {
            margin-bottom: 5px;
            font-size: 1rem;
            font-weight: 600;
            color: #1e3a8a;
        }
        
        .task-info a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s ease;
        }
        
        .task-info a:hover {
            color: #3b82f6;
        }
        
        .task-info small {
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .task-due {
            margin-left: 15px;
            white-space: nowrap;
        }
        
        .due-date {
            font-size: 0.85rem;
            color: #6b7280;
            padding: 3px 8px;
            border-radius: 4px;
            background-color: #f3f4f6;
        }
        
        .due-date.overdue {
            color: #fff;
            background-color: #ef4444;
            font-weight: 500;
        }
        
        /* Filtres et sélecteurs */
        .date-filter {
            width: 200px;
        }
        
        .form-select {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            box-shadow: none;
            transition: all 0.2s ease;
        }
        
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        /* Boutons */
        .btn {
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
        
        .btn-outline-secondary {
            border-color: #e5e7eb;
            color: #6b7280;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f3f4f6;
            color: #4b5563;
            border-color: #d1d5db;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Progress bars */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        
        .progress-bar {
            background-color: #10b981;
            border-radius: 4px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .navbar {
                padding: 0.6rem 1rem;
            }
            
            .page-container {
                padding: 20px;
                margin-top: 70px;
            }
            
            .charts-section {
                grid-column: span 1;
            }
            
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-filter {
                width: 100%;
                margin-top: 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <img src="/frontend/assets/img/logo.svg" alt="ProjectFlow" height="30">
                ProjectFlow
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($pageTitle ?? '') === 'Tableau de bord' ? 'active' : '' ?>" href="/dashboard">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pageTitle ?? '') === 'Projets' ? 'active' : '' ?>" href="/projects">
                            <i class="fas fa-project-diagram"></i> Projets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pageTitle ?? '') === 'Tâches' ? 'active' : '' ?>" href="/tasks">
                            <i class="fas fa-tasks"></i> Tâches
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pageTitle ?? '') === 'Utilisateurs' ? 'active' : '' ?>" href="/users">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="avatar-circle me-2">
                                <span class="avatar-initials"><?= substr($_SESSION['user']['name'] ?? 'U', 0, 1) ?></span>
                            </div>
                            <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Utilisateur') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item logout-btn" href="#">
                                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid page-container">
