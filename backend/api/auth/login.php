<?php
// backend/api/auth/login.php

declare(strict_types=1); // Activation du typage strict

// Configuration initiale
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Désactivation de l'affichage des erreurs en production
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Fonction de journalisation améliorée
function log_auth_event(string $message, array $context = []): void {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $message,
        json_encode($context, JSON_PRETTY_PRINT)
    );
    
    file_put_contents($logDir . '/auth.log', $logEntry, FILE_APPEND);
}

try {
    // 1. Vérification de la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method Not Allowed');
    }

    // 2. Vérification du Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(415);
        throw new Exception('Unsupported Media Type');
    }

    // 3. Récupération et validation des données
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Error reading input data');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    // 4. Nettoyage et validation des entrées
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (empty($password) || strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    // 5. Inclusion sécurisée des dépendances
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../services/AuthService.php';
    require_once __DIR__ . '/../../utils/Security.php';

    // 6. Authentification
    $authService = AuthService::getInstance();
    $user = $authService->authenticate($email, $password);

    // 7. Gestion de session sécurisée
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);

    session_regenerate_id(true); // Protection contre les attaques par fixation de session

    $_SESSION = [
        'user_id' => $user['id'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'last_activity' => time()
    ];

    // 8. Réponse JSON sécurisée
    $response = [
        'success' => true,
        'message' => 'Authentication successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ],
        'csrf_token' => bin2hex(random_bytes(32))
    ];

    http_response_code(200);
    echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    log_auth_event('Validation error', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} catch (RuntimeException $e) {
    http_response_code(401);
    log_auth_event('Authentication failed', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);

} catch (Exception $e) {
    http_response_code(500);
    log_auth_event('System error', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}