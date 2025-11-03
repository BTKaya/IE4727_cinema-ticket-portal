<?php
include 'db.php';
include 'header.php';
include 'menu.php';
include 'auth.php';

// handle logout
if (isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

// ensure logged in
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

$stmt = $pdo->prepare("
    SELECT b.id, b.movie_id, b.screening_date, b.screening_time, b.seats, b.total_price, m.title
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

        <p><strong>Date:</strong> <?= $b['screening_date']; ?></p>
        <p><strong>Time:</strong> <?= substr($b['screening_time'], 0, 5); ?></p>

        <p><strong>Seats:</strong>
          <?= implode(", ", json_decode($b['seats'], true)); ?>
        </p>

        <p class="price"><strong>Total:</strong> $ <?= number_format($b['total_price'], 2); ?></p>

        <form method="post" action="remove_booking.php">
          <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
          <button class="remove-btn">Remove</button>
        </form>

      </div>
    <?php endforeach; ?>

    <div class="confirm-checkout-wrap">
      <a href="checkout.php" class="checkout-btn">Confirm Checkout</a>
    </div>

  <?php endif; ?>

</div>

<?php include 'footer.php'; ?>