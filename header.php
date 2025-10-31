<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Lumina Cinema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
  <script src="header.js" defer></script>
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<?php
$layoutAttr = isset($layout) ? "data-layout='$layout'" : "";
?>

<body <?= $layoutAttr ?>>


  <header class="templates-header">
    <button class="menu" id="menuBtn">☰</button>
    <h1 class="site-title">
      <a href="index.php">
        <img src="assets/images/luminaLogo.png" alt="Lumina Cinema Logo" class="site-logo">
      </a>
    </h1>
    <button type="button" class="icon-btn search-icon" id="searchToggle" aria-label="Search">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </button>

    <!-- overlay + dropdown search -->
    <div id="searchOverlay" class="search-overlay"></div>
    <div id="searchBar" class="searchbar" role="search">
      <input type="text" id="searchInput" placeholder="Search movies..." autocomplete="off">
      <button type="button" class="closeButton" id="searchClose" aria-label="Close search">✕</button>
    </div>
  </header>