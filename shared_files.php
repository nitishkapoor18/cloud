<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch shared files from the database
$stmt = $pdo->prepare("
    SELECT sf.id AS shared_id, sf.sender_id, sf.receiver_id, sf.file_id, sf.date_shared,
           u1.username AS sender_username, u2.username AS receiver_username,
           f.filename, f.filepath
    FROM shared_files sf
    JOIN users u1 ON sf.sender_id = u1.id
    JOIN users u2 ON sf.receiver_id = u2.id
    JOIN files f ON sf.file_id = f.id
    WHERE sf.sender_id = ? OR sf.receiver_id = ?
    ORDER BY sf.date_shared DESC
");
$stmt->execute([$user_id, $user_id]);
$sharedFiles = $stmt->fetchAll();

function isImageFile($filename) {
    $imageExtensions = array('jpg', 'jpeg', 'png', 'gif');
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, $imageExtensions);
}

?>

<!-- The rest of your HTML code -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Files</title>
    <link rel="stylesheet" href="dash.css"> <!-- Use your stylesheet here -->

</head>

<body>
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="shared_files.php">Shared Files</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>Shared Files</h1>
        <div class="file-list">
            <?php foreach ($sharedFiles as $sharedFile): ?>
                <div class="file-card">
                    <div class="file-image">
                        <?php if (isImageFile($sharedFile['filename'])): ?>
                            <img src="<?php echo $sharedFile['filepath']; ?>" alt="<?php echo $sharedFile['filename']; ?>">
                        <?php else: ?>
                            <img src="images/default_file_icon.png" width="56" height="56" alt="Other File">
                        <?php endif; ?>
                    </div>
                    <div class="file-details">
                        <h3>
                            <?php echo $sharedFile['filename']; ?>
                        </h3>
                        <p>Sender:
                            <?php echo $sharedFile['sender_username']; ?>
                        </p>
                        <p>Receiver:
                            <?php echo $sharedFile['receiver_username']; ?>
                        </p>
                        <p>Shared on:
                            <?php echo $sharedFile['date_shared']; ?>
                        </p>
                    </div>
                    <div class="file-actions">
                        <a href="download_shared.php?shared_id=<?php echo $sharedFile['shared_id']; ?>" class="action-icon"
                            title="Download">
                            <img src="images/download_icon.png" width="56" height="56" alt="Download">
                        </a>
                        <?php if ($_SESSION['user_id'] === $sharedFile['sender_id']): ?>
                            <a href="remove_access.php?shared_id=<?php echo $sharedFile['shared_id']; ?>"
                                class="action-icon remove-button" data-file-id="<?php echo $sharedFile['file_id']; ?>"
                                data-receiver-id="<?php echo $sharedFile['receiver_id']; ?>" title="Remove Access">
                                <img src="images/remove.png" width="56" height="56" alt="Remove Access">
                            </a>


                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const removeButtons = document.querySelectorAll('.remove-button');
                                    removeButtons.forEach(button => {
                                        // Check if the event listener is already added to this button
                                        if (!button.getAttribute('data-listener-added')) {
                                            button.addEventListener('click', function (event) {
                                                event.preventDefault(); // Prevent the default link behavior

                                                const fileId = button.getAttribute('data-file-id');
                                                const receiverId = button.getAttribute('data-receiver-id');

                                                const confirmRemove = confirm('Are you sure you want to remove access?');
                                                if (confirmRemove) {
                                                    // Send a POST request to remove_access.php

                                                    fetch('remove_access.php', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/x-www-form-urlencoded',
                                                        },
                                                        body: `file_id=${fileId}&receiver_id=${receiverId}`,
                                                    })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                alert('Access removed successfully!');
                                                                location.reload(); // Reload the page after successful removal
                                                            } else {
                                                                alert(data.message);
                                                            }
                                                        })
                                                        .catch(error => {
                                                            console.error('An error occurred:', error);
                                                            alert('An error occurred while removing access.');
                                                        });
                                                }
                                            });

                                            // Mark the button with a custom attribute to indicate that the event listener is added
                                            button.setAttribute('data-listener-added', 'true');
                                        }
                                    });
                                });

                            </script>
                        <?php endif; ?>


                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>