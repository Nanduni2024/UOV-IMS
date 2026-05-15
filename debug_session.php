<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; }
        pre { background: #333; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    <h2>$_SESSION Variables:</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>User Status:</h2>
    <?php
    if (isset($_SESSION['user_id'])) {
        echo "✓ User ID: " . $_SESSION['user_id'] . "<br>";
        echo "✓ Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
    } else {
        echo "✗ User not logged in<br>";
    }
    ?>
    
    <h2>Debug Actions:</h2>
    <a href="login.php" style="color: #137fec; text-decoration: none; display: block; margin: 10px 0;">Go to Login</a>
    <a href="dashboard.php" style="color: #137fec; text-decoration: none; display: block; margin: 10px 0;">Go to Dashboard</a>
    <a href="user_management.php" style="color: #137fec; text-decoration: none; display: block; margin: 10px 0;">Go to User Management</a>
</body>
</html>