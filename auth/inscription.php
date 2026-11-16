<?php
// auth/inscription.php
require_once '../config/db.php';

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $postnom = trim($_POST['postnom']);
    $prenom = trim($_POST['prenom']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['mot_de_passe'];
    $telephone = trim($_POST['telephone']);
    $ville = trim($_POST['ville']);
    $commune = trim($_POST['commune_zone']);
    $role = $_POST['role'];

    if (!$email || empty($password) || empty($nom) || empty($prenom)) {
        $message = "Veuillez remplir correctement les champs obligatoires.";
        $status = "danger";
    } else {
        // Vérification unicité email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Cet email est déjà utilisé en RDC.";
            $status = "danger";
        } else {
            // Hachage fort du mot de passe avec BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Génération d'une clé secrète 2FA simulée à 6 chiffres pour l'exemple PHP natif
            // En production, utilisez une bibliothèque comme PHPGangsta_GoogleAuthenticator
            $secret_2fa = random_int(100000, 999999); 

            try {
                $pdo->beginTransaction();

                // 1. Insertion dans la table commune users
                $stmt = $pdo->prepare("INSERT INTO users (nom, postnom, prenom, email, mot_de_passe, telephone, ville, commune_zone, role, secret_2fa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $postnom, $prenom, $email, $hashed_password, $telephone, $ville, $commune, $role, $secret_2fa]);
                $user_id = $pdo->lastInsertId();

                // 2. Si Médecin, insertion dans la table spécifique medecins
                if ($role === 'medecin') {
                    $specialite = trim($_POST['specialite']);
                    $numero_ordre = trim($_POST['numero_ordre']);
                    
                    $stmtMed = $pdo->prepare("INSERT INTO medecins (id, specialite, numero_ordre) VALUES (?, ?, ?)");
                    $stmtMed->execute([$user_id, $specialite, $numero_ordre]);
                }

                $pdo->commit();
                $message = "Inscription réussie ! Votre code d'activation 2FA temporaire est : <strong>$secret_2fa</strong> (Simulé). Connectez-vous.";
                $status = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Une erreur est survenue lors de la création du compte.";
                $status = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TelMed RDC - Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-rdc-blue text-white text-center py-3" style="border-bottom: 4px solid var(--rdc-yellow);">
                    <h3 class="m-0">Créer un compte TelMed RDC</h3>
                </div>
                <div class="card-body p-4">
                    <?php if(!empty($message)): ?>
                        <div class="alert alert-<?= $status ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <form action="inscription.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Postnom</label>
                                <input type="text" class="form-control" name="postnom">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="prenom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email (Identifiant)</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" name="mot_de_passe" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="telephone" placeholder="+243..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="ville" placeholder="Ex: Kinshasa" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Commune / Zone sanitaire</label>
                                <input type="text" class="form-control" name="commune_zone" placeholder="Ex: Gombe" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Vous êtes ?</label>
                                <select class="form-select border-rdc-yellow" name="role" id="roleSelect" onchange="toggleMedecinFields()" required>
                                    <option value="patient">Patient (Besoin de soins)</option>
                                    <option value="medecin">Médecin (Prestataire de santé)</option>
                                </select>
                            </div>

                            <div id="medecinFields" class="row g-3 d-none mt-1">
                                <div class="col-md-6">
                                    <label class="form-label text-rdc-blue fw-bold">Spécialité Médicale </label>
                                    <input type="text" class="form-control border-primary" name="specialite" id="specialite">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-rdc-blue fw-bold">N° d'Ordre National des Médecins (CNOM RDC) </label>
                                    <input type="text" class="form-control border-primary" name="numero_ordre" id="numero_ordre">
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-rdc-blue w-100 fw-bold py-2">S'INSCRIRE SUR LA PLATEFORME</button>
                            </div>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="connexion.php" class="text-decoration-none">Déjà inscrit ? Connectez-vous ici</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMedecinFields() {
    var role = document.getElementById('roleSelect').value;
    var medFields = document.getElementById('medecinFields');
    var spec = document.getElementById('specialite');
    var ord = document.getElementById('numero_ordre');
    
    if(role === 'medecin') {
        medFields.classList.remove('d-none');
        spec.setAttribute('required', 'required');
        ord.setAttribute('required', 'required');
    } else {
        medFields.classList.add('d-none');
        spec.removeAttribute('required');
        ord.removeAttribute('required');
    }
}
</script>
</body>
</html>