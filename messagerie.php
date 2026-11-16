<?php
// messagerie.php
session_start();
require_once 'config/db.php';

// Sécurité : Vérification de l'authentification 2FA
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Identification du destinataire (id transmis par l'URL)
$destinataire_id = isset($_GET['id_contact']) ? (int)$_GET['id_contact'] : 0;

// Récupération des informations du destinataire pour l'affichage
$destinataire_nom = "Sélectionnez un contact";
if ($destinataire_id > 0) {
    $stmtDest = $pdo->prepare("SELECT nom, prenom FROM users WHERE id = ?");
    $stmtDest->execute([$destinataire_id]);
    $dest = $stmtDest->fetch();
    if ($dest) {
        $destinataire_nom = "Discussion avec : " . htmlspecialchars($dest['prenom'] . ' ' . $dest['nom']);
    }
}

// Traitement de l'envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $destinataire_id > 0) {
    $contenu = trim($_POST['contenu']);
    if (!empty($contenu)) {
        $stmtIns = $pdo->prepare("INSERT INTO messages (id_expediteur, id_destinataire, contenu) VALUES (?, ?, ?)");
        $stmtIns->execute([$user_id, $destinataire_id, $contenu]);
        
        // Rafraîchir pour éviter la double soumission de formulaire
        header("Location: messagerie.php?id_contact=" . $destinataire_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Messagerie Sécurisée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-rdc-blue shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-comments text-warning me-2"></i>Messagerie <span class="text-warning">TelMed</span>
            </a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-arrow-left"></i> Retour Dashboard</a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">
                        <i class="fa-solid fa-address-book text-warning me-2"></i>Vos Contacts Récents
                    </div>
                    <div class="list-group list-group-flush bg-white rounded-bottom">
                        <?php
                        // On cherche les utilisateurs avec qui il y a eu un échange de rendez-vous
                        if ($user_role === 'patient') {
                            $stmtContacts = $pdo->prepare("SELECT DISTINCT u.id, u.nom, u.prenom, m.specialite FROM users u JOIN medecins m ON u.id = m.id JOIN rendez_vous r ON u.id = r.id_medecin WHERE r.id_patient = ?");
                        } else {
                            $stmtContacts = $pdo->prepare("SELECT DISTINCT u.id, u.nom, u.prenom FROM users u JOIN rendez_vous r ON u.id = r.id_patient WHERE r.id_medecin = ?");
                        }
                        $stmtContacts->execute([$user_id]);
                        $contacts = $stmtContacts->fetchAll();

                        if (count($contacts) > 0): foreach ($contacts as $c): ?>
                            <a href="messagerie.php?id_contact=<?= $c['id'] ?>" class="list-group-item list-group-item-action <?= ($destinataire_id === (int)$c['id']) ? 'active bg-rdc-blue border-0' : '' ?>">
                                <div class="fw-bold"><i class="fa-solid fa-circle-user me-2"></i><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                <small class="<?= ($destinataire_id === (int)$c['id']) ? 'text-white-50' : 'text-muted' ?>">
                                    <?= isset($c['specialite']) ? htmlspecialchars($c['specialite']) : 'Patient' ?>
                                </small>
                            </a>
                        <?php endforeach; else: ?>
                            <div class="p-3 text-muted small">Aucun contact médical lié à un rendez-vous actif.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom border-4 border-warning fw-bold text-rdc-blue">
                        <i class="fa-solid fa-comment-medical text-danger me-2"></i><?= $destinataire_nom ?>
                    </div>
                    
                    <div class="card-body bg-light overflow-auto p-4" style="height: 400px; display: flex; flex-direction: column-reverse;">
                        <div>
                            <?php
                            if ($destinataire_id > 0):
                                // Récupération chronologique de la conversation
                                $stmtMsg = $pdo->prepare("SELECT * FROM messages WHERE (id_expediteur = ? AND id_destinataire = ?) OR (id_expediteur = ? AND id_destinataire = ?) ORDER BY date_envoi ASC");
                                $stmtMsg->execute([$user_id, $destinataire_id, $destinataire_id, $user_id]);
                                $messages = $stmtMsg->fetchAll();

                                foreach ($messages as $msg): 
                                    $est_expediteur = ($msg['id_expediteur'] === $user_id);
                                    ?>
                                    <div class="d-flex mb-3 <?= $est_expediteur ? 'justify-content-end' : 'justify-content-start' ?>">
                                        <div class="p-3 rounded-3 shadow-sm max-width-75 <?= $est_expediteur ? 'bg-rdc-blue text-white rounded-bottom-end-0' : 'bg-white text-dark rounded-bottom-start-0' ?>">
                                            <p class="m-0 mb-1"><?= nl2br(htmlspecialchars($msg['contenu'])) ?></p>
                                            <small class="d-block text-end <?= $est_expediteur ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.75rem;">
                                                <?= date('H:i', strtotime($msg['date_envoi'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fa-solid fa-comments fa-3x mb-3 text-secondary"></i>
                                    <p>Veuillez sélectionner un professionnel ou un patient à gauche pour démarrer l'échange confidentiel.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($destinataire_id > 0): ?>
                        <div class="card-footer bg-white border-top-0 p-3">
                            <form action="messagerie.php?id_contact=<?= $destinataire_id ?>" method="POST">
                                <div class="input-group">
                                    <input type="text" class="form-control border-rdc-yellow" name="contenu" placeholder="Écrivez votre message médical sécurisé ici..." required autocomplete="off">
                                    <button class="btn btn-rdc-blue fw-bold" type="submit">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Envoyer
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>