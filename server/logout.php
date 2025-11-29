<?php
// Logout script
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
session_destroy();
// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// Redirect back to public login
header("Location: ../public/login.html");
exit;
?>
