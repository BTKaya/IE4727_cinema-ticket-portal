<?php
require __DIR__ . '/db.php';
$movies = $pdo->query("SELECT * FROM movies")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Movies - Cinema Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- your global css/js -->
  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
</head>

<body>

  <?php include __DIR__ . '/header.php'; ?>
  <?php include __DIR__ . '/menu.php'; ?>

  <main class="movies-page">
    <h1>Now Showing</h1>

    <section class="movies-grid-page">
      <?php if (count($movies) === 0): ?>
        <p>No movies available.</p>
      <?php else: ?>
        <?php foreach ($movies as $movie): ?>
          <article class="movie-card-page" onclick="location.href='movieDetail.php?id=<?= $movie['id'] ?>'">
            <?php if (!empty($movie['poster'])): ?>
              <img src="<?= htmlspecialchars($movie['poster']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php else: ?>
              <img src="assets/placeholder-poster.jpg" alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php endif; ?>

            <div class="movie-card-page-body">
              <h2 class="movie-card-page-title"><?= htmlspecialchars($movie['title']) ?></h2>
              <?php if (!empty($movie['summary'])): ?>
                <p class="movie-card-page-summary">
                  <?= htmlspecialchars(mb_strimwidth($movie['summary'], 0, 110, '…', 'UTF-8')) ?>
                </p>
              <?php endif; ?>

              <div class="movie-card-page-actions">
                <a href="sessions.php?movie_id=<?= $movie['id'] ?>" class="btn-sessions">Sessions</a>
                <a href="movieDetail.php?id=<?= $movie['id'] ?>" class="btn-details">Details</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>