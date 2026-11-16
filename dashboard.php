<?php
// dashboard.php
session_start();
require_once 'config/db.php';

// Sécurité : Vérification d'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Récupération des informations de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT nom, prenom, ville, commune_zone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

// Si c'est un médecin, récupération de son lien Meet
$lien_meet_actuel = "";
if ($user_role === 'medecin') {
    $stmtM = $pdo->prepare("SELECT lien_meet FROM medecins WHERE id = ?");
    $stmtM->execute([$user_id]);
    $medData = $stmtM->fetch();
    $lien_meet_actuel = $medData['lien_meet'] ?? '';
}

$ville_filtre = isset($_GET['ville']) ? trim($_GET['ville']) : '';
$zone_filtre = isset($_GET['zone']) ? trim($_GET['zone']) : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Tableau de Bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-rdc-blue shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-user-shield text-warning me-2"></i>Espace <span class="text-warning"><?= ucfirst($user_role) ?></span>
            </a>
            <div class="ms-auto">
                <span class="text-white me-3"><i class="fa-regular fa-user me-1"></i> <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></span>
                <a href="auth/deconnexion.php" class="btn btn-sm btn-danger"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'success_meet'): ?>
                <div class="alert alert-success alert-dismissible fade show">Lien Google Meet mis à jour avec succès !</div>
            <?php elseif ($_GET['msg'] === 'success_rdv'): ?>
                <div class="alert alert-success alert-dismissible fade show">Demande de rendez-vous enregistrée en attente.</div>
            <?php elseif ($_GET['msg'] === 'success_dispo'): ?>
                <div class="alert alert-success alert-dismissible fade show">Nouvelle plage horaire ajoutée.</div>
            <?php elseif ($_GET['msg'] === 'status_updated'): ?>
                <div class="alert alert-info alert-dismissible fade show">Le statut du rendez-vous a été mis à jour.</div>
            <?php elseif ($_GET['msg'] === 'consultation_terminee'): ?>
                <div class="alert alert-success alert-dismissible fade show">Consultation clôturée et ordonnance signée électroniquement.</div>
            <?php elseif ($_GET['msg'] === 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show">Une erreur technique est survenue.</div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($user_role === 'patient'): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card bg-white p-4 shadow-sm border-start border-4 border-primary">
                        <h2 class="fw-bold text-rdc-blue">Bienvenue dans votre espace santé</h2>
                        <p class="text-muted m-0">Consultez vos rendez-vous, accédez aux visioconférences et téléchargez vos ordonnances médicales.</p>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-dark text-white fw-bold">
                            <i class="fa-solid fa-filter text-warning me-2"></i>Filtrer les Médecins
                        </div>
                        <div class="card-body bg-white">
                            <form action="dashboard.php" method="GET">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Ville</label>
                                    <input type="text" class="form-control" name="ville" value="<?= htmlspecialchars($ville_filtre) ?>" placeholder="Ex: Kinshasa">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Commune / Zone</label>
                                    <input type="text" class="form-control" name="zone" value="<?= htmlspecialchars($zone_filtre) ?>" placeholder="Ex: Gombe">
                                </div>
                                <button type="submit" class="btn btn-rdc-blue w-100 fw-bold"><i class="fa-solid fa-search me-2"></i>Rechercher</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-secondary text-white fw-bold">
                            <i class="fa-solid fa-notes-medical text-warning me-2"></i>Suivi de mes RDV
                        </div>
                        <div class="card-body p-2 bg-white">
                            <?php
                            $stmtMyRDV = $pdo->prepare("SELECT r.*, u.nom, u.prenom FROM rendez_vous r JOIN users u ON r.id_medecin = u.id WHERE r.id_patient = ? ORDER BY r.date_rdv DESC");
                            $stmtMyRDV->execute([$user_id]);
                            $myRdvs = $stmtMyRDV->fetchAll();
                            
                            if(count($myRdvs) > 0): foreach($myRdvs as $r): ?>
                                <div class="p-3 border-bottom bg-light rounded mb-2">
                                    <span class="fw-bold text-rdc-blue">Dr. <?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></span><br>
                                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i><?= $r['date_rdv'] ?> à <?= $r['heure_rdv'] ?></small><br>
                                    
                                    <div class="mt-2">
                                        <?php if ($r['statut'] === 'confirme' && !empty($r['lien_consultation'])): ?>
                                            <span class="badge bg-success mb-2">Confirmé</span><br>
                                            <a href="<?= htmlspecialchars($r['lien_consultation']) ?>" target="_blank" class="btn btn-sm btn-danger fw-bold text-white w-100 mb-1"><i class="fa-solid fa-video me-1"></i> Appel Vidéo Meet</a>
                                        <?php elseif($r['statut'] === 'en_attente'): ?>
                                            <span class="badge bg-warning text-dark mb-2">En attente</span>
                                        <?php elseif($r['statut'] === 'termine'): ?>
                                            <span class="badge bg-dark text-white mb-2"><i class="fa-solid fa-circle-check text-success me-1"></i>Consultation Terminée</span><br>
                                            <a href="actions/telecharger_ordonnance.php?id_rdv=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-warning fw-bold text-dark w-100 mb-1">
                                                <i class="fa-solid fa-file-pdf me-1"></i> Ordonnance (PDF)
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mb-2">Annulé</span>
                                        <?php endif; ?>
                                        
                                        <a href="messagerie.php?id_contact=<?= $r['id_medecin'] ?>" class="btn btn-sm btn-outline-dark w-100">
                                            <i class="fa-solid fa-comment-dots text-rdc-blue me-1"></i> Messagerie
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <p class="text-muted small p-2 m-0 text-center">Aucun rendez-vous.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <h4 class="fw-bold text-rdc-blue mb-3"><i class="fa-solid fa-user-md text-warning me-2"></i>Médecins disponibles</h4>
                    <?php
                    $sql = "SELECT u.id, u.nom, u.prenom, u.ville, u.commune_zone, m.specialite, d.jour_semaine, d.heure_debut, d.heure_fin 
                            FROM users u  JOIN medecins m ON u.id = m.id 
                            JOIN disponibilites d ON m.id = d.id_medecin 
                            WHERE u.role = 'medecin' AND d.est_disponible = 1";
                    
                    $params = [];
                    if (!empty($ville_filtre)) { $sql .= " AND u.ville LIKE ?"; $params[] = "%$ville_filtre%"; }
                    if (!empty($zone_filtre)) { $sql .= " AND u.commune_zone LIKE ?"; $params[] = "%$zone_filtre%"; }
                    
                    $stmtMed = $pdo->prepare($sql); $stmtMed->execute($params); $medecins = $stmtMed->fetchAll();

                    if (count($medecins) > 0): ?>
                        <div class="row g-3">
                            <?php foreach ($medecins as $med): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm border-0 border-top border-3 border-warning">
                                        <div class="card-body">
                                            <h5 class="card-title fw-bold text-primary">Dr. <?= htmlspecialchars($med['prenom'] . ' ' . $med['nom']) ?></h5>
                                            <p class="badge bg-rdc-blue mb-2"><?= htmlspecialchars($med['specialite']) ?></p>
                                            <p class="small text-muted mb-1"><i class="fa-solid fa-location-dot text-danger"></i> <?= htmlspecialchars($med['ville'] . ' ('. $med['commune_zone'].')') ?></p>
                                            <p class="small text-success fw-bold mb-3"><i class="fa-regular fa-clock"></i> Dispo : <?= $med['jour_semaine'] ?> de <?= $med['heure_debut'] ?> à <?= $med['heure_fin'] ?></p>
                                            <a href="actions/prendre_rdv.php?id_medecin=<?= $med['id'] ?>&heure=<?= $med['heure_debut'] ?>" class="btn btn-sm btn-outline-primary w-100 fw-bold">Prendre RDV</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Aucun médecin trouvé.</div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($user_role === 'medecin'): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card bg-dark text-white p-4 shadow-sm border-start border-4 border-warning">
                        <h2 class="fw-bold text-warning">Espace Praticien - Dr. <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></h2>
                        <p class="text-white-50 m-0">Gérez vos rendez-vous et rédigez vos diagnostics numériques.</p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-rdc-blue text-white fw-bold">Mon Salon Meet</div>
                        <div class="card-body bg-white">
                            <form action="actions/update_meet.php" method="POST">
                                <div class="mb-3">
                                    <input type="url" class="form-control border-rdc-yellow" name="lien_meet" value="<?= htmlspecialchars($lien_meet_actuel) ?>" required>
                                </div>
                                <button type="submit" class="btn btn-sm btn-rdc-blue w-100 fw-bold">Mettre à jour</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-secondary text-white fw-bold">Ajouter une Disponibilité</div>
                        <div class="card-body bg-white">
                            <form action="actions/ajouter_dispo.php" method="POST">
                                <div class="mb-3">
                                    <select class="form-select" name="jour_semaine" required>
                                        <option value="Lundi">Lundi</option><option value="Mardi">Mardi</option><option value="Mercredi">Mercredi</option><option value="Jeudi">Jeudi</option><option value="Vendredi">Vendredi</option><option value="Samedi">Samedi</option><option value="Dimanche">Dimanche</option>
                                    </select>
                                </div>
                                <div class="row mb-3">
                                    <div class="col"><input type="time" class="form-control" name="heure_debut" required></div>
                                    <div class="col"><input type="time" class="form-control" name="heure_fin" required></div>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-bold text-dark btn-sm">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 mb-4">
                    <h4 class="fw-bold text-rdc-blue mb-3">Demandes de consultations</h4>
                    <?php
                    $stmtRDV = $pdo->prepare("SELECT r.*, u.nom, u.prenom FROM rendez_vous r JOIN users u ON r.id_patient = u.id WHERE r.id_medecin = ? ORDER BY r.date_rdv DESC");
                    $stmtRDV->execute([$user_id]); $rdvs = $stmtRDV->fetchAll();

                    if (count($rdvs) > 0): ?>
                        <div class="table-responsive bg-white rounded shadow-sm p-2">
                            <table class="table table-hover align-middle m-0">
                                <thead class="table-light"><tr><th>Patient</th><th>Date & Heure</th><th>Statut</th><th class="text-center">Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rdvs as $r): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></td>
                                            <td><?= $r['date_rdv'] ?> à <?= $r['heure_rdv'] ?></td>
                                            <td>
                                                <?php if($r['statut'] === 'en_attente'): ?><span class="badge bg-warning text-dark">En attente</span>
                                                <?php elseif($r['statut'] === 'confirme'): ?><span class="badge bg-success">Confirmé</span>
                                                <?php elseif($r['statut'] === 'termine'): ?><span class="badge bg-dark text-white">Terminé</span>
                                                <?php else: ?><span class="badge bg-danger">Annulé</span><?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="messagerie.php?id_contact=<?= $r['id_patient'] ?>" class="btn btn-sm btn-outline-dark me-1"><i class="fa-solid fa-comments text-rdc-blue"></i></a>
                                                
                                                <?php if($r['statut'] === 'en_attente'): ?>
                                                    <a href="actions/statut_rdv.php?id=<?= $r['id'] ?>&statut=confirme" class="btn btn-sm btn-success text-white me-1"><i class="fa-solid fa-check"></i></a>
                                                    <a href="actions/statut_rdv.php?id=<?= $r['id'] ?>&statut=annule" class="btn btn-sm btn-danger text-white"><i class="fa-solid fa-xmark"></i></a>
                                                <?php elseif($r['statut'] === 'confirme'): ?>
                                                    <a href="consultation.php?id_rdv=<?= $r['id'] ?>" class="btn btn-sm btn-danger fw-bold text-white"><i class="fa-solid fa-stethoscope me-1"></i> Consulter</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Aucune demande reçue.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>