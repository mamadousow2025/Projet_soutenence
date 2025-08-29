<?php
// Inclure le header
include(__DIR__ . '/../includes/header.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>À propos de nous - ISEP Abdoulaye Ly</title>
<!-- Font Awesome pour icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    color: #333;
  }
  /* SECTION INTRO */
  .about-intro {
    position: relative;
    background: linear-gradient(135deg, #009688, #FF9800);
    color: #fff;
    padding: 120px 20px 80px 20px;
    text-align: center;
    overflow: hidden;
  }
  .about-intro h1 {
    font-size: 48px;
    margin-bottom: 20px;
  }
  .about-intro p {
    font-size: 20px;
    max-width: 900px;
    margin: 0 auto;
    line-height: 1.8;
  }
  .about-intro .icon-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 200px;
    color: rgba(255,255,255,0.08);
    z-index: 0;
  }
  .about-intro .content {
    position: relative;
    z-index: 1;
  }

  /* SECTION FONDATEURS */
  .founders {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
    text-align: center;
  }
  .founders h2 {
    font-size: 40px;
    color: #009688;
    margin-bottom: 50px;
  }
  .founders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
  }
  .founder {
    background: #fff;
    border-radius: 20px;
    padding: 30px 20px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
  }
  .founder:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
  }
  .founder img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin-bottom: 20px;
  }
  .founder h3 {
    font-size: 22px;
    color: #009688;
    margin-bottom: 10px;
  }
  .founder p {
    color: #555;
    font-size: 16px;
  }

  /* SECTION HISTOIRE / TIMELINE */
  .timeline {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
    position: relative;
  }
  .timeline h2 {
    font-size: 40px;
    color: #FF9800;
    text-align: center;
    margin-bottom: 50px;
  }
  .timeline-item {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    position: relative;
    transition: transform 0.3s;
  }
  .timeline-item::before {
    content: '';
    position: absolute;
    left: -15px;
    top: 30px;
    width: 10px;
    height: 10px;
    background: #009688;
    border-radius: 50%;
  }
  .timeline-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
  }
  .timeline-item h3 {
    font-size: 24px;
    color: #009688;
    margin-bottom: 10px;
  }
  .timeline-item p {
    color: #555;
    font-size: 16px;
  }

  /* SECTION VALEURS */
  .values {
    background: #009688;
    color: #fff;
    padding: 80px 20px;
    text-align: center;
    border-radius: 20px;
    margin: 80px 0;
  }
  .values h2 {
    font-size: 40px;
    margin-bottom: 50px;
  }
  .values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
    gap: 40px;
  }
  .value-card {
    background: rgba(255,255,255,0.1);
    padding: 30px;
    border-radius: 20px;
    transition: transform 0.3s;
  }
  .value-card:hover {
    transform: translateY(-10px);
  }
  .value-card i {
    font-size: 50px;
    color: #FF9800;
    margin-bottom: 20px;
  }
  .value-card h3 {
    font-size: 22px;
    margin-bottom: 15px;
  }
  .value-card p {
    font-size: 16px;
  }

  /* SECTION POURQUOI NOUS CHOISIR */
  .why-us {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
    text-align: center;
  }
  .why-us h2 {
    font-size: 40px;
    color: #009688;
    margin-bottom: 50px;
  }
  .why-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
    gap: 40px;
  }
  .why-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transition: transform 0.3s;
  }
  .why-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
  }
  .why-card i {
    font-size: 50px;
    color: #FF9800;
    margin-bottom: 20px;
  }
  .why-card h3 {
    font-size: 22px;
    margin-bottom: 15px;
  }
  .why-card p {
    font-size: 16px;
  }

  /* APPEL À L'ACTION FINAL */
  .join-us {
    background: linear-gradient(135deg, #009688, #FF9800);
    color: #fff;
    text-align: center;
    padding: 100px 20px;
    border-radius: 20px;
    margin: 80px 0;
  }
  .join-us h2 {
    font-size: 48px;
    margin-bottom: 30px;
  }
  .join-us p {
    font-size: 20px;
    max-width: 900px;
    margin: 0 auto 40px auto;
    line-height: 1.8;
  }
  .join-us a {
    display: inline-block;
    padding: 18px 40px;
    font-size: 20px;
    font-weight: bold;
    border-radius: 50px;
    background-color: #fff;
    color: #009688;
    text-decoration: none;
    transition: 0.3s;
  }
  .join-us a:hover {
    background-color: #FF9800;
    color: #fff;
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    transition: 0.4s;
  }

  /* MEDIA QUERIES */
  @media(max-width:768px){
    .about-intro h1 { font-size:36px; }
    .values h2, .why-us h2 { font-size:32px; }
    .join-us h2 { font-size:36px; }
  }
</style>
</head>
<body>

<!-- SECTION INTRODUCTION -->
<section class="about-intro">
  <div class="icon-bg"><i class="fas fa-university"></i></div>
  <div class="content">
    <h1>À propos de nous</h1>
    <p>
      Bienvenue sur la plateforme des apprenants de l’ISEP Abdoulaye Ly de Thiès ! Notre objectif est de fournir des outils interactifs, des cours en ligne de qualité, des quiz et un suivi personnalisé pour aider chaque étudiant à exceller dans son parcours universitaire.
    </p>
  </div>
</section>

<!-- FONDATEURS / APPRENANTS -->
<section class="founders">
  <h2>Nos apprenants & fondateurs</h2>
  <div class="founders-grid">
    <div class="founder">
      <img src="assets/images/fondateur1.jpg" alt="Fondateur 1">
      <h3>El Hadji M. Sow</h3>
      <p>Responsable du projet et coordonnateur principal, passionné par l’enseignement et le développement pédagogique.</p>
    </div>
    <div class="founder">
      <img src="assets/images/fondateur2.jpg" alt="Fondateur 2">
      <h3>Serigne B. Camara</h3>
      <p>Expert en technologies éducatives et mentor des étudiants, garantissant l’interactivité et la qualité des contenus.</p>
    </div>
  </div>
</section>

<!-- TIMELINE / HISTOIRE -->
<section class="timeline" style="max-width: 1200px; margin: 80px auto; padding: 0 20px; position: relative;">
  <h2 style="text-align:center; font-size:40px; color:#009688; margin-bottom:60px;">Notre histoire</h2>

  <div class="timeline-line" style="position:absolute; left:50%; top:0; transform:translateX(-50%); width:4px; height:100%; background:#FF9800;"></div>

  <!-- Timeline Item -->
  <div class="timeline-item left" style="position:relative; width:50%; padding:30px 40px; box-sizing:border-box;">
    <div style="position:absolute; top:20px; right:-28px; background:#009688; color:#fff; border-radius:50%; width:50px; height:50px; display:flex; align-items:center; justify-content:center; font-size:24px;">
      <i class="fas fa-rocket"></i>
    </div>
    <h3 style="color:#009688; font-size:24px; margin-bottom:10px;">2025</h3>
    <p style="color:#555; font-size:16px; line-height:1.7;">Début du projet par les apprenants de l’ISEP Abdoulaye Ly de Thiès pour moderniser l’apprentissage en ligne et offrir des outils interactifs aux étudiants.</p>
  </div>

  <div class="timeline-item right" style="position:relative; width:50%; left:50%; padding:30px 40px; box-sizing:border-box;">
    <div style="position:absolute; top:20px; left:-28px; background:#FF9800; color:#fff; border-radius:50%; width:50px; height:50px; display:flex; align-items:center; justify-content:center; font-size:24px;">
      <i class="fas fa-book-open"></i>
    </div>
    <h3 style="color:#FF9800; font-size:24px; margin-bottom:10px;">2026</h3>
    <p style="color:#555; font-size:16px; line-height:1.7;">Ajout de nouvelles fonctionnalités : quiz interactifs, suivi personnalisé, messagerie intégrée et tableau de bord complet.</p>
  </div>

  <div class="timeline-item left" style="position:relative; width:50%; padding:30px 40px; box-sizing:border-box;">
    <div style="position:absolute; top:20px; right:-28px; background:#009688; color:#fff; border-radius:50%; width:50px; height:50px; display:flex; align-items:center; justify-content:center; font-size:24px;">
      <i class="fas fa-chart-line"></i>
    </div>
    <h3 style="color:#009688; font-size:24px; margin-bottom:10px;">2027</h3>
    <p style="color:#555; font-size:16px; line-height:1.7;">Optimisation de la plateforme avec contenus enrichis, certifications numériques et meilleure expérience utilisateur.</p>
  </div>

  <div class="timeline-item right" style="position:relative; width:50%; left:50%; padding:30px 40px; box-sizing:border-box;">
    <div style="position:absolute; top:20px; left:-28px; background:#FF9800; color:#fff; border-radius:50%; width:50px; height:50px; display:flex; align-items:center; justify-content:center; font-size:24px;">
      <i class="fas fa-globe"></i>
    </div>
    <h3 style="color:#FF9800; font-size:24px; margin-bottom:10px;">2028 et au-delà</h3>
    <p style="color:#555; font-size:16px; line-height:1.7;">Expansion continue, nouvelles fonctionnalités innovantes et suivi des besoins des étudiants pour un apprentissage toujours plus efficace.</p>
  </div>
</section>

<style>
/* Timeline Responsif et animation */
.timeline-item {
  opacity: 0;
  transform: translateY(50px);
  transition: all 0.8s ease-in-out;
}
.timeline-item.show {
  opacity: 1;
  transform: translateY(0);
}

@media(max-width:768px){
  .timeline-item, .timeline-item.left, .timeline-item.right {
    width: 100% !important;
    left: 0 !important;
    padding-left: 60px !important;
    padding-right: 20px !important;
  }
  .timeline-line {
    left:20px !important;
  }
  .timeline-item div {
    left: -28px !important;
    right: auto !important;
  }
}
</style>

<script>
// Animation au scroll
const items = document.querySelectorAll('.timeline-item');
function showItems() {
  const triggerBottom = window.innerHeight / 1.2;
  items.forEach(item => {
    const itemTop = item.getBoundingClientRect().top;
    if(itemTop < triggerBottom) {
      item.classList.add('show');
    }
  });
}
window.addEventListener('scroll', showItems);
showItems();
</script>


<!-- POURQUOI NOUS CHOISIR -->
<section class="why-us">
  <h2>Pourquoi nous choisir</h2>
  <div class="why-grid">
    <div class="why-card">
      <i class="fas fa-book"></i>
      <h3>Contenu complet</h3>
      <p>Des cours détaillés et des ressources pédagogiques interactives pour chaque filière.</p>
    </div>
    <div class="why-card">
      <i class="fas fa-clock"></i>
      <h3>Accessibilité 24/7</h3>
      <p>Apprenez à votre rythme, n’importe quand et n’importe où.</p>
    </div>
    <div class="why-card">
      <i class="fas fa-chart-line"></i>
      <h3>Suivi personnalisé</h3>
      <p>Visualisez votre progression avec des tableaux de bord et rapports détaillés.</p>
    </div>
    <div class="why-card">
      <i class="fas fa-certificate"></i>
      <h3>Certifications</h3>
      <p>Recevez des certificats numériques officiels pour valoriser vos compétences.</p>
    </div>
  </div>
</section>

<!-- APPEL À L'ACTION -->
<section class="join-us">
  <h2>Rejoignez-nous dès aujourd’hui !</h2>
  <p>Commencez votre parcours académique avec la meilleure plateforme pour les apprenants de l’ISEP Abdoulaye Ly de Thiès. Apprenez, progressez et excellez !</p>
  <a href="register.php">Commencer</a>
</section>

</body>
</html>


<?php
// Inclure le footer
include(__DIR__ . '/../includes/footer.php');
?>