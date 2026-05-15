<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: user_management.php?error=Admin access required");
    exit;
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Prevent deleting own account
if ($user_id == $_SESSION['user_id']) {
    header("Location: user_management.php?error=Cannot delete your own account");
    exit;
}

// Get user details for logging
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: user_management.php?error=User not found");
    exit;
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    log_audit($_SESSION['user_id'], 'delete_user', "Deleted user: " . $user['username']);
    header("Location: user_management.php?success=User deleted successfully");
} else {
    header("Location: user_management.php?error=Error deleting user");
}
exit;