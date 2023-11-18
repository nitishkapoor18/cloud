<?php
require_once 'config.php';

if (isset($_GET['shared_id'])) {
    $sharedId = $_GET['shared_id'];

    // Fetch shared file info from the database
    $stmt = $pdo->prepare("
        SELECT f.filename, f.filepath
        FROM shared_files sf
        JOIN files f ON sf.file_id = f.id
        WHERE sf.id = ?
    ");
    $stmt->execute([$sharedId]);
    $sharedFile = $stmt->fetch();

    if ($sharedFile) {
        $filePath = $sharedFile['filepath'];
        $fileName = $sharedFile['filename'];

        // Set headers for download
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: binary");

        // Read and output the file content
        readfile($filePath);
        exit();
    }
}

// Redirect if file not found or sharing link is invalid
header('Location: error_page.php'); // You can create an error_page.php with appropriate error message
exit();
?>