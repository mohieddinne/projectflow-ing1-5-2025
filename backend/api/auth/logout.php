<?php
require_once __DIR__ . '/../../services/AuthService.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    AuthService::logout();

    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie',
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
