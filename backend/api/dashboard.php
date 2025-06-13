<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . "../../models/Task.php";
// require_once __DIR__ . "../../models/Activity.php";

// Initialisation de la base de données
$database = new Database();
$db = $database->getConnection();

// Récupérer la période sélectionnée
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

try {
    // Récupérer les statistiques principales
    $stats = getDashboardStats($db, $period);
    
    // Récupérer les données des projets
    $projectsData = getProjectsData($db);
    
    // Récupérer la répartition des tâches
    $tasksData = getTasksDistribution($db);
    
    // Récupérer les activités récentes
    $activities = getRecentActivities($db);
    
    // Récupérer les tâches urgentes
    $urgentTasks = getUrgentTasks($db);
    
    // Préparer la réponse
    $response = [
        'status' => 'success',
        'data' => [
            'stats' => $stats,
            'projects' => $projectsData,
            'tasks' => $tasksData,
            'activities' => $activities,
            'urgent_tasks' => $urgentTasks
        ],
        'timestamp' => time()
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Récupère les statistiques principales du tableau de bord
 */
function getDashboardStats($db, $period) {
    $dateRange = getDateRange($period);
    $prevDateRange = getDateRange($period, true);

    // 1. Projets actifs (status = 'in_progress' ou 'active') sur la période
    $query = "SELECT COUNT(*) as count FROM projects
              WHERE status IN ('in_progress', 'active')
              AND start_date <= :end_date
              AND (end_date IS NULL OR end_date >= :start_date)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        'start_date' => $dateRange['start'],
        'end_date' => $dateRange['end']
    ]);
    $activeProjects = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Projets sur la période précédente
    $stmt = $db->prepare($query);
    $stmt->execute([
        'start_date' => $prevDateRange['start'],
        'end_date' => $prevDateRange['end']
    ]);
    $prevProjects = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $projectsTrend = $prevProjects > 0 
        ? round((($activeProjects - $prevProjects) / $prevProjects) * 100, 1) 
        : ($activeProjects > 0 ? 100 : 0);

    // 2. Tâches en attente (toutes périodes)
    $query = "SELECT COUNT(*) as count FROM tasks 
              WHERE status IN ('todo', 'in_progress', 'review')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pendingTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Tâches en attente période précédente
    $query = "SELECT COUNT(*) as count FROM tasks
              WHERE status IN ('todo', 'in_progress', 'review')
              AND created_at BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->execute([
        'start_date' => $prevDateRange['start'],
        'end_date' => $prevDateRange['end']
    ]);
    $prevPendingTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $tasksTrend = $prevPendingTasks > 0 
        ? round((($pendingTasks - $prevPendingTasks) / $prevPendingTasks) * 100, 1) 
        : ($pendingTasks > 0 ? 100 : 0);

    // 3. Tâches terminées
    $query = "SELECT COUNT(*) as count FROM tasks WHERE status = 'done'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $completedTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 4. Taux d'avancement
    $query = "SELECT COUNT(*) as count FROM tasks";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $completionRate = $totalTasks > 0 
        ? round(($completedTasks / $totalTasks) * 100, 1) 
        : 0;

    return [
        'active_projects' => $activeProjects,
        'projects_trend' => $projectsTrend,
        'pending_tasks' => $pendingTasks,
        'tasks_trend' => $tasksTrend,
        'completed_tasks' => $completedTasks,
        'completion_rate' => $completionRate,
    ];
}

 /*
 * Récupère les données des projets pour le graphique
 */
function getProjectsData($db) {
    $query = "SELECT 
                p.id, 
                p.title, 
                p.start_date, 
                p.end_date, 
                p.status,
                p.progress_percentage
              FROM projects p
              WHERE p.status IN ('planning', 'in_progress', 'active') -- cohérence avec les projets actifs
              ORDER BY p.id DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'labels' => array_column($projects, 'title'),
        'progress' => array_map(function($proj) {
            return (float)$proj['progress_percentage'];
        }, $projects),
        'projects' => $projects
    ];
}


/**
 * Récupère la répartition des tâches
 */
function getTasksDistribution($db) {
    $query = "SELECT 
                COALESCE(SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END), 0) as todo,
                COALESCE(SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END), 0) as in_progress,
                COALESCE(SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END), 0) as review,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed
              FROM tasks";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'distribution' => array_map('intval', $result)
    ];
}


/**
 * Récupère les activités récentes depuis activity_logs
 */
function getRecentActivities($db) {
    try {
        // Récupérer les activités récentes avec une seule requête
        $query = "
            (SELECT 
                'user_created' AS action_type,
                'user' AS entity_type,
                id AS entity_id,
                name AS user_name,
                CONCAT('Nouvel utilisateur : ', name) AS description,
                created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT 5)
            
            UNION ALL
            
            (SELECT 
                'project_created' AS action_type,
                'project' AS entity_type,
                p.id AS entity_id,
                u.name AS user_name,
                CONCAT('Nouveau projet : ', p.title) AS description,
                p.created_at
            FROM projects p
            JOIN users u ON p.created_by = u.id
            ORDER BY p.created_at DESC
            LIMIT 5)
            
            UNION ALL
            
            (SELECT 
                'task_created' AS action_type,
                'task' AS entity_type,
                t.id AS entity_id,
                u.name AS user_name,
                CONCAT('Nouvelle tâche : ', t.title, ' (Projet : ', COALESCE(p.title, 'Sans projet'), ')') AS description,
                t.created_at
            FROM tasks t
            JOIN users u ON t.created_by = u.id
            LEFT JOIN projects p ON t.project_id = p.id
            ORDER BY t.created_at DESC
            LIMIT 5)
            
            ORDER BY created_at DESC
            LIMIT 15
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur getRecentActivities: " . $e->getMessage());
        return [];
    }
}

// Fonction pour formater la différence de temps
function timeAgo($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return "il y a " . $diff->y . " an" . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return "il y a " . $diff->m . " mois";
    if ($diff->d > 0) return "il y a " . $diff->d . " jour" . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return "il y a " . $diff->h . " heure" . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return "il y a " . $diff->i . " minute" . ($diff->i > 1 ? 's' : '');
    return "à l'instant";
}



/**
 * Récupère les tâches urgentes
 */
function getUrgentTasks($db) {
    $query = "SELECT t.id, t.title, t.status, t.due_date, t.project_id, p.title as project_name
              FROM tasks t
              JOIN projects p ON t.project_id = p.id
              WHERE t.status != 'completed'
              AND t.due_date IS NOT NULL
              AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
              ORDER BY t.due_date ASC
              LIMIT 4";
    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Calcule la plage de dates en fonction de la période sélectionnée
 */
function getDateRange($period, $previous = false) {
    $now = new DateTime();

    switch ($period) {
        case 'month':
            $start = new DateTime('first day of this month');
            $end = new DateTime('last day of this month');
            if ($previous) {
                $start->modify('-1 month');
                $end->modify('-1 month');
            }
            break;

        case 'quarter':
            $month = (int) $now->format('n');
            $quarter = ceil($month / 3);
            $startMonth = ($quarter - 1) * 3 + 1;
            $start = new DateTime("{$now->format('Y')}-{$startMonth}-01");
            $end = clone $start;
            $end->modify('+2 months')->modify('last day of this month');
            if ($previous) {
                $start->modify('-3 months');
                $end->modify('-3 months');
            }
            break;

        case 'week':
        default:
            $start = clone $now;
            $start->modify('Monday this week');
            $end = clone $start;
            $end->modify('Sunday this week');
            if ($previous) {
                $start->modify('-1 week');
                $end->modify('-1 week');
            }
            break;
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
    ];
}
