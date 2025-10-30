<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Lumina Cinema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
</head>

<?php
$layoutAttr = isset($layout) ? "data-layout='$layout'" : "";
?>

<body <?= $layoutAttr ?>>


  <header class="templates-header">
    <button class="menu" id="menuBtn">Menu</button>
    <h1 class="site-title">
      <a href="index.php">
        <img src="assets/images/luminaLogo.png" alt="Lumina Cinema Logo" class="site-logo">
      </a>
    </h1>
    <a href="cart.php" class="cart-icon">🛒</a>
  </header>