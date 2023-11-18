<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $usernameCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE mobile = ?");
    $stmt->execute([$phoneNumber]);
    $phoneCount = $stmt->fetchColumn();

    if ($usernameCount > 0) {
        echo '<script>alert("Username already exists. Please choose a different username."); window.location.href = "register.html";</script>';
    } elseif ($emailCount > 0) {
        echo '<script>alert("Email already exists. Please choose a different email."); window.location.href = "register.html";</script>';
    } elseif ($phoneCount > 0) {
        echo '<script>alert("Phone number already exists. Please choose a different phone number."); window.location.href = "register.html";</script>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, mobile, password) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $phoneNumber, $password])) {
            echo '<script>alert("Registration successful. You can now login."); window.location.href = "login.php";</script>';
        } else {
            echo '<script>alert("An error occurred while registering. Please try again later."); window.location.href = "register.html";</script>';
        }
    }
}
?>