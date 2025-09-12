<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plateforme ISEP Thiès</title>
  <style>
    /* RESET */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      background: #f9f9f9;
      color: #333;
      line-height: 1.6;
    }

    a {
      text-decoration: none;
      transition: 0.3s;
    }

    /* HEADER */
    header {
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    header .container {
      max-width: 1200px;
      margin: auto;
      padding: 15px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    header a.logo {
      font-size: 26px;
      font-weight: bold;
      color: #009688;
    }

    nav {
      display: flex;
      gap: 20px;
    }

    nav a {
      font-weight: 500;
      color: #333;
    }

    nav a:hover {
      color: #FF9800;
    }

    .buttons a {
      padding: 10px 18px;
      border-radius: 6px;
      font-weight: bold;
    }

    .btn-primary {
      background: #009688;
      color: #fff;
    }

    .btn-primary:hover {
      background: #00796B;
    }

    .btn-outline {
      border: 2px solid #009688;
      color: #009688;
    }

    .btn-outline:hover {
      background: #009688;
      color: #fff;
    }

    /* HERO */
    .hero {
      background: linear-gradient(90deg, #009688, #FF9800);
      color: #fff;
      text-align: center;
      padding: 100px 20px;
    }

    .hero h1 {
      font-size: 52px;
      font-weight: 800;
      margin-bottom: 20px;
    }

    .hero h1 span {
      color: #ffeb3b;
    }

    .hero p {
      font-size: 20px;
      margin-bottom: 30px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
    }

    .hero a {
      display: inline-block;
      padding: 15px 30px;
      border-radius: 8px;
      background: #fff;
      color: #009688;
      font-weight: bold;
    }

    .hero a:hover {
      background: #FF9800;
      color: #fff;
    }

    /* SERVICES */
    .services {
      max-width: 1200px;
      margin: auto;
      padding: 80px 20px;
    }

    .services h2 {
      text-align: center;
      font-size: 38px;
      color: #009688;
      margin-bottom: 50px;
    }

    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
    }

    .service {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: 0.3s;
    }

    .service:hover {
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .service h3 {
      color: #FF9800;
      font-size: 22px;
      margin-bottom: 15px;
    }

    .service p {
      margin-bottom: 15px;
      color: #666;
    }

    .service a {
      color: #009688;
      font-weight: bold;
    }

    .service a:hover {
      text-decoration: underline;
    }

    /* A PROPOS */
    .about {
      background: #f2f2f2;
      padding: 80px 20px;
      text-align: center;
    }

    .about h2 {
      font-size: 38px;
      color: #009688;
      margin-bottom: 30px;
    }

    .about p {
      max-width: 900px;
      margin: auto;
      margin-bottom: 40px;
      font-size: 18px;
      color: #555;
    }

    .about-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .about-box {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .about-box h3 {
      color: #FF9800;
      font-size: 20px;
      margin-bottom: 10px;
    }

    .about-box p {
      color: #666;
    }

    /* CTA */
    .cta {
      background: linear-gradient(90deg, #009688, #FF9800);
      color: #fff;
      text-align: center;
      padding: 80px 20px;
    }

    .cta h2 {
      font-size: 34px;
      margin-bottom: 20px;
    }

    .cta a {
      display: inline-block;
      padding: 15px 30px;
      border-radius: 8px;
      background: #fff;
      color: #009688;
      font-weight: bold;
    }

    .cta a:hover {
      background: #FF9800;
      color: #fff;
    }

    /* FOOTER */
    footer {
      background: #009688;
      color: #fff;
      padding: 50px 20px;
    }

    footer .container {
      max-width: 1200px;
      margin: auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
    }

    footer h3 {
      margin-bottom: 15px;
      font-size: 20px;
    }

    footer ul {
      list-style: none;
    }

    footer ul li {
      margin-bottom: 10px;
    }

    footer ul li a {
      color: #fff;
    }

    footer ul li a:hover {
      text-decoration: underline;
    }

    footer .copy {
      text-align: center;
      margin-top: 30px;
      font-size: 14px;
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header>
    <div class="container">
      <a href="index.php" class="logo">ISEP Thiès</a>
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

  <!-- HERO -->
  <section class="hero">
    <h1>Bienvenue sur la <span>Plateforme E-learning</span> de l’ISEP Thiès</h1>
    <p>Découvrez une nouvelle façon d’apprendre : cours en ligne, quiz, ressources pédagogiques et échanges interactifs.</p>
    <a href="login.php">Commencer maintenant</a>
  </section>

<?php
// index.php

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plateforme ISEP Thiès</title>
<!-- Font Awesome CDN pour icônes professionnelles -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  .services {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
    text-align: center;
  }

  .services h2 {
    font-size: 40px;
    color: #009688;
    margin-bottom: 50px;
  }

  .service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
  }

  .service {
    background: #fff;
    border-radius: 15px;
    padding: 30px 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
  }

  .service:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }

  .service .icon {
    font-size: 50px;
    margin-bottom: 20px;
    color: #FF9800;
  }

  .service h3 {
    font-size: 22px;
    color: #009688;
    margin-bottom: 15px;
  }

  .service p {
    color: #555;
    font-size: 16px;
    margin-bottom: 20px;
  }

  .service a {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    background: #009688;
    color: #fff;
    font-weight: bold;
    transition: 0.3s;
    text-decoration: none;
  }

  .service a:hover {
    background: #FF9800;
    color: #fff;
  }
</style>
</head>
<body>

<!-- SERVICES -->
<section class="services">
  <h2>Nos Services</h2>
  <div class="service-grid">

    <!-- Service 1 -->
    <div class="service">
      <div class="icon"><i class="fas fa-book"></i></div>
      <h3>Cours en ligne</h3>
      <p>Accédez à des cours organisés par filière, disponibles 24h/24 avec vidéos, PDF et exercices pratiques.</p>
      <a href="#">En savoir plus</a>
    </div>

    <!-- Service 2 -->
    <div class="service">
      <div class="icon"><i class="fas fa-video"></i></div>
      <h3>Cours en visioconférence</h3>
      <p>Participez à des cours en direct avec vos enseignants via un système d’appel vidéo intégré.</p>
      <a href="#">Participer</a>
    </div>

    <!-- Service 3 -->
    <div class="service">
      <div class="icon"><i class="fas fa-pen-nib"></i></div>
      <h3>Quiz & Devoirs</h3>
      <p>Évaluez vos connaissances grâce à des quiz interactifs et soumettez vos devoirs directement en ligne.</p>
      <a href="#">Découvrir</a>
    </div>

    <!-- Service 4 -->
    <div class="service">
      <div class="icon"><i class="fas fa-chart-line"></i></div>
      <h3>Suivi de progression</h3>
      <p>Suivez vos progrès académiques avec un tableau de bord détaillé : notes, statistiques et avancement de vos cours.</p>
      <a href="#">Voir mon suivi</a>
    </div>

    <!-- Service 5 -->
    <div class="service">
      <div class="icon"><i class="fas fa-comments"></i></div>
      <h3>Messagerie</h3>
      <p>Discutez avec vos enseignants et camarades via une messagerie sécurisée intégrée à la plateforme.</p>
      <a href="#">Accéder</a>
    </div>

    <!-- Service 6 -->
    <div class="service">
      <div class="icon"><i class="fas fa-certificate"></i></div>
      <h3>Certifications</h3>
      <p>Obtenez des certificats numériques reconnus après avoir validé vos parcours d’apprentissage.</p>
      <a href="#">Voir plus</a>
    </div>

    <!-- Service 7 -->
    <div class="service">
      <div class="icon"><i class="fas fa-bell"></i></div>
      <h3>Notifications & Alertes</h3>
      <p>Recevez des alertes en temps réel pour les cours, devoirs et messages importants afin de ne rien manquer.</p>
      <a href="#">Configurer</a>
    </div>

    <!-- Service 8 -->
    <div class="service">
      <div class="icon"><i class="fas fa-chart-pie"></i></div>
      <h3>Statistiques & Rapports</h3>
      <p>Visualisez vos performances et accédez à des rapports détaillés pour mieux gérer votre apprentissage.</p>
      <a href="#">Voir les stats</a>
    </div>

  </div>
</section>

</body>
</html>




  <!-- A PROPOS -->
  <section class="about">
    <h2>À propos de nous</h2>
    <p>
      La plateforme E-learning de l’ISEP Thiès a pour mission d’offrir une éducation moderne et accessible à tous.
      Elle accompagne les étudiants des filières Développement Web, Réseaux Télécom et Administration Système dans leur apprentissage.
      Notre vision est de transformer l’éducation en un espace d’innovation, de collaboration et de réussite académique.
    </p>
    <div class="about-grid">
      <div class="about-box">
        <h3>Notre Mission</h3>
        <p>Accompagner chaque étudiant dans son parcours académique avec des outils pédagogiques innovants.</p>
      </div>
      <div class="about-box">
        <h3>Notre Vision</h3>
        <p>Devenir la référence en matière de formation digitale et professionnelle au Sénégal.</p>
      </div>
      <div class="about-box">
        <h3>Nos Valeurs</h3>
        <p>Innovation, Accessibilité, Excellence académique et Collaboration entre enseignants et étudiants.</p>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta">
    <h2>Rejoignez dès aujourd’hui la plateforme ISEP Thiès</h2>
    <p>Inscrivez-vous gratuitement et accédez à des milliers de ressources pédagogiques adaptées à votre parcours.</p>
    <a href="login.php">Connecter maintenant</a>
  </section>

  <!-- FOOTER -->
<footer>
  <div class="footer-container">
    <div class="footer-column">
      <h3>ISEP Thiès</h3>
      <p>Votre partenaire pour un apprentissage moderne et réussi.</p>
      <div class="social-icons">
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

  <div class="footer-copy">
    &copy; <?php echo date("Y"); ?> ISEP Thiès - Tous droits réservés
  </div>

  <style>
    footer {
      background: #009688;
      color: #fff;
      padding: 50px 20px 20px 20px;
      font-family: Arial, sans-serif;
    }

    .footer-container {
      display: flex;
      flex-wrap: wrap;
      max-width: 1200px;
      margin: auto;
      gap: 40px;
      justify-content: space-between;
    }

    .footer-column {
      flex: 1;
      min-width: 250px;
    }

    .footer-column h3 {
      font-size: 20px;
      margin-bottom: 20px;
      color: #FF9800;
    }

    .footer-column p, .footer-column ul li a {
      font-size: 16px;
      color: #fff;
      margin-bottom: 10px;
      text-decoration: none;
    }

    .footer-column ul {
      list-style: none;
      padding: 0;
    }

    .footer-column ul li {
      margin-bottom: 10px;
    }

    .footer-column ul li a i {
      margin-right: 8px;
      color: #FF9800;
    }

    .footer-column p i {
      margin-right: 8px;
      color: #FF9800;
    }

    .social-icons a {
      color: #fff;
      margin-right: 15px;
      font-size: 18px;
      transition: color 0.3s;
    }

    .social-icons a:hover {
      color: #FF9800;
    }

    .footer-copy {
      text-align: center;
      margin-top: 30px;
      font-size: 14px;
      color: #fff;
    }

    @media (max-width: 768px) {
      .footer-container {
        flex-direction: column;
        text-align: center;
        gap: 30px;
      }
      .footer-column ul li a {
        display: inline-block;
      }
    }
  </style>
</footer>
