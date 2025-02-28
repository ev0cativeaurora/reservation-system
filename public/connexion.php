<?php
// connexion.php with vertical layout
require_once '../config/config.php';  
require_once '../utils/functions.php';  
require_once '../includes/csrf.php';   

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to profile
if (isset($_SESSION['user_id'])) {
    header("Location: profil.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        $email      = sanitizeInput($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Veuillez remplir tous les champs.";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Format d'email invalide.";
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id, password FROM users WHERE email = :email");
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $errors[] = "Utilisateur non trouvé.";
                } else {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        header("Location: profil.php");
                        exit;
                    } else {
                        $errors[] = "Mot de passe incorrect.";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="auth-container">
    <h2 class="text-center mb-4">Connexion</h2>

    <?php if (!empty($errors)) : ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error) : ?>
            <li><?php echo $error; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="auth-form">
        <form action="" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Mot de passe:</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-primary" id="log-in-btn">Se connecter</button>
          </div>
        </form>
        <div class="text-center mt-3">
            <p>Vous n'avez pas de compte ? <a href="inscription.php">Inscrivez-vous</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>