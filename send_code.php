<?php
include('db.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/PHPMailer-7.1.1/src/Exception.php';
require __DIR__ . '/PHPMailer-7.1.1/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-7.1.1/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate 6-digit verification code
        $code = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;

        $brevoApiKey = getenv('BREVO_API_KEY');
        $emailSent = false;
        $errorMsg = '';

        if ($brevoApiKey) {
            // Fast HTTPS API send via Brevo (works on Render without SMTP port blocks)
            $payload = json_encode([
                'sender' => ['name' => 'HouseHubKe', 'email' => 'househubke254@gmail.com'],
                'to' => [['email' => $email]],
                'subject' => 'HouseHubKe Password Reset Code',
                'textContent' => "Your verification code is: $code"
            ]);

            $ch = curl_init('https://brevo.com');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: ' . $brevoApiKey,
                'content-type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $emailSent = true;
            } else {
                $errorMsg = $curlErr ?: $response;
            }
        } else {
            // Fallback to PHPMailer for local XAMPP testing (with 10-second timeout)
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Timeout = 10;
                $mail->Host = '://gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'househubke254@gmail.com';
                $mail->Password = getenv('SMTP_PASS') ?: 'ucfj btvy ysga duqc';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('househubke254@gmail.com', 'HouseHubKe');
                $mail->addAddress($email);
                $mail->Subject = 'HouseHubKe Password Reset Code';
                $mail->Body    = "Your verification code is: $code";

                $mail->send();
                $emailSent = true;
            } catch (Exception $e) {
                $errorMsg = $mail->ErrorInfo;
            }
        }

        if ($emailSent) {
            echo "<p style='color:green;text-align:center;'>Verification code sent to your email.</p>";
            echo "<p style='text-align:center;'><a href='verify_code.php'>Go to Verify Code Page</a></p>";
        } else {
            echo "<p style='color:red;text-align:center;'>Message could not be sent: " . htmlspecialchars($errorMsg) . "</p>";
        }
    } else {
        echo "<p style='color:red;text-align:center;'>No account found with that email.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
