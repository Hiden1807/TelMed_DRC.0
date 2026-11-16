<?php
// config/db.php

$host = 'localhost';
$db   = 'telmed_drc_db';
$user = 'admin';
$pass = 'votre_mot_de_passe'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Désactive l'émulation pour bloquer les injections SQL complexes
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En production, ne jamais afficher $e->getMessage() directement (fuite d'informations)
     die("Erreur critique de connexion.");
}
?>