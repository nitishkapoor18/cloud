<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Query to fetch user data
    $query = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);
    $result = $stmt->fetch();

    if ($result) {
        $hashedPassword = $result["password"];

        // Verify the hashed password
        if (password_verify($password, $hashedPassword)) {
            $_SESSION["username"] = $result["username"];
            $_SESSION["user_id"] = $result["id"];
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Invalid username or password. Please try again.";
        }
    } else {
        $error_message = "Invalid username or password. Please try again.";
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="login.css">
</head>

<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (isset($error_message)) { ?>
            <p class="error">
                <?php echo $error_message; ?>
            </p>
        <?php } ?>
        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br>
            <input type="submit" value="Login" class="btn">
        </form>
        <a href="register.html" class="btn-register">Register here</a>
    </div>
</body>

</html>