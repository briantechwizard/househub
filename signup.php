<?php
include('db.php');
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';

$statusMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $statusMessage = "<p style='color:red; text-align:center;'>" . $translations[$lang]['password_mismatch'] . "</p>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed);

        try {
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: login.php?signup=success");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $statusMessage = "<p style='color:red; text-align:center;'>This email is already registered. Please log in instead.</p>";
            } else {
                $statusMessage = "<p style='color:red; text-align:center;'>" . $translations[$lang]['signup_error'] . " " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>HouseHubKe - <?= $translations[$lang]['signup_page'] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <?php include('navbar.php'); ?>

    <!-- Main content -->
    <main>
        <section class="form-section">
            <h2><?= $translations[$lang]['signup_heading'] ?></h2>
            <?= $statusMessage ?>

            <form class="form-box" method="post">
                <label for="name"><?= $translations[$lang]['full_name'] ?></label>
                <input type="text" id="name" name="name" required autocomplete="name">

                <label for="email"><?= $translations[$lang]['email'] ?></label>
                <input type="email" id="email" name="email" required autocomplete="email">

                <label for="password"><?= $translations[$lang]['password'] ?></label>
                <input type="password" id="password" name="password" required autocomplete="new-password">

                <label for="confirm"><?= $translations[$lang]['confirm_password'] ?></label>
                <input type="password" id="confirm" name="confirm" required autocomplete="new-password">

                <button type="submit" class="glow-btn"><?= $translations[$lang]['signup_button'] ?></button>
            </form>
            <p><?= $translations[$lang]['already_account'] ?> <a href="login.php"><?= $translations[$lang]['login'] ?></a></p>
        </section>
    </main>

    <!-- Footer -->
    <?php include('footer.php'); ?>
</body>
</html>
