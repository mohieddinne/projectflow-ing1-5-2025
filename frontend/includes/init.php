<?php
// frontend/includes/init.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Paris');

// Charger les fonctions utilitaires
require_once __DIR__ . '/functions.php';

// Définir les constantes
define('BASE_PATH', dirname(__DIR__));
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);

// Vérifier l'expiration de la session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Si la dernière activité date de plus de 30 minutes, déconnecter l'utilisateur
    session_unset();
    session_destroy();
    header('Location: /login');
    exit;
}

// Mettre à jour le timestamp de dernière activité
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}
