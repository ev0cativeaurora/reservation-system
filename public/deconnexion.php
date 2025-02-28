<?php
// deconnexion.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page or homepage
header("Location: connexion.php");
exit;
