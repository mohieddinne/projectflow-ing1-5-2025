<?php
// frontend/includes/database.php

/**
 * Établit une connexion à la base de données
 *
 * @return PDO Instance de connexion PDO
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        // Modifie ces informations selon ta configuration
        $host = 'localhost';
        $db   = 'projectflow'; // Nom de ta base de données
        $user = 'root';        // Utilise 'root' ou ton nom d'utilisateur MySQL
        $pass = '123@mohA';            // Mot de passe de ton utilisateur MySQL (souvent vide pour 'root' en développement local)
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // En production, on ne devrait pas afficher l'erreur directement
            // mais plutôt la logger et afficher un message générique
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    return $pdo;
}

/**
 * Exécute une requête SQL et retourne tous les résultats
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array Résultats de la requête
 */
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête SQL et retourne un seul résultat
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array|false Résultat de la requête ou false si aucun résultat
 */
function dbQuerySingle($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécute une requête SQL et retourne l'ID de la dernière insertion
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return int ID de la dernière insertion
 */
function dbInsert($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

/**
 * Exécute une requête SQL et retourne le nombre de lignes affectées
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return int Nombre de lignes affectées
 */
function dbExecute($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
