<?php
// Inclure le header
include(__DIR__ . '/../includes/header.php');
?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact - ISEP Abdoulaye Ly</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  body {
    margin:0;
    padding:0;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color:#f5f5f5;
    color:#333;
  }

  /* SECTION CONTACT */
  .contact-section {
    position: relative;
    background: linear-gradient(135deg, #009688, #FF9800);
    color: #fff;
    padding: 100px 20px 60px 20px;
    border-radius: 20px;
    text-align:center;
  }
  .contact-section h1 {
    font-size:48px;
    margin-bottom:20px;
  }
  .contact-section p {
    font-size:20px;
    max-width:900px;
    margin:0 auto;
    line-height:1.8;
  }
  .contact-section i {
    font-size: 180px;
    color: rgba(255,255,255,0.08);
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    z-index:0;
  }
  .contact-content {
    position:relative;
    z-index:1;
  }

  /* GRILLE FORMULAIRE + INFO */
  .contact-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap:40px;
    max-width:1200px;
    margin: -60px auto 60px auto;
  }

  /* FORMULAIRE */
  .contact-form {
    background:#fff;
    color:#333;
    padding:50px;
    border-radius:20px;
    flex:1 1 400px;
    min-width:300px;
    box-shadow:0 8px 30px rgba(0,0,0,0.1);
    transition:transform 0.3s, box-shadow 0.3s;
  }
  .contact-form:hover {
    transform:translateY(-10px);
    box-shadow:0 12px 35px rgba(0,0,0,0.2);
  }
  .contact-form h2 {
    color:#009688;
    margin-bottom:30px;
    font-size:28px;
    text-align:center;
  }
  .contact-form input,
  .contact-form textarea {
    width:100%;
    padding:15px 20px;
    margin-bottom:20px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:16px;
  }
  .contact-form textarea {
    resize:none;
    height:150px;
  }
  .contact-form button {
    width:100%;
    padding:18px 0;
    border:none;
    border-radius:50px;
    background:#009688;
    color:#fff;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
  }
  .contact-form button:hover {
    background:#FF9800;
    transform:translateY(-5px);
    box-shadow:0 8px 20px rgba(0,0,0,0.3);
    transition:0.4s;
  }

  /* INFO CONTACT */
  .contact-info {
    background:#fff;
    border-radius:20px;
    padding:50px;
    flex:1 1 400px;
    min-width:300px;
    display:flex;
    flex-direction:column;
    gap:25px;
    box-shadow:0 8px 30px rgba(0,0,0,0.1);
    transition:transform 0.3s, box-shadow 0.3s;
  }
  .contact-info:hover {
    transform:translateY(-10px);
    box-shadow:0 12px 35px rgba(0,0,0,0.2);
  }
  .contact-info-item {
    display:flex;
    align-items:center;
    gap:20px;
  }
  .contact-info-item i {
    font-size:28px;
    color:#009688;
    min-width:40px;
  }
  .contact-info-item h3 {
    margin:0;
    font-size:20px;
    color:#FF9800;
  }
  .contact-info-item p {
    margin:0;
    font-size:16px;
    color:#555;
  }

  /* CARTE */
  .map-container {
    margin-top:50px;
    border-radius:20px;
    overflow:hidden;
    height:450px;
    max-width:1200px;
    margin-left:auto;
    margin-right:auto;
    box-shadow:0 8px 30px rgba(0,0,0,0.1);
  }

  /* RESPONSIVE */
  @media(max-width:992px){
    .contact-grid { flex-direction: column; margin-top:-40px; gap:30px; }
    .contact-form, .contact-info { flex:1 1 100%; }
  }
</style>
</head>
<body>

<!-- SECTION CONTACT -->
<section class="contact-section">
  <i class="fas fa-envelope-open-text"></i>
  <div class="contact-content">
    <h1>Contactez-nous</h1>
    <p>Pour toutes questions, suggestions ou demandes d’assistance, nos équipes sont là pour vous répondre rapidement et efficacement. Remplissez le formulaire ci-dessous ou utilisez nos informations directes pour nous contacter.</p>
  </div>
</section>
<br>
<br>
<br>
<br>

<!-- GRILLE FORMULAIRE + INFO -->
<div class="contact-grid">

  <!-- FORMULAIRE -->
  <div class="contact-form">
    <h2>Envoyer un message</h2>
    <form>
      <input type="text" name="name" placeholder="Votre nom" required>
      <input type="email" name="email" placeholder="Votre email" required>
      <input type="text" name="subject" placeholder="Sujet" required>
      <textarea name="message" placeholder="Votre message..." required></textarea>
      <button type="submit">Envoyer</button>
    </form>
  </div>

  <!-- INFORMATIONS -->
  <div class="contact-info">
    <div class="contact-info-item">
      <i class="fas fa-map-marker-alt"></i>
      <div>
        <h3>Adresse</h3>
        <p>Route Nationale 2 x VCN – Thiès</p>
      </div>
    </div>
    <div class="contact-info-item">
      <i class="fas fa-envelope"></i>
      <div>
        <h3>Email</h3>
        <p>isep@isep-thies.edu.sn</p>
      </div>
    </div>
    <div class="contact-info-item">
      <i class="fas fa-phone"></i>
      <div>
        <h3>Téléphone </h3>
        <p>+221 33 951 24 257</p>
      </div>
    </div>
    <div class="contact-info-item">
      <i class="fas fa-clock"></i>
      <div>
        <h3>Horaires</h3>
        <p>Lundi - Samedi : 8h00 - 18h30</p>
      </div>
    </div>
  </div>

</div>

<!-- CARTE GOOGLE MAPS -->
<div class="map-container">
  <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3945.0123456789!2d-16.0!3d14.0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xabcdef!2sISEP%20Abdoulaye%20Ly!5e0!3m2!1sfr!2ssn!4v1692480000000!5m2!1sfr!2ssn" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</div>

</body>
</html>

<?php
// Inclure le footer
include(__DIR__ . '/../includes/footer.php');
?>