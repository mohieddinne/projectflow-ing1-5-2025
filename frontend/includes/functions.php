<?php

/**
 * Vérifie si l'utilisateur est authentifié
 */
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Redirige vers une page
 */
function redirect($path) {
    header("Location: $path");
    exit();
}

/**
 * Affiche un message flash
 */
function flashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * 
 * @param string|array $roles Le ou les rôles requis
 * @return bool True si l'utilisateur a le rôle requis
 */
function hasRole($roles) {
    if (empty($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return $_SESSION['user_role'] === $roles;
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * Redirige vers la page d'accueil si ce n'est pas le cas
 * 
 * @param string|array $roles Le ou les rôles requis
 */
function requireRole($roles) {
    if (!hasRole($roles)) {
        header('Location: /dashboard');
        exit;
    }
}

/**
 * Génère un jeton CSRF
 * 
 * @return string Le jeton CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * 
 * @param string $token Le jeton à vérifier
 * @return bool True si le jeton est valide
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un message d'alerte
 * 
 * @param string $message Le message à afficher
 * @param string $type Le type d'alerte (success, danger, warning, info)
 * @param bool $dismissible Si l'alerte peut être fermée
 */
function showAlert($message, $type = 'info', $dismissible = true) {
    $class = 'alert alert-' . $type;
    if ($dismissible) {
        $class .= ' alert-dismissible fade show';
        $button = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    } else {
        $button = '';
    }
    
    echo '<div class="' . $class . '" role="alert">' . $message . $button . '</div>';
}



/**
 * Vérifie si l'utilisateur a la permission requise
 * 
 * @param string $permission La permission requise
 * @return bool True si l'utilisateur a la permission
 */
function hasPermission($permission) {
    // Vérifier d'abord si l'utilisateur est authentifié
    if (empty($_SESSION['authenticated'])) {
        return false;
    }
    
    // Les administrateurs ont toutes les permissions
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        return true;
    }
    
    // Pour la compatibilité avec le code existant
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // Vérifier les permissions spécifiques
    // Dans un système réel, cela pourrait venir d'une base de données
    $permissions = [
        'view_projects' => ['user', 'manager', 'admin'],
        'create_project' => ['manager', 'admin'],
        'edit_project' => ['manager', 'admin'],
        'delete_project' => ['admin'],
        'view_tasks' => ['user', 'manager', 'admin'],
        'create_task' => ['user', 'manager', 'admin'],
        'edit_task' => ['user', 'manager', 'admin'],
        'delete_task' => ['manager', 'admin'],
        'view_users' => ['admin'],
        'create_user' => ['admin'],
        'edit_user' => ['admin'],
        'delete_user' => ['admin'],
    ];
    
    // Vérifier si la permission existe et si le rôle de l'utilisateur est dans la liste
    if (isset($permissions[$permission])) {
        // Vérifier le rôle dans $_SESSION['user_role']
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $permissions[$permission])) {
            return true;
        }
        
        // Vérifier le rôle dans $_SESSION['user']['role']
        if (isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], $permissions[$permission])) {
            return true;
        }
    }
    
    return false;
}


/**
 * Vérifie si l'utilisateur a la permission requise
 * Redirige vers la page d'accueil si ce n'est pas le cas
 * 
 * @param string $permission La permission requise
 */
function requirePermission($permission) {
    // Vérifier d'abord l'authentification
    requireAuthentication();
    
    // Ensuite vérifier la permission
    if (!hasPermission($permission)) {
        // Rediriger vers une page d'erreur 403 ou le tableau de bord
        header('Location: /dashboard?error=permission');
        exit;
    }
}


/**
 * Formate une date au format français
 * 
 * @param string $date La date à formater
 * @param bool $withTime Inclure l'heure
 * @return string La date formatée
 */
function formatDate($date, $withTime = false) {
    if (empty($date)) {
        return '';
    }
    
    $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, strtotime($date));
}



/**
 * Formate un texte pour l'affichage (nl2br + htmlspecialchars)
 * 
 * @param string $text Le texte à formater
 * @return string Le texte formaté
 */
function formatText($text) {
    return nl2br(htmlspecialchars($text));
}

/**
 * Tronque un texte à une longueur donnée
 * 
 * @param string $text Le texte à tronquer
 * @param int $length La longueur maximale
 * @param string $suffix Le suffixe à ajouter
 * @return string Le texte tronqué
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Génère une couleur aléatoire
 * 
 * @return string Code hexadécimal de la couleur
 */
function randomColor() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * Obtient l'URL actuelle
 * 
 * @return string L'URL actuelle
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}


/**
 * Vérifie si la requête est une requête AJAX
 * 
 * @return bool True si c'est une requête AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
/**
 * Vérifie si l'utilisateur est authentifié
 * Si non, redirige vers la page de connexion
 */
function requireAuthentication() {
    // Cette fonction est maintenant redondante car l'authentification
    // est déjà vérifiée dans frontend/index.php
    // Mais on la garde pour la compatibilité avec le code existant
    if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: /login');
        exit;
    }
}

/**
 * Récupère les informations de l'utilisateur connecté
 * @return array Informations de l'utilisateur
 */
function getCurrentUser() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    
    // Utilisateur par défaut si aucun n'est défini
    return [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'monezefigy@mailinator.com',
        'role' => 'admin'
    ];
}

/**
 * Formate une durée en minutes en format lisible
 * @param int|float|string|null $minutes Durée en minutes
 * @return string Durée formatée
 */
function formatDuration($minutes) {
    if ($minutes === null || $minutes === '' || !is_numeric($minutes)) {
        return '0 min';
    }
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return '0 min';
    }
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    if ($hours > 0) {
        return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
    }

    return $mins . ' min';
}

/**
 * Vérifie si une date est dépassée
 * @param string $date Date au format Y-m-d
 * @return bool True si la date est dépassée
 */
function isOverdue($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}
/**
 * Récupère les détails complets d'un projet avec ses tâches et membres
 * 
 * @param int $projectId ID du projet
 * @return array|null Tableau des données ou null si non trouvé
 */
function getProjectDetails($projectId) {
    global $db; // Supposant que vous avez une connexion DB disponible

    try {
        // 1. Récupération des infos de base du projet
        $stmt = $db->prepare("
            SELECT p.*, 
                   COUNT(t.id) as tasks_count,
                   SUM(t.status = 'completed') as completed_tasks
            FROM projects p
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            return null;
        }

        // 2. Récupération des membres du projet
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.avatar_url, pu.role
            FROM project_users pu
            JOIN users u ON pu.user_id = u.id
            WHERE pu.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $project['members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Récupération des tâches par statut
        $statuses = ['todo', 'in_progress', 'review', 'completed'];
        $project['tasks'] = [];
        
        foreach ($statuses as $status) {
            $stmt = $db->prepare("
                SELECT t.*, u.name as assignee_name
                FROM tasks t
                LEFT JOIN users u ON t.assignee_id = u.id
                WHERE t.project_id = ? AND t.status = ?
                ORDER BY t.priority DESC, t.due_date ASC
            ");
            $stmt->execute([$projectId, $status]);
            $project['tasks'][$status] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4. Calcul de la progression
        $totalTasks = $project['tasks_count'];
        $completed = $project['completed_tasks'];
        $project['progress'] = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;

        // 5. Récupération des activités récentes
        $stmt = $db->prepare("
            SELECT * FROM project_activities
            WHERE project_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$projectId]);
        $project['activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $project;

    } catch (PDOException $e) {
        error_log("Project details error: " . $e->getMessage());
        return null;
    }
}

function getDaysRemaining($endDate) {
    if (empty($endDate)) return 'N/A';
    
    $now = new DateTime();
    $end = new DateTime($endDate);
    
    if ($end < $now) return 'Dépassé';
    
    return $end->diff($now)->days . ' jours';
}
