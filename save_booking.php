<?php
require 'db.php';
session_start();

/**
 * 1. Read from normal POST (no JSON body)
 */
$movie_id = $_POST['movie_id'] ?? null;
$session_id = $_POST['session_id'] ?? null;
$seats = $_POST['seats'] ?? [];   // seats[] from the form
$date = $_POST['date'] ?? null; // optional, sent by JS
$time = $_POST['time'] ?? null;
$location_id_from_form = $_POST['location_id'] ?? null;

// basic validation
if (!$movie_id || !$session_id || empty($seats)) {
    echo "Missing required data (movie_id, session_id, seats).";
    exit;
}

// ensure seats is an array
if (!is_array($seats)) {
    $seats = [$seats];
}

/**
 * 2. User handling, replaced
 */
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in before booking seats.'); window.location='account.php';</script>";
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/**
 * 3. Get the session from DB to verify it and to get canonical date/time/location
 */
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
    echo "Session not found.";
    exit;
}

// check that the session really belongs to this movie
if ((int) $session['session_movie_id'] !== (int) $movie_id) {
    echo "Session does not belong to this movie.";
    exit;
}

// trust DB values over client-provided ones
$location_id = (int) $session['location_id'];
$screening_date = $session['session_date'];
$screening_time = $session['session_time'];

/**
 * 4. Get layout → decide seat price
 */
$layoutStmt = $pdo->prepare("SELECT location_type FROM movies WHERE id = ?");
$layoutStmt->execute([$movie_id]);
$layout = (int) $layoutStmt->fetchColumn();

// Price rules
$price_per_seat = ($layout === 1) ? 15 : 10;
$total_price = count($seats) * $price_per_seat;

/**
 * 5. Check if any of the seats are already taken for this session
 */
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
    echo "Some of the selected seats are already booked: " . implode(', ', $conflicts);
    exit;
}

/**
 * 6. Insert booking
 */
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
        json_encode($seats),   // stored as JSON in DB is fine
        $price_per_seat,
        $total_price
    ]);

    // OPTION A: redirect to a cart / success page
    header("Location: cart.php?status=success");
    exit;

    // OPTION B (for quick testing):
    // echo "Booking saved!";
} catch (Exception $e) {
    echo "Error saving booking: " . $e->getMessage();
}
