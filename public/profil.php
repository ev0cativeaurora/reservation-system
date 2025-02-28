<?php
// profil.php

require_once '../config/config.php';  
require_once '../utils/functions.php';  
require_once '../includes/csrf.php';   

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user not logged in, redirect
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

// Fetch user data
$userId = $_SESSION['user_id'];
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Just in case user not found
        header("Location: connexion.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<h2>Mon Profil</h2>

<p><strong>First Name:</strong> <?php echo htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Last Name:</strong> <?php echo htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
<!-- Add other fields as needed -->

<a class="btn btn-primary" href="profil_edit.php">Edit Profile</a>
<a class="btn btn-danger" href="delete_account.php">Delete Account</a>
<a class="btn btn-secondary" href="deconnexion.php">Logout</a>

<?php require_once '../includes/footer.php'; ?>
