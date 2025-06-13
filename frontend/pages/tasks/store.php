<?php
// frontend/pages/tasks/store.php

// Vérifier si le fichier est accédé directement
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
}

// Inclure les fichiers nécessaires
if (file_exists(__DIR__ . '/../../includes/init.php')) {
    require_once __DIR__ . '/../../includes/init.php';
} else {
    // Initialisation minimale si init.php n'existe pas
    session_start();
    
    // Fonction de base pour les permissions
    if (!function_exists('requirePermission')) {
        function requirePermission($permission) {
            // Pour le développement, on autorise tout
            return true;
        }
    }
    
    // Fonction pour rediriger
    if (!function_exists('redirect')) {
        function redirect($url) {
            header("Location: $url");
            exit;
        }
    }
    
    // Fonction pour afficher une alerte
    if (!function_exists('setAlert')) {
        function setAlert($message, $type = 'info') {
            $_SESSION['alert'] = [
                'message' => $message,
                'type' => $type
            ];
        }
    }
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('Méthode non autorisée', 'danger');
    redirect('/tasks');
    exit;
}

// Vérifier les permissions
requirePermission('create_task');

// Récupérer et valider les données du formulaire
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
$due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
$priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'medium';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'to_do';
$estimated_hours = isset($_POST['estimated_hours']) ? floatval($_POST['estimated_hours']) : 0;
$assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
$parent_task_id = isset($_POST['parent_task_id']) && !empty($_POST['parent_task_id']) ? intval($_POST['parent_task_id']) : null;
$tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';

// Validation des données
$errors = [];

if (empty($title)) {
    $errors[] = 'Le titre de la tâche est obligatoire';
} elseif (strlen($title) < 3 || strlen($title) > 100) {
    $errors[] = 'Le titre doit contenir entre 3 et 100 caractères';
}

if (empty($project_id)) {
    $errors[] = 'Le projet est obligatoire';
}

if (empty($description)) {
    $errors[] = 'La description de la tâche est obligatoire';
}

if (empty($start_date)) {
    $errors[] = 'La date de début est obligatoire';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $errors[] = 'La date de début doit être au format YYYY-MM-DD';
}

if (empty($due_date)) {
    $errors[] = 'La date d\'échéance est obligatoire';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    $errors[] = 'La date d\'échéance doit être au format YYYY-MM-DD';
}

if ($start_date && $due_date && strtotime($due_date) < strtotime($start_date)) {
    $errors[] = 'La date d\'échéance doit être postérieure à la date de début';
}

if ($estimated_hours < 0) {
    $errors[] = 'Les heures estimées ne peuvent pas être négatives';
}

// Si des erreurs sont présentes, rediriger vers le formulaire avec les erreurs
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST; // Sauvegarder les données du formulaire
    redirect('/tasks/create' . ($project_id ? "?project_id=$project_id" : ''));
    exit;
}

// Connexion à la base de données
try {
    if (function_exists('dbConnect')) {
        $db = dbConnect();
    } else {
        // Connexion minimale si dbConnect n'existe pas
        $db = new PDO('sqlite:' . BASE_PATH . '/database/database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // Vérifier si la table tasks existe
    $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tasks'")->fetchColumn();
    
    if (!$tableExists) {
        // Créer la table tasks si elle n'existe pas
        $db->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            project_id INTEGER NOT NULL,
            start_date TEXT NOT NULL,
            due_date TEXT NOT NULL,
            priority TEXT NOT NULL DEFAULT 'medium',
            status TEXT NOT NULL DEFAULT 'to_do',
            estimated_hours REAL DEFAULT 0,
            actual_hours REAL DEFAULT 0,
            progress INTEGER DEFAULT 0,
            assigned_to INTEGER,
            parent_task_id INTEGER,
            created_by INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id),
            FOREIGN KEY (parent_task_id) REFERENCES tasks(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )");
    }
    
    // Récupérer l'ID de l'utilisateur connecté
    $created_by = $_SESSION['user_id'] ?? 1; // Utiliser 1 par défaut pour le développement
    
    // Insérer la tâche dans la base de données
    $stmt = $db->prepare("
        INSERT INTO tasks (
            title, description, project_id, start_date, due_date, 
            priority, status, estimated_hours, assigned_to, parent_task_id, created_by
        )
        VALUES (
            :title, :description, :project_id, :start_date, :due_date, 
            :priority, :status, :estimated_hours, :assigned_to, :parent_task_id, :created_by
        )
    ");
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':estimated_hours', $estimated_hours);
    $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
    $stmt->bindParam(':parent_task_id', $parent_task_id, PDO::PARAM_INT);
    $stmt->bindParam(':created_by', $created_by);
    
    $stmt->execute();
    
    // Récupérer l'ID de la tâche insérée
    $task_id = $db->lastInsertId();
    
    // Traiter les tags si nécessaire
    if (!empty($tags)) {
        // Vérifier si la table task_tags existe
        $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='task_tags'")->fetchColumn();
        
        if (!$tableExists) {
            // Créer la table task_tags si elle n'existe pas
            $db->exec("CREATE TABLE IF NOT EXISTS task_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id INTEGER NOT NULL,
                tag TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(task_id, tag),
                FOREIGN KEY (task_id) REFERENCES tasks(id)
            )");
        }
        
        // Diviser les tags et les insérer
        $tagList = array_map('trim', explode(',', $tags));
        $stmt = $db->prepare("INSERT OR IGNORE INTO task_tags (task_id, tag) VALUES (:task_id, :tag)");
        
        foreach ($tagList as $tag) {
            if (!empty($tag)) {
                $stmt->bindParam(':task_id', $task_id);
                $stmt->bindParam(':tag', $tag);
                $stmt->execute();
            }
        }
    }
    
    // Rediriger vers la liste des tâches avec un message de succès
    setAlert('Tâche créée avec succès', 'success');
    redirect('/tasks');
    
} catch (PDOException $e) {
    // En cas d'erreur, rediriger vers le formulaire avec un message d'erreur
    $_SESSION['form_errors'] = ['Erreur lors de la création de la tâche : ' . $e->getMessage()];
    $_SESSION['form_data'] = $_POST; // Sauvegarder les données du formulaire
    redirect('/tasks/create' . ($project_id ? "?project_id=$project_id" : ''));
    exit;
}
