<?php
// Load translations
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';
?>
<script>
// Ensure mobile viewport meta tag is present in <head> on every page
if (!document.querySelector('meta[name="viewport"]')) {
    const meta = document.createElement('meta');
    meta.name = 'viewport';
    meta.content = 'width=device-width, initial-scale=1.0';
    document.head.appendChild(meta);
}
</script>

<nav>
    <div class="nav-left">
        <!-- Logo -->
        <a href="index.php">
            <img src="images/logo.png" alt="HouseHubKe Logo" class="logo-img">
        </a>

        <!-- Settings Icon -->
        <a href="settings.php">
            <img src="images/settings.png" alt="Settings" class="logo-img settings-icon">
        </a>
    </div>

    <div class="nav-center">
        <ul class="nav-menu">
            <li><a href="index.php"><?= $translations[$lang]['home'] ?></a></li>
            <li><a href="browse.php"><?= $translations[$lang]['browse'] ?></a></li>
            <li><a href="post.php"><?= $translations[$lang]['post'] ?></a></li>
            <li><a href="about.php"><?= $translations[$lang]['about'] ?></a></li>
            <li><a href="contact.php"><?= $translations[$lang]['contact'] ?></a></li>
        </ul>
    </div>

    <div class="nav-right">
        <a href="login.php" class="glow-btn"><?= $translations[$lang]['login'] ?></a>
        <a href="signup.php" class="glow-btn"><?= $translations[$lang]['signup'] ?></a>
    </div>
</nav>
