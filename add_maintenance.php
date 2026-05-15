<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Schedule Maintenance';
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id       = (int)($_POST['asset_id'] ?? 0);
    $scheduled_date = trim($_POST['scheduled_date'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $status         = trim($_POST['status'] ?? 'pending');

    // Validation
    if ($asset_id <= 0) {
        $error_msg = "Please select a valid asset.";
    } elseif (empty($scheduled_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduled_date)) {
        $error_msg = "Please select a valid scheduled date.";
    } elseif (!in_array($status, ['pending', 'in_progress', 'completed', 'overdue'])) {
        $error_msg = "Invalid status selected.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO maintenance_logs 
            (asset_id, description, scheduled_date, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("isss", $asset_id, $description, $scheduled_date, $status);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            log_audit(
                $_SESSION['user_id'],
                'maintenance_scheduled',
                "Created maintenance record #$new_id for asset #$asset_id"
            );
            $success_msg = "Maintenance scheduled successfully! (Record #$new_id)";
            // Clear form
            $_POST = [];
        } else {
            $error_msg = "Failed to save record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Active assets dropdown
$assets = $conn->query("
    SELECT id, asset_id, name 
    FROM assets 
    WHERE status = 'active' 
    ORDER BY name
") or die($conn->error);
?>

<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <!-- Your usual head content (tailwind, fonts, etc.) -->
    <title><?= htmlspecialchars($page_title) ?> | UOV IMS</title>
    <!-- ... -->
</head>
<body class="min-h-screen bg-background-dark text-white flex flex-col">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto">

        <h1 class="text-3xl md:text-4xl font-bold mb-2">Schedule Maintenance</h1>
        <p class="text-slate-400 mb-8">Add a new maintenance task</p>

        <?php if ($success_msg): ?>
            <div class="mb-6 p-4 bg-emerald-900/40 border border-emerald-700 rounded-xl text-emerald-300">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="mb-6 p-4 bg-red-900/40 border border-red-700 rounded-xl text-red-300">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-surface rounded-2xl border border-border-slate shadow-xl p-6 md:p-8">
            <form method="POST" class="space-y-6">
                <!-- Asset -->
                <div>
                    <label class="block text-sm font-medium mb-2">Asset *</label>
                    <select name="asset_id" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-lg">
                        <option value="">Choose asset...</option>
                        <?php while ($a = $assets->fetch_assoc()): ?>
                            <option value="<?= $a['id'] ?>">
                                <?= htmlspecialchars($a['asset_id'] . ' – ' . $a['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Scheduled Date -->
                <div>
                    <label class="block text-sm font-medium mb-2">Scheduled Date *</label>
                    <input type="date" name="scheduled_date" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-lg">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium mb-2">Description / Work Required</label>
                    <textarea name="description" rows="4" class="w-full p-3 bg-slate-800 border border-slate-700 rounded-lg" placeholder="Enter details..."></textarea>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium mb-2">Initial Status *</label>
                    <select name="status" required class="w-full p-3 bg-slate-800 border border-slate-700 rounded-lg">
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 py-3 bg-primary hover:bg-blue-600 rounded-lg font-medium transition">
                        Schedule Maintenance
                    </button>
                    <a href="maintenance.php" class="flex-1 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg text-center font-medium transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
</body>
</html>