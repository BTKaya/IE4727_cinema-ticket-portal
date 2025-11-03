<?php
include 'db.php';
$movies = $pdo->query("SELECT * FROM movies")->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Movie IDs to display
$allowedIds = [1, 2, 3];
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

$movieMap = [];
$sessionMap = []; // movie_id => [ [session_id, location_name, session_date, session_time], ... ]

foreach ($sessions as $s) {
  $movieMap[$s['movie_id']] = $s['movie_title'];
  $sessionMap[$s['movie_id']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Lumina Cinema - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">

  <!-- Inline style to hide scrollbar -->
  <style>
    html,
    body {
      height: 100%;
      overflow-y: scroll;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
      display: none;
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
        <li><a href="#" id="quickBuyToggle" class="quickbuy-text">Quick Buy</a></li>
      </ul>

      <div class="quickbuy-container" id="quickBuyContainer">
        <form id="quickBuyForm" action="movieDetail.php" method="GET">
          <label for="movie">Movie:</label>
          <select id="movie" name="id" class="opened-list-styling" required>
            <option value="">Select a movie</option>
            <?php foreach ($movieMap as $mid => $mtitle): ?>
              <option value="<?= (int) $mid ?>"><?= htmlspecialchars($mtitle) ?></option>
            <?php endforeach; ?>
          </select>

          <label for="location">Location:</label>
          <select id="location" name="location_id" class="opened-list-styling" required disabled>
            <option value="">Select a cinema</option>
            <?php
            // Ensure unique movie-location pairs
            $seen = [];
            foreach ($sessions as $s):
              $key = $s['movie_id'] . '-' . $s['location_id'];
              if (isset($seen[$key]))
                continue;
              $seen[$key] = true;
              ?>
              <option value="<?= (int) $s['location_id'] ?>" data-movie-id="<?= (int) $s['movie_id'] ?>">
                <?= htmlspecialchars($s['location_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="showtime">Showtime:</label>
          <select id="showtime" name="session_id" class="opened-list-styling" required disabled>
            <option value="">Select a showtime</option>
            <?php foreach ($sessions as $s): ?>
              <option value="<?= (int) $s['session_id'] ?>" data-movie-id="<?= (int) $s['movie_id'] ?>"
                data-location-id="<?= (int) $s['location_id'] ?>">
                <?= htmlspecialchars($s['session_date']) ?> @ <?= htmlspecialchars($s['session_time']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="buy-btn">Buy</button>
        </form>
      </div>
    </aside>
  </main>

  <script src="quickbuy.js" defer></script>

  <section class="movies-grid">
    <?php foreach ($movies as $m): ?>
      <div class="movie-card" onclick="location.href='movieDetail.php?id=<?= $m['id'] ?>'">
        <img src="<?= htmlspecialchars($m['poster']) ?>" alt="">
        <h3><?= htmlspecialchars($m['title']) ?></h3>
      </div>
    <?php endforeach; ?>
  </section>

  <?php include 'footer.php'; ?>
</body>

</html>