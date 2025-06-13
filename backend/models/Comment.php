<?php
class Comment {
    // Connexion à la base de données
    private $conn;
    private $table_name = "comments";

    // Propriétés de l'objet
    public $id;
    public $task_id;
    public $user_id;
    public $content;
    public $created_at;
    public $name; // Pour joindre le nom de l'utilisateur

    /**
     * Constructeur avec $db comme connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Créer un nouveau commentaire
     */
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (task_id, user_id, content)
                    VALUES
                    (:task_id, :user_id, :content)";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->content = htmlspecialchars(strip_tags($this->content));

            // Binding
            $stmt->bindParam(":task_id", $this->task_id);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":content", $this->content);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Lire les commentaires d'une tâche
     */
    public function getTaskComments($task_id) {
        try {
            $query = "SELECT c.*, u.name 
                     FROM " . $this->table_name . " c
                     LEFT JOIN users u ON c.user_id = u.id
                     WHERE c.task_id = :task_id
                     ORDER BY c.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":task_id", $task_id);
            $stmt->execute();

            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour un commentaire
     */
    public function update() {
        try {
            // Vérifier si l'utilisateur est l'auteur du commentaire
            if(!$this->isCommentOwner()) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . "
                     SET content = :content
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->content = htmlspecialchars(strip_tags($this->content));

            // Binding
            $stmt->bindParam(":content", $this->content);
            $stmt->bindParam(":id", $this->id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer un commentaire
     */
    public function delete() {
        try {
            // Vérifier si l'utilisateur est l'auteur du commentaire
            if(!$this->isCommentOwner()) {
                return false;
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifier si l'utilisateur est l'auteur du commentaire
     */
    private function isCommentOwner() {
        try {
            $query = "SELECT user_id FROM " . $this->table_name . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['user_id'] == $this->user_id;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Compter les commentaires d'une tâche
     */
    public function countTaskComments($task_id) {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                     WHERE task_id = :task_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":task_id", $task_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'];
        } catch(PDOException $e) {
            return 0;
        }
    }
}
?>
