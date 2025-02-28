<?php
// Fixed header.php with vertical layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Système de Réservation</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../resources/css/style.css">
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <i class="bi bi-calendar-check me-2"></i>Système de Réservation
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Accueil</a>
          </li>
          <?php if (isset($_SESSION['user_id'])) : ?>
            <li class="nav-item">
              <a class="nav-link" href="calendrier.php">Calendrier</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="annulation_rdv.php">Mes Rendez-vous</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="contact.php">Contact</a>
          </li>
        </ul>
        <div class="d-flex">
          <?php if (isset($_SESSION['user_id'])) : ?>
            <div class="dropdown">
              <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i> Mon compte
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                <li><a class="dropdown-item" href="calendrier.php"><i class="bi bi-calendar-plus me-2"></i>Prendre RDV</a></li>
                <li><a class="dropdown-item" href="annulation_rdv.php"><i class="bi bi-calendar-x me-2"></i>Mes RDV</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
              </ul>
            </div>
          <?php else : ?>
            <a href="connexion.php" class="btn btn-outline-light me-2"><i class="bi bi-box-arrow-in-right me-1"></i> Connexion</a>
            <a href="inscription.php" class="btn btn-light"><i class="bi bi-person-plus me-1"></i> Inscription</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
  
  <div class="main-content">