<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . "../../models/Task.php";
require_once __DIR__ . '/../controllers/TaskController.php';

// Initialisation de la base de données
$database = new Database();
$db = $database->getConnection();
$taskController = new TaskController($db);

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            if(isset($_GET['id'])) {
                // Récupérer une tâche spécifique
                $result = $taskController->getTask($_GET['id']);
            } elseif(isset($_GET['project_id'])) {
                // Récupérer les tâches d'un projet
                $result = $taskController->getTasksByProject($_GET['project_id']);
            } else {
                // Récupérer toutes les tâches
                $result = $taskController->getTasks();
            }
            echo json_encode($result);
            break;

        case 'POST':
            // Créer une nouvelle tâche
            $data = json_decode(file_get_contents("php://input"), true);
            $requiredFields = ['title', 'project_id', 'description', 'status', 'priority'];
            
            if(validateFields($data, $requiredFields)) {
                $result = $taskController->createTask($data);
                echo json_encode($result);
            } else {
                throw new Exception("Missing required fields");
            }
            break;

        case 'PUT':
            // Mettre à jour une tâche
            if(isset($_GET['id'])) {
                $data = json_decode(file_get_contents("php://input"), true);
                $result = $taskController->updateTask($_GET['id'], $data);
                echo json_encode($result);
            } else {
                throw new Exception("Task ID is required");
            }
            break;

        case 'DELETE':
            // Supprimer une tâche
            if(isset($_GET['id'])) {
                $result = $taskController->deleteTask($_GET['id']);
                echo json_encode($result);
            } else {
                throw new Exception("Task ID is required");
            }
            break;

        default:
            throw new Exception("Method not allowed");
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
}

// Fonction de validation des champs requis
function validateFields($data, $required) {
    foreach($required as $field) {
        if(!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    return true;
}

// Fermer la connexion
$database = null;
?>
