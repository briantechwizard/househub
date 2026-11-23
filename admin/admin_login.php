<?php
session_start();
include('../db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header("Location: admin.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No admin account found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login - HouseHubKe</title>
    <style>
        body { background:#111; color:#fff; font-family:Arial, sans-serif; }
        .form-section {
            width:40%; margin:100px auto; padding:20px;
            background:#1a1a1a; border:2px solid #FFD700;
            border-radius:10px;
        }
        h2 { color:#FFD700; text-align:center; }
        label { color:#FFD700; display:block; margin-top:10px; }
        input {
            width:100%; padding:10px; margin-top:5px;
            background:#111; border:1px solid #FFD700;
            border-radius:6px; color:#fff;
        }
        input:focus { outline:none; box-shadow:0 0 10px #FFD700; }
        button {
            margin-top:15px; width:100%; padding:12px;
            background:#FFD700; color:#000; font-weight:bold;
            border:none; border-radius:6px; cursor:pointer;
        }
        button:hover { background:#e6c200; }
        .error { color:red; text-align:center; }
    </style>
</head>
<body>
    <section class="form-section">
        <h2>Admin Login</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post" autocomplete="off">
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </section>
</body>
</html>
