<?php
// consultation.php
session_start();
date_default_timezone_set('Africa/Kinshasa');

if (!file_exists('config/db.php')) {
    die("Erreur critique : config/db.php introuvable.");
}
require_once 'config/db.php';

// Sécurité : Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Simulation ou récupération de l'ID de consultation en cours (Ex: depuis l'URL ?id=1)
$id_consultation = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Récupération des détails de la consultation, du médecin et du patient
// Pour le prototype, on génère un nom de salle unique basé sur l'ID de la consultation
$room_name = "TelMedRDC_Room_" . $id_consultation;
$nom_affichage = ($user_role === 'medecin') ? "Dr. Praticien (M)" : "Patient RDC";

// Traitement de la confirmation de disponibilité par le médecin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_statut'])) {
    $nouveau_statut = $_POST['action_statut']; // Ex: 'en_cours', 'termine', 'absent'
    
    // Exemple de mise à jour en BDD (à adapter selon votre table rendez_vous / consultations)
    // $stmt = $pdo->prepare("UPDATE consultations SET statut = ? WHERE id = ?");
    // $stmt->execute([$nouveau_statut, $id_consultation]);
    
    $success_msg = "Le statut de la consultation a été mis à jour avec succès.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Salle de Consultation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --rdc-blue: #007FFF;
            --rdc-yellow: #FDD017;
            --rdc-red: #D21034;
        }
        .bg-gradient-header {
            background: linear-gradient(135deg, var(--rdc-blue) 0%, #0056b3 100%);
        }
        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            background: #ffffff;
        }
        #video-container {
            width: 100%;
            height: 550px;
            border-radius: 12px;
            overflow: hidden;
            background: #1e1e24;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }
        .chat-box {
            height: 380px;
            overflow-y: auto;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 15px;
        }
        .badge-online {
            animation: blink 2s infinite;
        }
        @keyframes blink {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }
    </style>
</head>
<body class="bg-light">

    <div class="bg-gradient-header text-white py-3 shadow-sm mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-danger mb-1 badge-online"><i class="fa-solid fa-circle me-1"></i> Consultation en direct</span>
                <h3 class="fw-bold m-0">Espace Médical Numérique</h3>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-light fw-bold btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Quitter l'espace</a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show card-custom p-3 mb-4" role="alert">
                <i class="fa-solid fa-circle-check text-success me-2"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-custom p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-video text-primary me-2"></i>Canal de Visioconférence Sécurisé</h5>
                        <span class="text-muted small"><i class="fa-solid fa-lock text-success me-1"></i> Chiffrement de bout en bout</span>
                    </div>
                    
                    <div id="video-container"></div>
                </div>
            </div>

            <div class="col-lg-4">
                
                <?php if ($user_role === 'medecin'): ?>
                <div class="card card-custom p-4 mb-4 border-start border-primary border-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-user-doctor text-primary me-2"></i>Panneau Praticien</h5>
                    <p class="small text-muted">Confirmez votre disponibilité ou actez la clôture de cet acte médical pour générer le rapport.</p>
                    
                    <form action="" method="POST" class="d-grid gap-2">
                        <button type="submit" name="action_statut" value="en_cours" class="btn btn-success fw-bold text-white btn-sm py-2">
                            <i class="fa-solid fa-circle-check me-2"></i>Confirmer ma présence (En ligne)
                        </button>
                        <button type="submit" name="action_statut" value="absent" class="btn btn-outline-danger fw-bold btn-sm py-2">
                            <i class="fa-solid fa-user-slash me-2"></i>Signaler Patient Absent
                        </button>
                        <hr class="my-2">
                        <a href="actions/telecharger_ordonnance.php?id=<?= $id_consultation ?>" target="_blank" class="btn btn-primary fw-bold btn-sm py-2">
                            <i class="fa-solid fa-file-medical me-2"></i>Rédiger & Exporter l'Ordonnance (PDF)
                        </a>
                    </form>
                </div>
                <?php endif; ?>

                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-notes-medical text-warning me-2"></i>Dossier de Session</h5>
                    <div class="small">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Intervenant :</span>
                            <span class="fw-bold text-dark"><?= $nom_affichage ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Fuseau Horaire :</span>
                            <span class="fw-bold text-dark">Kinshasa (GMT+1)</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Canal OTP d'accès :</span>
                            <span class="badge bg-light text-dark border border-secondary"><i class="fa-solid fa-phone text-success me-1"></i> Réseau Mobile Validé</span>
                        </div>
                    </div>
                </div>

                <div class="card card-custom p-3">
                    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-comments text-primary me-2"></i>Notes & Messagerie Instantanée</h6>
                    <div class="chat-box mb-2">
                        <div class="mb-2">
                            <small class="fw-bold text-primary">Système :</small>
                            <div class="bg-white p-2 rounded small border">La liaison sécurisée est établie. Vous pouvez échanger des messages écrits ici en complément du flux vidéo.</div>
                        </div>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" placeholder="Écrire une note...">
                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://meet.jit.si/external_api.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const domain = 'meet.jit.si';
            const options = {
                roomName: '<?= $room_name ?>',
                width: '100%',
                height: 550,
                parentNode: document.querySelector('#video-container'),
                userInfo: {
                    displayName: '<?= $nom_affichage ?>'
                },
                interfaceConfigOverwrite: {
                    filmStripOnly: false,
                    SHOW_JITSI_WATERMARK: false
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false
                }
            };
            // Initialisation de la salle de visioconférence permanente intégrée
            const api = new JitsiMeetExternalAPI(domain, options);
        });
    </script>
</body>
</html>