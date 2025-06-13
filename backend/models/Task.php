<?php
class Task {
    // Connexion à la base de données
    private $conn;
    private $table_name = "tasks";

    // Propriétés de la tâche
    public $id;
    public $project_id;
    public $title;
    public $description;
    public $status; // 'todo', 'in_progress', 'completed'
    public $priority; // 'low', 'medium', 'high'
    public $assigned_to;
    public $start_date;
    public $due_date;
    public $completion_percentage;
    public $created_at;
    public $updated_at;

    /**
     * Constructeur avec $db comme connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Créer une nouvelle tâche
     */
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (project_id, title, description, status, priority, assigned_to, 
                     start_date, due_date, completion_percentage)
                    VALUES
                    (:project_id, :title, :description, :status, :priority, :assigned_to,
                     :start_date, :due_date, :completion_percentage)";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Binding
            $stmt->bindParam(":project_id", $this->project_id);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":priority", $this->priority);
            $stmt->bindParam(":assigned_to", $this->assigned_to);
            $stmt->bindParam(":start_date", $this->start_date);
            $stmt->bindParam(":due_date", $this->due_date);
            $stmt->bindParam(":completion_percentage", $this->completion_percentage);

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Lire les tâches d'un projet
     */
    public function readByProject($project_id) {
        try {
            $query = "SELECT t.*, u.name as assigned_to_name,
                     (SELECT COUNT(*) FROM comments WHERE task_id = t.id) as comments_count
                     FROM " . $this->table_name . " t
                     LEFT JOIN users u ON t.assigned_to = u.id
                     WHERE t.project_id = :project_id
                     ORDER BY t.priority DESC, t.due_date ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":project_id", $project_id);
            $stmt->execute();

            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Lire les tâches assignées à un utilisateur
     */
    public function readByUser($user_id) {
        try {
            $query = "SELECT t.*, p.title as project_name
                     FROM " . $this->table_name . " t
                     LEFT JOIN projects p ON t.project_id = p.id
                     WHERE t.assigned_to = :user_id
                     ORDER BY t.priority DESC, t.due_date ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            return $stmt;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour une tâche
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET title = :title,
                         description = :description,
                         status = :status,
                         priority = :priority,
                         assigned_to = :assigned_to,
                         due_date = :due_date,
                         completion_percentage = :completion_percentage,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Nettoyer et sécuriser les données
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));

            // Binding
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":priority", $this->priority);
            $stmt->bindParam(":assigned_to", $this->assigned_to);
            $stmt->bindParam(":due_date", $this->due_date);
            $stmt->bindParam(":completion_percentage", $this->completion_percentage);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer une tâche
     */
    public function delete() {
        try {
            // Supprimer d'abord les commentaires associés
            $query = "DELETE FROM comments WHERE task_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();

            // Supprimer la tâche
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Obtenir les statistiques des tâches
     */
    public function getTaskStats($project_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
                        AVG(completion_percentage) as average_completion
                     FROM " . $this->table_name . "
                     WHERE project_id = :project_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":project_id", $project_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
