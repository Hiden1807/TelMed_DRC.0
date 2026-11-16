<?php
// actions/update_meet.php
session_start();
require_once '../config/db.php';

// Sécurité d'accès : Doit être connecté et être un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: ../auth/connexion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medecin = $_SESSION['user_id'];
    $lien_meet = filter_var(trim($_POST['lien_meet']), FILTER_VALIDATE_URL);

    if ($lien_meet) {
        try {
            // Mise à jour sécurisée du champ lien_meet pour ce médecin précis
            $stmt = $pdo->prepare("UPDATE medecins SET lien_meet = ? WHERE id = ?");
            $stmt->execute([$lien_meet, $id_medecin]);

            // Redirection avec message de succès
            header("Location: ../dashboard.php?msg=success_meet");
            exit();
        } catch (Exception $e) {
            header("Location: ../dashboard.php?msg=error");
            exit();
        }
    } else {
        header("Location: ../dashboard.php?msg=error");
        exit();
    }
} else {
    header("Location: ../dashboard.php");
    exit();
}