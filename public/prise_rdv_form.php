<?php
// prise_rdv_form.php - Version corrigée
require_once '../config/config.php';
require_once '../utils/functions.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$userId = $_SESSION['user_id'];
$date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');

// Valider le format de la date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header("Location: calendrier.php");
    exit;
}

// Vérifier si la date est dans le passé
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    header("Location: calendrier.php?error=past_date");
    exit;
}

// Récupérer les créneaux déjà réservés pour la date sélectionnée
try {
    $stmt = $db->prepare("
        SELECT r.heure_debut, r.heure_fin, u.prenom, u.nom
        FROM rendezvous r
        JOIN users u ON r.user_id = u.id
        WHERE r.date_rdv = :date
        ORDER BY r.heure_debut
    ");
    $stmt->bindValue(':date', $date);
    $stmt->execute();
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

// Définir les créneaux disponibles (par exemple, de 9h à 17h avec des intervalles de 1 heure)
$startHour = 9; // 9h00
$endHour = 17;  // 17h00
$interval = 60; // 60 minutes

$availableSlots = [];
$currentTime = $startHour * 60; // Conversion en minutes
$endTime = $endHour * 60;

while ($currentTime < $endTime) {
    $slotStart = sprintf('%02d:%02d', floor($currentTime / 60), $currentTime % 60);
    $slotEnd = sprintf('%02d:%02d', floor(($currentTime + $interval) / 60), ($currentTime + $interval) % 60);
    
    // Vérifier si le créneau est disponible
    $isAvailable = true;
    foreach ($bookedSlots as $bookedSlot) {
        if (
            ($slotStart >= $bookedSlot['heure_debut'] && $slotStart < $bookedSlot['heure_fin']) ||
            ($slotEnd > $bookedSlot['heure_debut'] && $slotEnd <= $bookedSlot['heure_fin']) ||
            ($slotStart <= $bookedSlot['heure_debut'] && $slotEnd >= $bookedSlot['heure_fin'])
        ) {
            $isAvailable = false;
            break;
        }
    }
    
    if ($isAvailable) {
        $availableSlots[] = [
            'start' => $slotStart,
            'end' => $slotEnd
        ];
    }
    
    $currentTime += $interval;
}

// Récupérer les informations de l'utilisateur courant
try {
    $userStmt = $db->prepare("SELECT prenom, nom, email FROM users WHERE id = :id");
    $userStmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de récupération des informations utilisateur: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<div class="content-container">
    <h2 class="text-center mb-4">Prendre un Rendez-vous</h2>
    
    <div class="alert alert-info">
        <strong>Date sélectionnée:</strong> <?php echo date('d/m/Y', strtotime($date)); ?>
    </div>
    
    <?php if (empty($availableSlots)) : ?>
        <div class="alert alert-warning">
            <p>Aucun créneau disponible pour cette date.</p>
            <p>Veuillez sélectionner une autre date dans le calendrier.</p>
        </div>
        <div class="text-center mt-3">
            <a href="calendrier.php" class="btn btn-primary">Retour au calendrier</a>
        </div>
    <?php else : ?>
        <!-- IMPORTANT: Changez l'action du formulaire pour pointer vers calendrier.php au lieu de prise_rdv.php -->
        <form action="calendrier.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="reserver">
            <input type="hidden" name="date_rdv" value="<?php echo $date; ?>">
            
            <div class="mb-4">
                <label for="time_slot" class="form-label">Choisissez un créneau horaire :</label>
                <select class="form-select" id="time_slot" name="time_slot" required>
                    <option value="">-- Sélectionnez un horaire --</option>
                    <?php foreach ($availableSlots as $slot) : ?>
                        <option value="<?php echo $slot['start'] . '-' . $slot['end']; ?>">
                            <?php echo $slot['start'] . ' - ' . $slot['end']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="motif" class="form-label">Motif du rendez-vous :</label>
                <select class="form-select" id="motif" name="motif" required>
                    <option value="">-- Sélectionnez un motif --</option>
                    <option value="Consultation">Consultation</option>
                    <option value="Suivi">Suivi</option>
                    <option value="Examen">Examen</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="notes" class="form-label">Notes supplémentaires (optionnel) :</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="email_confirmation" class="form-label">Recevoir une confirmation par email :</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="email_confirmation" name="email_confirmation" value="1" checked>
                    <label class="form-check-label" for="email_confirmation">
                        Envoyer une confirmation à <?php echo htmlspecialchars($user['email']); ?>
                    </label>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="calendrier.php" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Confirmer le rendez-vous</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>