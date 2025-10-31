<?php
include 'db.php';
include 'header.php';
include 'menu.php';
include 'auth.php';

session_start();

// handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
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

// fetch user info
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}
?>

<div class="account-wrapper">
  <div class="account-card">
    <h1>Your Account</h1>

    <div class="account-details">
      <p><span>Username:</span> <?= htmlspecialchars($user['username']); ?></p>
      <p><span>Email:</span> <?= htmlspecialchars($user['email']); ?></p>
    </div>

    <div class="account-actions">
      <a href="cart.php" class="btn btn-cart">Go to Cart</a>
      <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>