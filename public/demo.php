<?php
// demo.php - Démonstration plateforme E-learning ISEP ABDOULAYE LY
session_start();

// Simulation de données de l'utilisateur
if (!isset($_SESSION['utilisateur'])) {
    $_SESSION['utilisateur'] = [
        'nom' => 'Abdoulaye Ly',
        'role' => 'Étudiant',
        'filiere' => 'Informatique',
        'niveau' => 'L3',
        'avatar' => 'default-avatar.png'
    ];
}

// Simulation des départements et filières
$departements = [
    [
        'nom' => 'Département Informatique',
        'chef' => 'Dr. Mamadou Diop',
        'description' => 'Formation aux métiers du numérique et de la programmation',
        'filieres' => [
            [
                'nom' => 'Génie Logiciel',
                'debouchés' => 'Développeur, Architecte logiciel, Chef de projet',
                'icon' => 'fas fa-laptop-code'
            ],
            [
                'nom' => 'Réseaux et Sécurité',
                'debouchés' => 'Administrateur réseau, Expert cybersécurité, Consultant',
                'icon' => 'fas fa-shield-alt'
            ],
            [
                'nom' => 'Intelligence Artificielle',
                'debouchés' => 'Data Scientist, Ingénieur ML, Expert IA',
                'icon' => 'fas fa-brain'
            ]
        ],
        'video' => 'https://www.youtube.com/embed/abcdefghijk',
        'images' => ['informatique1.jpg', 'informatique2.jpg', 'informatique3.jpg', 'informatique4.jpg']
    ],
    [
        'nom' => 'Département Génie Civil',
        'chef' => 'Prof. Awa Fall',
        'description' => 'Formation aux techniques de construction et infrastructures',
        'filieres' => [
            [
                'nom' => 'Bâtiment et Travaux Publics',
                'debouchés' => 'Ingénieur BTP, Conducteur de travaux, Chef de chantier',
                'icon' => 'fas fa-hard-hat'
            ],
            [
                'nom' => 'Géomatique et Topographie',
                'debouchés' => 'Géomètre-topographe, Cartographe, Technicien géomètre',
                'icon' => 'fas fa-drafting-compass'
            ]
        ],
        'video' => 'https://www.youtube.com/embed/lmnopqrstuv',
        'images' => ['genie-civil1.jpg', 'genie-civil2.jpg', 'genie-civil3.jpg']
    ],
    [
        'nom' => 'Département Télécommunications',
        'chef' => 'Dr. Jean Ndiaye',
        'description' => 'Formation aux réseaux de communication et systèmes embarqués',
        'filieres' => [
            [
                'nom' => 'Réseaux Télécoms',
                'debouchés' => 'Ingénieur télécoms, Technicien réseaux, Architecte solutions',
                'icon' => 'fas fa-broadcast-tower'
            ],
            [
                'nom' => 'Systèmes Embarqués',
                'debouchés' => 'Ingénieur embedded, Développeur firmware, Concepteur systèmes',
                'icon' => 'fas fa-microchip'
            ]
        ],
        'video' => 'https://www.youtube.com/embed/wxyzabcdefg',
        'images' => ['telecoms1.jpg', 'telecoms2.jpg', 'telecoms3.jpg', 'telecoms4.jpg']
    ],
    [
        'nom' => 'Département Management',
        'chef' => 'Prof. Marie Sène',
        'description' => 'Formation aux sciences de gestion et management des entreprises',
        'filieres' => [
            [
                'nom' => 'Management des Organisations',
                'debouchés' => 'Manager, Chef de service, Responsable administratif',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'nom' => 'Gestion Financière',
                'debouchés' => 'Contrôleur de gestion, Analyste financier, Comptable',
                'icon' => 'fas fa-coins'
            ]
        ],
        'video' => 'https://www.youtube.com/embed/hijklmnopqr',
        'images' => ['management1.jpg', 'management2.jpg', 'management3.jpg']
    ]
];

// Simulation de démonstrations de la plateforme
$demonstrations = [
    [
        'titre' => 'Inscription aux cours',
        'description' => 'Découvrez comment vous inscrire à vos cours en ligne',
        'etapes' => [
            'Connectez-vous à votre compte',
            'Allez dans la section "Mes Cours"',
            'Choisissez le cours désiré',
            'Cliquez sur "S\'inscrire"'
        ],
        'icon' => 'fas fa-book-open'
    ],
    [
        'titre' => 'Accéder aux ressources',
        'description' => 'Apprenez à accéder aux ressources pédagogiques',
        'etapes' => [
            'Ouvrez le cours de votre choix',
            'Cliquez sur l\'onglet "Ressources"',
            'Téléchargez les documents nécessaires',
            'Consultez les vidéos de cours'
        ],
        'icon' => 'fas fa-file-download'
    ],
    [
        'titre' => 'Participer aux forums',
        'description' => 'Interagissez avec vos professeurs et collègues',
        'etapes' => [
            'Accédez au forum du cours',
            'Posez vos questions dans la section appropriée',
            'Répondez aux questions des autres étudiants',
            'Consultez les réponses des enseignants'
        ],
        'icon' => 'fas fa-comments'
    ],
    [
        'titre' => 'Rendre un devoir',
        'description' => 'Déposez vos travaux en ligne',
        'etapes' => [
            'Allez dans la section "Devoirs" du cours',
            'Cliquez sur le devoir à rendre',
            'Téléversez votre fichier',
            'Confirmez la soumission'
        ],
        'icon' => 'fas fa-tasks'
    ]
];

// Témoignages d'étudiants
$temoignages = [
    [
        'nom' => 'Aïcha Diop',
        'filiere' => 'Génie Logiciel',
        'texte' => 'Cette plateforme a révolutionné ma façon d\'apprendre. Les ressources sont complètes et accessibles à tout moment.',
        'avatar' => 'etudiant1.jpg'
    ],
    [
        'nom' => 'Mohamed Camara',
        'filiere' => 'Réseaux et Sécurité',
        'texte' => 'Les vidéos explicatives et les forums m\'ont beaucoup aidé à comprendre des concepts complexes.',
        'avatar' => 'etudiant2.jpg'
    ],
    [
        'nom' => 'Fatou Ndiaye',
        'filiere' => 'Management',
        'texte' => 'L\'interface intuitive et les fonctionnalités de la plateforme rendent l\'apprentissage agréable et efficace.',
        'avatar' => 'etudiant3.jpg'
    ]
];

// Documents à télécharger
$documents = [
    [
        'titre' => 'Guide d\'utilisation de la plateforme',
        'description' => 'Document complet expliquant toutes les fonctionnalités de la plateforme e-learning',
        'taille' => '2.5 MB',
        'lien' => '#',
        'icon' => 'fas fa-book'
    ],
    [
        'titre' => 'Manuel de l\'étudiant',
        'description' => 'Règlement intérieur et informations importantes pour les étudiants',
        'taille' => '1.8 MB',
        'lien' => '#',
        'icon' => 'fas fa-user-graduate'
    ],
    [
        'titre' => 'Calendrier académique 2023-2024',
        'description' => 'Dates importantes de l\'année académique en cours',
        'taille' => '0.5 MB',
        'lien' => '#',
        'icon' => 'fas fa-calendar-alt'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plateforme E-learning ISEP ABDOULAYE LY</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --couleur-primaire: #009688;
            --couleur-primaire-dark: #00766B;
            --couleur-secondaire: #FF9800;
            --couleur-secondaire-dark: #F57C00;
            --couleur-accent: #2196F3;
            --couleur-fond: #f8f9fa;
            --couleur-texte: #333;
            --couleur-texte-light: #6c757d;
            --couleur-blanc: #fff;
            --couleur-border: #e0e0e0;
            --ombre-legere: 0 2px 10px rgba(0, 0, 0, 0.08);
            --ombre-moyenne: 0 5px 15px rgba(0, 0, 0, 0.1);
            --ombre-forte: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--couleur-fond);
            color: var(--couleur-texte);
            line-height: 1.6;
            position: relative;
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f8f9fa"/><path d="M0 0L100 100" stroke="%23e0e0e0" stroke-width="1"/><path d="M100 0L0 100" stroke="%23e0e0e0" stroke-width="1"/></svg>');
            opacity: 0.3;
            z-index: -1;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* En-tête */
        header {
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-primaire-dark));
            color: var(--couleur-blanc);
            padding: 15px 0;
            box-shadow: var(--ombre-legere);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 15px;
            box-shadow: var(--ombre-legere);
        }
        
        .logo-icon span {
            color: var(--couleur-primaire);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.2;
        }
        
        .logo small {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--couleur-blanc);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--couleur-primaire);
            font-size: 1.2rem;
            box-shadow: var(--ombre-legere);
        }
        
        .user-details {
            text-align: right;
        }
        
        .user-details p {
            margin: 2px 0;
            font-size: 0.9rem;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        /* Navigation */
        nav {
            background-color: var(--couleur-blanc);
            padding: 0;
            box-shadow: var(--ombre-legere);
        }
        
        nav ul {
            display: flex;
            list-style: none;
            justify-content: center;
        }
        
        nav ul li {
            margin: 0 5px;
        }
        
        nav ul li a {
            color: var(--couleur-texte);
            text-decoration: none;
            font-weight: 500;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        nav ul li a:hover {
            color: var(--couleur-primaire);
            background-color: rgba(0, 150, 136, 0.05);
            border-bottom: 3px solid var(--couleur-primaire);
        }
        
        nav ul li a i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        /* Bannière */
        .banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400" viewBox="0 0 1200 400"><rect width="1200" height="400" fill="%23009688"/></svg>') center/cover no-repeat;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--couleur-blanc);
            text-align: center;
            position: relative;
            margin-bottom: 50px;
        }
        
        .banner-content {
            max-width: 800px;
            padding: 0 20px;
            animation: fadeIn 1s ease-in-out;
        }
        
        .banner h2 {
            font-size: 2.8rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .banner p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--couleur-secondaire);
            color: var(--couleur-blanc);
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: var(--ombre-legere);
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--couleur-secondaire-dark);
            transform: translateY(-2px);
            box-shadow: var(--ombre-moyenne);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--couleur-blanc);
            margin-left: 15px;
        }
        
        .btn-outline:hover {
            background-color: var(--couleur-blanc);
            color: var(--couleur-primaire);
        }
        
        /* Sections */
        .section-title {
            text-align: center;
            margin: 50px 0 40px;
            color: var(--couleur-primaire);
            position: relative;
            padding-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--couleur-secondaire);
            border-radius: 2px;
        }
        
        .section-subtitle {
            text-align: center;
            color: var(--couleur-texte-light);
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Départements */
        .departements {
            margin-bottom: 70px;
        }
        
        .departement {
            background-color: var(--couleur-blanc);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--ombre-legere);
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .departement:hover {
            transform: translateY(-5px);
            box-shadow: var(--ombre-moyenne);
        }
        
        .departement-header {
            background: linear-gradient(to right, var(--couleur-primaire), var(--couleur-primaire-dark));
            color: var(--couleur-blanc);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .departement-header h2 {
            font-size: 1.5rem;
        }
        
        .departement-icon {
            font-size: 1.8rem;
            opacity: 0.8;
        }
        
        .departement-content {
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .departement-content {
                grid-template-columns: 1fr;
            }
        }
        
        .departement-info h3 {
            color: var(--couleur-primaire);
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .departement-info h3 i {
            font-size: 1.1rem;
        }
        
        .departement-info p {
            margin-bottom: 20px;
            color: var(--couleur-texte-light);
        }
        
        .chef-departement {
            background-color: rgba(0, 150, 136, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chef-departement i {
            color: var(--couleur-primaire);
            font-size: 1.2rem;
        }
        
        .filieres-list {
            list-style: none;
            margin-top: 20px;
        }
        
        .filieres-list li {
            padding: 12px 0;
            border-bottom: 1px solid var(--couleur-border);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .filieres-list li:last-child {
            border-bottom: none;
        }
        
        .filiere-icon {
            color: var(--couleur-primaire);
            font-size: 1.2rem;
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .filiere-content {
            flex-grow: 1;
        }
        
        .filiere-titre {
            font-weight: 600;
            color: var(--couleur-primaire);
            margin-bottom: 5px;
        }
        
        .filiere-debouches {
            font-size: 0.9rem;
            color: var(--couleur-texte-light);
        }
        
        .departement-media {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: var(--ombre-legere);
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
        }
        
        .gallery-container {
            position: relative;
        }
        
        .gallery-title {
            font-size: 1rem;
            margin-bottom: 10px;
            color: var(--couleur-primaire);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .gallery-item {
            height: 80px;
            border-radius: 6px;
            overflow: hidden;
            background: linear-gradient(45deg, var(--couleur-primaire), var(--couleur-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            text-align: center;
            padding: 5px;
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        /* Démonstrations */
        .demonstrations {
            margin-bottom: 70px;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .demo-card {
            background-color: var(--couleur-blanc);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--ombre-legere);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .demo-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--ombre-moyenne);
        }
        
        .demo-header {
            background: linear-gradient(to right, var(--couleur-secondaire), var(--couleur-secondaire-dark));
            color: var(--couleur-blanc);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .demo-icon {
            font-size: 1.8rem;
        }
        
        .demo-header h3 {
            font-size: 1.2rem;
        }
        
        .demo-content {
            padding: 20px;
        }
        
        .demo-content p {
            margin-bottom: 20px;
            color: var(--couleur-texte-light);
        }
        
        .etapes-list {
            list-style: none;
            counter-reset: step-counter;
        }
        
        .etapes-list li {
            counter-increment: step-counter;
            margin-bottom: 15px;
            padding-left: 40px;
            position: relative;
            color: var(--couleur-texte-light);
        }
        
        .etapes-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background-color: var(--couleur-primaire);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        /* Message du directeur */
        .directeur-message {
            background-color: var(--couleur-blanc);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 70px;
            box-shadow: var(--ombre-legere);
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .directeur-message::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--couleur-primaire), var(--couleur-secondaire));
        }
        
        @media (max-width: 992px) {
            .directeur-message {
                grid-template-columns: 1fr;
            }
        }
        
        .directeur-photo {
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            min-height: 300px;
            box-shadow: var(--ombre-moyenne);
        }
        
        .directeur-text h3 {
            color: var(--couleur-primaire);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .directeur-text p {
            margin-bottom: 20px;
            color: var(--couleur-texte-light);
        }
        
        .signature {
            text-align: right;
            font-style: italic;
            color: var(--couleur-secondaire);
            margin-top: 30px;
            font-weight: 500;
        }
        
        /* Témoignages */
        .temoignages {
            margin-bottom: 70px;
        }
        
        .temoignages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .temoignage-card {
            background-color: var(--couleur-blanc);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--ombre-legere);
            position: relative;
        }
        
        .temoignage-card::before {
            content: """;
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 4rem;
            color: rgba(0, 150, 136, 0.1);
            font-family: Arial;
        }
        
        .temoignage-content {
            margin-bottom: 20px;
            color: var(--couleur-texte-light);
            font-style: italic;
            position: relative;
            z-index: 1;
        }
        
        .temoignage-auteur {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .auteur-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .auteur-details h4 {
            color: var(--couleur-primaire);
            margin-bottom: 3px;
        }
        
        .auteur-details p {
            font-size: 0.8rem;
            color: var(--couleur-texte-light);
        }
        
        /* Documents */
        .documents {
            margin-bottom: 70px;
        }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .document-card {
            background-color: var(--couleur-blanc);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--ombre-legere);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--ombre-moyenne);
        }
        
        .document-icon {
            font-size: 2.5rem;
            color: var(--couleur-primaire);
            margin-bottom: 20px;
        }
        
        .document-title {
            font-size: 1.2rem;
            color: var(--couleur-primaire);
            margin-bottom: 10px;
        }
        
        .document-desc {
            color: var(--couleur-texte-light);
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .document-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .document-size {
            font-size: 0.9rem;
            color: var(--couleur-texte-light);
        }
        
        .document-download {
            color: var(--couleur-primaire);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .document-download:hover {
            color: var(--couleur-primaire-dark);
        }
        
        /* Footer */
        footer {
            background: linear-gradient(to right, var(--couleur-primaire-dark), var(--couleur-primaire));
            color: var(--couleur-blanc);
            padding: 60px 0 30px;
            margin-top: 80px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--couleur-secondaire);
            border-radius: 2px;
        }
        
        .footer-section p, .footer-section a {
            margin-bottom: 12px;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: var(--couleur-blanc);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        
        .social-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .logo {
                justify-content: center;
            }
            
            .user-info {
                justify-content: center;
                text-align: center;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            nav ul li {
                margin: 5px;
            }
            
            nav ul li a {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .banner h2 {
                font-size: 2rem;
            }
            
            .banner p {
                font-size: 1rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .directeur-message {
                padding: 25px;
            }
            
            .image-gallery {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.4rem;
            }
            
            .banner {
                height: 350px;
            }
            
            .banner h2 {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .departement-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .image-gallery {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <header style="background-color: #009688; padding: 10px 20px;">
    <div class="container header-content" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        
        <!-- Logo -->
        <div class="logo" style="display: flex; align-items: center; gap: 10px; color: white;">
            <div class="logo-icon" style="font-weight: bold; font-size: 20px;">
                <span>ISEP</span>
            </div>
            <h1 style="margin: 0; font-size: 18px; line-height: 1.2;">
                ISEP ABDOULAYE LY<br>
                <small style="font-size: 12px;">Thiès - E-learning</small>
            </h1>
        </div>

        <!-- Barre de recherche -->
        <div class="search-bar" style="flex-grow: 1; max-width: 300px;">
            <input type="text" placeholder="Rechercher..." 
                style="width: 100%; padding: 8px 12px; border-radius: 25px; border: none; outline: none;">
        </div>

        <!-- Icônes réseaux sociaux -->
        <div class="social-icons" style="display: flex; gap: 15px; font-size: 18px; color: white;">
            <a href="https://facebook.com" target="_blank" style="color: white;"><i class="fab fa-facebook-f"></i></a>
            <a href="https://youtube.com" target="_blank" style="color: white;"><i class="fab fa-youtube"></i></a>
            <a href="https://instagram.com" target="_blank" style="color: white;"><i class="fab fa-instagram"></i></a>
        </div>

        <!-- Avatar utilisateur -->

      <div class="user-info">
    <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden;">
        <!-- Image étudiant -->
      <img src="../images/image5.jpg" alt="Étudiant" style="width: 100%; height: 100%; object-fit: cover;">

    </div>
</div>



</header>

    
    <!-- Navigation -->
    <nav>
        <div class="container">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="login.php"><i class="fas fa-book"></i> Cours</a></li>
                <li><a href="login.php"><i class="fas fa-tasks"></i> Devoirs</a></li>
                <li><a href="login.php"><i class="fas fa-chart-bar"></i> Notes</a></li>
                <li><a href="login.php"><i class="fas fa-calendar"></i> Emploi du temps</a></li>
                <li><a href="login.php"><i class="fas fa-download"></i> Ressources</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Bannière -->
    <div class="banner">
        <div class="banner-content">
            <h2>Plateforme E-learning ISEP ABDOULAYE LY DE THIES</h2>
            <p>Découvrez une nouvelle expérience d'apprentissage en ligne avec nos formations de qualité et nos ressources pédagogiques innovantes</p>
            <div>
                <a href="#" class="btn">Commencer maintenant</a>
               <a href="docs/pdf_68b33c253b6dc.pdf" class="btn btn-outline" target="_blank">Guide d'utilisation</a>

            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Message du directeur -->
        <h2 class="section-title">Message du Directeur Général</h2>
        <p class="section-subtitle">Découvrez la vision et les engagements de notre institution à travers le message de notre Directeur Général</p>
        
        <div class="directeur-message fade-in">
    <div class="directeur-photo">
        <img src="../assets/images/Photo_DG-removebg-preview (1).png" alt="Directeur Général" class="directeur-img">

    </div>
    <div class="directeur-text">
        <h3>Bienvenue à l'ISEP ABDOULAYE LY de Thiès</h3>
        <p>Notre institut s'engage à offrir une formation de qualité alliant théorie et pratique pour préparer nos étudiants aux défis du marché du travail. La plateforme e-learning que nous mettons à votre disposition représente notre engagement envers l'innovation pédagogique et l'accessibilité de l'éducation.</p>
        <p>À travers nos différents départements et filières, nous formons les professionnels de demain dans les domaines techniques et managériaux, avec un focus sur l'excellence académique et le développement personnel.</p>
        <p>Je vous invite à explorer notre plateforme et à découvrir toutes les opportunités de formation que nous proposons.</p>
        <div class="signature">
            Le Directeur Général<br>
            ISEP ABDOULAYE LY, Thiès 
        </div>
      
    </div>
      <div><h4>Dr FADELE NIANG</h4></div>
</div>

        
        <!-- Départements -->
        <h2 class="section-title">Nos Départements et Filières</h2>
        <p class="section-subtitle">Découvrez nos départements académiques et les diverses filières de formation que nous proposons</p>
        
        <div class="departements">
            <?php foreach ($departements as $index => $departement): ?>
            <div class="departement fade-in">
                <div class="departement-header">
                    <h2><?php echo $departement['nom']; ?></h2>
                    <div class="departement-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="departement-content">
                    <div class="departement-info">
                        <h3><i class="fas fa-info-circle"></i> Présentation</h3>
                        <p><?php echo $departement['description']; ?></p>
                        
                        <div class="chef-departement">
                            <i class="fas fa-user-tie"></i>
                            <div><strong>Chef de département:</strong> <?php echo $departement['chef']; ?></div>
                        </div>
                        
                        <h3 style="margin-top: 20px;"><i class="fas fa-stream"></i> Filières et Débouchés</h3>
                        <ul class="filieres-list">
                            <?php foreach ($departement['filieres'] as $filiere): ?>
                            <li>
                                <div class="filiere-icon">
                                    <i class="<?php echo $filiere['icon']; ?>"></i>
                                </div>
                                <div class="filiere-content">
                                    <div class="filiere-titre"><?php echo $filiere['nom']; ?></div>
                                    <div class="filiere-debouches">Débouchés: <?php echo $filiere['debouchés']; ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="departement-media">
                        <div class="video-container">
                            <iframe src="<?php echo $departement['video']; ?>" allowfullscreen></iframe>
                        </div>
                        
                        <div class="gallery-container">
                            <h4 class="gallery-title"><i class="fas fa-images"></i> Galerie du département</h4>
                            <div class="image-gallery">
                                <?php foreach ($departement['images'] as $image): ?>
                                <div class="gallery-item"><?php echo $image; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Démonstration de la plateforme -->
        <h2 class="section-title">Comment utiliser la plateforme</h2>
        <p class="section-subtitle">Apprenez à naviguer et à utiliser toutes les fonctionnalités de notre plateforme e-learning</p>
        
        <div class="demonstrations">
            <div class="demo-grid">
                <?php foreach ($demonstrations as $demo): ?>
                <div class="demo-card fade-in">
                    <div class="demo-header">
                        <div class="demo-icon">
                            <i class="<?php echo $demo['icon']; ?>"></i>
                        </div>
                        <h3><?php echo $demo['titre']; ?></h3>
                    </div>
                    <div class="demo-content">
                        <p><?php echo $demo['description']; ?></p>
                        <ol class="etapes-list">
                            <?php foreach ($demo['etapes'] as $etape): ?>
                            <li><?php echo $etape; ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Témoignages -->
        <h2 class="section-title">Témoignages</h2>
        <p class="section-subtitle">Découvrez ce que nos étudiants pensent de notre plateforme e-learning</p>
        
        <div class="temoignages">
            <div class="temoignages-grid">
                <?php foreach ($temoignages as $temoignage): ?>
                <div class="temoignage-card fade-in">
                    <p class="temoignage-content">"<?php echo $temoignage['texte']; ?>"</p>
                    <div class="temoignage-auteur">
                        <div class="auteur-avatar">
                            <?php echo substr($temoignage['nom'], 0, 1); ?>
                        </div>
                        <div class="auteur-details">
                            <h4><?php echo $temoignage['nom']; ?></h4>
                            <p><?php echo $temoignage['filiere']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Documents à télécharger -->
        <h2 class="section-title">Ressources à télécharger</h2>
        <p class="section-subtitle">Accédez à tous les documents et guides nécessaires pour utiliser notre plateforme</p>
        
        <div class="documents">
            <div class="documents-grid">
                <?php foreach ($documents as $document): ?>
                <div class="document-card fade-in">
                    <div class="document-icon">
                        <i class="<?php echo $document['icon']; ?>"></i>
                    </div>
                    <h3 class="document-title"><?php echo $document['titre']; ?></h3>
                    <p class="document-desc"><?php echo $document['description']; ?></p>
                    <div class="document-meta">
                        <span class="document-size"><?php echo $document['taille']; ?></span>
                        <a href="<?php echo $document['lien']; ?>" class="document-download">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Pied de page -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>À propos de nous</h3>
                    <p>ISEP ABDOULAYE LY de Thiès propose des formations professionnelles de qualité avec une plateforme e-learning moderne et interactive.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Adresse: Thiès, Sénégal</p>
                    <p><i class="fas fa-phone"></i> Téléphone: +221 33 123 45 67</p>
                    <p><i class="fas fa-envelope"></i> Email: contact@isep-thies.sn</p>
                </div>
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <a href="index.php"><i class="fas fa-angle-right"></i> Accueil</a>
                    <a href="login.php"><i class="fas fa-angle-right"></i> Cours</a>
                    <a href="login.php"><i class="fas fa-angle-right"></i> Bibliothèque</a>
                    <a href="contact.php"><i class="fas fa-angle-right"></i> FAQ</a>
                </div>
                <div class="footer-section">
                    <h3>Newsletter</h3>
                    <p>Abonnez-vous à notre newsletter pour recevoir les dernières actualités</p>
                    <form>
                        <input type="email" placeholder="Votre email" style="width: 100%; padding: 10px; margin-bottom: 10px; border: none; border-radius: 4px;">
                        <button type="submit" class="btn" style="width: 100%;">S'abonner</button>
                    </form>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 ISEP ABDOULAYE LY Thiès - Tous droits réservés</p>
            </div>
        </div>
    </footer>
</body>
</html>