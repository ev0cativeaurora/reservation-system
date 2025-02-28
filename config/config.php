<?php
// config.php

$host   = 'localhost';
$dbName = 'reservation_system';
$user   = 'root';
$pass   = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $pass);
    // Set error reporting mode
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Stop execution if DB connection fails
    die("Erreur de connexion : " . $e->getMessage());
}
