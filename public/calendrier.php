<?php
// calendrier.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

require_once '../includes/header.php';
?>

<h2>Calendrier des disponibilités</h2>
<p>Here, you could display a calendar or time slots. For simplicity, let's offer a simple date & time form.</p>

<form action="prise_rdv.php" method="POST">
  <!-- Add CSRF token if you want to secure this form as well -->
  <div class="mb-3">
    <label for="date_rdv" class="form-label">Choose Date:</label>
    <input type="date" name="date_rdv" id="date_rdv" class="form-control" required>
  </div>
  <div class="mb-3">
    <label for="heure_debut" class="form-label">Start Time:</label>
    <input type="time" name="heure_debut" id="heure_debut" class="form-control" required>
  </div>
  <div class="mb-3">
    <label for="heure_fin" class="form-label">End Time:</label>
    <input type="time" name="heure_fin" id="heure_fin" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Book Appointment</button>
</form>

<?php require_once '../includes/footer.php'; ?>
