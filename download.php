<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';

if (isset($_GET['file_id'])) {
    $fileId = $_GET['file_id'];

    // Fetch file record using the sharing link token
    $stmt = $pdo->prepare("SELECT filepath, filename FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if ($file) {
        $filePath = $file['filepath'];

        // Set headers for download
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=" . $file['filename']);
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: binary");

        // Read and output the file content
        readfile($filePath);
        exit();
    } else {
        echo 'File not found.';
    }
} else {
    echo 'Invalid sharing link.';
}
?>