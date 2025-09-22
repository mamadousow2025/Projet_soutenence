<?php
// Inclure le header
include(__DIR__ . '/../includes/header.php');
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
    <section class="services" style="position: relative; padding: 100px 20px; text-align: center; color: #fff; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, #009688, #FF9800);">
  
  <!-- Icône professionnelle en arrière-plan -->
  <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 250px; color: rgba(255,255,255,0.08); z-index: 0;">
    <i class="fas fa-graduation-cap"></i>
  </div>
  
  <!-- Contenu de la section -->
  <div style="position: relative; z-index: 1; max-width: 900px; margin: 0 auto;">
    <p style="font-size: 20px; line-height: 1.8;">
      Découvrez nos services conçus pour maximiser votre apprentissage et booster votre réussite. Cette plateforme appartient aux apprenants de l’ISEP Abdoulaye Ly de Thiès et leur offre des outils interactifs, des cours en ligne de qualité, des quiz et devoirs, ainsi qu’un suivi personnalisé pour atteindre vos objectifs académiques et exceller dans votre parcours universitaire.
    </p>
  </div>

</section>


  <h2>Nos Services</h2>
  <div class="service-grid">

    <!-- Service 1 -->
    <div class="service">
      <div class="icon"><i class="fas fa-book"></i></div>
      <h3>Cours en ligne</h3>
      <p>Accédez à des cours organisés par filière, disponibles 24h/24 avec vidéos, PDF et exercices pratiques.</p>
      <a href="login.php">En savoir plus</a>
    </div>

    <!-- Service 2 -->
    <div class="service">
      <div class="icon"><i class="fas fa-video"></i></div>
      <h3>Cours en visioconférence</h3>
      <p>Participez à des cours en direct avec vos enseignants via un système d’appel vidéo intégré.</p>
      <a href="login.php">Participer</a>
    </div>

    <!-- Service 3 -->
    <div class="service">
      <div class="icon"><i class="fas fa-pen-nib"></i></div>
      <h3>Quiz & Devoirs</h3>
      <p>Évaluez vos connaissances grâce à des quiz interactifs et soumettez vos devoirs directement en ligne.</p>
      <a href="login.php">Découvrir</a>
    </div>

    <!-- Service 4 -->
    <div class="service">
      <div class="icon"><i class="fas fa-chart-line"></i></div>
      <h3>Suivi de progression</h3>
      <p>Suivez vos progrès académiques avec un tableau de bord détaillé : notes, statistiques et avancement de vos cours.</p>
      <a href="login.php">Voir mon suivi</a>
    </div>

    <!-- Service 5 -->
    <div class="service">
      <div class="icon"><i class="fas fa-comments"></i></div>
      <h3>Messagerie</h3>
      <p>Discutez avec vos enseignants et camarades via une messagerie sécurisée intégrée à la plateforme.</p>
      <a href="login.php">Accéder</a>
    </div>

    <!-- Service 6 -->
    <div class="service">
      <div class="icon"><i class="fas fa-certificate"></i></div>
      <h3>
         Pratique intensive</h3>
      <p>Apprenez par la pratique grâce à des projets concrets et modernes.</p>
      <a href="login.php">Voir plus</a>
    </div>

    <!-- Service 7 -->
    <div class="service">
      <div class="icon"><i class="fas fa-bell"></i></div>
      <h3>Notifications & Alertes</h3>
      <p>Recevez des alertes en temps réel pour les cours, devoirs et messages importants afin de ne rien manquer.</p>
      <a href="loggin.php">Configurer</a>
    </div>

    <!-- Service 8 -->
    <div class="service">
      <div class="icon"><i class="fas fa-chart-pie"></i></div>
      <h3>Statistiques & Rapports</h3>
      <p>Visualisez vos performances et accédez à des rapports détaillés pour mieux gérer votre apprentissage.</p>
      <a href="login.php">Voir les stats</a>
    </div>

  </div>
</section>


<!-- AVANTAGES EXCLUSIFS -->
<section class="services-advantages" style="max-width: 1200px; margin: 80px auto; padding: 0 20px; text-align: center;">
  <h2 style="color:#009688; font-size:40px; margin-bottom:40px;">Avantages exclusifs pour les apprenants</h2>
  <p style="font-size:18px; color:#555; max-width:900px; margin:0 auto 50px auto; line-height:1.7;">
    En rejoignant notre plateforme, les apprenants de l’ISEP Abdoulaye Ly de Thiès bénéficient de fonctionnalités uniques qui facilitent l’apprentissage, favorisent la réussite académique et rendent l’expérience interactive et motivante.
  </p>

  <div class="advantages-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:40px;">

    <div class="advantage-card" style="background:#fff; border-radius:20px; padding:30px; box-shadow:0 8px 25px rgba(0,0,0,0.15); transition:0.5s; position:relative;">
      <div style="font-size:50px; color:#FF9800; margin-bottom:20px;"><i class="fas fa-graduation-cap"></i></div>
      <h3 style="color:#009688; font-size:24px; margin-bottom:15px;">Formation complète</h3>
      <p style="color:#555; font-size:16px;">Accédez à des parcours complets couvrant toutes les filières, avec des ressources pédagogiques interactives et actualisées.</p>
    </div>

    <div class="advantage-card" style="background:#fff; border-radius:20px; padding:30px; box-shadow:0 8px 25px rgba(0,0,0,0.15); transition:0.5s; position:relative;">
      <div style="font-size:50px; color:#FF9800; margin-bottom:20px;"><i class="fas fa-user-friends"></i></div>
      <h3 style="color:#009688; font-size:24px; margin-bottom:15px;">Collaboration et échanges</h3>
      <p style="color:#555; font-size:16px;">Communiquez facilement avec vos camarades et enseignants grâce à notre messagerie intégrée et nos forums interactifs.</p>
    </div>

   

    <div class="advantage-card" style="background:#fff; border-radius:20px; padding:30px; box-shadow:0 8px 25px rgba(0,0,0,0.15); transition:0.5s; position:relative;">
      <div style="font-size:50px; color:#FF9800; margin-bottom:20px;"><i class="fas fa-chart-line"></i></div>
      <h3 style="color:#009688; font-size:24px; margin-bottom:15px;">Suivi personnalisé</h3>
      <p style="color:#555; font-size:16px;">Suivez votre progression avec des tableaux de bord détaillés, statistiques et rapports pour mieux gérer votre apprentissage.</p>
    </div>

  </div>
</section>

<style>
.advantage-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}
</style>

<!-- REJOIGNEZ-NOUS -->
<section class="join-us" style="background: linear-gradient(135deg, #009688, #FF9800); color: #fff; padding: 100px 20px; text-align: center; border-radius: 20px; margin: 80px 0;">
  <h2 style="font-size: 48px; margin-bottom: 30px;">Rejoignez-nous vite !</h2>
  <p style="font-size: 20px; max-width: 900px; margin: 0 auto 40px auto; line-height: 1.8;">
    "Votre avenir commence aujourd'hui !" <br>
    "Apprenez, progressez, excellez et devenez la meilleure version de vous-même." <br>
    "Chaque jour est une opportunité d'apprendre quelque chose de nouveau."
  </p>
  <a href="login.php" style="display: inline-block; padding: 18px 40px; font-size: 20px; font-weight: bold; border-radius: 50px; background-color: #fff; color: #009688; text-decoration: none; transition: 0.3s;">
    Commencer
  </a>
</section>

<style>
.join-us a:hover {
  background-color: #FF9800;
  color: #fff;
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  transition: 0.4s;
}
@media(max-width:768px){
  .join-us h2 {
    font-size: 36px;
  }
  .join-us p {
    font-size: 18px;
  }
  .join-us a {
    font-size: 18px;
    padding: 15px 30px;
  }
}
</style>


</body>
</html>

<?php
// Inclure le footer
include(__DIR__ . '/../includes/footer.php');
?>
