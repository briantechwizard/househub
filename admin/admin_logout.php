<?php
session_start();
include('../db.php');

// Clear only admin-related session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

// Or destroy the whole session if you prefer
session_destroy();

// Redirect back to the admin login page
header("Location: admin_login.php");
exit;
?>
