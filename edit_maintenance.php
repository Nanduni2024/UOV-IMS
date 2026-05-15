<?php
// edit_maintenance.php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Edit Maintenance';
$success_msg = '';
$error_msg   = '';

$maintenance_id = (int)($_GET['id'] ?? 0);

if ($maintenance_id <= 0) {
    header("Location: maintenance.php");
    exit();
}

// Fetch existing record
$stmt = $conn->prepare("
    SELECT ml.*, a.name AS asset_name, a.asset_id AS asset_code
    FROM maintenance_logs ml
    LEFT JOIN assets a ON ml.asset_id = a.id
    WHERE ml.id = ?
");
$stmt->bind_param("i", $maintenance_id);
$stmt->execute();
$maintenance = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$maintenance) {
    header("Location: maintenance.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduled_date = trim($_POST['scheduled_date'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $status         = trim($_POST['status'] ?? 'pending');

    if (empty($scheduled_date)) {
        $error_msg = "Scheduled date is required.";
    } elseif (!in_array($status, ['pending','in_progress','completed','overdue'])) {
        $error_msg = "Invalid status.";
    } else {
        $update_stmt = $conn->prepare("
            UPDATE maintenance_logs 
            SET scheduled_date = ?, description = ?, status = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("sssi", $scheduled_date, $description, $status, $maintenance_id);

        if ($update_stmt->execute()) {
            log_audit(
                $_SESSION['user_id'],
                'edit_maintenance',
                "Updated maintenance log ID $maintenance_id (status: $status)"
            );
            $success_msg = "Maintenance record updated successfully!";
            // Refresh data
            $maintenance['scheduled_date'] = $scheduled_date;
            $maintenance['description']    = $description;
            $maintenance['status']         = $status;
        } else {
            $error_msg = "Update failed: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <!-- Same head as other pages -->
    <title><?= htmlspecialchars($page_title) ?> | UOV IMS</title>
    <!-- tailwind, fonts, etc. -->
</head>
<body class="min-h-screen bg-background-dark text-white flex flex-col">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold">Edit Maintenance</h1>
            <p class="text-slate-400 mt-1">Update scheduled maintenance details</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="mb-6 p-4 bg-emerald-900/40 border border-emerald-700/50 rounded-xl text-emerald-300 flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl">check_circle</span>
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="mb-6 p-4 bg-red-900/40 border border-red-700/50 rounded-xl text-red-300 flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl">error</span>
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-surface rounded-2xl border border-border-slate shadow-xl p-6 md:p-8">
            <div class="mb-6 pb-4 border-b border-slate-700">
                <h3 class="text-lg font-semibold">Asset Information</h3>
                <p class="text-slate-300 mt-1">
                    <?= htmlspecialchars($maintenance['asset_name'] ?? '—') ?>
                    <span class="text-slate-500 text-sm">(<?= htmlspecialchars($maintenance['asset_code'] ?? '—') ?>)</span>
                </p>
            </div>

            <form method="POST" class="space-y-6">

                <!-- Scheduled Date -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Scheduled Date *</label>
                    <input type="date" name="scheduled_date" required
                           value="<?= htmlspecialchars($maintenance['scheduled_date'] ?? '') ?>"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-primary focus:ring-2 focus:ring-primary/30">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Description / Notes</label>
                    <textarea name="description" rows="5"
                              class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-primary focus:ring-2 focus:ring-primary/30"
                    ><?= htmlspecialchars($maintenance['description'] ?? '') ?></textarea>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Status *</label>
                    <select name="status" required
                            class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/30">
                        <option value="pending"     <?= $maintenance['status'] === 'pending'     ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $maintenance['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed"   <?= $maintenance['status'] === 'completed'   ? 'selected' : '' ?>>Completed</option>
                        <option value="overdue"     <?= $maintenance['status'] === 'overdue'     ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" class="flex-1 py-3 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg transition flex items-center justify-center gap-2 shadow-md">
                        <span class="material-symbols-outlined">save</span>
                        Save Changes
                    </button>
                    <a href="maintenance.php" class="flex-1 py-3 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition text-center">
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