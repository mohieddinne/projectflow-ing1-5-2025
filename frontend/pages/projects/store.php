<?php
// frontend/pages/projects/store.php

// Inclure les fonctions nécessaires
if (!function_exists('requirePermission')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
require_once __DIR__ . '/../../includes/log.php';

// Log initial
logToFile("store.php called with method: {$_SERVER['REQUEST_METHOD']}");

// Vérification des permissions
requirePermission('create_project');

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Log des données brutes du formulaire
    logToFile("Form data received: " . json_encode($_POST));

    // Récupérer et valider les données du formulaire
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'planning';
    $progress = intval($_POST['progress'] ?? 0);
    $managerId = intval($_POST['manager_id'] ?? 0);
    $categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $members = $_POST['members'] ?? [];

    // Validation des données
    $errors = [];

    if (strlen($title) < 3) {
        $errors[] = "Le titre doit contenir au moins 3 caractères.";
    }

    if (strlen($description) < 10) {
        $errors[] = "La description doit contenir au moins 10 caractères.";
    }

    if (empty($startDate)) {
        $errors[] = "La date de début est requise.";
    }

    if (empty($endDate)) {
        $errors[] = "La date de fin est requise.";
    }

    if (strtotime($endDate) < strtotime($startDate)) {
        $errors[] = "La date de fin doit être postérieure à la date de début.";
    }

    if ($managerId <= 0) {
        $errors[] = "Veuillez sélectionner un chef de projet valide.";
    }

    // Si erreurs détectées
    if (!empty($errors)) {
        logToFile("Validation errors: " . implode(' | ', $errors));
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: /projects/create');
        exit;
    }

    try {
        // Préparer les données pour l'API
        $projectData = [
            'title' => $title,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'progress' => $progress,
            'manager_id' => $managerId,
            'category_id' => $categoryId
        ];

        logToFile("Sending project data to API: " . json_encode($projectData));

        // Appel API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/backend/api/projects.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($projectData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        logToFile("API HTTP Code: $httpCode");
        logToFile("API Response: $response");

        if (!empty($curlError)) {
            logToFile("cURL error: $curlError");
        }

        // Vérifier la réponse
        if ($httpCode >= 200 && $httpCode < 300) {
            $result = json_decode($response, true);
            logToFile("API success result: " . json_encode($result));

            if (isset($result['id']) && !empty($members) && is_array($members)) {
                $projectId = $result['id'];
                logToFile("Project created with ID: $projectId, adding members: " . json_encode($members));
                
                // TODO: ajouter les membres via API ici si nécessaire
            }

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Le projet a été créé avec succès!'
            ];

            header('Location: /projects');
            exit;

        } else {
            $result = json_decode($response, true);
            $errorMessage = $result['message'] ?? 'Erreur lors de la création du projet';
            logToFile("API Error: $errorMessage");
            throw new Exception($errorMessage);
        }

    } catch (Exception $e) {
        logToFile("Exception caught: " . $e->getMessage());
        $_SESSION['form_errors'] = ["Erreur lors de la création du projet: " . $e->getMessage()];
        $_SESSION['form_data'] = $_POST;
        header('Location: /projects/create');
        exit;
    }

} else {
    // Redirection si non-POST
    logToFile("Méthode non autorisée: redirection vers /projects/create");
    header('Location: /projects/create');
    exit;
}
