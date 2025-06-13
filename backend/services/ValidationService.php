<?php
class ValidationService {
    /**
     * Valider les données d'un projet
     */
    public function validateProject($data) {
        $errors = [];

        // Vérification des champs obligatoires
        $requiredFields = ['title', 'description', 'start_date', 'end_date'];
        foreach($requiredFields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Le champ '$field' est obligatoire";
            }
        }

        // Validation des dates
        if(isset($data['start_date']) && isset($data['end_date'])) {
            if(strtotime($data['start_date']) > strtotime($data['end_date'])) {
                $errors[] = "La date de début doit être antérieure à la date de fin";
            }
        }

        // Validation du titre
        if(isset($data['title'])) {
            if(strlen($data['title']) < 3 || strlen($data['title']) > 100) {
                $errors[] = "Le titre doit contenir entre 3 et 100 caractères";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valider les données d'une tâche
     */
    public function validateTask($data) {
        $errors = [];

        // Vérification des champs obligatoires
        $requiredFields = ['title', 'project_id', 'status', 'priority'];
        foreach($requiredFields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Le champ '$field' est obligatoire";
            }
        }

        // Validation du statut
        $validStatuses = ['todo', 'in_progress', 'completed'];
        if(isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = "Statut invalide";
        }

        // Validation de la priorité
        $validPriorities = ['low', 'medium', 'high'];
        if(isset($data['priority']) && !in_array($data['priority'], $validPriorities)) {
            $errors[] = "Priorité invalide";
        }

        // Validation du pourcentage d'avancement
        if(isset($data['completion_percentage'])) {
            if(!is_numeric($data['completion_percentage']) || 
               $data['completion_percentage'] < 0 || 
               $data['completion_percentage'] > 100) {
                $errors[] = "Le pourcentage d'avancement doit être entre 0 et 100";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valider les données utilisateur
     */
    public function validateUser($data, $isUpdate = false) {
        $errors = [];

        // Vérification des champs obligatoires
        $requiredFields = $isUpdate ? ['name'] : ['name', 'email', 'password'];
        foreach($requiredFields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Le champ '$field' est obligatoire";
            }
        }

        // Validation de l'email
        if(isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide";
        }

        // Validation du mot de passe
        if(isset($data['password']) && !$isUpdate) {
            if(strlen($data['password']) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
            }
            if(!preg_match("/[A-Z]/", $data['password'])) {
                $errors[] = "Le mot de passe doit contenir au moins une majuscule";
            }
            if(!preg_match("/[0-9]/", $data['password'])) {
                $errors[] = "Le mot de passe doit contenir au moins un chiffre";
            }
        }

        // Validation du rôle
        if(isset($data['role'])) {
            $validRoles = ['admin', 'manager', 'member'];
            if(!in_array($data['role'], $validRoles)) {
                $errors[] = "Rôle invalide";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valider les données d'un commentaire
     */
    public function validateComment($data) {
        $errors = [];

        // Vérification des champs obligatoires
        if(!isset($data['content']) || empty(trim($data['content']))) {
            $errors[] = "Le contenu du commentaire est obligatoire";
        }

        // Validation de la longueur du commentaire
        if(isset($data['content'])) {
            if(strlen($data['content']) > 1000) {
                $errors[] = "Le commentaire ne doit pas dépasser 1000 caractères";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Nettoyer les données entrantes
     */
    public function sanitizeInput($data) {
        if(is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = $this->sanitizeInput($value);
            }
        } else {
            $data = htmlspecialchars(strip_tags(trim($data)));
        }
        return $data;
    }
}
?>
