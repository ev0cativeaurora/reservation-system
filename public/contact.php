<?php
// contact.php with vertical layout
require_once '../config/config.php';
require_once '../utils/functions.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Collect & sanitize form inputs
        $nom = sanitizeInput($_POST['nom'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $sujet = sanitizeInput($_POST['sujet'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');

        // Basic validations
        if (empty($nom) || empty($email) || empty($message)) {
            $errors[] = "Veuillez remplir tous les champs obligatoires.";
        }
        if (!isValidEmail($email)) {
            $errors[] = "Format d'email invalide.";
        }

        // If no errors, process the contact form
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO contact_messages (nom, email, sujet, message, created_at)
                    VALUES (:nom, :email, :sujet, :message, NOW())
                ");
                
                $stmt->bindValue(':nom', $nom);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':sujet', $sujet);
                $stmt->bindValue(':message', $message);
                $stmt->execute();

                $successMessage = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content-container">
    <h2 class="text-center mb-4">Contactez-nous</h2>
    
    <?php if (!empty($errors)) : ?>
      <div class="alert alert-danger">
        <ul>
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
    <?php else : ?>
      <div class="contact-form">
        <form action="" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
          
          <div class="mb-3">
            <label for="nom" class="form-label">Nom complet *</label>
            <input type="text" class="form-control" id="nom" name="nom" required>
          </div>
          
          <div class="mb-3">
            <label for="email" class="form-label">Email *</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          
          <div class="mb-3">
            <label for="sujet" class="form-label">Sujet</label>
            <input type="text" class="form-control" id="sujet" name="sujet">
          </div>
          
          <div class="mb-3">
            <label for="message" class="form-label">Message *</label>
            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
          </div>
          
          <div class="text-center">
            <button type="submit" class="btn btn-primary">Envoyer</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>