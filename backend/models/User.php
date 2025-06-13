<?php
class User {
    // Connexion à la base de données
    private $conn;
    private $table_name = "users";

    // Propriétés de l'utilisateur
    public $id;
    public $name;
    public $email;
    public $password;
    public $role; // 'admin', 'manager', 'member'
    public $created_at;
    public $updated_at;
    public $last_login;

    /**
     * Constructeur avec $db comme connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function create() {
        try {
            // Vérifier si l'email existe déjà
            if($this->emailExists()) {
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . "
                    (name, email, password, role)
                    VALUES
                    (:name, :email, :password, :role)";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            // Binding
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":role", $this->role);

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Authentification utilisateur
     */
    public function login() {
        try {
            $query = "SELECT id, name, password, role 
                     FROM " . $this->table_name . " 
                     WHERE email = :email";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();

            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(password_verify($this->password, $row['password'])) {
                    // Mettre à jour la date de dernière connexion
                    $this->updateLastLogin($row['id']);
                    return $row;
                }
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET name = :name,
                         email = :email,
                         role = :role,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";

            // Si le mot de passe doit être mis à jour
            if(!empty($this->password)) {
                $query = str_replace("role = :role", 
                                   "role = :role, password = :password", 
                                   $query);
            }

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->email = htmlspecialchars(strip_tags($this->email));

            // Binding
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":role", $this->role);
            $stmt->bindParam(":id", $this->id);

            if(!empty($this->password)) {
                $this->password = password_hash($this->password, PASSWORD_DEFAULT);
                $stmt->bindParam(":password", $this->password);
            }

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete() {
        try {
            // Réassigner ou supprimer les tâches associées
            $query = "UPDATE tasks SET assigned_to = NULL WHERE assigned_to = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            // Supprimer l'utilisateur
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifier si l'email existe déjà
     */
    private function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Mettre à jour la date de dernière connexion
     */
    private function updateLastLogin($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET last_login = CURRENT_TIMESTAMP 
                 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    /**
     * Obtenir les statistiques utilisateur
     */
    public function getUserStats() {
        try {
            $query = "SELECT 
                        (SELECT COUNT(*) FROM tasks WHERE assigned_to = :id) as total_tasks,
                        (SELECT COUNT(*) FROM tasks WHERE assigned_to = :id AND status = 'completed') as completed_tasks,
                        (SELECT COUNT(*) FROM projects WHERE created_by = :id) as created_projects
                     FROM " . $this->table_name . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
