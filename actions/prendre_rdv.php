<?php
// actions/prendre_rdv.php
session_start();
require_once '../config/db.php';

// Sécurité : Seuls les patients connectés peuvent prendre RDV
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../auth/connexion.php");
    exit();
}

if (isset($_GET['id_medecin']) && isset($_GET['heure'])) {
    $id_patient = $_SESSION['user_id'];
    $id_medecin = (int)$_GET['id_medecin'];
    $heure_rdv = $_GET['heure'];
    $date_rdv = date('Y-m-d'); // Par défaut aujourd'hui, à adapter avec un calendrier complet
    $motif = "Consultation générale à distance";

    try {
        // Insertion sécurisée du rendez-vous avec le statut 'en_attente'
        $stmt = $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, date_rdv, heure_rdv, statut, motif) VALUES (?, ?, ?, ?, 'en_attente', ?)");
        $stmt->execute([$id_patient, $id_medecin, $date_rdv, $heure_rdv, $motif]);

        // Redirection vers le dashboard avec un message de succès
        header("Location: ../dashboard.php?msg=success_rdv");
        exit();
    } catch (Exception $e) {
        header("Location: ../dashboard.php?msg=error");
        exit();
    }
} else {
    header("Location: ../dashboard.php");
    exit();
}