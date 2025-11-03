<?php
require 'db.php';

session_start();
$user_id = $_SESSION['user_id'] ?? 0;

if (!isset($_POST['booking_id'])) {
    header("Location: cart.php");
    exit;
}

$booking_id = $_POST['booking_id'];

$stmt = $pdo->prepare("SELECT session_id, seats FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($booking) {
    $session_id = (int) $booking['session_id'];
    $clean = str_replace(['[', ']', '"', "'"], '', $booking['seats']);
    $seats = array_filter(array_map('trim', explode(',', $clean)));

    $sStmt = $pdo->prepare("SELECT held_seats FROM sessions WHERE id = ? LIMIT 1");
    $sStmt->execute([$session_id]);
    $csv = trim((string)$sStmt->fetchColumn());

    if ($csv !== '') {
        $existing = array_filter(array_map('trim', explode(',', $csv)));
        $updated = array_diff($existing, $seats);
        $newCsv = implode(",", $updated);

        $uStmt = $pdo->prepare("UPDATE sessions SET held_seats = ? WHERE id = ?");
        $uStmt->execute([$newCsv, $session_id]);
    }

    $del = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
    $del->execute([$booking_id, $user_id]);
}

header("Location: cart.php");
exit;
