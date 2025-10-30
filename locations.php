<?php
require __DIR__ . '/db.php';

$locations = $pdo->query("SELECT * FROM locations")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Our Locations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="style.css">
  <script src="app.js" defer></script>
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<body>

  <?php include __DIR__ . '/header.php'; ?>
  <?php include __DIR__ . '/menu.php'; ?>

  <main class="locations-page">
    <h1>Our Locations</h1>

    <section class="locations-list">
      <?php if (empty($locations)): ?>
        <p>No locations added yet.</p>
      <?php else: ?>
        <?php foreach ($locations as $loc): ?>
          <article class="location-row">
            <div class="location-picture">
              <?php if (!empty($loc['image'])): ?>
                <img src="<?= htmlspecialchars($loc['image']) ?>" alt="<?= htmlspecialchars($loc['name']) ?>">
              <?php else: ?>
                <!-- placeholder -->
                <span style="opacity:.6;">Location Picture</span>
              <?php endif; ?>
            </div>

            <div class="location-info">
              <div class="location-name">
                <?= htmlspecialchars($loc['name']) ?>
              </div>
              <div class="location-address">
                <?= htmlspecialchars($loc['address']) ?>
                <?php if (!empty($loc['city'])): ?>
                  , <?= htmlspecialchars($loc['city']) ?>
                <?php endif; ?>
              </div>
              <div class="location-phone">
                Phone: +<?= htmlspecialchars($loc['phone']) ?>
              </div>

              <div class="location-actions">
                <!-- route this to movies filtered by location -->
                <a href="movies.php?location_id=<?= $loc['id'] ?>" class="btn-view-sessions">
                  View Movies
                </a>
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