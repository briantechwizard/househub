<?php
include('db.php');
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$statusMessage = '';

// Fetch current 2FA status & user email
$stmt = $conn->prepare("SELECT email, two_factor FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($userEmail, $currentTwoFa);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Handle verification code submission
    if (isset($_POST['verify_code'])) {
        $enteredCode = trim($_POST['verify_code']);
        if (!empty($_SESSION['twofa_code']) && $enteredCode === (string)$_SESSION['twofa_code']) {
            $stmt = $conn->prepare("UPDATE users SET two_factor = 1 WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            $currentTwoFa = 1;
            unset($_SESSION['twofa_code']);
            $statusMessage = "<p style='color:lightgreen;text-align:center;margin-bottom:15px;'>Two-Factor Authentication enabled and verified successfully!</p>";
        } else {
            $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Invalid code. Please try again.</p>";
        }
    }
    // 2. Handle Save 2FA toggle form
    else {
        $twofa = isset($_POST['enable_2fa']) ? 1 : 0;

        if ($twofa === 1) {
            $code = random_int(100000, 999999);
            $_SESSION['twofa_code'] = (string)$code;

            $brevoApiKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ($_SERVER['BREVO_API_KEY'] ?? ''));

            if (empty($brevoApiKey)) {
                $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Server error: BREVO_API_KEY is missing in Render Environment.</p>";
            } else {
                $host = implode('.', ['api', 'brevo', 'com']);
                $apiUrl = 'https://' . $host . '/v3/smtp/email';

                $payload = json_encode([
                    'sender'      => ['name' => 'HouseHubKe Security', 'email' => 'briantechwizard.ke@gmail.com'],
                    'to'          => [['email' => $userEmail]],
                    'subject'     => 'Your HouseHubKe 2FA Verification Code',
                    'textContent' => "Hello,\n\nYour Two-Factor Authentication verification code is: $code\n\nEnter this code on HouseHubKe to complete 2FA setup."
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
                    $statusMessage = "<p style='color:lightgreen;text-align:center;margin-bottom:15px;'>A 6-digit verification code has been sent to <strong>" . htmlspecialchars($userEmail) . "</strong>. Enter it below to confirm.</p>";
                } else {
                    unset($_SESSION['twofa_code']);
                    $statusMessage = "<p style='color:red;text-align:center;margin-bottom:15px;'>Failed to send code ($httpCode): " . htmlspecialchars($curlErr ?: $response) . "</p>";
                }
            }
        } else {
            // Disable 2FA
            $stmt = $conn->prepare("UPDATE users SET two_factor = 0 WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            $currentTwoFa = 0;
            unset($_SESSION['twofa_code']);
            $statusMessage = "<p style='color:lightgreen;text-align:center;margin-bottom:15px;'>" . $translations[$lang]['twofa_updated'] . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations[$lang]['twofa_page'] ?> - HouseHubKe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body, h2, label { color: white; }
        .form-section { background: #1e1e1e; padding: 25px; border-radius: 8px; }
        .glow-btn { background: #FFD700; color: black; font-weight: bold; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-top: 12px; }
        input[type="text"] { width: 100%; padding: 10px; margin-top: 8px; border-radius: 6px; border: 1px solid #FFD700; background: #111; color: #fff; }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    <main>
        <section class="form-section">
            <h2><?= $translations[$lang]['twofa_heading'] ?></h2>
            <?= $statusMessage ?>

            <form method="post">
                <label style="display:flex; align-items:center; gap:10px; justify-content:center; cursor:pointer;">
                    <input type="checkbox" name="enable_2fa" style="width:auto;" <?= ($currentTwoFa == 1 || !empty($_SESSION['twofa_code'])) ? 'checked' : '' ?>>
                    <?= $translations[$lang]['enable_2fa'] ?>
                </label>
                <button type="submit" class="glow-btn"><?= $translations[$lang]['save_button'] ?></button>
            </form>

            <?php if (!empty($_SESSION['twofa_code'])): ?>
                <form method="post" style="margin-top:25px; border-top:1px solid #444; padding-top:20px;">
                    <label>Enter 6-Digit Verification Code:</label>
                    <input type="text" name="verify_code" maxlength="6" placeholder="e.g. 123456" required>
                    <button type="submit" class="glow-btn">Verify</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include('footer.php'); ?>
</body>
</html>
