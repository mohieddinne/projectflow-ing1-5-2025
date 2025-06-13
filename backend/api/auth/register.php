<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/AuthService.php';
require_once __DIR__ . '/../../services/ValidationService.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupération des données
    $data = [
        'name' => $_POST['name'] ?? '',
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    // Validation
    $validator = new ValidationService();
    $validator->validate($data, [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'password' => 'required|min:8',
        'confirm_password' => 'required|same:password'
    ]);

    if (!$validator->isValid()) {
        throw new Exception($validator->getErrors()[0]);
    }

    // Inscription
    $auth = AuthService::getInstance();
    $result = $auth->register($data);

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie',
        'redirect' => '/login'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit();
