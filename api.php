<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'fetch_movies') {
  $stmt = $pdo->query("SELECT * FROM movies");
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

if ($action === 'save_booking') {
  $data = json_decode(file_get_contents("php://input"), true);
  $user_id = $_SESSION['user_id'] ?? 1; // for testing
  $stmt = $pdo->prepare("INSERT INTO bookings (user_id, movie_id, location, screening_date, screening_time, seats)
                         VALUES (?,?,?,?,?,?)");
  $stmt->execute([$user_id, $data['movie_id'], $data['location'], $data['date'], $data['time'], json_encode($data['seats'])]);
  echo json_encode(['success' => true]);
  exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
