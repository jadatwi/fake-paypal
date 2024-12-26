<?php
// Database configuration
$host = 'localhost';
$dbname = 'paybro';
$username = 'root'; // Replace with your MySQL username
$password = ''; // Replace with your MySQL password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
