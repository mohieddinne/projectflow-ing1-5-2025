<?php
class ProjectController {
    private $db;
    private $table_name = "projects";

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Créer un nouveau projet
     */
    public function createProject($data) {
        try {
            // Validation des données requises
            if(!isset($data['title']) || !isset($data['description'])) {
                throw new Exception("Title and description are required");
            }

            $query = "INSERT INTO " . $this->table_name . " 
                    (title, description, start_date, end_date, status, created_by) 
                    VALUES 
                    (:title, :description, :start_date, :end_date, :status, :created_by)";

            $stmt = $this->db->prepare($query);

            // Nettoyage et binding des données
            $title = htmlspecialchars($data['title']);
            $description = htmlspecialchars($data['description']);
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            $status = $data['status'];
            $created_by = $data['created_by'];

            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":created_by", $created_by);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Project created successfully",
                    "project_id" => $this->db->lastInsertId()
                );
            }
            throw new Exception("Failed to create project");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Récupérer tous les projets
     */
    public function getAllProjects() {
        try {
            $query = "SELECT p.*, u.name as creator_name, 
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                     FROM " . $this->table_name . " p 
                     LEFT JOIN users u ON p.created_by = u.id
                     ORDER BY p.created_at DESC";

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
     * Récupérer un projet spécifique
     */
    public function getProject($id) {
        try {
            $query = "SELECT p.*, u.name as creator_name,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                     (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                     FROM " . $this->table_name . " p 
                     LEFT JOIN users u ON p.created_by = u.id 
                     WHERE p.id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return array(
                    "status" => "success",
                    "data" => $stmt->fetch(PDO::FETCH_ASSOC)
                );
            }
            throw new Exception("Project not found");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Mettre à jour un projet
     */
    public function updateProject($id, $data) {
        try {
            // Préparer la requête SQL avec tous les champs pertinents
            $query = "UPDATE " . $this->table_name . " 
                     SET title = :title, 
                         description = :description, 
                         status = :status, 
                         start_date = :start_date, 
                         end_date = :end_date,
                         manager_id = :manager_id,
                         progress_percentage = :progress_percentage,
                         updated_at = NOW()
                     WHERE id = :id";

            $stmt = $this->db->prepare($query);

            // Nettoyage et binding des données
            $title = htmlspecialchars($data['title']);
            $description = htmlspecialchars($data['description']);
            $status = $data['status'];
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            $manager_id = $data['manager_id'];
            $progress_percentage = $data['progress_percentage'];

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
            $stmt->bindParam(":manager_id", $manager_id, PDO::PARAM_INT);
            $stmt->bindParam(":progress_percentage", $progress_percentage);

            if($stmt->execute()) {
                // Mettre à jour les membres du projet si fourni
                if (isset($data['members']) && is_array($data['members'])) {
                    // Supposons qu'il existe une table project_members avec project_id et user_id
                    // On supprime d'abord les membres existants puis on insère les nouveaux
                    $deleteMembers = $this->db->prepare("DELETE FROM project_members WHERE project_id = :project_id");
                    $deleteMembers->bindParam(":project_id", $id, PDO::PARAM_INT);
                    $deleteMembers->execute();

                    $insertMember = $this->db->prepare("INSERT INTO project_members (project_id, user_id) VALUES (:project_id, :user_id)");
                    foreach ($data['members'] as $memberId) {
                        $insertMember->bindParam(":project_id", $id, PDO::PARAM_INT);
                        $insertMember->bindParam(":user_id", $memberId, PDO::PARAM_INT);
                        $insertMember->execute();
                    }
                }

                return array(
                    "status" => "success",
                    "message" => "Project updated successfully"
                );
            }
            throw new Exception("Failed to update project");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Supprimer un projet
     */
    public function deleteProject($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if($stmt->execute()) {
                return array(
                    "status" => "success",
                    "message" => "Project deleted successfully"
                );
            }
            throw new Exception("Failed to delete project");
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Calculer les statistiques du projet
     */
    public function getProjectStats($id) {
        try {
            $query = "SELECT 
                        (SELECT COUNT(*) FROM tasks WHERE project_id = :id) as total_tasks,
                        (SELECT COUNT(*) FROM tasks WHERE project_id = :id AND status = 'completed') as completed_tasks,
                        (SELECT COUNT(*) FROM tasks WHERE project_id = :id AND status = 'in_progress') as in_progress_tasks,
                        (SELECT COUNT(*) FROM tasks WHERE project_id = :id AND status = 'todo') as pending_tasks";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            return array(
                "status" => "success",
                "data" => $stmt->fetch(PDO::FETCH_ASSOC)
            );
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }
}
?>
