<?php
$host = "localhost";
$dbname = "cinema_portal";
$user = "root";
$pass = "";
date_default_timezone_set('Asia/Singapore');

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("DB Connection failed: " . $e->getMessage());
}
?>