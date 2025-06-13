<?php
// backend/api/auth/create-session.php

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

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Vérifier les données
if (!$data || !isset($data['email']) || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

// Vérifier si l'email est valide (ici on accepte juste l'email codé en dur)
if ($data['email'] === 'monezefigy@mailinator.com') {
    // Créer la session
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => $data['email'],
        'role' => 'admin'
    ];
    $_SESSION['token'] = $data['token'];
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Session créée avec succès'
    ]);
} else {
    // Email invalide
    http_response_code(401);
    echo json_encode(['error' => 'Email invalide']);
}
