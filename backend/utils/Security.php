<?php
// backend/utils/Security.php

class Security {
    /**
     * Vérifie si un mot de passe est suffisamment fort
     */
    public function isPasswordStrong($password) {
        // Au moins 8 caractères
        if (strlen($password) < 8) {
            return false;
        }
        
        // Au moins une lettre majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Génère un token CSRF
     */
    public function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public function verifyCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Nettoie les données d'entrée
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeInput($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
}
