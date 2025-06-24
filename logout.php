<?php
session_start();
session_unset();     // Unset all session variables
session_destroy();   // Destroy the session

// Optionally clear cookies too
setcookie(session_name(), '', time() - 3600, '/');

// Redirect to login page
header("Location: register.php?form=login");
exit();
?>