<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

if (isset($_GET['file_id'])) {
    $fileId = $_GET['file_id'];

    // Fetch file info from the database
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if ($file && $file['user_id'] === $_SESSION['user_id']) {
        $filePath = $file['filepath'];
        $fileSize = $file['file_size'];

        // Delete the file from the file system
        if (unlink($filePath)) {
            // Delete the file record from the database
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$fileId]);

            // Fetch user's total used data from the database
            $stmt = $pdo->prepare("SELECT total_used_data FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $totalUsedData = $stmt->fetchColumn();

            // Calculate new total used data
            $newTotalUsedData = max(0, $totalUsedData - $fileSize);

            // Update total used data in the database
            $stmt = $pdo->prepare("UPDATE users SET total_used_data = ? WHERE id = ?");
            $stmt->execute([$newTotalUsedData, $_SESSION['user_id']]);

            // Redirect back to the dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Error deleting file.";
        }
    }
}

// Redirect if file not found or user doesn't own the file
header('Location: dashboard.php');
exit();
?>