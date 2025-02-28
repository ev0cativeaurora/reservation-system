<?php
// inscription.php

require_once '../config/config.php';  // DB connection via PDO
require_once '../utils/functions.php'; // e.g. sanitizeInput(), isValidEmail()
require_once '../includes/csrf.php';   // CSRF token generation/validation

// Start the session if not already started (important for CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Collect & sanitize form inputs
        $prenom       = sanitizeInput($_POST['prenom'] ?? '');
        $nom          = sanitizeInput($_POST['nom'] ?? '');
        $dateNaissance= sanitizeInput($_POST['date_naissance'] ?? '');
        $adresse      = sanitizeInput($_POST['adresse'] ?? '');
        $telephone    = sanitizeInput($_POST['telephone'] ?? '');
        $email        = sanitizeInput($_POST['email'] ?? '');
        $motDePasse   = $_POST['mot_de_passe'] ?? '';

        // Basic validations
        if (empty($prenom) || empty($nom) || empty($email) || empty($motDePasse)) {
            $errors[] = "Please fill in all required fields.";
        }
        if (!isValidEmail($email)) {
            $errors[] = "Invalid email format.";
        }

        // If no errors so far, check if email already exists
        if (empty($errors)) {
            try {
                // Prepare a query to check if the email is taken
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    $errors[] = "Email is already in use.";
                } else {
                    // If email is unique, insert user
                    $hashedPassword = password_hash($motDePasse, PASSWORD_DEFAULT);

                    // Insert with prepared statement
                    $insertStmt = $db->prepare("
                        INSERT INTO users 
                        (prenom, nom, date_naissance, adresse, telephone, email, password, created_at)
                        VALUES 
                        (:prenom, :nom, :date_naissance, :adresse, :telephone, :email, :password, NOW())
                    ");

                    $insertStmt->bindValue(':prenom', $prenom);
                    $insertStmt->bindValue(':nom', $nom);
                    $insertStmt->bindValue(':date_naissance', $dateNaissance);
                    $insertStmt->bindValue(':adresse', $adresse);
                    $insertStmt->bindValue(':telephone', $telephone);
                    $insertStmt->bindValue(':email', $email);
                    $insertStmt->bindValue(':password', $hashedPassword);
                    $insertStmt->execute();

                    $successMessage = "Registration successful! You can now log in.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<h2>Inscription</h2>

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
  <div class="alert alert-success">
    <?php echo $successMessage; ?>
  </div>
<?php else : ?>
  <form action="" method="POST">
    <?php // Generate and embed CSRF token ?>
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="mb-3">
      <label for="prenom" class="form-label">First Name:</label>
      <input type="text" class="form-control" id="prenom" name="prenom" required>
    </div>
    <div class="mb-3">
      <label for="nom" class="form-label">Last Name:</label>
      <input type="text" class="form-control" id="nom" name="nom" required>
    </div>
    <div class="mb-3">
      <label for="date_naissance" class="form-label">Date of Birth:</label>
      <input type="date" class="form-control" id="date_naissance" name="date_naissance">
    </div>
    <div class="mb-3">
      <label for="adresse" class="form-label">Address:</label>
      <input type="text" class="form-control" id="adresse" name="adresse">
    </div>
    <div class="mb-3">
      <label for="telephone" class="form-label">Phone:</label>
      <input type="text" class="form-control" id="telephone" name="telephone">
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email:</label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
      <label for="mot_de_passe" class="form-label">Password:</label>
      <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
  </form>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
