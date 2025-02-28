<?php
// calendrier.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

require_once '../config/config.php';
require_once '../utils/functions.php';

// Get current month and year or use the ones from the query string
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Ensure the month is valid (1-12)
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get the first day of the month
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Get the previous and next month links
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

// Fetch booked time slots for the current month
try {
    $stmt = $db->prepare("
        SELECT date_rdv, heure_debut, heure_fin
        FROM rendezvous
        WHERE MONTH(date_rdv) = :month AND YEAR(date_rdv) = :year
    ");
    $stmt->bindValue(':month', $month, PDO::PARAM_INT);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create an array of booked dates for easy lookup
    $bookedDates = [];
    foreach ($bookedSlots as $slot) {
        $date = $slot['date_rdv'];
        if (!isset($bookedDates[$date])) {
            $bookedDates[$date] = [];
        }
        $bookedDates[$date][] = [
            'start' => $slot['heure_debut'],
            'end' => $slot['heure_fin']
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
                                    $hasBookings = isset($bookedDates[$currentDate]) && !empty($bookedDates[$currentDate]);
                                    if ($hasBookings) {
                                        $dateClass .= ' has-bookings';
                                    }
                                    
                                    echo "<td class='calendar-day $dateClass'>";
                                    echo "<div class='day-number'>$currentDay</div>";
                                    
                                    // If the date is not in the past, add a booking button
                                    if ($currentDate >= $today) {
                                        echo "<div class='booking-btn-container'>";
                                        echo "<a href='prise_rdv_form.php?date=$currentDate' class='btn btn-sm btn-cool'>Réserver</a>";
                                        echo "</div>";
                                    }
                                    
                                    // If there are bookings, show them
                                    if ($hasBookings) {
                                        echo "<div class='bookings-info'>";
                                        echo "<small>" . count($bookedDates[$currentDate]) . " réservation(s)</small>";
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
                    <div class="d-flex gap-3 justify-content-center">
                        <div><span class="legend-item today"></span> Aujourd'hui</div>
                        <div><span class="legend-item has-bookings"></span> Jours avec réservations</div>
                        <div><span class="legend-item past-date"></span> Jours passés</div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="annulation_rdv.php" class="btn btn-outline-danger">Gérer mes rendez-vous</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>