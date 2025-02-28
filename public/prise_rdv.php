<?php
// prise_rdv.php
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$userId       = $_SESSION['user_id'];
$dateRdv      = $_POST['date_rdv'] ?? '';
$heureDebut   = $_POST['heure_debut'] ?? '';
$heureFin     = $_POST['heure_fin'] ?? '';

// Basic validation
if (empty($dateRdv) || empty($heureDebut) || empty($heureFin)) {
    die("Please provide all appointment details.");
}

try {
    // Check availability
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rendezvous
        WHERE date_rdv = :date_rdv
          AND (
              (heure_debut < :heure_fin AND heure_fin > :heure_debut)
          )
    ");
    $stmt->bindValue(':date_rdv', $dateRdv);
    $stmt->bindValue(':heure_fin', $heureFin);
    $stmt->bindValue(':heure_debut', $heureDebut);
    $stmt->execute();
    $conflictCount = $stmt->fetchColumn();

    if ($conflictCount > 0) {
        // Slot is not available
        echo "Sorry, this timeslot is already booked. <a href='calendrier.php'>Go back</a>";
    } else {
        // Insert new appointment
        $insert = $db->prepare("
            INSERT INTO rendezvous (user_id, date_rdv, heure_debut, heure_fin)
            VALUES (:user_id, :date_rdv, :heure_debut, :heure_fin)
        ");
        $insert->bindValue(':user_id', $userId);
        $insert->bindValue(':date_rdv', $dateRdv);
        $insert->bindValue(':heure_debut', $heureDebut);
        $insert->bindValue(':heure_fin', $heureFin);
        $insert->execute();

        echo "Appointment booked successfully! <a href='calendrier.php'>Back to calendar</a>";
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
