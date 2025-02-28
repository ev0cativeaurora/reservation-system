<?php
// compte_supprime.php
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="custom-card text-center">
                <h2 class="mb-4">Compte Supprimé</h2>
                
                <div class="alert alert-success">
                    <p>Votre compte a été supprimé avec succès.</p>
                    <p>Toutes vos données personnelles ont été effacées de notre base de données.</p>
                </div>
                
                <p class="mt-4">Nous espérons vous revoir bientôt !</p>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-cool">Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>