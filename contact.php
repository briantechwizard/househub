<?php
include('db.php'); // db.php already starts the session and connects to MySQL
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';

$statusMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $brevoApiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ($_SERVER['BREVO_API_KEY'] ?? ''));

        if (empty($brevoApiKey)) {
            $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Server error: BREVO_API_KEY is missing in Render Environment.</p>";
        } else {
            // Built with implode so copy-paste tools never strip the domain name
            $host = implode('.', ['api', 'brevo', 'com']);
            $apiUrl = 'https://' . $host . '/v3/smtp/email';

            $payload = json_encode([
                'sender'      => ['name' => 'HouseHubKe Contact Form', 'email' => 'briantechwizard.ke@gmail.com'],
                'to'          => [['email' => 'househubke254@gmail.com', 'name' => 'HouseHubKe Admin']],
                'replyTo'     => ['email' => $email, 'name' => $name],
                'subject'     => 'New Contact Us Message from ' . $name,
                'textContent' => "Name: $name\nEmail: $email\n\nMessage:\n$message"
            ]);

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: ' . trim($brevoApiKey),
                'content-type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                // Save message to Aiven MySQL database
                $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $message);
                $stmt->execute();
                $stmt->close();

                $statusMessage = "<p style='color:green;text-align:center;margin-bottom:15px;'>Your message has been sent successfully!</p>";
            } else {
                $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Failed to send message ($httpCode): " . htmlspecialchars($curlErr ?: $response) . "</p>";
            }
        }
    } else {
        $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Please fill in all fields.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>HouseHubKe - <?= $translations[$lang]['contact_us_page'] ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-section {
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255,215,0,0.5);
        }
        .form-section h2 {
            color: #FFD700;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-section label {
            color: #FFD700;
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        .form-section input, .form-section textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #FFD700;
            border-radius: 6px;
            background: #111;
            color: #fff;
            font-size: 14px;
        }
        .form-section input:focus, .form-section textarea:focus {
            outline: none;
            box-shadow: 0 0 10px #FFD700;
        }
        .form-section button {
            background: #FFD700;
            color: #000;
            font-weight: bold;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        .form-section button:hover {
            background: #e6c200;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <section class="form-section">
        <h2><?= $translations[$lang]['contact_us_page'] ?></h2>
        <?= $statusMessage ?>
        <form class="form-box" method="post" autocomplete="off">
            <label for="name"><?= $translations[$lang]['your_name'] ?></label>
            <input type="text" id="name" name="name" placeholder="Enter your name" required>

            <label for="email"><?= $translations[$lang]['your_email'] ?></label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>

            <label for="message"><?= $translations[$lang]['your_message'] ?></label>
            <textarea id="message" name="message" rows="5" placeholder="Enter your message..." required></textarea>

            <button type="submit" class="glow-btn"><?= $translations[$lang]['send_message'] ?></button>
        </form>
    </section>

    <?php include('footer.php'); ?>
</body>
</html>
