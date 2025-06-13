<?php
// Assurez-vous que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirige si déjà connecté
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: /dashboard');
    exit;
}

// Traitement du formulaire d'inscription
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validation simple
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || !$terms) {
        $error = "Veuillez remplir tous les champs et accepter les conditions.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (strlen($name) < 3) {
        $error = "Le nom doit contenir au moins 3 caractères.";
    } else {
        // Pour le développement, accepter n'importe quelle inscription
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = [
            'id' => 1,
            'name' => $name,
            'email' => $email,
            'role' => 'user'
        ];
        header('Location: /dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ProjectFlow</title>
    <link rel="stylesheet" href="/frontend/assets/css/auth.css">
    <link rel="stylesheet" href="/frontend/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="/assets/img/logo.png" alt="ProjectFlow Logo" class="auth-logo">
                <h1>Créer un compte</h1>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form id="registerForm" class="auth-form" method="POST" action="/register" novalidate>
                <div class="form-section">
                    <div class="form-group">
                        <label for="fullName" class="form-label">Nom complet</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text"
                                   id="fullName"
                                   name="name"
                                   class="form-input"
                                   required
                                   minlength="3"
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                   placeholder="Votre nom complet">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email professionnel</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-input"
                                   required
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                   placeholder="votre@email.com">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-input"
                                   required
                                   minlength="8"
                                   placeholder="Créez un mot de passe fort">
                            <button type="button" class="btn-toggle-password" aria-label="Afficher/Masquer le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                   id="confirmPassword"
                                   name="confirm_password"
                                   class="form-input"
                                   required
                                   placeholder="Confirmez votre mot de passe">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox"
                                   name="terms"
                                   id="terms"
                                   required <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                            <span>J'accepte les <a href="/terms" target="_blank">conditions d'utilisation</a></span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </form>
            <div class="auth-footer">
                <p>Déjà inscrit ? <a href="/login">Se connecter</a></p>
            </div>
        </div>
    </div>
    <script src="/frontend/assets/js/auth.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordButtons = document.querySelectorAll('.btn-toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
    </script>
</body>
</html>
