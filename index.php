<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('lang.php');
$lang = $_SESSION['language'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="CSUmHWeKQwmXKRrVCeKLz0Clr7O7lLT702TQluko9-0" />
    <title>HouseHubKe - Find Bedsitters, Apartments & Houses for Rent in Kenya</title>
    <meta name="description" content="HouseHubKe helps tenants and landlords across Nairobi and Kenya find, list, and rent bedsitters, 1-4 bedroom apartments, and mansions easily.">
    <meta name="keywords" content="HouseHubKe, houses for rent in Kenya, Nairobi apartments, bedsitters in Nairobi, rent house Kenya, HouseHub">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://onrender.com">

    <!-- Social Media / WhatsApp Link Preview Tags -->
    <meta property="og:title" content="HouseHubKe - Find Houses & Apartments for Rent in Kenya">
    <meta property="og:description" content="Browse verified bedsitters, 1-4 bedroom apartments, and mansions across Nairobi and Kenya on HouseHubKe.">
    <meta property="og:url" content="https://onrender.com">
    <meta property="og:type" content="website">

    <link rel="stylesheet" href="style.css">
    <script>
        // Simple scroll reveal for .info section
        document.addEventListener("DOMContentLoaded", () => {
            const infoSection = document.querySelector(".info");
            if (infoSection) {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            infoSection.classList.add("visible");
                        }
                    });
                });
                observer.observe(infoSection);
            }

            // Toast notification example
            const toast = document.getElementById("toast");
            if (toast) {
                toast.classList.add("show");
                setTimeout(() => toast.classList.remove("show"), 3000);
            }
        });
    </script>
</head>
<body>
    <!-- Background image + overlay -->
    <img src="images/background.png" alt="Background" class="bg-image">
    <div class="overlay"></div>

    <!-- Toast Notification -->
    <?php if(isset($_SESSION['username'])): ?>
        <div id="toast" class="toast">
            <?= $translations[$lang]['welcome'] ?>, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </div>
    <?php endif; ?>

    <!-- Navbar -->
    <?php include('navbar.php'); ?>
  
    <!-- Main content wrapper -->
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <?php if(isset($_SESSION['username'])): ?>
                    <h1><?= $translations[$lang]['welcome'] ?> <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <?php else: ?>
                    <h1><?= $translations[$lang]['welcome'] ?></h1>
                <?php endif; ?>
                <p><?= $translations[$lang]['tagline'] ?></p>

                <!-- Hero Call-to-Action Buttons -->
                <div class="hero-actions">
                    <a href="browse.php" class="glow-btn">🏠 <?= $translations[$lang]['browse'] ?></a>
                    <a href="signup.php" class="glow-btn">✨ <?= $translations[$lang]['signup'] ?></a>
                </div>

                <!-- Video Gallery Section -->
                <section class="video-gallery">
                    <h2><?= $translations[$lang]['featured'] ?></h2>
                    <div class="video-row">
                        <div class="video-card">
                            <h3><?= $translations[$lang]['one_bedroom'] ?></h3>
                            <video autoplay loop muted controls>
                                <source src="videos/1 bedroom.mp4" type="video/mp4">
                                Your browser does not support video playback.
                            </video>
                        </div>

                        <div class="video-card">
                            <h3><?= $translations[$lang]['two_bedroom'] ?></h3>
                            <video autoplay loop muted controls>
                                <source src="videos/2 bedroom.mp4" type="video/mp4">
                                Your browser does not support video playback.
                            </video>
                        </div>

                        <div class="video-card">
                            <h3><?= $translations[$lang]['three_bedroom'] ?></h3>
                            <video autoplay loop muted controls>
                                <source src="videos/3 bedroom.mp4" type="video/mp4">
                                Your browser does not support video playback.
                            </video>
                        </div>

                        <div class="video-card">
                            <h3><?= $translations[$lang]['four_bedroom'] ?></h3>
                            <video autoplay loop muted controls>
                                <source src="videos/4 bedroom.mp4" type="video/mp4">
                                Your browser does not support video playback.
                            </video>
                        </div>

                        <div class="video-card">
                            <h3><?= $translations[$lang]['mansion'] ?></h3>
                            <video autoplay loop muted controls>
                                <source src="videos/mansion.mp4" type="video/mp4">
                                Your browser does not support video playback.
                            </video>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <!-- Extra Info Section -->
        <section class="info">
            <h2 style="text-align:center; margin-top:50px;"><?= $translations[$lang]['why_choose'] ?></h2>
            <ul style="width:70%; margin:20px auto; font-size:18px; line-height:1.8;">
                <li>🏠 <?= $translations[$lang]['listings'] ?></li>
                <li>✨ <?= $translations[$lang]['design'] ?></li>
                <li>📤 <?= $translations[$lang]['posting'] ?></li>
                <li>🔍 <?= $translations[$lang]['filters'] ?></li>
                <li>💬 <?= $translations[$lang]['contact'] ?></li>
            </ul>
        </section>
    </main>

    <!-- Footer -->
    <?php include('footer.php'); ?>
</body>
</html>
