<?php
include 'db.php';
$movies = $pdo->query("SELECT * FROM movies")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Cinema Portal - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Correct flat paths -->
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
</head>

<body>

  <?php include 'header.php'; ?>

  <?php include 'menu.php'; ?>

  <main class="hero-wrap">
    <aside class="popular-list">
      <ul id="popular">
        <?php foreach ($movies as $i => $m): ?>
          <li>
            <a href="movie.php?id=<?= $m['id'] ?>" class="movie-name"
              data-summary="<?= htmlspecialchars($m['summary']) ?>" data-index="<?= $i ?>">
              <?= htmlspecialchars($m['title']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <div class="movie-summary-box"></div>

    <section class="carousel" id="carousel">
      <div class="slides">
        <?php foreach ($movies as $i => $m): ?>
          <img src="<?= htmlspecialchars($m['poster']) ?>" class="slideshow-image <?= $i === 0 ? 'active' : '' ?>">
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <section class="movies-grid">
    <?php foreach ($movies as $m): ?>
      <div class="movie-card" onclick="location.href='movie.php?id=<?= $m['id'] ?>'">
        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="">
        <h3><?= htmlspecialchars($m['title']) ?></h3>
      </div>
    <?php endforeach; ?>
  </section>

  <?php include 'footer.php'; ?>

</body>

</html>