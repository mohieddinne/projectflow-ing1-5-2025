<?php
require_once __DIR__ . '/config/Database.php';
$database = new Database();
$db = $database->getConnection();

$users = [
    ['name' => 'alice', 'email' => 'alice.manager@example.com', 'password' => 'pass123', 'role' => 'manager'],
    ['name' => 'bob', 'email' => 'bob.manager@example.com', 'password' => 'pass123', 'role' => 'manager'],
    ['name' => 'carol', 'email' => 'carol.admin@example.com', 'password' => 'pass123', 'role' => 'admin'],
    ['name' => 'dave', 'email' => 'dave.member@example.com', 'password' => 'pass123', 'role' => 'member'],
    ['name' => 'eve', 'email' => 'eve.manager@example.com', 'password' => 'pass123', 'role' => 'manager'],
    ['name' => 'frank', 'email' => 'frank.member@example.com', 'password' => 'pass123', 'role' => 'member'],
];

foreach ($users as $user) {
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, role)
        VALUES (:name, :email, :password, :role)
    ");

    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);

    $stmt->bindParam(':name', $user['name']);
    $stmt->bindParam(':email', $user['email']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $user['role']);

    $stmt->execute();
}

echo "✅ Utilisateurs insérés avec succès.\n";