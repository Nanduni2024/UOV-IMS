<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vavuniya_ims";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to log audit
function log_audit($user_id, $action, $details) {
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        return;
    }

    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
