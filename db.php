<?php
$host = "localhost";
$dbname = "cinema_portal";
$user = "root";
$pass = ""; // set to your MySQL password if needed

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("DB Connection failed: " . $e->getMessage());
}
?>
