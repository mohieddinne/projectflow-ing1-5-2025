<?php
class AuthController {
    private $db;
    private $table_name = "users";

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Authentification utilisateur
     */
    public function login($email, $password) {
        try {
            // Vérifier les données
            if(empty($email) || empty($password)) {
                throw new Exception("Email and password are required");
            }

            // Préparer la requête
            $query = "SELECT id, name, email, password, role 
                     FROM " . $this->table_name . " 
                     WHERE email = :email";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $row['password'])) {
                    // Générer le token JWT
                    $token = $this->generateJWTToken($row);
                    
                    return array(
                        "status" => "success",
                        "message" => "Login successful",
                        "token" => $token,
                        "user" => array(
                            "id" => $row['id'],
                            "name" => $row['name'],
                            "email" => $row['email'],
                            "role" => $row['role']
                        )
                    );
                } else {
                    throw new Exception("Invalid password");
                }
            } else {
                throw new Exception("User not found");
            }
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Vérification du token
     */
    public function validateToken($token) {
        try {
            // Décoder et vérifier le token JWT
            $decoded = $this->decodeJWTToken($token);
            
            if($decoded) {
                return array(
                    "status" => "success",
                    "user_id" => $decoded->user_id,
                    "role" => $decoded->role
                );
            } else {
                throw new Exception("Invalid token");
            }
        } catch(Exception $e) {
            return array(
                "status" => "error",
                "message" => $e->getMessage()
            );
        }
    }

    /**
     * Vérification des permissions
     */
    public function checkPermission($user_id, $required_role) {
        try {
            $query = "SELECT role FROM " . $this->table_name . " WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasPermission($row['role'], $required_role);
            }
            return false;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Génération du token JWT
     */
    private function generateJWTToken($user) {
        $secret_key = "your_secret_key"; // À stocker dans config.php
        $issued_at = time();
        $expiration = $issued_at + (60 * 60); // 1 heure

        $payload = array(
            "iat" => $issued_at,
            "exp" => $expiration,
            "user_id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role']
        );

        return JWT::encode($payload, $secret_key);
    }

    /**
     * Décodage du token JWT
     */
    private function decodeJWTToken($token) {
        $secret_key = "your_secret_key";
        try {
            return JWT::decode($token, $secret_key, array('HS256'));
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Vérification des rôles
     */
    private function hasPermission($user_role, $required_role) {
        $roles_hierarchy = array(
            'admin' => ['admin', 'manager', 'member'],
            'manager' => ['manager', 'member'],
            'member' => ['member']
        );

        return in_array($required_role, $roles_hierarchy[$user_role]);
    }
}
?>
