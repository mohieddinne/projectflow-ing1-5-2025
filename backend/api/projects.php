<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:8000");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../models/Project.php';
    require_once __DIR__ . '/../controllers/ProjectController.php';

    $database = new Database();
    $db = $database->getConnection();
    $projectController = new ProjectController($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $response = null;

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $response = $projectController->getProject($_GET['id']);
            } else {
                $status = $_GET['status'] ?? null;
                $sort = $_GET['sort'] ?? 'newest';
                $response = $projectController->getAllProjects($status, $sort);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $response = $projectController->createProject($data);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                throw new Exception("ID du projet requis pour la mise à jour");
            }
            $data = json_decode(file_get_contents("php://input"), true);
            $response = $projectController->updateProject($_GET['id'], $data);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception("ID du projet requis pour la suppression");
            }
            $response = $projectController->deleteProject($_GET['id']);
            break;

        default:
            throw new Exception("Méthode HTTP non supportée");
    }

    echo json_encode([
        "success" => true,
        "data" => $response
    ]);

} catch (Exception $e) {
    error_log("Error in API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
