<?php
// profil_edit.php
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

// Fetch current user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: connexion.php");
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Erreur de base de données: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
        // Collect & sanitize form inputs
        $prenom = sanitizeInput($_POST['prenom'] ?? '');
        $nom = sanitizeInput($_POST['nom'] ?? '');
        $dateNaissance = sanitizeInput($_POST['date_naissance'] ?? '');
        $adresse = sanitizeInput($_POST['adresse'] ?? '');
        $telephone = sanitizeInput($_POST['telephone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Basic validations
        if (empty($prenom) || empty($nom) || empty($email)) {
            $errors[] = "Veuillez remplir tous les champs obligatoires.";
        }
        
        if (!isValidEmail($email)) {
            $errors[] = "Format d'email invalide.";
        }
        
        // Check if the new email is already taken by another user
        if ($email !== $user['email']) {
            try {
                $emailStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
                $emailStmt->bindValue(':email', $email);
                $emailStmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $emailStmt->execute();
                
                if ($emailStmt->fetchColumn() > 0) {
                    $errors[] = "Cet email est déjà utilisé par un autre compte.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
        
        // If no errors, update user profile
        if (empty($errors)) {
            try {
                $updateStmt = $db->prepare("
                    UPDATE users SET
                        prenom = :prenom,
                        nom = :nom,
                        date_naissance = :date_naissance,
                        adresse = :adresse,
                        telephone = :telephone,
                        email = :email
                    WHERE id = :id
                ");
                
                $updateStmt->bindValue(':prenom', $prenom);
                $updateStmt->bindValue(':nom', $nom);
                $updateStmt->bindValue(':date_naissance', $dateNaissance ?: null);
                $updateStmt->bindValue(':adresse', $adresse);
                $updateStmt->bindValue(':telephone', $telephone);
                $updateStmt->bindValue(':email', $email);
                $updateStmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $updateStmt->execute();
                
                // Update the user variable with new values
                $user['prenom'] = $prenom;
                $user['nom'] = $nom;
                $user['date_naissance'] = $dateNaissance;
                $user['adresse'] = $adresse;
                $user['telephone'] = $telephone;
                $user['email'] = $email;
                
                $successMessage = "Votre profil a été mis à jour avec succès.";
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="custom-card">
                <h2 class="text-center mb-4">Modifier Mon Profil</h2>
                
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
                
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_naissance" class="form-label">Date de Naissance</label>
                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <input type="text" class="form-control" id="adresse" name="adresse" value="<?php echo htmlspecialchars($user['adresse'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="profil.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-cool">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>