<?php
// index.php with vertical layout
require_once '../includes/header.php';
?>

<div class="content-container">
    <div class="hero-section">
        <h1>Bienvenue sur notre Système de Réservation</h1>
        <p class="lead">Réservez facilement vos rendez-vous en quelques clics</p>
        
        <?php if (isset($_SESSION['user_id'])) : ?>
            <div class="mt-4">
                <a href="calendrier.php" class="btn btn-primary btn-lg">Prendre un rendez-vous</a>
            </div>
        <?php else : ?>
            <div class="mt-4">
                <a href="connexion.php" class="btn btn-primary me-2">Se connecter</a>
                <a href="inscription.php" class="btn btn-outline-primary">S'inscrire</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="feature-boxes">
        <div class="feature-box">
            <i class="bi bi-calendar-check"></i>
            <h3>Réservation Simple</h3>
            <p>Notre calendrier interactif vous permet de visualiser les créneaux disponibles et de réserver celui qui vous convient.</p>
        </div>
        
        <div class="feature-box">
            <i class="bi bi-person-circle"></i>
            <h3>Compte Personnel</h3>
            <p>Créez votre compte pour gérer vos rendez-vous et garder une trace de vos réservations passées et à venir.</p>
        </div>
        
        <div class="feature-box">
            <i class="bi bi-shield-check"></i>
            <h3>Sécurité Garantie</h3>
            <p>Notre système assure la sécurité de vos données personnelles avec des protocoles de sécurité modernes.</p>
        </div>
    </div>

    <div class="content-container">
        <h2 class="text-center mb-4">Comment ça marche ?</h2>
        
        <div class="steps-container">
            <div class="step">
                <div class="step-circle">1</div>
                <h5>Inscrivez-vous</h5>
                <p>Créez votre compte en quelques instants</p>
            </div>
            
            <div class="step">
                <div class="step-circle">2</div>
                <h5>Connectez-vous</h5>
                <p>Accédez à votre espace personnel</p>
            </div>
            
            <div class="step">
                <div class="step-circle">3</div>
                <h5>Choisissez une date</h5>
                <p>Sélectionnez le jour qui vous convient</p>
            </div>
            
            <div class="step">
                <div class="step-circle">4</div>
                <h5>Confirmez</h5>
                <p>Validez votre rendez-vous</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>