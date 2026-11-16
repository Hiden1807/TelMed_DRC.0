<?php
// actions/statut_rdv.php
session_start();
require_once '../config/db.php';

// Sécurité : Seuls les médecins peuvent modifier le statut d'un RDV reçu
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header("Location: ../auth/connexion.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['statut'])) {
    $id_rdv = (int)$_GET['id'];
    $statut = $_GET['statut'];
    $id_medecin = $_SESSION['user_id'];

    // Validation du statut pour éviter les injections de valeurs incorrectes
    if (in_array($statut, ['confirme', 'annule'])) {
        try {
            // Un médecin ne peut modifier que ses propres rendez-vous
            $sql = "UPDATE rendez_vous SET statut = ? ";
            $params = [$statut];

            // Si le médecin confirme, on génère ou lie un salon Google Meet (Ici un lien de démo Meet)
            if ($statut === 'confirme') {
                $sql .= ", lien_consultation = ? ";
                $params[] = "https://meet.google.com/abc-defg-hij"; // Remplacer par l'API ou un lien dédié
            }

            $sql .= "WHERE id = ? AND id_medecin = ?";
            $params[] = $id_rdv;
            $params[] = $id_medecin;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: ../dashboard.php?msg=status_updated");
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