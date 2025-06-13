<?php
class CategoryController {
    private $db;
    private $table_name = "categories";

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllCategories() {
        try {
            $query = "SELECT id, name, description 
                     FROM " . $this->table_name . " 
                     ORDER BY name ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCategory($id) {
        try {
            $query = "SELECT id, name, description 
                     FROM " . $this->table_name . " 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                throw new Exception("Category not found");
            }
            
            return $category;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createCategory($data) {
        try {
            if (empty($data['name'])) {
                throw new Exception("Category name is required");
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (name, description) 
                     VALUES 
                     (:name, :description)";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":name", htmlspecialchars(strip_tags($data['name'])));
            $stmt->bindParam(":description", htmlspecialchars(strip_tags($data['description'] ?? '')));
            
            if ($stmt->execute()) {
                return [
                    "status" => "success",
                    "message" => "Category created successfully",
                    "category_id" => $this->db->lastInsertId()
                ];
            }
            throw new Exception("Failed to create category");
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    public function updateCategory($id, $data) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET name = :name, description = :description 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":name", htmlspecialchars(strip_tags($data['name'])));
            $stmt->bindParam(":description", htmlspecialchars(strip_tags($data['description'] ?? '')));
            
            if ($stmt->execute()) {
                return [
                    "status" => "success",
                    "message" => "Category updated successfully"
                ];
            }
            throw new Exception("Failed to update category");
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    public function deleteCategory($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                return [
                    "status" => "success",
                    "message" => "Category deleted successfully"
                ];
            }
            throw new Exception("Failed to delete category");
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }
}