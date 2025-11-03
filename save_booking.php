<?php
require 'db.php';
session_start();

$movie_id = isset($_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;
$session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
$seats = $_POST['seats'] ?? [];
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$location_id_from_form = $_POST['location_id'] ?? null;

if (!$movie_id || !$session_id || empty($seats)) {
    echo "Missing required data (movie_id, session_id, seats).";
    exit;
}

if (!is_array($seats))
    $seats = [$seats];
$seats = array_values(array_unique(array_filter(array_map(fn($s) => strtoupper(trim($s)), $seats), fn($s) => $s !== '')));
if (empty($seats)) {
    echo "Missing required data (movie_id, session_id, seats).";
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in before booking seats.'); window.location='account.php';</script>";
    exit;
}
$user_id = (int) $_SESSION['user_id'];

$sessionStmt = $pdo->prepare("
    SELECT s.id, s.movie_id AS session_movie_id, s.location_id, s.session_date, s.session_time
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
if ((int) $session['session_movie_id'] !== (int) $movie_id) {
    echo "Session does not belong to this movie.";
    exit;
}

$location_id = (int) $session['location_id'];
$screening_date = $session['session_date'];
$screening_time = $session['session_time'];

$layoutStmt = $pdo->prepare("SELECT location_type FROM movies WHERE id = ?");
$layoutStmt->execute([$movie_id]);
$layout = (int) $layoutStmt->fetchColumn();
$price_per_seat = ($layout === 1) ? 15 : 10;
$total_price = count($seats) * $price_per_seat;

$checkStmt = $pdo->prepare("
    SELECT seats
    FROM bookings
    WHERE session_id = ?
      AND status IN ('pending','checked_out')
");
$checkStmt->execute([$session_id]);
$existingBookings = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

$alreadyTaken = [];
foreach ($existingBookings as $bk) {
    $raw = trim((string) $bk['seats']);
    if ($raw !== '') {
        $raw = trim($raw, '[]"');
        if ($raw !== '') {
            $arr = explode('","', $raw);
            foreach ($arr as $r) {
                $t = strtoupper(trim($r));
                if ($t !== '')
                    $alreadyTaken[] = $t;
            }
        }
    }
}
$alreadyTaken = array_values(array_unique($alreadyTaken));
$conflicts = array_intersect($alreadyTaken, $seats);
if (!empty($conflicts)) {
    echo "Some of the selected seats are already booked: " . implode(', ', $conflicts);
    exit;
}

$seatsForDb = '["' . implode('","', $seats) . '"]';

$insertSql = "
    INSERT INTO bookings
    (user_id, movie_id, session_id, location_id, screening_date, screening_time, seats,
     price_per_seat, total_price, status, created_at)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
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
        $seatsForDb,
        $price_per_seat,
        $total_price
    ]);
    header("Location: cart.php?status=success");
    exit;
} catch (Exception $e) {
    echo "Error saving booking: " . $e->getMessage();
}
