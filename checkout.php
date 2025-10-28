<?php
include 'db.php';
session_start();

if (!isset($_POST['booking_ids'])) {
    echo "<script>alert('No bookings selected.'); window.location='cart.php';</script>";
    exit;
}

$booking_ids = $_POST['booking_ids'];

$stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
foreach ($booking_ids as $bid) {
    $stmt->execute([$bid]);
}

echo "<script>alert('✅ Checkout successful! Your seats are now permanently booked.'); window.location='index.php';</script>";
