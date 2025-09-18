<?php
// header.php
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

    /* RESPONSIVE */
    @media (max-width: 992px) {
      nav {
        display: none;
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