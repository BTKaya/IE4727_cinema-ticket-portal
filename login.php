<?php
session_start();
require __DIR__ . '/db.php';

$errors = [];

// capture redirect target (if user was sent here from auth.php)
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  // also keep redirect if posted
  $redirect = $_POST['redirect'] ?? $redirect;

  if ($username === '' || $password === '') {
    $errors[] = 'Username and password required';
  } else {
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $user['username'];

      // go back to where user wanted
      header('Location: ' . $redirect);
      exit;
    } else {
      $errors[] = 'Username or password incorrect';
    }
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Login - Cinema</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<body>

  <?php include 'header.php'; ?>

  <div class="auth-container">
    <h1>Login</h1>

    <?php if ($errors): ?>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post">
      <!-- keep redirect through POST -->
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

      <label>Username
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </label>

      <label>Password
        <input type="password" name="password">
      </label>

      <button type="submit">Login</button>
    </form>

    <div class="alt-link">
      Don’t have an account?
      <a href="register.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Register</a>
    </div>
  </div>

</body>

</html>