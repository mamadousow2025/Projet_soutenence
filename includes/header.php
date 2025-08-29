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
        <a href="register.php" class="btn-primary">S’inscrire</a>
        <a href="login.php" class="btn-outline">Se connecter</a>
      </div>
    </div>
  </header>
