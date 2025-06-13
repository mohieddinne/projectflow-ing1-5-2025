<?php
// backend/config/Database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'projectflow';
    private $name = 'projectflow';
    private $password = '123@mohA';
    private $conn;

    /**
     * Obtenir la connexion à la base de données
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, $this->name, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            throw new PDOException("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
}
