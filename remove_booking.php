<?php
require 'db.php';

session_start();
$user_id = $_SESSION['user_id'] ?? 1;

if (!isset($_POST['booking_id'])) {
    header("Location: cart.php");
    exit;
}

$booking_id = $_POST['booking_id'];

$stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);

header("Location: cart.php");
exit;
