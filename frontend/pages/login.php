<?php
// frontend/pages/login.php



// Protection CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// FIN DE LA CORRECTION

// Redirection si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /dashboard');
    exit;
}

// Traitement du formulaire
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Validation améliorée
        if (empty($email)) {
            $error = "L'adresse email est requise";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format d'email invalide";
        } elseif (empty($password)) {
            $error = "Le mot de passe est requis";
        } else {
            // Simulation de connexion (à remplacer par votre logique réelle)
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = [
                'id' => 1,
                'name' => 'Utilisateur Test',
                'email' => $email,
                'role' => 'admin'
            ];
            
            // Régénération de l'ID de session
            session_regenerate_id(true);
            
            // Redirection sécurisée
            header('Location: /dashboard');
            exit;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | NomApplication</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --danger: #ef233c;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 420px;
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .auth-card:hover {
            transform: translateY(-5px);
        }
        
        .auth-header {
            background: var(--primary);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .input-group {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            transition: border-color 0.3s;
        }
        
        .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .input-group-text {
            background: #f5f5f5;
            padding: 0 15px;
            display: flex;
            align-items: center;
            color: #666;
        }
        
        .form-control {
            flex: 1;
            padding: 12px 15px;
            border: none;
            outline: none;
            font-size: 1rem;
        }
        
        .password-toggle {
            background: transparent;
            border: none;
            padding: 0 15px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background: rgba(239, 35, 60, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        .text-muted {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-lock"></i> Connexion</h1>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="email@exemple.com" 
                                required
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="••••••••" 
                                required
                                minlength="8"
                            >
                            <button type="button" class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 8 caractères</small>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="rememberMe" name="rememberMe" class="form-check-input">
                        <label for="rememberMe" class="form-check-label">Se souvenir de moi</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i> Connexion
                    </button>
                    
                    <div class="auth-footer">
                        <p>
                            <a href="/forgot-password">Mot de passe oublié ?</a> • 
                            <a href="/register">Créer un compte</a>
                        </p>
                        <p>
                            <a href="/direct-login" class="text-muted">Mode démo</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password
        const toggleBtn = document.querySelector('.password-toggle');
        const passwordInput = document.getElementById('password');
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Change icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Form validation
        const form = document.getElementById('loginForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        }
    });
    </script>
</body>
</html>