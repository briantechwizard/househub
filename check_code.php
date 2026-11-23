<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['code'] ?? '');
    $savedCode = trim((string)($_SESSION['reset_code'] ?? ''));

    if (empty($savedCode)) {
        echo "<p style='color:red;text-align:center;'>Session expired. Please request a new verification code.</p>";
        echo "<p style='text-align:center;'><a href='forgot_password.php'>Request New Code</a></p>";
    } elseif ($code === $savedCode) {
        header("Location: reset_password.php");
        exit();
    } else {
        echo "<p style='color:red;text-align:center;'>Invalid code. Try again.</p>";
        echo "<p style='text-align:center;'><a href='verify_code.php'>Back</a></p>";
    }
}
?>
