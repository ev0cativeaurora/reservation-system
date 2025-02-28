<?php
// verification.php
require_once '../config/config.php';
require_once '../utils/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = sanitizeInput($_GET['token'] ?? '');
$userId = (int)($_GET['id'] ?? 0);
$message = '';
$status = 'warning';

if (empty($token) || $userId === 0) {
    $message = "Lien de vérification invalide. Veuillez réessayer ou contacter l'administrateur.";
} else {
    try {
        $stmt = $db->prepare("
            SELECT * FROM verification_tokens
            WHERE user_id = :user_id AND token = :token
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verification) {
            // Check if token is not expired (optional: tokens expire after 24 hours)
            $tokenDate = new DateTime($verification['created_at']);
            $now = new DateTime();
            $interval = $tokenDate->diff($now);
            
            if ($interval->days > 1) {
                $message = "Le lien de vérification a expiré. Veuillez vous reconnecter pour recevoir un nouveau lien.";
            } else {
                // Update user status to verified
                $updateStmt = $db->prepare("
                    UPDATE users 
                    SET email_verified = 1 
                    WHERE id = :user_id
                ");
                $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $updateStmt->execute();
                
                // Delete the used token
                $deleteStmt = $db->prepare("DELETE FROM verification_tokens WHERE user_id = :user_id");
                $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                $message = "Votre email a été vérifié avec succès! Vous pouvez maintenant vous connecter.";
                $status = 'success';
            }
        } else {
            $message = "Lien de vérification invalide ou déjà utilisé.";
        }
    } catch (PDOException $e) {
        $message = "Erreur de base de données: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="custom-card text-center">
                <h2 class="mb-4">Vérification de l'Email</h2>
                
                <div class="alert alert-<?php echo $status; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
                
                <div class="mt-4">
                    <?php if ($status === 'success') : ?>
                        <a href="connexion.php" class="btn btn-cool">Se connecter</a>
                    <?php else : ?>
                        <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>