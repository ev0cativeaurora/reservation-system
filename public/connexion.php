<?php
// connexion.php

require_once '../config/config.php';  
require_once '../utils/functions.php';  
require_once '../includes/csrf.php';   

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        $email      = sanitizeInput($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Please fill in both email and password.";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id, password FROM users WHERE email = :email");
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $errors[] = "User not found.";
                } else {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        header("Location: profil.php");
                        exit;
                    } else {
                        $errors[] = "Incorrect password.";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<h2>Connexion</h2>

<?php if (!empty($errors)) : ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $error) : ?>
        <li><?php echo $error; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form action="" method="POST">
  <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
  <div class="mb-3">
    <label for="email" class="form-label">Email:</label>
    <input type="email" class="form-control" id="email" name="email" required>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Password:</label>
    <input type="password" class="form-control" id="password" name="password" required>
  </div>
  <button type="submit" class="btn btn-primary">Log In</button>
</form>

<?php require_once '../includes/footer.php'; ?>
