<?php
// Database credentials
$host = 'localhost';
$dbname = 'pharma';
$username = 'root';
$password = '';

try {
  // Correct connection string format
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo 'Connection failed: ' . $e->getMessage();
}
?>
