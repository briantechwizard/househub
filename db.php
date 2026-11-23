<?php
// Load local Aiven credentials if running on your PC (ignored by Git)
if (file_exists(__DIR__ . '/db_local.php')) {
    include(__DIR__ . '/db_local.php');
}

// Start session for login system
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use Render environment variables (or db_local.php) if available, otherwise fall back to local XAMPP
$host     = getenv('DB_HOST') ?: "localhost";
$port     = (int)(getenv('DB_PORT') ?: 3306);
$user     = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$database = getenv('DB_NAME') ?: "househubke254";

// Initialize MySQLi
$conn = mysqli_init();

if (getenv('DB_HOST')) {
    // Enable SSL for Aiven Cloud MySQL on Render & local Admin
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $connected = mysqli_real_connect($conn, $host, $user, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    // Standard connection for local XAMPP
    $connected = mysqli_real_connect($conn, $host, $user, $password, $database, $port);
}

// Check connection
if (!$connected) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
