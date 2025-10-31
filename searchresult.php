<?php
require __DIR__ . '/db.php';

$q = trim($_GET['q'] ?? '');
$movies = [];

if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT id, title, summary, poster
        FROM movies
        WHERE title LIKE :q OR summary LIKE :q
        ORDER BY title ASC
    ");
    $stmt->execute([':q' => "%$q%"]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Search Results - Cinema Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<body>

    <?php include __DIR__ . '/header.php'; ?>
    <?php include __DIR__ . '/menu.php'; ?>

    <main class="movies-page">
        <h1>Search Results for “<?= htmlspecialchars($q) ?>”</h1>

        <section class="movies-grid-page">
            <?php if ($q === ''): ?>
                <p>Please enter a search term.</p>
            <?php elseif (count($movies) === 0): ?>
                <p>No movies found for “<?= htmlspecialchars($q) ?>”.</p>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <article class="movie-card-page" onclick="location.href='movieDetail.php?id=<?= $movie['id'] ?>'">
                        <img src="<?= htmlspecialchars($movie['poster'] ?: 'assets/placeholder-poster.jpg') ?>"
                            alt="<?= htmlspecialchars($movie['title']) ?>">
                        <div class="movie-card-page-body">
                            <h2 class="movie-card-page-title"><?= htmlspecialchars($movie['title']) ?></h2>
                            <?php if (!empty($movie['summary'])): ?>
                                <p class="movie-card-page-summary">
                                    <?= htmlspecialchars(mb_strimwidth($movie['summary'], 0, 120, '…', 'UTF-8')) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>