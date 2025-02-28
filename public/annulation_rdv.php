<?php
// annulation_rdv.php
require_once '../config/config.php';
require_once '../utils/functions.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$successMessage = '';

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
        $appointmentId = (int)$_POST['appointment_id'];
        
        try {
            // Verify the appointment belongs to the current user
            $stmt = $db->prepare("
                SELECT id FROM rendezvous 
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->bindValue(':id', $appointmentId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Delete the appointment
                $deleteStmt = $db->prepare("DELETE FROM rendezvous WHERE id = :id");
                $deleteStmt->bindValue(':id', $appointmentId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                $successMessage = "Votre rendez-vous a été annulé avec succès.";
            } else {
                $errors[] = "Rendez-vous introuvable ou vous n'êtes pas autorisé à l'annuler.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}

// Fetch all upcoming appointments for the user
try {
    $stmt = $db->prepare("
        SELECT id, date_rdv, heure_debut, heure_fin
        FROM rendezvous
        WHERE user_id = :user_id AND (date_rdv > CURDATE() OR (date_rdv = CURDATE() AND heure_debut > CURTIME()))
        ORDER BY date_rdv ASC, heure_debut ASC
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des rendez-vous: " . $e->getMessage();
    $appointments = [];
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="custom-card">
                <h2 class="text-center mb-4">Mes Rendez-vous</h2>
                
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
                
                <?php if (empty($appointments)) : ?>
                    <div class="alert alert-info">
                        Vous n'avez aucun rendez-vous à venir.
                    </div>
                    <div class="text-center mt-3">
                        <a href="calendrier.php" class="btn btn-cool">Prendre un rendez-vous</a>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Heure de début</th>
                                    <th>Heure de fin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment) : ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($appointment['date_rdv'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($appointment['heure_debut'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($appointment['heure_fin'])); ?></td>
                                        <td>
                                            <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Annuler</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="calendrier.php" class="btn btn-cool">Prendre un nouveau rendez-vous</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>