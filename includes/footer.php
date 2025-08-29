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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      <p><i class="fas fa-envelope"></i> Email : contact@isep-thies.sn</p>
      <p><i class="fas fa-phone"></i> Tél : +221 77 000 00 00</p>
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
