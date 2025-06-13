<?php
class Project {
    // Connexion à la base de données
    private $conn;
    private $table_name = "projects";

    // Propriétés du projet
    public $id;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $status;
    public $created_by;
    public $progress_percentage;
    public $created_at;
    public $updated_at;

    /**
     * Constructeur avec $db comme connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Créer un nouveau projet
     */
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (title, description, start_date, end_date, status, created_by, progress_percentage)
                    VALUES
                    (:title, :description, :start_date, :end_date, :status, :created_by, :progress_percentage)";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Binding
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":start_date", $this->start_date);
            $stmt->bindParam(":end_date", $this->end_date);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":created_by", $this->created_by);
            $stmt->bindParam(":progress_percentage", $this->progress_percentage);

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Lire tous les projets
     */
    public function readAll() {
        try {
            $query = "SELECT p.*, u.name as creator_name,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                     FROM " . $this->table_name . " p
                     LEFT JOIN users u ON p.created_by = u.id
                     ORDER BY p.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Lire un projet spécifique
     */
    public function readOne() {
        try {
            $query = "SELECT p.*, u.name as creator_name,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                     FROM " . $this->table_name . " p
                     LEFT JOIN users u ON p.created_by = u.id
                     WHERE p.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour un projet
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET title = :title,
                         description = :description,
                         start_date = :start_date,
                         end_date = :end_date,
                         status = :status,
                         progress_percentage = :progress_percentage,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Binding
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":start_date", $this->start_date);
            $stmt->bindParam(":end_date", $this->end_date);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":progress_percentage", $this->progress_percentage);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer un projet
     */
    public function delete() {
        try {
            // Supprimer d'abord les tâches associées
            $query = "DELETE FROM tasks WHERE project_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            // Supprimer le projet
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Obtenir les statistiques du projet
     */
    public function getStatistics() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                        AVG(completion_percentage) as average_completion
                     FROM tasks 
                     WHERE project_id = :id";

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
