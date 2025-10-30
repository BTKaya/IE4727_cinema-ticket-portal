<?php
session_start();
require __DIR__ . '/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  // basic validation
  if ($username === '') {
    $errors[] = 'Username required';
  }
  if ($password === '') {
    $errors[] = 'Password required';
  }
  if ($password !== $password2) {
    $errors[] = 'Passwords do not match';
  }

  // check if username taken
  if (!$errors) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
      $errors[] = 'Username already taken';
    }
  }

  if (!$errors) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    // auto-login after register
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;

    header('Location: index.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/luminaIcon.png">
</head>

<body>

  <?php include 'header.php'; ?>

  <div class="auth-container">
    <h1>Create account</h1>

    <?php if ($errors): ?>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post">
      <label>Username
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </label>

      <label>Email (optional)
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </label>

      <label>Password
        <input type="password" name="password">
      </label>

      <label>Confirm password
        <input type="password" name="password2">
      </label>

      <button type="submit">Sign up</button>
    </form>

    <p class="alt-link">
      Already have an account? <a href="login.php">Log in</a>
    </p>
  </div>

</body>

</html>