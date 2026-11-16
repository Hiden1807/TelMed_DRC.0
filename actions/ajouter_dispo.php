<?php
// actions/ajouter_dispo.php
session_start();
require_once '../config/db.php';

// Sécurité : Seuls les médecins connectés peuvent ajouter des disponibilités
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: ../auth/connexion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medecin = $_SESSION['user_id'];
    $jour = $_POST['jour_semaine'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];

    if (!empty($jour) && !empty($heure_debut) && !empty($heure_fin)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO disponibilites (id_medecin, jour_semaine, heure_debut, heure_fin, est_disponible) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$id_medecin, $jour, $heure_debut, $heure_fin]);

            header("Location: ../dashboard.php?msg=success_dispo");
            exit();
        } catch (Exception $e) {
            header("Location: ../dashboard.php?msg=error");
            exit();
        }
    }
} else {
    header("Location: ../dashboard.php");
    exit();
}