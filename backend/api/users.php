<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Activer le logging des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/UserController.php';

// Initialisation de la DB et du contrôleur
$database = new Database();
$db = $database->getConnection();
$userController = new UserController($db);

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer un utilisateur spécifique
                $result = $userController->getUser($_GET['id']);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'data' => $result]);
                
            } elseif (isset($_GET['role'])) {
                // Récupérer par rôle
                $result = $userController->getUsersByRole($_GET['role']);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'data' => $result]);
                
            } else {
                // Récupérer tous les utilisateurs
                $result = $userController->getAllUsers();
                http_response_code(200);
                echo json_encode(['status' => 'success', 'data' => $result]);
            }
            break;

        case 'POST':
            // Créer un nouvel utilisateur
            if (!empty($input)) {
                $result = $userController->createUser($input);
                if ($result['status'] === 'success') {
                    http_response_code(201);
                } else {
                    http_response_code(400);
                }
                echo json_encode($result);
            } else {
                throw new Exception("No input data provided");
            }
            break;

    case 'PUT':
        // Mettre à jour un utilisateur
        if (!empty($input) && isset($input['id'])) {
            // Remove 'id' from input to avoid binding it twice
            $updateData = $input;
            unset($updateData['id']);
            $result = $userController->updateUser($input['id'], $updateData);
            http_response_code($result['status'] === 'success' ? 200 : 400);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "ID or input data missing"]);
        }
        break;


        case 'DELETE':
            // Supprimer un utilisateur
            if (isset($_GET['id'])) {
                $result = $userController->deleteUser($_GET['id']);
                if ($result['status'] === 'success') {
                    http_response_code(200);
                } else {
                    http_response_code(400);
                }
                echo json_encode($result);
            } else {
                throw new Exception("ID parameter missing");
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}