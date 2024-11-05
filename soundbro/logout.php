<?php
session_start();
session_unset(); // unset all session variables
session_destroy(); // destroy the session
header("Location: index.php"); // Redirect to index page
exit();
?>