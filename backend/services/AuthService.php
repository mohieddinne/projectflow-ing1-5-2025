<?php
// backend/services/AuthService.php

class AuthService {
    private static $instance = null;
    private $db;
    private $security;

    private function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        require_once __DIR__ . '/../utils/Security.php';
        
        $database = new Database();
        $this->db = $database->getConnection();
        $this->security = new Security();
    }

    /**
     * Obtenir l'instance unique de AuthService (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirige vers une page
     */
    public static function redirect($path) {
        header("Location: $path");
        exit();
    }

    /**
     * Authentifie un utilisateur
     */
    public function login($email, $password) {
        try {
            // Vérifier si les tables existent
             $this->checkTablesExist();
            // Vérifier si l'utilisateur est bloqué
            if ($this->isUserBlocked($email)) {
                throw new Exception("Compte temporairement bloqué. Veuillez réessayer plus tard.");
            }

            // Validation des entrées
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format d'email invalide");
            }

            // Requête préparée
            $query = "SELECT id, email, password, role, name FROM users WHERE email = ? AND active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    // Démarrer la session si nécessaire
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Régénérer l'ID de session pour la sécurité
                    session_regenerate_id(true);

                    // Stocker les informations utilisateur en session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['authenticated'] = true; // Ajout pour indiquer que l'utilisateur est authentifié

                    // Logger la connexion réussie
                    $this->logLogin($user['id'], true);

                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'name' => $user['name'],
                            'role' => $user['role']
                        ]
                    ];
                }
            }

            // Logger la tentative échouée
            $this->logLogin($email, false);
            
            throw new Exception("Email ou mot de passe incorrect");

        } catch (PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            throw new Exception("Erreur lors de la connexion");
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire toutes les données de session
        $_SESSION = array();

        // Détruire le cookie de session si présent
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Détruire la session
        session_destroy();
    }

    /**
     * Inscrit un nouvel utilisateur
     */
    public function register($name, $email, $password, $confirmPassword) {
        try {
            // Validation
            if ($password !== $confirmPassword) {
                throw new Exception("Les mots de passe ne correspondent pas");
            }

            if (!$this->security->isPasswordStrong($password)) {
                throw new Exception("Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre");
            }

            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format d'email invalide");
            }

            // Vérifier si l'email existe déjà
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Cet email est déjà utilisé");
            }

            // Hasher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur
            $query = "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([$name, $email, $hashedPassword])) {
                return [
                    'success' => true,
                    'message' => 'Inscription réussie',
                    'user_id' => $this->db->lastInsertId()
                ];
            }

            throw new Exception("Erreur lors de l'inscription");

        } catch (PDOException $e) {
            error_log("Erreur d'inscription : " . $e->getMessage());
            throw new Exception("Erreur lors de l'inscription");
        }
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        try {
            $query = "SELECT id, name, email, role, created_at FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getCurrentUser : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($role) {
        if (!self::isAuthenticated()) {
            return false;
        }
        return $_SESSION['user_role'] === $role;
    }

    /**
     * Logger les tentatives de connexion
     */
    private function logLogin($identifier, $success) {
        try {
            $query = "INSERT INTO login_attempts (identifier, success, ip_address, created_at) 
                     VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $identifier,
                $success ? 1 : 0,
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur de logging : " . $e->getMessage());
        }
    }

    /**
     * Vérifie si l'utilisateur est bloqué après trop de tentatives
     */
    private function isUserBlocked($email) {
        try {
            $query = "SELECT COUNT(*) FROM login_attempts 
                     WHERE identifier = ? 
                     AND success = 0 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            return $stmt->fetchColumn() >= 5;
        } catch (PDOException $e) {
            error_log("Erreur isUserBlocked : " . $e->getMessage());
            return false;
        }
    }
    /**
 * Vérifie si les tables nécessaires existent
 */
private function checkTablesExist() {
    try {
        $tables = ['users', 'login_attempts'];
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new Exception("La table '$table' n'existe pas. Veuillez exécuter le script setup_db.sh");
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Erreur checkTablesExist : " . $e->getMessage());
        throw new Exception("Erreur de base de données. Veuillez contacter l'administrateur.");
    }
}


    /**
     * Vérifie et met à jour la session
     */
    public static function checkSession() {
        if (self::isAuthenticated()) {
            // Vérifier l'expiration de la session (30 minutes)
            if (time() - $_SESSION['last_activity'] > 1800) {
                self::logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
}
