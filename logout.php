<?php
include 'config.php';
log_audit($_SESSION['user_id'], 'logout', 'User logged out');
session_destroy();
header("Location: login.php");
?>