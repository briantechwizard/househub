<?php
// Include database connection (db.php already starts the session safely)
include('db.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // sanitize input

    // Fetch house record including file names
    $sql = "SELECT user_id, image, video FROM houses WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $house = $result->fetch_assoc();

    if (!$house) {
        die("House not found.");
    }

    // Only allow deletion if current user owns the house
    if (!isset($_SESSION['user_id']) || $house['user_id'] != $_SESSION['user_id']) {
        die("Unauthorized action.");
    }

    // Path to uploads folder (works on both Windows and Linux/Render)
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

    // Delete images
    if (!empty($house['image'])) {
        $images = explode(',', $house['image']);
        foreach ($images as $img) {
            $cleanName = basename(trim($img));
            if ($cleanName !== '') {
                $filePath = $uploadDir . $cleanName;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }

    // Delete video
    if (!empty($house['video'])) {
        $cleanVideo = basename(trim($house['video']));
        if ($cleanVideo !== '') {
            $videoPath = $uploadDir . $cleanVideo;
            if (file_exists($videoPath)) {
                unlink($videoPath);
            }
        }
    }

    // Delete record from DB
    $sql = "DELETE FROM houses WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: browse.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    echo "No ID provided.";
}
?>
