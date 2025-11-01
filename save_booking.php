<?php
require 'db.php';
session_start();

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

$movie_id = $data['movie_id'] ?? null;
$session_id = $data['session_id'] ?? null;
$seats = $data['seats'] ?? [];

if (!$movie_id || !$session_id || !$seats) {
    echo json_encode(["success" => false, "message" => "Missing required data (movie_id, session_id, seats)."]);
    exit;
}

// Ensure seats is an array
if (!is_array($seats) || count($seats) === 0) {
    echo json_encode(["success" => false, "message" => "Invalid seat data."]);
    exit;
}

// Temporary user handling (replace with real auth later)
$user_id = $_SESSION['user_id'] ?? 1;

$sessionStmt = $pdo->prepare("
    SELECT s.id,
           s.movie_id AS session_movie_id,
           s.location_id,
           s.session_date,
           s.session_time
    FROM sessions s
    WHERE s.id = ?
    LIMIT 1
");
$sessionStmt->execute([$session_id]);
$session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo json_encode(["success" => false, "message" => "Session not found."]);
    exit;
}

if ((int) $session['session_movie_id'] !== (int) $movie_id) {
    echo json_encode(["success" => false, "message" => "Session does not belong to this movie."]);
    exit;
}

$location_id = (int) $session['location_id'];
$screening_date = $session['session_date'];
$screening_time = $session['session_time'];

$layoutStmt = $pdo->prepare("SELECT location_type FROM movies WHERE id = ?");
$layoutStmt->execute([$movie_id]);
$layout = (int) $layoutStmt->fetchColumn();

// Price rules
$price_per_seat = ($layout === 1) ? 15 : 10;
$total_price = count($seats) * $price_per_seat;

$checkStmt = $pdo->prepare("
    SELECT seats
    FROM bookings
    WHERE session_id = ?
      AND status IN ('pending','confirmed')
");
$checkStmt->execute([$session_id]);
$existingBookings = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

$alreadyTaken = [];
foreach ($existingBookings as $bk) {
    $bkSeats = json_decode($bk['seats'], true);
    if (is_array($bkSeats)) {
        $alreadyTaken = array_merge($alreadyTaken, $bkSeats);
    }
}

$conflicts = array_intersect($alreadyTaken, $seats);
if (!empty($conflicts)) {
    echo json_encode([
        "success" => false,
        "message" => "Some of the selected seats are already booked: " . implode(', ', $conflicts)
    ]);
    exit;
}


$insertSql = "
    INSERT INTO bookings
    (user_id, movie_id, session_id, location_id, screening_date, screening_time, seats,
     price_per_seat, total_price, status, created_at)
    VALUES
    (?,       ?,        ?,         ?,           ?,              ?,              ?, 
     ?,             ?,           'pending', NOW())
";

$stmt = $pdo->prepare($insertSql);

try {
    $stmt->execute([
        $user_id,
        $movie_id,
        $session_id,
        $location_id,
        $screening_date,
        $screening_time,
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
