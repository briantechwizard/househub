<?php
include('db.php');
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';

// Ensure user is logged in before posting or editing
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$editMode = false;
$house = null;
$id = null;
$toastError = '';

// Check if editing
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $sql = "SELECT * FROM houses WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $house = $result->fetch_assoc();

    // Security check: only allow editing if current user owns the house
    if ($house && $house['user_id'] != $_SESSION['user_id']) {
        die($translations[$lang]['unauthorized_edit']);
    }
}

// Helper function to upload image or video to Cloudinary permanently
function uploadToCloudinary($tmpPath, $originalName, $mimeType, $resourceType = 'image') {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? ''));
    $apiKey    = getenv('CLOUDINARY_API_KEY')    ?: ($_ENV['CLOUDINARY_API_KEY']    ?? ($_SERVER['CLOUDINARY_API_KEY']    ?? ''));
    $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? ($_SERVER['CLOUDINARY_API_SECRET'] ?? ''));

    $cloudName = trim($cloudName);
    $apiKey    = trim($apiKey);
    $apiSecret = trim($apiSecret);

    if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
        return ['error' => 'Cloudinary environment variables are missing on the server.'];
    }

    $timestamp = time();
    $folder = 'househubke';
    // Cloudinary signature requires parameters sorted alphabetically
    $paramsToSign = "folder={$folder}&timestamp={$timestamp}" . $apiSecret;
    $signature = sha1($paramsToSign);

    $host = implode('.', ['api', 'cloudinary', 'com']);
    $url  = "https://{$host}/v1_1/{$cloudName}/{$resourceType}/upload";

    $postFields = [
        'file'      => new CURLFile($tmpPath, $mimeType ?: 'application/octet-stream', basename($originalName)),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'folder'    => $folder,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        if (!empty($data['secure_url'])) {
            return ['url' => $data['secure_url']];
        }
    }

    $errData = json_decode($response, true);
    $msg = $errData['error']['message'] ?? ($curlErr ?: 'Cloudinary upload failed');
    return ['error' => $msg];
}

// Detect if POST exceeded server limit (empty $_POST when Content-Length > 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $toastError = "File size limit exceeded! Please upload files under 100MB.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $title    = trim($_POST['title'] ?? '');
    $price    = intval($_POST['price'] ?? 0);
    $phone    = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    // Handle multiple image uploads to Cloudinary
    $imagePaths = [];
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $key => $filename) {
            if (!empty($filename)) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmpPath  = $_FILES['photos']['tmp_name'][$key];
                    $mimeType = $_FILES['photos']['type'][$key] ?? 'image/jpeg';
                    $uploadRes = uploadToCloudinary($tmpPath, $filename, $mimeType, 'image');
                    if (isset($uploadRes['url'])) {
                        $imagePaths[] = $uploadRes['url'];
                    } else {
                        $toastError = "Photo upload failed: " . $uploadRes['error'];
                        break;
                    }
                } else {
                    $toastError = "Photo upload error (Code: " . $_FILES['photos']['error'][$key] . ").";
                    break;
                }
            }
        }
    }
    $images = !empty($imagePaths) ? implode(',', $imagePaths) : ($house['image'] ?? "");

    // Handle video upload to Cloudinary (max 100MB)
    $video = $house['video'] ?? "";
    if (empty($toastError) && !empty($_FILES['videos']['name'][0])) {
        if ($_FILES['videos']['error'][0] === UPLOAD_ERR_OK) {
            $videoSize = $_FILES['videos']['size'][0];
            if ($videoSize <= 100 * 1024 * 1024) {
                $tmpVidPath = $_FILES['videos']['tmp_name'][0];
                $vidName    = $_FILES['videos']['name'][0];
                $vidMime    = $_FILES['videos']['type'][0] ?? 'video/mp4';
                $vidRes     = uploadToCloudinary($tmpVidPath, $vidName, $vidMime, 'video');
                if (isset($vidRes['url'])) {
                    $video = $vidRes['url'];
                } else {
                    $toastError = "Video upload failed: " . $vidRes['error'];
                }
            } else {
                $toastError = "Video is too large! Please upload a video smaller than 100MB.";
            }
        } elseif ($_FILES['videos']['error'][0] === UPLOAD_ERR_INI_SIZE || $_FILES['videos']['error'][0] === UPLOAD_ERR_FORM_SIZE) {
            $toastError = "File exceeds the 100MB upload limit. Please choose a smaller file.";
        } else {
            $toastError = "Video upload error (Code: " . $_FILES['videos']['error'][0] . ").";
        }
    }

    if (empty($toastError)) {
        if ($editMode) {
            $sql = "UPDATE houses SET name=?, title=?, price=?, phone=?, location=?, image=?, video=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissssi", $name, $title, $price, $phone, $location, $images, $video, $id);
        } else {
            $userId = $_SESSION['user_id'];
            $sql = "INSERT INTO houses (name, title, price, phone, location, image, video, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissssi", $name, $title, $price, $phone, $location, $images, $video, $userId);
        }

        if ($stmt->execute()) {
            header("Location: browse.php?msg=success");
            exit();
        } else {
            $toastError = "Database error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editMode ? $translations[$lang]['edit_house'] : $translations[$lang]['post_house'] ?> - HouseHubKe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            width: 70%;
            margin: 40px auto;
            padding: 30px;
            background: rgba(255,255,255,0.9);
            border: 3px solid #FFD700;
            border-radius: 12px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 10px #FFD700;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 25px;
            padding: 12px 25px;
            background: #FFD700;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: black;
            color: #FFD700;
            box-shadow: 0 0 15px #FFD700;
        }
        /* Toast Notification Styling */
        #toast-alert {
            visibility: hidden;
            min-width: 280px;
            max-width: 90%;
            background-color: #111;
            color: #FFD700;
            border: 2px solid #ff4d4d;
            text-align: center;
            border-radius: 10px;
            padding: 16px 22px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            font-size: 15px;
            font-weight: bold;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7);
            opacity: 0;
            transition: opacity 0.3s ease, bottom 0.3s ease;
        }
        #toast-alert.show {
            visibility: visible;
            opacity: 1;
            bottom: 45px;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    <main>
        <div class="form-container">
            <h2><?= $editMode ? $translations[$lang]['edit_listing'] : $translations[$lang]['post_listing'] ?></h2>
            <form id="houseForm" action="post.php<?= $editMode ? '?edit='.$id : '' ?>" method="post" enctype="multipart/form-data">
                <label for="name"><?= $translations[$lang]['poster_name'] ?>:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($house['name'] ?? '') ?>" required>

                <label for="title"><?= $translations[$lang]['house_type'] ?>:</label>
                <select id="title" name="title" required>
                    <option value=""><?= $translations[$lang]['select_type'] ?></option>
                    <option <?= ($house['title'] ?? '')=='Bedsitter'?'selected':'' ?>>Bedsitter</option>
                    <option <?= ($house['title'] ?? '')=='1 Bedroom'?'selected':'' ?>>1 Bedroom</option>
                    <option <?= ($house['title'] ?? '')=='2 Bedroom'?'selected':'' ?>>2 Bedroom</option>
                    <option <?= ($house['title'] ?? '')=='3 Bedroom'?'selected':'' ?>>3 Bedroom</option>
                    <option <?= ($house['title'] ?? '')=='4 Bedroom'?'selected':'' ?>>4 Bedroom</option>
                    <option <?= ($house['title'] ?? '')=='5 Bedroom'?'selected':'' ?>>5 Bedroom</option>
                    <option <?= ($house['title'] ?? '')=='Mansion'?'selected':'' ?>>Mansion</option>
                </select>

                <label for="price"><?= $translations[$lang]['price'] ?> (KSh):</label>
                <input type="text" id="price" name="price" value="<?= htmlspecialchars($house['price'] ?? '') ?>" required>

                <label for="phone"><?= $translations[$lang]['phone_number'] ?>:</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($house['phone'] ?? '') ?>" required>

                <label for="location"><?= $translations[$lang]['location'] ?>:</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($house['location'] ?? '') ?>" required>

                <label for="photos"><?= $translations[$lang]['upload_photos'] ?>:</label>
                <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                <?php if ($editMode && !empty($house['image'])): ?>
                    <div class="preview-gallery">
                        <?php
                        $images = explode(',', $house['image']);
                        foreach ($images as $img) {
                            $imgPath = htmlspecialchars(trim($img));
                            if (!empty($imgPath)) {
                                echo "<img src='$imgPath' alt='".$translations[$lang]['house_image']."'>";
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <label for="videos"><?= $translations[$lang]['upload_video'] ?> (max 100MB):</label>
                <input type="file" id="videos" name="videos[]" accept="video/mp4,video/webm">
                <?php if ($editMode && !empty($house['video'])): ?>
                    <div class="preview-video">
                        <video controls>
                            <source src="<?= htmlspecialchars($house['video']) ?>" type="video/mp4">
                            <?= $translations[$lang]['video_not_supported'] ?>
                        </video>
                    </div>
                <?php endif; ?>

                <button type="submit" id="submitBtn"><?= $editMode ? $translations[$lang]['update_house'] : $translations[$lang]['post_house_btn'] ?></button>
            </form>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toast-alert"></div>

    <?php include('footer.php'); ?>

    <script>
        const MAX_SIZE = 100 * 1024 * 1024; // 100MB in bytes

        function showToast(message) {
            const toast = document.getElementById("toast-alert");
            toast.textContent = "⚠️ " + message;
            toast.classList.add("show");
            setTimeout(() => {
                toast.classList.remove("show");
            }, 5000);
        }

        function checkTotalFileSize() {
            const photos = document.getElementById("photos").files;
            const videos = document.getElementById("videos").files;
            let totalSize = 0;

            for (let i = 0; i < photos.length; i++) totalSize += photos[i].size;
            for (let i = 0; i < videos.length; i++) totalSize += videos[i].size;

            if (totalSize > MAX_SIZE) {
                const sizeMB = (totalSize / (1024 * 1024)).toFixed(1);
                showToast(`Selected files (${sizeMB}MB) exceed the 100MB limit. Please upload files smaller than 100MB.`);
                return false;
            }
            return true;
        }

        // Check immediately when user selects photos or video
        document.getElementById("photos").addEventListener("change", function() {
            if (!checkTotalFileSize()) this.value = "";
        });

        document.getElementById("videos").addEventListener("change", function() {
            if (!checkTotalFileSize()) this.value = "";
        });

        // Double-check before submitting form & show uploading feedback
        document.getElementById("houseForm").addEventListener("submit", function(e) {
            if (!checkTotalFileSize()) {
                e.preventDefault();
            } else {
                const btn = document.getElementById("submitBtn");
                btn.textContent = "Uploading media... Please wait";
                btn.style.opacity = "0.7";
            }
        });

        // Show server-side error toast if PHP caught an issue
        <?php if (!empty($toastError)): ?>
            window.addEventListener("DOMContentLoaded", () => {
                showToast(<?= json_encode($toastError) ?>);
            });
        <?php endif; ?>
    </script>
</body>
</html>
