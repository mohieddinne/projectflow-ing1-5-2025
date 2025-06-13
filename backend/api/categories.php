<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../controllers/CategoryController.php';

$database = new Database();
$db = $database->getConnection();
$categoryController = new CategoryController($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer une catégorie spécifique
                $result = $categoryController->getCategory($_GET['id']);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'data' => $result]);
            } else {
                // Récupérer toutes les catégories
                $result = $categoryController->getAllCategories();
                http_response_code(200);
                echo json_encode(['status' => 'success', 'data' => $result]);
            }
            break;

        case 'POST':
            // Créer une nouvelle catégorie
            if (!empty($input)) {
                $result = $categoryController->createCategory($input);
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
            // Mettre à jour une catégorie
            if (isset($_GET['id']) && !empty($input)) {
                $result = $categoryController->updateCategory($_GET['id'], $input);
                if ($result['status'] === 'success') {
                    http_response_code(200);
                } else {
                    http_response_code(400);
                }
                echo json_encode($result);
            } else {
                throw new Exception("ID or input data missing");
            }
            break;

        case 'DELETE':
            // Supprimer une catégorie
            if (isset($_GET['id'])) {
                $result = $categoryController->deleteCategory($_GET['id']);
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