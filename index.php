<?php
include 'db.php';
$movies = $pdo->query("SELECT * FROM movies")->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Define which IDs you want to show
$allowedIds = [1, 2, 3];

// Filter the movies array
$filteredMovies = array_filter($movies, fn($m) => in_array($m['id'], $allowedIds));

$sql = "
    SELECT
        s.id AS session_id,
        s.movie_id,
        s.location_id,
        s.session_date,
        s.session_time,
        m.title AS movie_title,
        l.name  AS location_name
    FROM sessions s
    JOIN movies m   ON m.id = s.movie_id
    JOIN locations l ON l.id = s.location_id
    ORDER BY m.title, l.name, s.session_date, s.session_time
";
$stmt = $pdo->query($sql);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// build unique movies
$movieMap = [];
foreach ($sessions as $row) {
  $movieMap[$row['movie_id']] = $row['movie_title'];
}

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
  <style>
    html,
    body {
      height: 100%;
      overflow-y: scroll;
      /* allow scrolling */
      scrollbar-width: none;
      /* Firefox */
      -ms-overflow-style: none;
      /* IE/Edge */
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
      display: none;
      /* Chrome, Safari, Opera */
    }
  </style>
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

    <aside class="quickbuy-list">
      <ul id="quickbuy">
        <li>
          <a href="#" id="quickBuyToggle" class="quickbuy-text">Quick Buy</a>
        </li>
      </ul>

      <div class="quickbuy-container" id="quickBuyContainer">
        <form id="quickBuyForm" action="#" method="GET">
          <label for="movie">Movie:</label>
          <select id="movie" name="movie_id" class="opened-list-styling" required>
            <option value="">Select a movie</option>
            <?php foreach ($movieMap as $mid => $mtitle): ?>
              <option value="<?= htmlspecialchars($mid) ?>"><?= htmlspecialchars($mtitle) ?></option>
            <?php endforeach; ?>
          </select>

          <label for="location">Location:</label>
          <select id="location" name="location_id" class="opened-list-styling" required disabled>
            <option value="">Select a cinema</option>
          </select>

          <label for="showtime">Showtime:</label>
          <select id="showtime" name="session_id" class="opened-list-styling" required disabled>
            <option value="">Select a showtime</option>
          </select>

          <button type="submit" class="buy-btn">Buy</button>
        </form>
      </div>
    </aside>
  </main>

  <section class="movies-grid">
    <?php foreach ($movies as $m): ?>
      <div class="movie-card" onclick="location.href='movieDetail.php?id=<?= $m['id'] ?>'">
        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="">
        <h3><?= htmlspecialchars($m['title']) ?></h3>
      </div>
    <?php endforeach; ?>
  </section>

  <script>
    const SESSIONS = <?= json_encode($sessions, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="quickbuy.js" defer></script>

  <?php include 'footer.php'; ?>