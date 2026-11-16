<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelMed RDC - Télémédecine en République Démocratique du Congo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles spécifiques pour la carte et le guide */
        #map {
            height: 450px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1;
        }
        .timeline-steps {
            position: relative;
            padding-left: 2rem;
            border-left: 3px solid #007FFF;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2.5rem;
        }
        .timeline-icon {
            position: absolute;
            left: -3.1rem;
            background: #fff;
            border: 3px solid #007FFF;
            color: #007FFF;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

    <div class="bg-dark text-white py-2 small d-none d-md-block">
        <div class="container d-flex justify-content-between">
            <div><i class="fa-solid fa-location-dot text-warning me-2"></i> Kinshasa, République Démocratique du Congo</div>
            <div><i class="fa-solid fa-shield-halved text-success me-2"></i> Plateforme médicale sécurisée conforme CNOM</div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-rdc-blue" href="index.php">
                <i class="fa-solid fa-heart-pulse text-danger me-2"></i>TelMed <span class="text-warning">RDC</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">
                    <li class="nav-item"><a class="nav-link fw-bold" href="#fonctionnement">Comment ça marche</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold" href="#hopitaux">Centres Agréés</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-rdc-blue fw-bold"><i class="fa-solid fa-chart-pie me-2"></i>Mon Espace</a>
                    <?php else: ?>
                        <a href="auth/connexion.php" class="btn btn-outline-primary fw-bold">Connexion</a>
                        <a href="auth/inscription.php" class="btn btn-warning fw-bold text-dark">S'inscrire gratuitement</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-gradient-rdc py-5 text-white position-relative">
        <div class="container my-5">
            <div class="row align-items-center">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 mb-3 shadow-sm text-uppercase">L'innovation médicale au Congo</span>
                    <h1 class="display-4 fw-bold mb-3">Consultez un médecin en ligne, où que vous soyez en RDC</h1>
                    <p class="lead mb-4 opacity-90">Évitez les embouteillages de Kinshasa. Prenez rendez-vous, échangez par message sécurisé, passez votre visioconférence avec un professionnel inscrit à l'Ordre et téléchargez votre ordonnance.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-3">
                        <a href="auth/inscription.php" class="btn btn-lg btn-warning text-dark fw-bold shadow"><i class="fa-solid fa-user-plus me-2"></i>Créer mon compte</a>
                        <a href="#fonctionnement" class="btn btn-lg btn-outline-light fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Comment ça marche ?</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center">
                    <i class="fa-solid fa-laptop-medical fa-10x text-warning opacity-75 animate-pulse"></i>
                </div>
            </div>
        </div>
    </header>

    <section id="fonctionnement" class="container my-5 py-5">
        <div class="text-center mb-5">
            <span class="text-uppercase text-primary fw-bold small tracking-wider">Guide d'utilisation</span>
            <h2 class="fw-bold text-rdc-blue mt-2">Votre parcours de soin en 4 étapes</h2>
            <p class="text-muted">Simple, rapide et entièrement confidentiel.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="timeline-steps">
                    <div class="timeline-item">
                        <div class="timeline-icon">1</div>
                        <h5 class="fw-bold text-dark">Inscription et Sécurisation (2FA)</h5>
                        <p class="text-muted">Créez votre compte en quelques clics. Votre accès est immédiatement protégé par une authentification à double facteur stricte pour garantir l'inviolabilité de votre dossier médical.</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">2</div>
                        <h5 class="fw-bold text-dark">Recherche et Prise de rendez-vous</h5>
                        <p class="text-muted">Filtrez les médecins disponibles par spécialité, ville ou commune (Gombe, Bandalungwa, Limete, etc.). Choisissez le créneau horaire qui vous convient.</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">3</div>
                        <h5 class="fw-bold text-dark">Téléconsultation en Direct</h5>
                        <p class="text-muted">Le jour J, connectez-vous à votre espace. Échangez par messagerie chiffrée ou lancez la visioconférence sécurisée directement depuis votre navigateur avec le praticien.</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">4</div>
                        <h5 class="fw-bold text-dark">Suivi et Ordonnance PDF</h5>
                        <p class="text-muted">À la fin de la consultation, le médecin rédige son rapport. Téléchargez instantanément votre ordonnance numérique officielle au format PDF pour vous présenter en pharmacie.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="hopitaux" class="bg-white py-5 shadow-sm">
        <div class="container my-4">
            <div class="row align-items-center">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <span class="text-uppercase text-danger fw-bold small">Réseau Local</span>
                    <h2 class="fw-bold text-rdc-blue mt-2">Établissements partenaires à Kinshasa</h2>
                    <p class="text-muted">Explorez les centres de santé et hôpitaux universitaires connectés à notre infrastructure. Vous pouvez y effectuer vos examens physiques complémentaires si votre médecin TelMed vous le recommande.</p>
                    <hr class="border-warning opacity-75 my-4" style="width: 60px; border-width: 3px;">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Cliniques Universitaires de Kinshasa (UNIKIN)</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Hôpital Biamba Marie Mutombo</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Hopital de Munkole</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Centre Médical Diamant</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Centre Médical de Kinshasa (CMK)</li>
                    </ul>
                </div>
                <div class="col-lg-8">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-2 fw-bold">TelMed RDC &copy; 2026 — Plateforme Intégrée de Télémédecine</p>
            <p class="small text-muted m-0">Conçu et développé dans le respect des normes du secret médical et de l'audit système.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialisation de la carte centrée sur Kinshasa
        var map = L.map('map').setView([-4.325, 15.315], 12);

        // Chargement des tuiles cartographiques (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Ajout des marqueurs sur les hôpitaux principaux de Kinshasa
        var points = [
            { name: "Cliniques Universitaires de Kinshasa (UNIKIN)", lat: -4.417, lng: 15.308, desc: "Centre Hospitalier Académique - Mont Amba" },
            { name: "Hôpital Biamba Marie Mutombo", lat: -4.376, lng: 15.364, desc: "Infrastructure moderne - Comm. de Masina" },
            { name: "Centre Médical de Kinshasa (CMK)", lat: -4.305, lng: 15.301, desc: "Urgences et Spécialités - Comm. de la Gombe" },
            { name: "Hôpital Général de Référence de Kinshasa (Ex-Mama Yemo)", lat: -4.318, lng: 15.312, desc: "Grand Centre Hospitalier Public" }
        ];

        points.forEach(function(place) {
            L.marker([place.lat, place.lng])
                .addTo(map)
                .bindPopup("<b>" + place.name + "</b><br><span class='text-muted small'>" + place.desc + "</span>");
        });
    </script>
</body>
</html>