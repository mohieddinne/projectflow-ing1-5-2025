<?php
// backend/api/auth/destroy-session.php

// Démarrer la session
session_start();

// Autoriser les requêtes CORS
header("Access-Control-Allow-Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Gérer les requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Détruire la session
session_unset();
session_destroy();

// Réponse de succès
echo json_encode([
    'success' => true,
    'message' => 'Session détruite avec succès'
]);
