<?php
require 'db.php';

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

$movie_id = $data['movie_id'] ?? null;
$seats = $data['seats'] ?? [];
$date = $data['date'] ?? null;
$time = $data['time'] ?? null;
$location = $data['location_id'] ?? null;

if (!$movie_id || !$seats || !$date || !$time || !$location) {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
    exit;
}

// Ensure seats is an array
if (!is_array($seats)) {
    echo json_encode(["success" => false, "message" => "Invalid seat data."]);
    exit;
}

session_start();

// Temporary user handling (use actual login later)
$user_id = $_SESSION['user_id'] ?? 1; // fallback user for now

// Retrieve movie layout type
$stmt = $pdo->prepare("SELECT location_type FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$layout = (int) $stmt->fetchColumn();

// Price rules:
$price_per_seat = ($layout === 1) ? 15 : 10;
$total_price = count($seats) * $price_per_seat;

// Insert booking as "pending"
$stmt = $pdo->prepare("
    INSERT INTO bookings
    (user_id, movie_id, location_id, screening_date, screening_time, seats, price_per_seat, total_price, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

try {
    $stmt->execute([
        $user_id,
        $movie_id,
        $location,
        $date,
        $time,
        json_encode($seats),
        $price_per_seat,
        $total_price
    ]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
