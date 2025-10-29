<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Movie Project Group 9</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
</head>

<?php
$layoutAttr = isset($layout) ? "data-layout='$layout'" : "";
?>

<body <?= $layoutAttr ?>>


  <header class="templates-header">
    <button class="menu">menu</button>
    <h1 class="site-title"><a href="index.php" class="home-link">Movie Project Group 9</a></h1>
    <a href="cart.php" class="cart-icon">🛒</a>
  </header>