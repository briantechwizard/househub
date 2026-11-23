<?php
include('db.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $code = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;

        $brevoApiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ($_SERVER['BREVO_API_KEY'] ?? ''));

        if (empty($brevoApiKey)) {
            echo "<p style='color:red;text-align:center;'>Server configuration error: BREVO_API_KEY is missing in Render Environment.</p>";
        } else {
            $apiUrl = 'https://' . '://brevo.com' . '/v3/smtp/email';

            $payload = json_encode([
                'sender' => ['name' => 'HouseHubKe', 'email' => 'househubke254@gmail.com'],
                'to' => [['email' => $email]],
                'subject' => 'HouseHubKe Password Reset Code',
                'textContent' => "Your HouseHubKe verification code is: $code"
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
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                echo "<p style='color:green;text-align:center;'>Verification code sent to your email!</p>";
                echo "<p style='text-align:center;'><a href='verify_code.php'>Go to Verify Code Page</a></p>";
            } else {
                echo "<p style='color:red;text-align:center;'>Brevo Error ($httpCode): " . htmlspecialchars($curlErr ?: $response) . "</p>";
            }
        }
    } else {
        echo "<p style='color:red;text-align:center;'>No account found with that email.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
