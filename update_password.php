<?php
// update_password.php

require_once __DIR__ . '/backend/config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Mot de passe à définir
    $password = 'Pa$$w0rd!';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe de l'utilisateur
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashed_password, 'monezefigy@mailinator.com']);
    
    if ($result) {
        echo "Mot de passe mis à jour avec succès pour monezefigy@mailinator.com\n";
        echo "Nouveau mot de passe: $password\n";
        echo "Nouveau hash: $hashed_password\n";
    } else {
        echo "Échec de la mise à jour du mot de passe\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
