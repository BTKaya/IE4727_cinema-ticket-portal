<?php
include 'db.php';
include 'header.php';
include 'menu.php';
include 'auth.php';

if (isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt2 = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ? LIMIT 1");
$stmt2->execute([$user_id]);
$user = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

$expireStmt = $pdo->prepare("
    SELECT id, session_id, seats
    FROM bookings
    WHERE user_id = ?
      AND status = 'pending'
      AND created_at < (NOW() - INTERVAL 10 MINUTE)
");
$expireStmt->execute([$user_id]);
$expiredBookings = $expireStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expiredBookings as $exp) {
    $sid = (int)$exp['session_id'];
    $seats = array_filter(array_map('trim', explode(',', $exp['seats'])));

    $sStmt = $pdo->prepare("SELECT held_seats FROM sessions WHERE id = ?");
    $sStmt->execute([$sid]);
    $csv = trim((string)$sStmt->fetchColumn());

    if ($csv !== '') {
        $existing = array_filter(array_map('trim', explode(',', $csv)));
        $updated = array_diff($existing, $seats);
        $newCsv = implode(",", $updated);

        $uStmt = $pdo->prepare("UPDATE sessions SET held_seats = ? WHERE id = ?");
        $uStmt->execute([$newCsv, $sid]);
    }

    $dStmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $dStmt->execute([$exp['id']]);
}


$stmt = $pdo->prepare("
    SELECT b.id, b.movie_id, b.screening_date, b.screening_time, b.seats, b.total_price, b.created_at, m.title
    FROM bookings b
    JOIN movies m ON b.movie_id = m.id
    WHERE b.user_id = ? AND b.status = 'pending'
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="cart-page">

  <h1><span>Hi, </span> <?= htmlspecialchars($user['username']); ?>!<br>Your Pending Bookings</h1>

  <?php if (empty($bookings)): ?>
    <p class="empty-cart">You have no pending bookings.</p>
  <?php else: ?>

    <?php foreach ($bookings as $b): ?>
      <div class="booking-card">

        <h2><?= htmlspecialchars($b['title']); ?></h2>

        <?php
        $created = strtotime($b['created_at']);
        $expires = $created + (10 * 60);
        $remaining = max(0, $expires - time());
        ?>

        <div class="timer-box" data-remaining="<?= $remaining ?>">
          <strong>Time Left:</strong> <span class="timer-countdown">...</span>
        </div>

        <p><strong>Date:</strong> <?= htmlspecialchars($b['screening_date']); ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars(substr($b['screening_time'], 0, 5)); ?></p>

        <p><strong>Seats:</strong>
          <?= htmlspecialchars($b['seats']); ?>
        </p>

        <p class="price"><strong>Total:</strong> $ <?= number_format($b['total_price'], 2); ?></p>

        <form method="post" action="remove_booking.php">
          <input type="hidden" name="booking_id" value="<?= (int) $b['id']; ?>">
          <button class="remove-btn">Remove</button>
        </form>

      </div>
    <?php endforeach; ?>

    <div class="confirm-checkout-wrap">
      <form action="checkout.php" method="POST">
        <?php foreach ($bookings as $b): ?>
          <input type="hidden" name="booking_ids[]" value="<?= (int) $b['id']; ?>">
        <?php endforeach; ?>
        <button type="submit" class="checkout-btn">Confirm Checkout</button>
      </form>
    </div>

  <?php endif; ?>

</div>

<script src="cart.js" defer></script>

<?php include 'footer.php'; ?>