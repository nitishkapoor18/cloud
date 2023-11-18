<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = $_POST['file_id'];
    $username = $_POST['username'];

    // Get the sender's user ID
    $senderId = $_SESSION['user_id'];

    // Check if the sender and receiver exist
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $receiverId = $stmt->fetchColumn();

    if ($receiverId) {
        // Check if the file is already shared with the receiver
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shared_files WHERE sender_id = ? AND receiver_id = ? AND file_id = ?");
        $stmt->execute([$senderId, $receiverId, $fileId]);
        $alreadySharedCount = $stmt->fetchColumn();

        if ($alreadySharedCount > 0) {
            echo json_encode(['success' => false, 'message' => 'File is already shared with this user.']);
        } else {
            // Get the current date and time
            $currentDate = date('Y-m-d H:i:s');

            // Insert a record into the shared_files table
            $stmt = $pdo->prepare("INSERT INTO shared_files (sender_id, receiver_id, file_id, date_shared) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$senderId, $receiverId, $fileId, $currentDate])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'An error occurred while sharing the file.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Receiver username not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

?>