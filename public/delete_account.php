<?php
// delete_account.php
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
$confirmDeletePage = false;

// First page is confirmation, second page is actual deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton CSRF invalide. Veuillez réessayer.";
    } else if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        // User has confirmed deletion, proceed with account removal
        try {
            // No need to manually delete appointments due to CASCADE constraint in the database
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Destroy the session
            session_unset();
            session_destroy();
            
            // Redirect to a confirmation page
            header("Location: compte_supprime.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la suppression du compte: " . $e->getMessage();
        }
    } else {
        // Show the confirmation page
        $confirmDeletePage = true;
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="custom-card">
                <?php if (!empty($errors)) : ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($confirmDeletePage) : ?>
                    <div class="text-center">
                        <h2 class="text-danger mb-4">Confirmation de Suppression</h2>
                        <div class="alert alert-warning">
                            <p><strong>Attention !</strong> Vous êtes sur le point de supprimer définitivement votre compte.</p>
                            <p>Cette action est irréversible et entraînera la suppression de toutes vos données personnelles et rendez-vous.</p>
                            <p>Êtes-vous absolument sûr de vouloir continuer ?</p>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="profil.php" class="btn btn-secondary">Annuler</a>
                            
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="confirm_delete" value="yes">
                                <button type="submit" class="btn btn-danger">Oui, supprimer mon compte</button>
                            </form>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="text-center">
                        <h2 class="text-danger mb-4">Supprimer Mon Compte</h2>
                        <div class="alert alert-warning">
                            <p><strong>Attention !</strong> La suppression de votre compte entraînera la perte définitive de toutes vos données.</p>
                            <p>Tous vos rendez-vous seront annulés et vos informations personnelles seront supprimées de notre base de données.</p>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="profil.php" class="btn btn-secondary">Annuler</a>
                            
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>