<?php
class UserController {
    private $db;
    private $table_name = "users";

    public function __construct($db) {
        $this->db = $db;
    }

    // Créer un utilisateur
    public function createUser($data) {
    try {
        if (!$this->validateUserData($data)) {
            throw new Exception("Invalid user data");
        }

        if ($this->emailExists($data['email'])) {
            throw new Exception("Email already exists");
        }

        $query = "INSERT INTO " . $this->table_name . " 
                 (name, email, password, role) 
                 VALUES 
                 (:name, :email, :password, :role)";

        $stmt = $this->db->prepare($query);

        // Sanitize and store values in variables
        $name = htmlspecialchars(strip_tags($data['name']));
        $email = htmlspecialchars(strip_tags($data['email']));
        $role = htmlspecialchars(strip_tags($data['role']));
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Bind using variables
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":role", $role);

        if ($stmt->execute()) {
            return [
                "status" => "success",
                "message" => "User created successfully",
                "user_id" => $this->db->lastInsertId()
            ];
        }
        throw new Exception("Failed to create user");
    } catch (Exception $e) {
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}

    // Récupérer un utilisateur par ID
    public function getUser($id) {
        try {
            $query = "SELECT id, name, email, role, created_at 
                     FROM " . $this->table_name . " 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            return $user;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Récupérer tous les utilisateurs
    public function getAllUsers() {
        try {
            $query = "SELECT id, name, email, role, created_at 
                     FROM " . $this->table_name . " 
                     ORDER BY created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Récupérer les utilisateurs par rôle
    public function getUsersByRole($role) {
        try {
            $query = "SELECT id, name, email, role, created_at 
                     FROM " . $this->table_name . " 
                     WHERE role = :role
                     ORDER BY created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":role", $role);
            $stmt->execute();

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                throw new Exception("No users found with role: " . $role);
            }

            return $users;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Mettre à jour un utilisateur
     public function updateUser($id, $data) {
        try {
            $updateFields = [];
            $params = [':id' => $id];

            // Traiter les champs normaux
            foreach ($data as $key => $value) {
                // Exclure les champs spéciaux
                if ($key != 'id' && $key != 'password') {
                    $updateFields[] = "$key = :$key";
                    $params[":$key"] = htmlspecialchars(strip_tags($value));
                }
            }

            // Traiter le mot de passe séparément
            if (isset($data['password']) && !empty($data['password'])) {
                $updateFields[] = "password = :password";
                $params[":password"] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Vérifier s'il y a des champs à mettre à jour
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET " . implode(", ", $updateFields) . " 
                     WHERE id = :id";

            $stmt = $this->db->prepare($query);
            
            // Journalisation pour débogage
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            
            if ($stmt->execute($params)) {
                return [
                    "status" => "success",
                    "message" => "User updated successfully"
                ];
            }
            
            // Obtenir plus d'informations sur l'erreur
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to update user: " . $errorInfo[2]);
        } catch (Exception $e) {
            // Journalisation de l'erreur
            error_log("Update User Error: " . $e->getMessage());
            
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }


    // Supprimer un utilisateur
    public function deleteUser($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                return [
                    "status" => "success",
                    "message" => "User deleted successfully"
                ];
            }
            throw new Exception("Failed to delete user");
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    // Authentification
    public function authenticate($email, $password) {
        try {
            $query = "SELECT id, name, email, password, role 
                     FROM " . $this->table_name . " 
                     WHERE email = :email";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    unset($user['password']);
                    return [
                        "status" => "success",
                        "data" => $user
                    ];
                }
            }
            throw new Exception("Invalid credentials");
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    // Méthodes utilitaires
    private function validateUserData($data) {
        return (
            !empty($data['name']) &&
            !empty($data['email']) &&
            !empty($data['password']) &&
            !empty($data['role']) &&
            filter_var($data['email'], FILTER_VALIDATE_EMAIL)
        );
    }

    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}