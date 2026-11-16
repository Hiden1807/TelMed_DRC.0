<?php
// auth/connexion.php
session_start();
date_default_timezone_set('Africa/Kinshasa');

if (!file_exists('../config/db.php')) {
    die("Erreur critique : Le fichier de configuration de la base de données (config/db.php) est introuvable.");
}
require_once '../config/db.php';

// Si l'utilisateur est déjà connecté, redirection directe vers le dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    if (!empty($email) && !empty($password)) {
        try {
            // Récupération de l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                
                // 1. Initialisation des variables temporaires de transition
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_user_role'] = $user['role'];

                // 2. CORRECTION CRITIQUE : Vérification immédiate du cookie de confiance
                $cookie_name = "telmed_trusted_device";
                if (isset($_COOKIE[$cookie_name]) && !empty($user['appareil_token'])) {
                    // On compare le hash du cookie avec l'empreinte en BDD
                    if (hash_equals($user['appareil_token'], hash('sha256', $_COOKIE[$cookie_name]))) {
                        
                        // L'appareil est validé ! Connexion globale directe sans 2FA
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];

                        // Nettoyage des accès temporaires
                        unset($_SESSION['temp_user_id'], $_SESSION['temp_user_role']);
                        
                        header("Location: ../dashboard.php");
                        exit();
                    }
                }

                // 3. PARCOURS 2FA STANDARD : Si le cookie n'existe pas ou n'est plus valide
                $otp_code = rand(100000, 999999);
                $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                // Insertion ou mise à jour de l'OTP en BDD
                $stmtOTP = $pdo->prepare("INSERT INTO double_facteur (id_user, code_otp, expire_a) VALUES (?, ?, ?) 
                                          ON DUPLICATE KEY UPDATE code_otp = ?, expire_a = ?, utilise = 0");
                $stmtOTP->execute([$user['id'], $otp_code, $expires_at, $otp_code, $expires_at]);

                // Simulation locale de l'envoi de l'OTP (fichier log)
                // Note : C'est ici que s'intégrera l'API SMS (Twilio / Africa's Talking) plus tard
                $log_message = "[" . date('Y-m-d H:i:s') . "] Pour : " . $email . " | Code 2FA : " . $otp_code . "\n";
                file_put_contents("../2fa_mail_simulation.log", $log_message, FILE_APPEND);

                header("Location: 2fa.php");
                exit();
            } else {
                $error = "Identifiants ou mot de passe incorrects.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>TelMed <span class="text-warning">RDC</span></h2>
                <p class="text-muted">Espace d'accès sécurisé</p>
            </div>
            
            <div class="card shadow border-0">
                <div class="card-body p-4 bg-white">
                    <h4 class="fw-bold text-center mb-4 text-dark">Connexion</h4>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="connexion.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Adresse E-mail</label>
                            <input type="email" class="form-control" name="email" required placeholder="nom@domaine.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Mot de passe</label>
                            <input type="password" class="form-control" name="mot_de_passe" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Se connecter</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="small text-muted m-0">Pas encore de compte ? <a href="inscription.php" class="text-decoration-none fw-bold text-primary">Inscrivez-vous ici</a></p>
                <a href="../index.php" class="small text-secondary d-block mt-2"><i class="fa-solid fa-arrow-left"></i> Retour à l'accueil</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>