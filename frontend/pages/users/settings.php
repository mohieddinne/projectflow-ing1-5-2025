<?php
// Minimal user settings page (language & timezone only)
if (!function_exists('requireAuthentication')) {
    function requireAuthentication() { return true; }
}
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return [
            'settings' => [
                'language' => 'fr',
                'timezone' => 'Europe/Paris'
            ]
        ];
    }
}
if (!function_exists('getTimezones')) {
    function getTimezones() {
        return ['Europe/Paris', 'Europe/London', 'America/New_York', 'Asia/Tokyo'];
    }
}
$pageTitle = "Paramètres";
requireAuthentication();
$user = getCurrentUser();

$headerFile = __DIR__ . '/../../includes/header.php';
if (file_exists($headerFile)) {
    include_once $headerFile;
} else {
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Paramètres - ProjectFlow</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>';
}
?>

<div class="container mt-4">
    <div class="mb-4">
        <h1 class="h4">Paramètres</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="h6 mb-0">Préférences utilisateur</h2>
                </div>
                <div class="card-body">
                    <form id="accountSettingsForm" method="POST" action="/api/users/settings/account">
                        <div class="mb-3">
                            <label for="language" class="form-label">Langue</label>
                            <select id="language" name="language" class="form-select">
                                <option value="fr" <?= $user['settings']['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                <option value="en" <?= $user['settings']['language'] === 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Fuseau horaire</label>
                            <select id="timezone" name="timezone" class="form-select">
                                <?php foreach(getTimezones() as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $user['settings']['timezone'] === $tz ? 'selected' : '' ?>>
                                        <?= $tz ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('accountSettingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        });
        const data = await response.json();
        if (response.ok) {
            alert('Paramètres mis à jour avec succès');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        alert(error.message);
    }
});
</script>

<?php
$footerFile = __DIR__ . '/../../includes/footer.php';
if (file_exists($footerFile)) {
    include_once $footerFile;
} else {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
?>
