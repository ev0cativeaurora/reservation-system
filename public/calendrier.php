<?php
// calendrier.php - Mise à jour pour traiter les réservations
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

$userId = $_SESSION['user_id'];
$errors = [];
$successMessage = '';

// Traitement du formulaire de réservation si soumis depuis prise_rdv_form.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserver') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
        $dateRdv = sanitizeInput($_POST['date_rdv'] ?? '');
        $timeSlot = sanitizeInput($_POST['time_slot'] ?? '');
        $motif = sanitizeInput($_POST['motif'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $emailConfirmation = isset($_POST['email_confirmation']) ? 1 : 0;

        // Valider le format de la date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRdv)) {
            $errors[] = "Format de date invalide.";
        }

        // Analyser le créneau horaire
        if (!preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $timeSlot, $matches)) {
            $errors[] = "Format d'horaire invalide.";
        } else {
            $heureDebut = $matches[1];
            $heureFin = $matches[2];

            try {
                // Vérifier si le créneau est toujours disponible
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
                    $errors[] = "Ce créneau horaire n'est plus disponible. Veuillez en choisir un autre.";
                } else {
                    // Insérer le rendez-vous
                    $insertStmt = $db->prepare("
                        INSERT INTO rendezvous (user_id, date_rdv, heure_debut, heure_fin, motif, notes, created_at)
                        VALUES (:user_id, :date_rdv, :heure_debut, :heure_fin, :motif, :notes, NOW())
                    ");
                    
                    $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                    $insertStmt->bindValue(':date_rdv', $dateRdv);
                    $insertStmt->bindValue(':heure_debut', $heureDebut);
                    $insertStmt->bindValue(':heure_fin', $heureFin);
                    $insertStmt->bindValue(':motif', $motif);
                    $insertStmt->bindValue(':notes', $notes);
                    $insertStmt->execute();
                    
                    $rdvId = $db->lastInsertId();
                    
                    // Récupérer les informations de l'utilisateur
                    if ($emailConfirmation) {
                        $userStmt = $db->prepare("SELECT prenom, nom, email FROM users WHERE id = :user_id");
                        $userStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                        $userStmt->execute();
                        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Simuler l'envoi d'un email (à remplacer par un envoi réel en production)
                        $emailSent = true;
                    }
                    
                    $successMessage = "Votre rendez-vous a été réservé avec succès pour le " . date('d/m/Y', strtotime($dateRdv)) . 
                                    " de " . $heureDebut . " à " . $heureFin . ".";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    }
}

// Récupérer le mois et l'année actuels ou depuis les paramètres
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// S'assurer que le mois est valide (1-12)
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Obtenir le premier jour du mois
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Traduire le nom du mois en français
$moisFrancais = [
    'January' => 'Janvier',
    'February' => 'Février',
    'March' => 'Mars',
    'April' => 'Avril',
    'May' => 'Mai',
    'June' => 'Juin',
    'July' => 'Juillet',
    'August' => 'Août',
    'September' => 'Septembre',
    'October' => 'Octobre',
    'November' => 'Novembre',
    'December' => 'Décembre'
];

if (isset($moisFrancais[$monthName])) {
    $monthName = $moisFrancais[$monthName];
}

// Liens vers le mois précédent et suivant
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Récupérer tous les créneaux réservés pour le mois en cours
try {
    $stmt = $db->prepare("
        SELECT date_rdv, heure_debut, heure_fin, user_id 
        FROM rendezvous
        WHERE MONTH(date_rdv) = :month AND YEAR(date_rdv) = :year
    ");
    $stmt->bindValue(':month', $month, PDO::PARAM_INT);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau des dates réservées pour faciliter la recherche
    $bookedDates = [];
    foreach ($bookedSlots as $slot) {
        $date = $slot['date_rdv'];
        if (!isset($bookedDates[$date])) {
            $bookedDates[$date] = [];
        }
        
        $bookedDates[$date][] = [
            'start' => $slot['heure_debut'],
            'end' => $slot['heure_fin'],
            'isCurrentUser' => ($slot['user_id'] == $userId)
        ];
    }
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="custom-card">
                <h2 class="text-center mb-4">Calendrier des Rendez-vous</h2>
                
                <?php if (!empty($errors)) : ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage) : ?>
                <div class="alert alert-success" id="successAlert">
                    <?php echo $successMessage; ?>
                </div>
                <?php endif; ?>
                
                <!-- Month Navigation -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-chevron-left"></i> Mois précédent
                    </a>
                    <h3 class="mb-0"><?php echo ucfirst($monthName) . ' ' . $year; ?></h3>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-primary">
                        Mois suivant <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
                
                <!-- Calendar Table -->
                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr>
                                <th>Dimanche</th>
                                <th>Lundi</th>
                                <th>Mardi</th>
                                <th>Mercredi</th>
                                <th>Jeudi</th>
                                <th>Vendredi</th>
                                <th>Samedi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Fill in blank days until the first day of the month
                                for ($i = 0; $i < $dayOfWeek; $i++) {
                                    echo "<td class='empty-day'></td>";
                                }
                                
                                // Fill in the days of the month
                                $currentDay = 1;
                                while ($currentDay <= $numberDays) {
                                    // If we're at the start of a row, start a new row
                                    if ($dayOfWeek == 7) {
                                        $dayOfWeek = 0;
                                        echo "</tr><tr>";
                                    }
                                    
                                    // Format the current date
                                    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                                    $today = date('Y-m-d');
                                    $dateClass = '';
                                    
                                    // Check if it's today
                                    if ($currentDate == $today) {
                                        $dateClass = 'today';
                                    }
                                    
                                    // Check if the date is in the past
                                    if ($currentDate < $today) {
                                        $dateClass .= ' past-date';
                                    }
                                    
                                    // Check if the date has any bookings
                                    $hasBookings = isset($bookedDates[$currentDate]) && count($bookedDates[$currentDate]) > 0;
                                    
                                    // Check if the current user has bookings on this date
                                    $hasUserBookings = false;
                                    if ($hasBookings) {
                                        foreach ($bookedDates[$currentDate] as $slot) {
                                            if ($slot['isCurrentUser']) {
                                                $hasUserBookings = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if ($hasBookings) {
                                        $dateClass .= ' has-bookings';
                                    }
                                    
                                    if ($hasUserBookings) {
                                        $dateClass .= ' user-bookings';
                                    }
                                    
                                    echo "<td class='calendar-day $dateClass'>";
                                    echo "<div class='day-number'>$currentDay</div>";
                                    
                                    // If the date is not in the past, add a booking button
                                    if ($currentDate >= $today) {
                                        echo "<div class='booking-btn-container'>";
                                        
                                        if ($hasUserBookings) {
                                            // L'utilisateur a déjà des réservations ce jour
                                            echo "<div class='booking-status user-booking'>";
                                            echo "<i class='bi bi-calendar-check'></i> Vous avez un RDV";
                                            echo "</div>";
                                            echo "<a href='annulation_rdv.php' class='btn btn-sm btn-outline-primary'>Voir mes RDV</a>";
                                        } else {
                                            echo "<a href='prise_rdv_form.php?date=$currentDate' class='btn btn-sm btn-primary'>Réserver</a>";
                                        }
                                        
                                        echo "</div>";
                                    }
                                    
                                    // If there are bookings, show them
                                    if ($hasUserBookings) {
                                        echo "<div class='user-bookings-info'>";
                                        foreach ($bookedDates[$currentDate] as $slot) {
                                            if ($slot['isCurrentUser']) {
                                                echo "<small>" . substr($slot['start'], 0, 5) . "</small>";
                                            }
                                        }
                                        echo "</div>";
                                    }
                                    
                                    echo "</td>";
                                    
                                    // Move to the next day
                                    $currentDay++;
                                    $dayOfWeek++;
                                }
                                
                                // Fill in empty cells for the last row
                                if ($dayOfWeek != 7) {
                                    $remainingDays = 7 - $dayOfWeek;
                                    for ($i = 0; $i < $remainingDays; $i++) {
                                        echo "<td class='empty-day'></td>";
                                    }
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="calendar-legend mt-3">
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <div><span class="legend-item today"></span> Aujourd'hui</div>
                        <div><span class="legend-item user-bookings"></span> Vos rendez-vous</div>
                        <div><span class="legend-item has-bookings"></span> Jours avec réservations</div>
                        <div><span class="legend-item past-date"></span> Jours passés</div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="annulation_rdv.php" class="btn btn-outline-primary">Gérer mes rendez-vous</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le calendrier */
.calendar-table th {
    background-color: #f8f9fa;
    text-align: center;
    padding: 10px;
}

.calendar-table td {
    height: 120px;
    width: 14.28%;
    padding: 5px;
    vertical-align: top;
    border: 1px solid #dee2e6;
    position: relative;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}

.empty-day {
    background-color: #f9f9f9;
}

.today {
    background-color: rgba(76, 175, 80, 0.1);
    border: 2px solid #4CAF50;
}

.has-bookings {
    background-color: rgba(255, 193, 7, 0.1);
}

.user-bookings {
    background-color: rgba(13, 110, 253, 0.1);
}

.past-date {
    opacity: 0.6;
}

.booking-btn-container {
    position: absolute;
    bottom: 5px;
    left: 0;
    right: 0;
    text-align: center;
}

.booking-status {
    font-size: 0.8rem;
    padding: 2px 5px;
    border-radius: 3px;
    margin-bottom: 5px;
}

.user-booking {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.user-bookings-info {
    font-size: 0.75rem;
    position: absolute;
    top: 25px;
    right: 5px;
    color: #0d6efd;
}

.legend-item {
    display: inline-block;
    width: 15px;
    height: 15px;
    margin-right: 5px;
    vertical-align: middle;
    border-radius: 3px;
}

.legend-item.today {
    background-color: rgba(76, 175, 80, 0.1);
    border: 2px solid #4CAF50;
}

.legend-item.user-bookings {
    background-color: rgba(13, 110, 253, 0.1);
}

.legend-item.has-bookings {
    background-color: rgba(255, 193, 7, 0.1);
}

.legend-item.past-date {
    background-color: #f9f9f9;
    opacity: 0.6;
}

/* Responsive styles */
@media (max-width: 768px) {
    .calendar-table td {
        height: 100px;
    }
    
    .day-number {
        font-size: 0.9rem;
    }
    
    .booking-btn-container .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .calendar-table td {
        height: 80px;
    }
    
    .booking-btn-container {
        bottom: 2px;
    }
    
    .booking-btn-container .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>