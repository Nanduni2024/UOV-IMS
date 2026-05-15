<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Maintenance';
include 'header.php';
include 'sidebar.php';

// Fetch maintenance logs with status filter
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$query = "
    SELECT ml.*, 
           a.name AS asset_name, 
           a.asset_id 
    FROM maintenance_logs ml 
    LEFT JOIN assets a ON ml.asset_id = a.id
";

$where = [];
$params = [];
$types = "";

if ($status !== '') {
    $where[] = "ml.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY ml.scheduled_date DESC";   // ← FIXED HERE

$stmt = $conn->prepare($query);

if ($stmt) {
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total_logs = $result->num_rows;
} else {
    $result = null;
    $total_logs = 0;
    error_log("Prepare failed: " . $conn->error);
}

// Get status statistics
$stats = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM maintenance_logs 
    GROUP BY status
");

$stat_data = ['pending' => 0, 'overdue' => 0, 'completed' => 0];

if ($stats) {
    while ($row = $stats->fetch_assoc()) {
        $stat_data[$row['status']] = (int)$row['count'];
    }
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Maintenance Management</h1>
        <p class="text-slate-400">Track and manage asset maintenance schedules</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-blue-500 text-2xl">schedule</span>
                <h4 class="font-bold text-white">Pending</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= $stat_data['pending'] ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-amber-500 text-2xl">warning</span>
                <h4 class="font-bold text-white">Overdue</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= $stat_data['overdue'] ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-500 text-2xl">check_circle</span>
                <h4 class="font-bold text-white">Completed</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= $stat_data['completed'] ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">build</span>
                <h4 class="font-bold text-white">In Progress</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= $stat_data['in_progress'] ?? 0 ?></h2>
        </div>
    </div>

    <!-- Filter and Actions -->
    <div class="flex flex-wrap gap-4 mb-6 items-center">
        <div class="flex flex-wrap gap-2">
            <a href="maintenance.php" 
               class="px-4 py-2 rounded-lg transition <?= !$status ? 'bg-primary text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white' ?>">
                All
            </a>
            <a href="?status=pending" 
               class="px-4 py-2 rounded-lg transition <?= $status === 'pending' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white' ?>">
                Pending
            </a>
            <a href="?status=overdue" 
               class="px-4 py-2 rounded-lg transition <?= $status === 'overdue' ? 'bg-amber-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white' ?>">
                Overdue
            </a>
            <a href="?status=completed" 
               class="px-4 py-2 rounded-lg transition <?= $status === 'completed' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white' ?>">
                Completed
            </a>
        </div>

        <a href="add_maintenance.php" 
           class="ml-auto px-6 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition flex items-center gap-2 shadow-md">
            <span class="material-symbols-outlined text-sm">add</span>
            Schedule Maintenance
        </a>
    </div>

    <!-- Maintenance Logs Table -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
        <div class="p-6 border-b border-border-slate">
            <h2 class="text-xl font-bold text-white">
                Maintenance Logs (<?= number_format($total_logs) ?>)
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[900px]">
                <thead class="bg-slate-800 text-slate-400 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Asset</th>
                        <th class="px-6 py-3 font-semibold">Scheduled Date</th>
                        <th class="px-6 py-3 font-semibold">Description</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-white divide-y divide-border-slate">
                    <?php if ($result && $total_logs > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?= htmlspecialchars($row['asset_name'] ?? '—') ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($row['asset_id'] ?? '—') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    if (!empty($row['scheduled_date'])) {
                                        echo date('M d, Y', strtotime($row['scheduled_date']));
                                    } else {
                                        echo '<span class="text-slate-500">—</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 max-w-md truncate">
                                    <?= htmlspecialchars(substr($row['description'] ?? '', 0, 120)) ?>
                                    <?= strlen($row['description'] ?? '') > 120 ? '...' : '' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        <?php 
                                        switch ($row['status'] ?? 'pending') {
                                            case 'pending':   echo 'bg-blue-500/20 text-blue-400'; break;
                                            case 'overdue':   echo 'bg-red-500/20 text-red-400';   break;
                                            case 'completed': echo 'bg-emerald-500/20 text-emerald-400'; break;
                                            default:          echo 'bg-amber-500/20 text-amber-400';
                                        }
                                        ?>">
                                        <?= ucfirst($row['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="edit_maintenance.php?id=<?= $row['id'] ?>" 
                                       class="text-blue-400 hover:text-blue-300 text-sm font-medium">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No maintenance records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>