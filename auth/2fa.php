<?php
// auth/2fa.php
session_start();
date_default_timezone_set('Africa/Kinshasa');

if (!file_exists('../config/db.php')) {
    die("Erreur critique : Le fichier de configuration de la base de données (config/db.php) is introuvable.");
}
require_once '../config/db.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: connexion.php");
    exit();
}

$error = "";
$id_user = $_SESSION['temp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_saisi = isset($_POST['code_otp']) ? trim($_POST['code_otp']) : '';

    if (!empty($code_saisi)) {
        try {
            // Utilisation de l'heure PHP précise de Kinshasa pour la comparaison SQL
            $now = date("Y-m-d H:i:s");
            
            $stmt = $pdo->prepare("SELECT * FROM double_facteur WHERE id_user = ? AND utilise = 0 AND expire_a > ?");
            $stmt->execute([$id_user, $now]);
            $otp = $stmt->fetch();

            if ($otp && trim($otp['code_otp']) === $code_saisi) {
                
                $stmtUpdateOTP = $pdo->prepare("UPDATE double_facteur SET utilise = 1 WHERE id_user = ?");
                $stmtUpdateOTP->execute([$id_user]);

                $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                $_SESSION['user_role'] = $_SESSION['temp_user_role'];

                $device_token = bin2hex(random_bytes(32)); 
                $hashed_token = hash('sha256', $device_token); 

                $stmtDevice = $pdo->prepare("UPDATE users SET appareil_token = ? WHERE id = ?");
                $stmtDevice->execute([$hashed_token, $_SESSION['user_id']]);

                setcookie(
                    "telmed_trusted_device",
                    $device_token,
                    time() + (3600 * 24 * 30), 
                    "/",
                    "",
                    false, 
                    true   
                );

                unset($_SESSION['temp_user_id'], $_SESSION['temp_user_role']);

                header("Location: ../dashboard.php");
                exit();
            } else {
                $error = "Code incorrect ou expiré. Veuillez vérifier vos accès.";
            }
        } catch (PDOException $e) {
            $error = "Erreur système : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez saisir le code à 6 chiffres.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Double Facteur Sécurité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0 mt-5">
                <div class="card-body p-4 bg-white">
                    <div class="text-center mb-4">
                        <div class="text-primary mb-2">
                            <i class="fa-solid fa-shield-lock fa-3x text-warning"></i>
                        </div>
                        <h4 class="fw-bold text-dark m-0">Vérification de sécurité</h4>
                        <p class="text-muted small mt-2">Un code de validation temporaire a été envoyé. Pour vos tests en local, ouvrez le fichier <code class="text-danger">2fa_mail_simulation.log</code> à la racine du projet.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small text-center"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="2fa.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label small HTMLFormElement fw-bold text-center d-block">Saisissez le code à 6 chiffres</label>
                            <input type="text" class="form-control form-control-lg text-center fw-bold border-primary" name="code_otp" maxlength="6" pattern="[0-9]{6}" required placeholder="000000" autocomplete="off">
                        </div>
                        
                        <div class="form-check mb-3 small">
                            <input class="form-check-input" type="checkbox" id="trustDevice" checked disabled>
                            <label class="form-check-label text-muted" for="trustDevice">
                                Confier cet appareil pendant 30 jours (Éviter le 2FA la prochaine fois)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Valider et Accéder au Dashboard</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="deconnexion.php" class="small text-decoration-none text-secondary">Annuler et revenir en arrière</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>