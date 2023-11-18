<?php
// Database configuration
$servername = "fdb31.biz.nf";
$username = "3901767_test";
$password = "tripta1972";
$dbname = "3901767_test";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>