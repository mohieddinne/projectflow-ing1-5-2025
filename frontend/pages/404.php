<?php
// frontend/pages/404.php
$pageTitle = "Page non trouvée";
?>

<!-- Inclure l'en-tête -->
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center mt-5 pt-5">
                <div class="error-code">404</div>
                <h1 class="display-4 mb-4">Page non trouvée</h1>
                <p class="lead text-muted mb-5">La page que vous recherchez n'existe pas ou a été déplacée.</p>
                <div class="error-actions">
                    <a href="/dashboard" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>
                        Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-code {
    font-size: 150px;
    font-weight: 700;
    color: #f8f9fa;
    text-shadow: 1px 1px 1px #343a40;
    background: #f8f9fa;
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 30px;
}

.error-actions {
    margin-top: 30px;
    margin-bottom: 30px;
}
</style>

<!-- Inclure le pied de page -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
