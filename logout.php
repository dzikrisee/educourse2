<?php
require_once 'includes/config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to homepage with message
header('Location: index.php');
exit();
?>