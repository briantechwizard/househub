<?php
include('db.php');
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';

$statusMessage = '';
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $statusMessage = "<p style='color:green; text-align:center;'>" . $translations[$lang]['signup_success'] . "</p>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, password, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['status'] === 'blocked') {
            $statusMessage = "<p style='color:red; text-align:center;'>" . $translations[$lang]['account_blocked'] . "</p>";
        } elseif (password_verify($password, $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['name'];
            $stmt->close();
            header("Location: index.php");
            exit();
        } else {
            $statusMessage = "<p style='color:red; text-align:center;'>" . $translations[$lang]['invalid_password'] . "</p>";
        }
    } else {
        $statusMessage = "<p style='color:red; text-align:center;'>" . $translations[$lang]['no_account'] . "</p>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>HouseHubKe - <?= $translations[$lang]['login_page'] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <?php include('navbar.php'); ?>

    <!-- Main content -->
    <main>
        <section class="form-section">
            <h2><?= $translations[$lang]['login_heading'] ?></h2>
            <?= $statusMessage ?>

            <form class="form-box" method="post">
                <label for="email"><?= $translations[$lang]['email'] ?></label>
                <input type="email" id="email" name="email" required autocomplete="email">

                <label for="password"><?= $translations[$lang]['password'] ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <button type="submit" class="glow-btn"><?= $translations[$lang]['login_button'] ?></button>
            </form>
            <p><a href="forgot_password.php"><?= $translations[$lang]['forgot_password'] ?></a></p>
            <p><?= $translations[$lang]['no_account_text'] ?> <a href="signup.php"><?= $translations[$lang]['signup'] ?></a></p>
        </section>
    </main>

    <!-- Footer -->
    <?php include('footer.php'); ?>
</body>
</html>
