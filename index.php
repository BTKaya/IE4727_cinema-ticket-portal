<?php
include 'db.php';
$movies = $pdo->query("SELECT * FROM movies")->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Define which IDs you want to show
$allowedIds = [1, 2, 3];

// Filter the movies array
$filteredMovies = array_filter($movies, fn($m) => in_array($m['id'], $allowedIds));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Lumina Cinema - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Correct flat paths -->
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<body>

  <?php include 'header.php'; ?>
  <?php include 'menu.php'; ?>

  <main class="hero-wrap">
    <aside class="popular-list">
      <ul id="popular">
        <?php foreach ($filteredMovies as $i => $m): ?>
          <li>
            <a href="movieDetail.php?id=<?= $m['id'] ?>" class="movie-name"
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
        <?php foreach ($filteredMovies as $i => $m): ?>
          <img src="<?= htmlspecialchars($m['poster']) ?>" class="slideshow-image <?= $i === 0 ? 'active' : '' ?>">
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <section class="movies-grid">
    <?php foreach ($movies as $m): ?>
      <div class="movie-card" onclick="location.href='movieDetail.php?id=<?= $m['id'] ?>'">
        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="">
        <h3><?= htmlspecialchars($m['title']) ?></h3>
      </div>
    <?php endforeach; ?>
  </section>

  <aside class="quickbuy-list">
    <ul id="quickbuy">
      <li>
        <a href="#" id="quickBuyToggle" class="quickbuy-text">Quick Buy</a>
      </li>
    </ul>

    <div class="quickbuy-container" id="quickBuyContainer">
      <form action="checkout.php" method="GET">
        <label for="movie">Movie:</label>
        <div class="select-wrap">
          <select id="movie" name="movie_id" required>
            <?php foreach ($movies as $m): ?>
              <option value="<?= htmlspecialchars($m['id']) ?>">
                <?= htmlspecialchars($m['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <label for="location">Location:</label>
        <select id="location" name="location_id" required>
          <option value="">Select a cinema</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc['id']) ?>">
              <?= htmlspecialchars($loc['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="showtime">Showtime:</label>
        <input type="datetime-local" id="showtime" name="showtime" required>

        <button type="submit" class="buy-btn">Buy</button>
      </form>
    </div>
  </aside>

  <?php include 'footer.php'; ?>

</body>

</html>