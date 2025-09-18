<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plateforme E-learning ISEP Thiès</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* RESET ET VARIABLES */
    :root {
      --primary: #009688;
      --primary-dark: #00796B;
      --secondary: #FF9800;
      --secondary-dark: #F57C00;
      --accent: #2196F3;
      --accent-dark: #1976D2;
      --text: #333;
      --text-light: #666;
      --light: #f9f9f9;
      --white: #ffffff;
      --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: var(--light);
      color: var(--text);
      line-height: 1.6;
      overflow-x: hidden;
    }

    a {
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* HEADER STYLISH */
    header {
      background: var(--white);
      box-shadow: var(--shadow);
      position: sticky;
      top: 0;
      z-index: 1000;
      padding: 15px 0;
    }

    header .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    header a.logo {
      font-size: 28px;
      font-weight: 800;
      color: var(--primary);
      display: flex;
      align-items: center;
    }

    header a.logo i {
      margin-right: 10px;
      font-size: 32px;
    }

    nav {
      display: flex;
      gap: 25px;
    }

    nav a {
      font-weight: 600;
      color: var(--text);
      position: relative;
      padding: 5px 0;
    }

    nav a:after {
      content: '';
      position: absolute;
      width: 0;
      height: 3px;
      bottom: 0;
      left: 0;
      background-color: var(--secondary);
      transition: width 0.3s ease;
    }

    nav a:hover:after {
      width: 100%;
    }

    nav a:hover {
      color: var(--primary);
    }

    .buttons a {
      padding: 10px 20px;
      border-radius: 30px;
      font-weight: 600;
      display: inline-block;
    }

    .btn-primary {
      background: var(--primary);
      color: var(--white);
      box-shadow: var(--shadow);
    }

    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: var(--shadow-hover);
    }

    .btn-outline {
      border: 2px solid var(--primary);
      color: var(--primary);
    }

    .btn-outline:hover {
      background: var(--primary);
      color: var(--white);
    }

    /* HERO SECTION IMPROVED */
    .hero {
      position: relative;
      padding: 100px 0;
      background: linear-gradient(135deg, rgba(0,150,136,0.1) 0%, rgba(255,152,0,0.1) 100%), 
                  url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f8f9fa"/><path d="M0 0L100 100" stroke="%23e9ecef" stroke-width="1"/><path d="M100 0L0 100" stroke="%23e9ecef" stroke-width="1"/></svg>');
      background-size: cover;
      overflow: hidden;
    }

    .hero:before {
      content: '';
      position: absolute;
      top: -100px;
      right: -100px;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(0,150,136,0.2) 0%, rgba(255,152,0,0.2) 100%);
      z-index: 0;
    }

    .hero:after {
      content: '';
      position: absolute;
      bottom: -100px;
      left: -100px;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(255,152,0,0.1) 0%, rgba(0,150,136,0.1) 100%);
      z-index: 0;
    }

    .hero-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 40px;
      position: relative;
      z-index: 2;
    }

    .hero-content {
      flex: 1;
      padding-top: 30px;
    }

    .hero-image-container {
      flex: 1;
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .hero-image {
      width: 100%;
      border-radius: 20px;
      box-shadow: var(--shadow-hover);
      transition: all 0.5s ease;
      transform: perspective(1000px) rotateY(-5deg);
    }

    .hero-image:hover {
      transform: perspective(1000px) rotateY(0deg);
    }

    .hero h1 {
      font-size: 3.2rem;
      font-weight: 800;
      margin-bottom: 20px;
      color: var(--primary);
      line-height: 1.2;
    }

    .hero h1 span {
      color: var(--secondary);
      display: block;
    }

    .hero p {
      font-size: 1.3rem;
      margin-bottom: 35px;
      color: var(--text-light);
      max-width: 90%;
      font-weight: 500;
    }

    .hero-cta-container {
      display: flex;
      gap: 20px;
      margin-top: 20px;
    }

    .hero-cta {
      display: inline-flex;
      align-items: center;
      padding: 16px 35px;
      border-radius: 50px;
      background: var(--secondary);
      color: var(--white);
      font-weight: 700;
      font-size: 1.1rem;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
    }

    .hero-cta:hover {
      background: var(--secondary-dark);
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 152, 0, 0.3);
    }

    .hero-cta.demo {
      background: var(--primary);
    }

    .hero-cta.demo:hover {
      background: var(--primary-dark);
      box-shadow: 0 10px 20px rgba(0, 150, 136, 0.3);
    }

    .hero-cta i {
      margin-left: 10px;
      font-size: 1.2rem;
    }

    /* ÉTIQUETTES AMÉLIORÉES */
    .tag {
      position: absolute;
      background: var(--white);
      padding: 15px 25px;
      border-radius: 50px;
      box-shadow: var(--shadow-hover);
      display: flex;
      align-items: center;
      font-weight: 700;
      z-index: 20;
      color: var(--text);
      transition: all 0.4s ease;
      animation: float 3s ease-in-out infinite;
    }

    .tag:hover {
      transform: scale(1.1) translateY(-5px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
    }

    .tag-1 {
      top: 10%;
      left: -5%;
      background: var(--primary);
      color: var(--white);
      animation-delay: 0.5s;
    }

    .tag-2 {
      top: 30%;
      right: -5%;
      background: var(--secondary);
      color: var(--white);
      animation-delay: 1s;
    }

    .tag-3 {
      bottom: 30%;
      left: -8%;
      background: var(--accent);
      color: var(--white);
      animation-delay: 1.5s;
    }

    .tag-4 {
      bottom: 10%;
      right: -5%;
      background: #673AB7;
      color: var(--white);
      animation-delay: 2s;
    }

    .tag-icon {
      margin-right: 10px;
      font-size: 1.4rem;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }

    /* SERVICES SECTION */
    .services {
      padding: 100px 0;
      background: var(--white);
    }

    .section-title {
      text-align: center;
      font-size: 2.8rem;
      color: var(--primary);
      margin-bottom: 60px;
      position: relative;
    }

    .section-title:after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--secondary);
      border-radius: 2px;
    }

    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }

    .service {
      background: var(--light);
      padding: 35px 30px;
      border-radius: 15px;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      text-align: center;
      border-top: 5px solid var(--primary);
      position: relative;
      overflow: hidden;
    }

    .service:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      transform: translateX(-100%);
      transition: transform 0.5s ease;
    }

    .service:hover:before {
      transform: translateX(0);
    }

    .service:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-hover);
    }

    .service-icon {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }

    .service:hover .service-icon {
      transform: scale(1.1);
    }

    .service h3 {
      color: var(--primary-dark);
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .service p {
      margin-bottom: 20px;
      color: var(--text-light);
    }

    .service a {
      color: var(--secondary);
      font-weight: 600;
      display: inline-flex;
      align-items: center;
    }

    .service a i {
      margin-left: 5px;
      transition: transform 0.3s ease;
    }

    .service a:hover i {
      transform: translateX(5px);
    }

    /* ABOUT SECTION */
    .about {
      padding: 100px 0;
      background: linear-gradient(135deg, rgba(0,150,136,0.05) 0%, rgba(255,152,0,0.05) 100%);
    }

    .about-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }

    .about-box {
      background: var(--white);
      padding: 35px 30px;
      border-radius: 15px;
      box-shadow: var(--shadow);
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .about-box:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, var(--primary), var(--secondary));
      transition: width 0.3s ease;
    }

    .about-box:hover:before {
      width: 100%;
      opacity: 0.1;
    }

    .about-box:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .about-icon {
      font-size: 2.5rem;
      color: var(--secondary);
      margin-bottom: 20px;
      position: relative;
      z-index: 2;
    }

    .about-box h3 {
      color: var(--primary);
      font-size: 1.5rem;
      margin-bottom: 15px;
      position: relative;
      z-index: 2;
    }

    .about-box p {
      color: var(--text-light);
      position: relative;
      z-index: 2;
    }

    /* CTA SECTION */
    .cta {
      background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
      color: var(--white);
      text-align: center;
      padding: 100px 0;
      position: relative;
      overflow: hidden;
    }

    .cta:before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      transform: rotate(30deg);
    }

    .cta h2 {
      font-size: 2.5rem;
      margin-bottom: 30px;
      position: relative;
      z-index: 2;
    }

    .cta p {
      font-size: 1.2rem;
      margin-bottom: 40px;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
      position: relative;
      z-index: 2;
    }

    .cta a {
      display: inline-flex;
      align-items: center;
      padding: 18px 40px;
      border-radius: 50px;
      background: var(--white);
      color: var(--primary);
      font-weight: 700;
      font-size: 1.2rem;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    }

    .cta a:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .cta a i {
      margin-left: 10px;
    }

    /* FOOTER */
    footer {
      background: var(--primary-dark);
      color: var(--white);
      padding: 70px 0 30px;
    }

    footer .container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
    }

    footer h3 {
      margin-bottom: 20px;
      font-size: 1.5rem;
      position: relative;
      padding-bottom: 10px;
    }

    footer h3:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background: var(--secondary);
    }

    footer ul {
      list-style: none;
    }

    footer ul li {
      margin-bottom: 12px;
    }

    footer ul li a {
      color: rgba(255, 255, 255, 0.8);
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    footer ul li a i {
      margin-right: 10px;
    }

    footer ul li a:hover {
      color: var(--white);
      transform: translateX(5px);
    }

    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }

    .social-links a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
      transition: all 0.3s ease;
    }

    .social-links a:hover {
      background: var(--secondary);
      transform: translateY(-3px);
    }

    footer .copy {
      text-align: center;
      margin-top: 50px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.7);
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .hero-container {
        flex-direction: column;
        text-align: center;
      }
      
      .hero p {
        max-width: 100%;
      }
      
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .tag {
        display: none;
      }
      
      nav {
        display: none;
      }
      
      .hero-cta-container {
        justify-content: center;
      }
    }

    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2rem;
      }
      
      .hero p {
        font-size: 1.1rem;
      }
      
      .section-title {
        font-size: 2.2rem;
      }
      
      .hero-cta-container {
        flex-direction: column;
        align-items: center;
      }
      
      .hero-cta {
        width: 100%;
        text-align: center;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header>
    <div class="container">
      <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i>ISEP Thiès</a>
      <nav>
        <a href="index.php">Accueil</a>
        <a href="services.php">Nos services</a>
        <a href="about.php">À propos</a>
        <a href="contact.php">Contact</a>
      </nav>
      <div class="buttons">
        <a href="login.php" class="btn-outline">Se connecter</a>
      </div>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="container hero-container">
      <div class="hero-content">
        <h1>Bienvenue sur la <span>Plateforme E-learning</span> de l'ISEP Thiès</h1>
        <p>Découvrez une nouvelle façon d'apprendre : cours en ligne, quiz, ressources pédagogiques et échanges interactifs.</p>
        <div class="hero-cta-container">
          <a href="login.php" class="hero-cta">Commencer maintenant <i class="fas fa-arrow-right"></i></a>
          <a href="demo.php" class="hero-cta demo">Démonstration <i class="fas fa-play-circle"></i></a>
        </div>
      </div>
      
      <div class="hero-image-container">
        <img src="../images/Frame 1 (8).png" alt="Étudiants de l'ISEP Thiès" class="hero-image">
        
        <!-- Étiquettes autour de l'image -->
        <div class="tag tag-1">
          <span class="tag-icon"><i class="fas fa-graduation-cap"></i></span> Excellence Académique
        </div>
        <div class="tag tag-2">
          <span class="tag-icon"><i class="fas fa-laptop-code"></i></span> Innovation Technologique
        </div>
        <div class="tag tag-3">
          <span class="tag-icon"><i class="fas fa-globe-africa"></i></span> Ouverture Internationale
        </div>
        <div class="tag tag-4">
          <span class="tag-icon"><i class="fas fa-users"></i></span> Communauté Étudiante
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services">
    <div class="container">
      <h2 class="section-title">Nos Services</h2>
      <div class="service-grid">
        <!-- Service 1 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-book"></i></div>
          <h3>Cours en ligne</h3>
          <p>Accédez à des cours organisés par filière, disponibles 24h/24 avec vidéos, PDF et exercices pratiques.</p>
          <a href="login.php">En savoir plus <i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Service 2 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-video"></i></div>
          <h3>Cours en visioconférence</h3>
          <p>Participez à des cours en direct avec vos enseignants via un système d'appel vidéo intégré.</p>
          <a href="login.php">Participer <i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Service 3 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-pen-nib"></i></div>
          <h3>Quiz & Devoirs</h3>
          <p>Évaluez vos connaissances grâce à des quiz interactifs et soumettez vos devoirs directement en ligne.</p>
          <a href="login.php">Découvrir <i class="fas fa-arrow-right"></i></a>
        </div>

        

        <!-- Service 5 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-comments"></i></div>
          <h3>Messagerie</h3>
          <p>Discutez avec vos enseignants et camarades via une messagerie sécurisée intégrée à la plateforme.</p>
          <a href="login.php">Accéder <i class="fas fa-arrow-right"></i></a>
        </div>

       

        <!-- Service 7 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-bell"></i></div>
          <h3>Notifications & Alertes</h3>
          <p>Recevez des alertes en temps réel pour les cours, devoirs et messages importants afin de ne rien manquer.</p>
          <a href="login.php">Configurer <i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Service 8 -->
        <div class="service">
          <div class="service-icon"><i class="fas fa-chart-pie"></i></div>
          <h3>Statistiques & Rapports</h3>
          <p>Visualisez vos performances et accédez à des rapports détaillés pour mieux gérer votre apprentissage.</p>
          <a href="login.php">Voir les stats <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- A PROPOS -->
  <section class="about">
    <div class="container">
      <h2 class="section-title">À propos de nous</h2>
      <p class="about-description">
        La plateforme E-learning de l'ISEP Thiès a pour mission d'offrir une éducation moderne et accessible à tous.
        Elle accompagne les étudiants des filières Développement Web, Réseaux Télécom et Administration Système dans leur apprentissage.
        Notre vision est de transformer l'éducation en un espace d'innovation, de collaboration et de réussite académique.
      </p>
      <div class="about-grid">
        <div class="about-box">
          <div class="about-icon"><i class="fas fa-bullseye"></i></div>
          <h3>Notre Mission</h3>
          <p>Accompagner chaque étudiant dans son parcours académique avec des outils pédagogiques innovants.</p>
        </div>
        <div class="about-box">
          <div class="about-icon"><i class="fas fa-eye"></i></div>
          <h3>Notre Vision</h3>
          <p>Devenir la référence en matière de formation digitale et professionnelle au Sénégal.</p>
        </div>
        <div class="about-box">
          <div class="about-icon"><i class="fas fa-hand-holding-heart"></i></div>
          <h3>Nos Valeurs</h3>
          <p>Innovation, Accessibilité, Excellence académique et Collaboration entre enseignants et étudiants.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta">
    <div class="container">
      <h2>Rejoignez dès aujourd'hui la plateforme ISEP Thiès</h2>
      <p>Inscrivez-vous gratuitement et accédez à des milliers de ressources pédagogiques adaptées à votre parcours.</p>
      <a href="login.php">Connecter maintenant <i class="fas fa-arrow-right"></i></a>
    </div>
  </section> <br>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-column">
        <h3>ISEP Thiès</h3>
        <p>Votre partenaire pour un apprentissage moderne et réussi.</p>
        <div class="social-links">
          <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <div class="footer-column">
        <h3>Liens rapides</h3>
        <ul>
          <li><a href="index.php"><i class="fas fa-chevron-right"></i> Accueil</a></li>
          <li><a href="services.php"><i class="fas fa-chevron-right"></i> Nos services</a></li>
          <li><a href="about.php"><i class="fas fa-chevron-right"></i> À propos</a></li>
          <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Contact</h3>
        <p><i class="fas fa-envelope"></i> Email : isep@isep-thies.edu.sn</p>
        <p><i class="fas fa-phone"></i> Tél : +221 33 951 24 25</p>
      </div>
    </div>

    <div class="copy">
      &copy; <?php echo date("Y"); ?> ISEP Thiès - Tous droits réservés
    </div>
  </footer>
</body>
</html>
  <style>
    