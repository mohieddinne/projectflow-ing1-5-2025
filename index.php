<?php
// index.php à la racine - SOLUTION IDÉALE

// Démarrer la session
session_start();

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir les constantes
define('ROOT_PATH', __DIR__);
define('FRONTEND_PATH', __DIR__ . '/frontend');
define('BACKEND_PATH', __DIR__ . '/backend');

// Inclure le fichier de fonctions
require_once FRONTEND_PATH . '/includes/functions.php';

// Récupération du chemin demandé
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . ltrim($uri, '/');

// Débogage des redirections
error_log("Chemin demandé: " . $path . " | Authentifié: " . (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] ? "OUI" : "NON"));

// Gestion des appels API
if (strpos($path, '/backend/api/') === 0) {
    $apiFile = ROOT_PATH . $path;
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

// Gestion des assets
if (strpos($path, '/frontend/assets/') === 0) {
    $file = ROOT_PATH . $path;
    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($file);
        exit;
    }
}

// Traitement de la connexion directe
if ($path === '/direct-login') {
    $_SESSION['authenticated'] = true;
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'role' => 'admin'
    ];
    
    header('Location: /dashboard');
    exit;
}

// Débogage des variables de session
if ($path === '/debug-session') {
    echo '<pre>';
    echo 'Session ID: ' . session_id() . "\n";
    echo 'Session status: ' . session_status() . "\n";
    echo 'Session data: ';
    print_r($_SESSION);
    echo '</pre>';
    exit;
}

// Page pour effacer la session
if ($path === '/clear-session') {
    session_unset();
    session_destroy();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    echo '<h1>Session effacée</h1>';
    echo '<p>La session a été complètement effacée.</p>';
    echo '<p><a href="/">Retour à l\'accueil</a></p>';
    exit;
}

// Liste des routes publiques
$public_routes = ['/login', '/register', '/', '/direct-login', '/debug-session', '/clear-session', '/test.php'];

// Authentification obligatoire sauf pour les routes publiques
if (!in_array($path, $public_routes)) {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        error_log("Redirection vers /login car non authentifié");
        header('Location: /login');
        exit;
    }
}

/**
 * Fonction pour inclure un fichier en toute sécurité
 */
function safeRequire($file, $defaultMessage = null) {
    if (file_exists($file)) {
        try {
            require $file;
            return true;
        } catch (Throwable $e) {
            error_log("Erreur lors de l'inclusion de $file: " . $e->getMessage());
            if ($defaultMessage) {
                echo $defaultMessage;
            } else {
                echo '<div class="container mt-5">';
                echo '<div class="alert alert-danger">';
                echo '<h4 class="alert-heading">Erreur lors du chargement de la page</h4>';
                echo '<p>Une erreur est survenue lors du chargement de cette page.</p>';
                echo '<p><a href="/dashboard" class="btn btn-primary">Retour au tableau de bord</a></p>';
                echo '</div></div>';
            }
            return false;
        }
    } else {
        error_log("Fichier non trouvé: $file");
        if ($defaultMessage) {
            echo $defaultMessage;
        } else {
            echo '<div class="container mt-5">';
            echo '<div class="alert alert-warning">';
            echo '<h4 class="alert-heading">Page non disponible</h4>';
            echo '<p>Cette page n\'est pas encore disponible.</p>';
            echo '<p><a href="/dashboard" class="btn btn-primary">Retour au tableau de bord</a></p>';
            echo '</div></div>';
        }
        return false;
    }
}

/**
 * Helper pour charger une page "view" avec un id
 * Exemple d'utilisation dans le switch:
 *   case '/projects/view':
 *       requireViewPage(FRONTEND_PATH . '/pages/projects/view.php');
 *       break;
 */
function requireViewPage($file) {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo '<div class="container mt-5">';
        echo '<div class="alert alert-danger">';
        echo '<h4 class="alert-heading">ID manquant ou invalide</h4>';
        echo '<p>L\'identifiant (id) est requis pour afficher cette page.</p>';
        echo '<p><a href="javascript:history.back()" class="btn btn-secondary">Retour</a></p>';
        echo '</div></div>';
        return false;
    }
    // L'id est disponible dans $_GET['id']
    return safeRequire($file);
}

// Routing principal
switch ($path) {
    case '/':
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header('Location: /dashboard');
        } else {
            header('Location: /login');
        }
        exit;
        break;

    case '/login':
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header('Location: /dashboard');
            exit;
        }
        
        safeRequire(FRONTEND_PATH . '/pages/login.php');
        break;

    case '/register':
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header('Location: /dashboard');
            exit;
        }
        safeRequire(FRONTEND_PATH . '/pages/register.php');
        break;

    case '/dashboard':
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            header('Location: /login');
            exit;
        }
        safeRequire(FRONTEND_PATH . '/pages/dashboard.php');
        break;

    // Dans le switch-case de votre index.php, ajoutez ou modifiez le cas pour /projects
case '/projects':
    error_log("Tentative d'accès à la page des projets");
    try {
        require FRONTEND_PATH . '/pages/projects/index.php';
    } catch (Throwable $e) {
        error_log("Erreur lors du chargement de la page projets: " . $e->getMessage());
        echo '<div class="container mt-5">';
        echo '<h1>Erreur lors du chargement de la page</h1>';
        echo '<p>Une erreur est survenue lors du chargement de la page des projets.</p>';
        echo '<p><a href="/dashboard" class="btn btn-primary">Retour au tableau de bord</a></p>';
        echo '</div>';
    }
    break;


    case '/projects/create':
        safeRequire(FRONTEND_PATH . '/pages/projects/create.php');
        break;

    case '/projects/edit':
    requireViewPage(FRONTEND_PATH . '/pages/projects/edit.php');
    break;

    case '/projects/view':
        requireViewPage(FRONTEND_PATH . '/pages/projects/view.php');
        break;

    case '/projects/store':
        safeRequire(FRONTEND_PATH . '/pages/projects/store.php');
    break;


    case '/tasks':
        safeRequire(FRONTEND_PATH . '/pages/tasks/index.php');
        break;

    case '/tasks/create':
        safeRequire(FRONTEND_PATH . '/pages/tasks/create.php');
        break;

    case '/tasks/edit':
        safeRequire(FRONTEND_PATH . '/pages/tasks/edit.php');
        break;

    case '/profile':
        safeRequire(FRONTEND_PATH . '/pages/users/profile.php');
        break;
        case '/users':
            safeRequire(FRONTEND_PATH . '/pages/users/index.php');
            break;
            case '/users/edit':
                requireViewPage(FRONTEND_PATH . '/pages/users/edit.php');
                break;
                case '/users/create':
                    safeRequire(FRONTEND_PATH . '/pages/users/create.php');
                    break;

    case '/settings':
        safeRequire(FRONTEND_PATH . '/pages/users/settings.php');
        break;

    case '/logout':
        session_unset();
        session_destroy();
        
        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        header('Location: /login');
        exit;
        break;

    default:
        // Page 404
        http_response_code(404);
        $page404 = FRONTEND_PATH . '/pages/404.php';
        if (file_exists($page404)) {
            require $page404;
        } else {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Page non trouvée</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-5 text-center">
                    <h1 class="display-1">404</h1>
                    <h2>Page non trouvée</h2>
                    <p>La page que vous recherchez n\'existe pas.</p>
                    <a href="/" class="btn btn-primary">Retour à l\'accueil</a>
                </div>
            </body>
            </html>';
        }
        break;
}
