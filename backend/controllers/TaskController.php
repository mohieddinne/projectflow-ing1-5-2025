<?php
class TaskController {
    private $db;
    private $table_name = "tasks";

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer une tâche spécifique par son ID
     */
    public function getTask($id) {
        try {
            $query = "SELECT t.*, u.name AS assigned_to_name,
                     (SELECT COUNT(*) FROM comments WHERE task_id = t.id) AS comments_count
                     FROM " . $this->table_name . " t
                     LEFT JOIN users u ON t.assigned_to = u.id
                     WHERE t.id = :id
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($task) {
                return array(
                    "status" => "success",
                    "data" => $task
                );
            } else {
                return array(
                    "status" => "error",
                    "message" => "Tâche non trouvée"
                );
            }
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Récupérer toutes les tâches
     */
    public function getTasks() {
        try {
            $query = "SELECT t.*, u.name AS assigned_to_name,
                     (SELECT COUNT(*) FROM comments WHERE task_id = t.id) AS comments_count
                     FROM " . $this->table_name . " t
                     LEFT JOIN users u ON t.assigned_to = u.id
                     ORDER BY t.priority DESC, t.due_date ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return array(
                "status" => "success",
                "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Créer une nouvelle tâche
     */
    public function createTask($data) {
        try {
            // Validation des données requises
            $required = ['title', 'project_id', 'created_by'];
            foreach($required as $field) {
                if(!isset($data[$field])) {
                    throw new Exception("Le champ $field est obligatoire");
                }
            }

            // Vérifier que l'utilisateur existe
            $created_by = (int)$data['created_by'];
            if(!$this->userExists($created_by)) {
                throw new Exception("L'utilisateur spécifié n'existe pas");
            }

            // Vérifier que le projet existe
            $project_id = (int)$data['project_id'];
            if(!$this->projectExists($project_id)) {
                throw new Exception("Le projet spécifié n'existe pas");
            }

            // Nettoyage des données
            $title = htmlspecialchars(strip_tags($data['title']));
            $description = isset($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : null;
            
            // Validation des ENUMs
            $status = isset($data['status']) ? $this->validateStatus($data['status']) : 'todo';
            $priority = isset($data['priority']) ? $this->validatePriority($data['priority']) : 'medium';

            // Validation de la date
            $due_date = null;
            if (!empty($data['due_date'])) {
                $due_date = $this->validateDate($data['due_date']);
            }

            // Validation de l'assignation
            $assigned_to = null;
            if (!empty($data['assigned_to'])) {
                $assigned_to = (int)$data['assigned_to'];
                if(!$this->userExists($assigned_to)) {
                    throw new Exception("L'utilisateur assigné n'existe pas");
                }
            }

            // Préparation de la requête
            $query = "INSERT INTO " . $this->table_name . " 
                    (project_id, title, description, status, priority, due_date, assigned_to, created_by) 
                    VALUES 
                    (:project_id, :title, :description, :status, :priority, :due_date, :assigned_to, :created_by)";

            $stmt = $this->db->prepare($query);

            // Binding des paramètres
            $stmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":priority", $priority);
            $stmt->bindParam(":due_date", $due_date);
            $stmt->bindParam(":assigned_to", $assigned_to, PDO::PARAM_INT);
            $stmt->bindParam(":created_by", $created_by, PDO::PARAM_INT);

            if(!$stmt->execute()) {
                throw new Exception("Échec de l'exécution de la requête SQL");
            }
            
            return [
                "status" => "success",
                "message" => "Tâche créée avec succès",
                "task_id" => $this->db->lastInsertId()
            ];

        } catch(Exception $e) {
            error_log("Erreur création tâche: " . $e->getMessage());
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    // Méthodes helper
    private function userExists($user_id) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }

    private function projectExists($project_id) {
        $stmt = $this->db->prepare("SELECT id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetch() !== false;
    }

    private function validateStatus($status) {
        $allowed = ['todo', 'in_progress', 'review', 'done'];
        return in_array($status, $allowed) ? $status : 'todo';
    }

    private function validatePriority($priority) {
        $allowed = ['low', 'medium', 'high', 'urgent'];
        return in_array($priority, $allowed) ? $priority : 'medium';
    }

    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date ? $date : null;
    }

    /**
     * Récupérer toutes les tâches d'un projet
     */
    public function getTasksByProject($project_id) {
        try {
            $query = "SELECT t.*, u.name as assigned_to_name,
                     (SELECT COUNT(*) FROM comments WHERE task_id = t.id) as comments_count
                     FROM " . $this->table_name . " t
                     LEFT JOIN users u ON t.assigned_to = u.id
                     WHERE t.project_id = :project_id
                     ORDER BY t.priority DESC, t.due_date ASC";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":project_id", $project_id);
            $stmt->execute();

            return array(
                "status" => "success",
                "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Mettre à jour une tâche
     */
    public function updateTask($task_id, $data) {
        try {
            // Récupérer les champs à mettre à jour
            $fields = [];
            $params = [":id" => $task_id];

            if (isset($data['title'])) {
                $fields[] = "title = :title";
                $params[":title"] = htmlspecialchars($data['title']);
            }

            if (isset($data['description'])) {
                $fields[] = "description = :description";
                $params[":description"] = htmlspecialchars($data['description']);
            }

            if (isset($data['status'])) {
                $fields[] = "status = :status";
                $params[":status"] = $data['status'];
            }

            if (isset($data['priority'])) {
                $fields[] = "priority = :priority";
                $params[":priority"] = $data['priority'];
            }

            if (isset($data['due_date'])) {
                $fields[] = "due_date = :due_date";
                $params[":due_date"] = $data['due_date'];
            }

            if (isset($data['assigned_to'])) {
                $fields[] = "assigned_to = :assigned_to";
                $params[":assigned_to"] = $data['assigned_to'];
            }

            if (empty($fields)) {
                throw new Exception("Aucune donnée à mettre à jour");
            }

            // Construction de la requête
            $query = "UPDATE " . $this->table_name . " SET 
                    " . implode(', ', $fields) . " 
                    WHERE id = :id";

            $stmt = $this->db->prepare($query);
            
            // Exécution
            if ($stmt->execute($params)) {
                return [
                    'status' => 'success',
                    'message' => 'Tâche mise à jour avec succès'
                ];
            }

            throw new Exception("Échec de la mise à jour de la tâche");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Supprimer une tâche
     */
    public function deleteTask($task_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $task_id);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Tâche supprimée avec succès"
                );
            }
            throw new Exception("Échec de la suppression de la tâche");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Méthode pour récupérer le project_id d'une tâche
     */
    private function getProjectIdOfTask($task_id) {
        $query = "SELECT project_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $task_id);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ? $task['project_id'] : null;
    }
}
?>