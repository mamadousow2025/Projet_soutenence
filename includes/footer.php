<?php
// footer.php
?>
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
  
  <style>
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
  </style>
</body>
</html>