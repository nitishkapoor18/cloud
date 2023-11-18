<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';
$user_id = $_SESSION['user_id'];

// Fetch user's uploaded files from the database
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ?");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll();

// Fetch total used data from the database
$stmt = $pdo->prepare("SELECT total_used_data FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$totalUsedData = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ((int) $totalUsedData + $file['size'] <= 2147483648) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Check if file size is less than 2 GB (2 * 1024 * 1024 * 1024 bytes)
            $maxFileSize = 2 * 1024 * 1024 * 1024;
            if ($file['size'] <= $maxFileSize) {
                // Directory where files will be stored
                $uploadDir = 'files/';

                // Generate a unique filename
                $fileName = uniqid() . '_' . $file['name'];
                $filePath = $uploadDir . $fileName;

                // Move the uploaded file to the designated directory
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $fileSize = $file['size'];

                    // Generate a unique sharing link token
                    $sharingLinkToken = bin2hex(random_bytes(16)); // Generate a 32-character token

                    // Insert file record into the database
                    $stmt = $pdo->prepare("INSERT INTO files (filename, filepath, file_size, user_id, sharing_link) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$fileName, $filePath, $fileSize, $_SESSION['user_id'], $sharingLinkToken]);

                    // Update total used data
                    $fileSizeInBytes = $fileSize;
                    $user_id = $_SESSION['user_id'];

                    // Fetch current total used data
                    $stmt = $pdo->prepare("SELECT total_used_data FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $currentTotalUsedData = $stmt->fetchColumn();

                    // Calculate new total used data
                    $newTotalUsedData = $currentTotalUsedData + $fileSizeInBytes;

                    // Update the total used data in the database
                    $stmt = $pdo->prepare("UPDATE users SET total_used_data = ? WHERE id = ?");
                    $stmt->execute([$newTotalUsedData, $user_id]);

                    // Redirect back to the dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error uploading file.';
                }
            } else {
                echo '<script>alert("File size must be less than 2 GB.");</script>';
            }
        } else {
            $_SESSION['error'] = 'File upload error.';
        }
    } else {
        echo '<script>alert("Insufficient storage on your account!");</script>';
    }
}

// Function to format file size
function formatFileSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($size >= 1024 && $i < 4) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dash.css">
    <script>
        function uploadFile() {
            var form = document.getElementById('upload-form');
            var status = document.getElementById('uploading-status');

            var formData = new FormData(form);

            var xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    var percentComplete = (event.loaded / event.total) * 100;
                    console.log(percentComplete);
                }
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        window.location.reload(); // Refresh the page after upload
                    } else {
                        status.innerHTML = "";
                    }
                }
            };

            xhr.open('POST', 'upload.php', true);
            xhr.send(formData);

            form.style.display = 'none';
            status.style.display = 'block';
        }
    </script>


</head>

<body>
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
        <a href="shared_files.php">Shared File</a>
    </div>


    <div class="container">
        <h1>Welcome,
            <?php echo $_SESSION['username']; ?>
        </h1>
        <h2>Your Uploaded Files</h2>
        <div id="uploading-status" style="display: none;">
            <div class="upload-progress">
                <div class="upload-spinner"></div>
            </div>
        </div>
        <form action="dashboard.php" id="upload-form" method="post" enctype="multipart/form-data"
            onsubmit="uploadFile();">
            <label for="file">
                <div class="image-upload">
                    <img src="images/file.png" height="56px" alt="">
                    <input type="file" name="file" id="file" required>
                </div>
                <input type="submit" value="Upload" class="btn">
            </label>
        </form>


        <div class="file-list">
            <?php foreach ($files as $file): ?>
                <div class="file-card">
                    <div class="file-image">
                        <?php if (isImageFile($file['filename'])): ?>
                            <img src="<?php echo $file['filepath']; ?>" alt="<?php echo $file['filename']; ?>">
                        <?php elseif (isVideoFile($file['filename'])): ?>
                            <video controls>
                                <source src="<?php echo $file['filepath']; ?>"
                                    type="video/<?php echo pathinfo($file['filename'], PATHINFO_EXTENSION); ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <img src="images/default_file_icon.png" width="56" height="56" alt="Other File">
                        <?php endif; ?>
                    </div>
                    <div class="file-details">
                        <h3>
                            <?php echo $file['filename']; ?>
                        </h3>
                        <p>Size:
                            <?php echo formatFileSize($file['file_size']); ?>
                        </p>
                        <p>Uploaded on:
                            <?php echo $file['upload_date']; ?>
                        </p>
                    </div>
                    <div class="file-actions">
                        <a href="download.php?file_id=<?php echo $file['id']; ?>" class="action-icon" title="Download">
                            <img src="images/download_icon.png" width="56" height="56" alt="Download">
                        </a>
                        <span class="action-icon share-icon" data-file-id="<?php echo $file['id']; ?>" title="Share">
                            <img src="images/send.png" width="56" height="56" alt="Share">
                        </span>








                        <a href="delete.php?file_id=<?php echo $file['id']; ?>" class="action-icon"
                            onclick="return confirm('Are you sure you want to delete this file?')" title="Delete">
                            <img src="images/delete_icon.png" width="56" height="56" alt="Delete">
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            <script>
                const shareIcons = document.querySelectorAll('.share-icon');
                shareIcons.forEach(icon => {
                    icon.addEventListener('click', () => {
                        const fileId = icon.getAttribute('data-file-id');
                        const username = prompt('Enter the username to share the file with:');

                        if (username !== null && username.trim() !== '') {
                            // Send the request to the server to share the file
                            const xhr = new XMLHttpRequest();
                            xhr.open('POST', 'share_file.php', true);
                            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        alert('File shared successfully!'); // Display the success message
                                        window.location.reload(); // Reload the page to see the changes
                                    } else {
                                        alert(response.message);
                                    }
                                } else {
                                    alert('An error occurred while sharing the file.');
                                }
                            };
                            const data = `file_id=${fileId}&username=${username}`;
                            xhr.send(data);
                        }
                    });
                });
            </script>
        </div>
        <?php
        function isImageFile($filename) {
            $imageExtensions = array('jpg', 'jpeg', 'png', 'gif');
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            return in_array($ext, $imageExtensions);
        }

        function isVideoFile($filename) {
            $videoExtensions = array('mp4', 'avi', 'mov');
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            return in_array($ext, $videoExtensions);
        }
        ?>

        <div class="total-used-data">
            <?php
            $remainingData = 2 * 1024 * 1024 * 1024 - $totalUsedData;
            echo formatFileSize($totalUsedData) . " used out of 2GB";
            ?>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const totalUsedData = <?php echo $totalUsedData; ?>;
                const remainingData = 2 * 1024 * 1024 * 1024 - totalUsedData;
                const totalUsedDataElement = document.querySelector('.total-used-data');

                if (remainingData > 0) {
                    totalUsedDataElement.style.color = 'green';
                } else {
                    totalUsedDataElement.style.color = 'red';
                }
            });
        </script>

    </div>
</body>

</html>