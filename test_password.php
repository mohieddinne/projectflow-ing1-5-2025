<?php
// test_password.php

// Le mot de passe que tu essaies d'utiliser
$password = 'Pa$$w0rd!';

// Le hash stocké dans la base de données (remplace par le hash réel)
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Vérifie si le mot de passe correspond au hash
$is_valid = password_verify($password, $stored_hash);

echo "Mot de passe testé: $password\n";
echo "Hash stocké: $stored_hash\n";
echo "Résultat: " . ($is_valid ? "VALIDE ✅" : "INVALIDE ❌") . "\n";

// Génère un nouveau hash pour ce mot de passe
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "Nouveau hash généré: $new_hash\n";

// Vérifie si le nouveau hash fonctionne
$is_new_valid = password_verify($password, $new_hash);
echo "Nouveau hash valide: " . ($is_new_valid ? "OUI ✅" : "NON ❌") . "\n";
