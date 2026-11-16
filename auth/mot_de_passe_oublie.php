<?php
// auth/mot_de_passe_oublie.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TelMed RDC - Récupération de compte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-center mb-3">Réinitialisation de mot de passe</h4>
                    <p class="text-muted small text-center">Saisissez votre adresse e-mail. Un lien de récupération vous sera envoyé après vérification de vos accès de sécurité.</p>
                    <form action="recuperer_action.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Adresse E-mail</label>
                            <input type="email" class="form-control" name="email" required placeholder="exemple@domaine.com">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Envoyer la demande</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="connexion.php" class="small text-decoration-none">Retour à la connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>