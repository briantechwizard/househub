<?php
include('db.php'); // db.php already starts the session safely

$statusMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userEmail = trim($_POST['email'] ?? '');
    $feedback  = trim($_POST['feedback'] ?? '');

    if (!empty($userEmail) && !empty($feedback)) {
        $brevoApiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ($_SERVER['BREVO_API_KEY'] ?? ''));

        if (empty($brevoApiKey)) {
            $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Server error: BREVO_API_KEY is missing in Render Environment.</p>";
        } else {
            // Built with implode so copy-paste never strips the domain
            $host = implode('.', ['api', 'brevo', 'com']);
            $apiUrl = 'https://' . $host . '/v3/smtp/email';

            $payload = json_encode([
                'sender'      => ['name' => 'HouseHubKe Feedback', 'email' => 'briantechwizard.ke@gmail.com'],
                'to'          => [['email' => 'househubke254@gmail.com', 'name' => 'HouseHubKe Admin']],
                'replyTo'     => ['email' => $userEmail, 'name' => 'Feedback User'],
                'subject'     => 'New Feedback Submission',
                'textContent' => "Feedback from: $userEmail\n\nMessage:\n$feedback"
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
                // Save to database
                $stmt = $conn->prepare("INSERT INTO feedback_messages (email, message) VALUES (?, ?)");
                $stmt->bind_param("ss", $userEmail, $feedback);
                $stmt->execute();
                $stmt->close();

                $statusMessage = "<p style='color:green;text-align:center;margin-bottom:15px;'>Thank you for your feedback! It has been emailed and saved successfully.</p>";
            } else {
                $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Failed to send feedback ($httpCode): " . htmlspecialchars($curlErr ?: $response) . "</p>";
            }
        }
    } else {
        $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Please enter both your email and feedback message.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Feedback - HouseHubKe</title>
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
        .form-section input, .form-section textarea {
            background: #111;
            color: #fff;
            border: 1px solid #FFD700;
            border-radius: 6px;
            padding: 10px;
            width: 100%;
            margin-bottom: 10px;
        }
        .form-section input:focus, .form-section textarea:focus {
            outline: none;
            box-shadow: 0 0 10px #FFD700;
        }
        ::placeholder { color: #ccc; font-style: italic; }
        .form-section label { color: #FFD700; font-weight: bold; margin-top: 10px; display: block; }
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
<main>
    <section class="form-section">
        <h2>Send Feedback</h2>
        <?= $statusMessage ?>
        <form method="post">
            <label>Your Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>
            
            <label>Your Feedback</label>
            <textarea name="feedback" rows="5" placeholder="Enter your feedback message..." required></textarea>
            
            <button type="submit" class="glow-btn">Submit</button>
        </form>
    </section>
</main>
<?php include('footer.php'); ?>
</body>
</html>
