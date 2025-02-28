<?php
// prise_rdv.php
require_once '../config/config.php';
require_once '../utils/functions.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: calendrier.php");
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Jeton CSRF invalide. Veuillez réessayer.");
}

$userId = $_SESSION['user_id'];
$dateRdv = sanitizeInput($_POST['date_rdv'] ?? '');
$timeSlot = sanitizeInput($_POST['time_slot'] ?? '');
$notes = sanitizeInput($_POST['notes'] ?? '');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRdv)) {
    die("Format de date invalide.");
}

// Parse time slot
if (!preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $timeSlot, $matches)) {
    die("Format d'horaire invalide.");
}

$heureDebut = $matches[1];
$heureFin = $matches[2];

try {
    // Check if the slot is still available (might have been booked by someone else)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rendezvous
        WHERE date_rdv = :date_rdv
        AND (
            (heure_debut <= :heure_debut AND heure_fin > :heure_debut) OR
            (heure_debut < :heure_fin AND heure_fin >= :heure_fin) OR
            (heure_debut >= :heure_debut AND heure_fin <= :heure_fin)
        )
    ");
    $stmt->bindValue(':date_rdv', $dateRdv);
    $stmt->bindValue(':heure_debut', $heureDebut);
    $stmt->bindValue(':heure_fin', $heureFin);
    $stmt->execute();
    
    $conflictCount = $stmt->fetchColumn();
    
    if ($conflictCount > 0) {
        // Slot is no longer available
        require_once '../includes/header.php';
        ?>
        <div class="container py-4">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="custom-card text-center">
                        <h2 class="text-danger mb-4">Créneau non disponible</h2>
                        
                        <div class="alert alert-warning">
                            <p>Désolé, ce créneau horaire vient d'être réservé par quelqu'un d'autre.</p>
                            <p>Veuillez sélectionner un autre créneau dans le calendrier.</p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="calendrier.php" class="btn btn-cool">Retour au calendrier</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require_once '../includes/footer.php';
        exit;
    }
    
    // Insert the appointment
    $stmt = $db->prepare("
        INSERT INTO rendezvous (user_id, date_rdv, heure_debut, heure_fin, notes)
        VALUES (:user_id, :date_rdv, :heure_debut, :heure_fin, :notes)
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':date_rdv', $dateRdv);
    $stmt->bindValue(':heure_debut', $heureDebut);
    $stmt->bindValue(':heure_fin', $heureFin);
    $stmt->bindValue(':notes', $notes);
    $stmt->execute();
    
    // Success message
    require_once '../includes/header.php';
    ?>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="custom-card text-center">
                    <h2 class="text-success mb-4">Rendez-vous confirmé</h2>
                    
                    <div class="alert alert-success" id="successAlert">
                        <p>Votre rendez-vous a été enregistré avec succès !</p>
                    </div>
                    
                    <div class="appointment-details mt-4">
                        <h4>Détails du rendez-vous</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($dateRdv)); ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Heure:</strong> <?php echo $heureDebut . ' - ' . $heureFin; ?>
                            </li>
                            <?php if (!empty($notes)) : ?>
                                <li class="list-group-item">
                                    <strong>Notes:</strong> <?php echo nl2br($notes); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="calendrier.php" class="btn btn-cool">Retour au calendrier</a>
                        <a href="annulation_rdv.php" class="btn btn-outline-primary">Gérer mes rendez-vous</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once '../includes/footer.php';
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}