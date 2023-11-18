<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = $_POST['file_id'];
    $receiverId = $_POST['receiver_id'];

    // Get the sender's user ID

    $senderId = $_SESSION['user_id'];

    // Check if the sender owns the file
    $stmt = $pdo->prepare("SELECT id FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $senderId]);
    $fileOwner = $stmt->fetchColumn();

    if ($fileOwner) {
        // Remove the access by deleting the record from shared_files table
        $stmt = $pdo->prepare("DELETE FROM shared_files WHERE sender_id = ? AND receiver_id = ? AND file_id = ?");
        if ($stmt->execute([$senderId, $receiverId, $fileId])) {
            echo json_encode(['success' => true]);
            exit(); // Terminate the script after successful removal
        } else {
            echo json_encode(['success' => false, 'message' => 'An error occurred while removing access.']);
            exit(); // Terminate the script if an error occurs
        }
    } else {
        echo json_encode(['success' => false, 'message' => "You do not have permission to remove access for this file (File ID: $fileId, Sender ID: $senderId)."]);

        exit(); // Terminate the script if the user doesn't have permission
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit(); // Terminate the script for invalid requests
}
?>