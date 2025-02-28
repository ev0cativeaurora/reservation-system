<?php
// annulation_rdv.php
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
$errors = [];
$successMessage = '';

// Traiter l'annulation d'un rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    // Valider le token CSRF
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
        $appointmentId = (int)$_POST['appointment_id'];
        
        try {
            // Récupérer les informations du rendez-vous avant la suppression
            $infoStmt = $db->prepare("
                SELECT r.date_rdv, r.heure_debut, r.heure_fin, r.motif, u.email, u.prenom, u.nom
                FROM rendezvous r
                JOIN users u ON r.user_id = u.id
                WHERE r.id = :id AND r.user_id = :user_id
            ");
            $infoStmt->bindValue(':id', $appointmentId, PDO::PARAM_INT);
            $infoStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $infoStmt->execute();
            $rdvInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rdvInfo) {
                // Supprimer le rendez-vous
                $deleteStmt = $db->prepare("DELETE FROM rendezvous WHERE id = :id AND user_id = :user_id");
                $deleteStmt->bindValue(':id', $appointmentId, PDO::PARAM_INT);
                $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                if ($deleteStmt->rowCount() > 0) {
                    $successMessage = "Votre rendez-vous a été annulé avec succès.";
                    
                    // Envoyer un email de confirmation d'annulation
                    if (isset($_POST['send_email']) && $_POST['send_email'] == 1) {
                        sendCancellationEmail(
                            $rdvInfo['email'],
                            $rdvInfo['prenom'],
                            $rdvInfo['nom'],
                            $rdvInfo['date_rdv'],
                            $rdvInfo['heure_debut'],
                            $rdvInfo['heure_fin'],
                            $rdvInfo['motif'],
                            $appointmentId
                        );
                        $successMessage .= " Un email de confirmation d'annulation a été envoyé.";
                    }
                } else {
                    $errors[] = "Aucun rendez-vous n'a été annulé. Veuillez réessayer.";
                }
            } else {
                $errors[] = "Rendez-vous introuvable ou vous n'êtes pas autorisé à l'annuler.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}

// Récupérer tous les rendez-vous à venir pour l'utilisateur
try {
    $stmt = $db->prepare("
        SELECT id, date_rdv, heure_debut, heure_fin, motif, notes
        FROM rendezvous
        WHERE user_id = :user_id
        ORDER BY date_rdv ASC, heure_debut ASC
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Séparer les rendez-vous à venir et passés
    $upcomingAppointments = [];
    $pastAppointments = [];
    $today = date('Y-m-d');
    $now = date('H:i:s');
    
    foreach ($allAppointments as $appointment) {
        if ($appointment['date_rdv'] > $today || 
            ($appointment['date_rdv'] == $today && $appointment['heure_debut'] > $now)) {
            $upcomingAppointments[] = $appointment;
        } else {
            $pastAppointments[] = $appointment;
        }
    }
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des rendez-vous: " . $e->getMessage();
    $upcomingAppointments = [];
    $pastAppointments = [];
}

require_once '../includes/header.php';
?>

<div class="content-container">
    <h2 class="text-center mb-4">Gestion de mes Rendez-vous</h2>
    
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
    
    <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                Rendez-vous à venir (<?php echo count($upcomingAppointments); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">
                Rendez-vous passés (<?php echo count($pastAppointments); ?>)
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="appointmentTabsContent">
        <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
            <?php if (empty($upcomingAppointments)) : ?>
                <div class="alert alert-info">
                    Vous n'avez aucun rendez-vous à venir.
                </div>
                <div class="text-center mt-3">
                    <a href="calendrier.php" class="btn btn-primary">Prendre un rendez-vous</a>
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Motif</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $appointment) : ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($appointment['date_rdv'])); ?></td>
                                    <td><?php echo substr($appointment['heure_debut'], 0, 5) . ' - ' . substr($appointment['heure_fin'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['motif'] ?? 'Non spécifié'); ?></td>
                                    <td><?php echo !empty($appointment['notes']) ? htmlspecialchars(substr($appointment['notes'], 0, 50)) . (strlen($appointment['notes']) > 50 ? '...' : '') : '-'; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger cancel-btn" data-bs-toggle="modal" data-bs-target="#cancelModal" data-appointment-id="<?php echo $appointment['id']; ?>" data-appointment-date="<?php echo date('d/m/Y', strtotime($appointment['date_rdv'])); ?>" data-appointment-time="<?php echo substr($appointment['heure_debut'], 0, 5); ?>">
                                            Annuler
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="calendrier.php" class="btn btn-primary">Prendre un nouveau rendez-vous</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
            <?php if (empty($pastAppointments)) : ?>
                <div class="alert alert-info">
                    Vous n'avez aucun rendez-vous passé.
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Motif</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastAppointments as $appointment) : ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($appointment['date_rdv'])); ?></td>
                                    <td><?php echo substr($appointment['heure_debut'], 0, 5) . ' - ' . substr($appointment['heure_fin'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['motif'] ?? 'Non spécifié'); ?></td>
                                    <td><?php echo !empty($appointment['notes']) ? htmlspecialchars(substr($appointment['notes'], 0, 50)) . (strlen($appointment['notes']) > 50 ? '...' : '') : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation d'annulation -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Confirmer l'annulation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir annuler votre rendez-vous du <span id="appointmentDate"></span> à <span id="appointmentTime"></span> ?</p>
                <form id="cancelForm" action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="appointment_id" id="appointmentId" value="">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email" value="1" checked>
                        <label class="form-check-label" for="sendEmail">
                            Recevoir une confirmation d'annulation par email
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Confirmer l'annulation</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du modal d'annulation
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    const appointmentDateSpan = document.getElementById('appointmentDate');
    const appointmentTimeSpan = document.getElementById('appointmentTime');
    const appointmentIdInput = document.getElementById('appointmentId');
    const cancelForm = document.getElementById('cancelForm');
    const confirmCancelButton = document.getElementById('confirmCancel');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-appointment-id');
            const appointmentDate = this.getAttribute('data-appointment-date');
            const appointmentTime = this.getAttribute('data-appointment-time');
            
            appointmentDateSpan.textContent = appointmentDate;
            appointmentTimeSpan.textContent = appointmentTime;
            appointmentIdInput.value = appointmentId;
        });
    });
    
    confirmCancelButton.addEventListener('click', function() {
        cancelForm.submit();
    });
    
    // Affichage dynamique des onglets
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab === 'past') {
        const pastTab = document.getElementById('past-tab');
        const pastTabContent = document.getElementById('past');
        const upcomingTab = document.getElementById('upcoming-tab');
        const upcomingTabContent = document.getElementById('upcoming');
        
        pastTab.classList.add('active');
        pastTab.setAttribute('aria-selected', 'true');
        pastTabContent.classList.add('show', 'active');
        
        upcomingTab.classList.remove('active');
        upcomingTab.setAttribute('aria-selected', 'false');
        upcomingTabContent.classList.remove('show', 'active');
    }
});
</script>

<?php
// Fonction pour envoyer un email de confirmation d'annulation
function sendCancellationEmail($email, $prenom, $nom, $date, $heureDebut, $heureFin, $motif, $reservationId) {
    $dateFormatee = date('d/m/Y', strtotime($date));
    $numeroReservation = str_pad($reservationId, 6, '0', STR_PAD_LEFT);
    
    $sujet = "Confirmation d'annulation de votre rendez-vous #$numeroReservation";
    
    $message = "
    <html>
    <head>
        <title>Confirmation d'annulation de rendez-vous</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <div style='background-color: #4CAF50; color: white; padding: 10px; text-align: center;'>
                <h2>Confirmation d'annulation</h2>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd;'>
                <p>Bonjour $prenom $nom,</p>
                <p>Votre rendez-vous a bien été annulé.</p>
                <h3>Détails du rendez-vous annulé:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Date:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd;'>$dateFormatee</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Heure:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd;'>$heureDebut - $heureFin</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>Motif:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd;'>$motif</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;'>N° de réservation:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #ddd;'>#$numeroReservation</td>
                    </tr>
                </table>
                <p style='margin-top: 20px;'>Si vous souhaitez prendre un nouveau rendez-vous, veuillez vous connecter à votre compte.</p>
                <p>Merci de votre confiance.</p>
                <p>L'équipe du Système de Réservation</p>
            </div>
            <div style='background-color: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // En-têtes pour l'email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Système de Réservation <noreply@reservation-system.com>' . "\r\n";
    
    // Simulation d'envoi d'email pour le développement
    
    // Enregistrer l'email dans un fichier de log ou une table de la base de données
    try {
        global $db;
        $logStmt = $db->prepare("
            INSERT INTO email_logs (user_id, email, subject, message, status, created_at)
            VALUES (:user_id, :email, :subject, :message, 'sent', NOW())
        ");
        $logStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $logStmt->bindValue(':email', $email);
        $logStmt->bindValue(':subject', $sujet);
        $logStmt->bindValue(':message', $message);
        $logStmt->execute();
    } catch (PDOException $e) {
        // Ignorer l'erreur pour ne pas interrompre le processus
        error_log("Erreur lors de l'enregistrement de l'email: " . $e->getMessage());
    }
    
    return true;
}

require_once '../includes/footer.php';
?>