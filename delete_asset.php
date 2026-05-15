<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($asset_id <= 0) {
    header("Location: asset_registry.php?error=Invalid asset ID");
    exit();
}

// Check if there are any maintenance logs for this asset
$check = $conn->prepare("SELECT COUNT(*) FROM maintenance_logs WHERE asset_id = ?");
$check->bind_param("i", $asset_id);
$check->execute();
$check->bind_result($count);
$check->fetch();
$check->close();

if ($count > 0) {
    header("Location: asset_registry.php?error=Cannot delete – this asset has maintenance history");
    exit();
}

// Get asset details for logging
$stmt = $conn->prepare("SELECT asset_id, name FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    header("Location: asset_registry.php?error=Asset not found");
    exit();
}

// Safe to delete
$stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);

if ($stmt->execute()) {
    log_audit(
        $_SESSION['user_id'],
        'delete_asset',
        "Deleted asset: {$asset['asset_id']} - {$asset['name']}"
    );
    header("Location: asset_registry.php?success=Asset deleted successfully");
} else {
    header("Location: asset_registry.php?error=Error deleting asset: " . $conn->error);
}

$stmt->close();
exit();