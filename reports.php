<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Reports';
include 'header.php';
include 'sidebar.php';

// Get asset statistics by category
$category_report = $conn->query("
    SELECT category, 
           COUNT(*) as count, 
           SUM(unit_price) as total_value 
    FROM assets 
    GROUP BY category
") or die("Category query error: " . $conn->error);

// Get asset status breakdown
$status_report = $conn->query("
    SELECT status, 
           COUNT(*) as count 
    FROM assets 
    GROUP BY status
") or die("Status query error: " . $conn->error);

// Total asset value (for summary card)
$total_value_query = $conn->query("SELECT SUM(unit_price) as total FROM assets");
$total_value = $total_value_query ? ($total_value_query->fetch_assoc()['total'] ?? 0) : 0;

// Active assets count
$active_query = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'active'");
$active_count = $active_query ? ($active_query->fetch_assoc()['count'] ?? 0) : 0;

// Pending maintenance count
$pending_query = $conn->query("SELECT COUNT(*) as count FROM maintenance_logs WHERE status = 'pending'");
$pending_count = $pending_query ? ($pending_query->fetch_assoc()['count'] ?? 0) : 0;
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Reports & Analytics</h1>
        <p class="text-slate-400">View detailed reports and analytics about your assets</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">inventory_2</span>
                <h4 class="font-bold text-white">Total Assets Value</h4>
            </div>
            <h2 class="text-3xl font-extrabold text-white">
                Rs. <?= number_format($total_value, 2) ?>
            </h2>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-500 text-2xl">check_circle</span>
                <h4 class="font-bold text-white">Active Assets</h4>
            </div>
            <h2 class="text-3xl font-extrabold text-white"><?= number_format($active_count) ?></h2>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-amber-500 text-2xl">build</span>
                <h4 class="font-bold text-white">Pending Maintenance</h4>
            </div>
            <h2 class="text-3xl font-extrabold text-white"><?= number_format($pending_count) ?></h2>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assets by Category -->
        <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
            <div class="p-6 border-b border-border-slate">
                <h3 class="text-xl font-bold text-white">Assets by Category</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-800 text-slate-400">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Category</th>
                            <th class="px-6 py-3 font-semibold">Count</th>
                            <th class="px-6 py-3 font-semibold">Total Value (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <?php if ($category_report && $category_report->num_rows > 0): ?>
                            <?php while ($row = $category_report->fetch_assoc()): ?>
                                <tr class="border-b border-border-slate hover:bg-slate-800/50">
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['category'] ?: 'Uncategorized') ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-medium">
                                            <?= number_format($row['count']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold">
                                        <?= number_format($row['total_value'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                    No category data available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assets by Status -->
        <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
            <div class="p-6 border-b border-border-slate">
                <h3 class="text-xl font-bold text-white">Assets by Status</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-800 text-slate-400">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">Count</th>
                            <th class="px-6 py-3 font-semibold">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <?php 
                        $total_assets = $conn->query("SELECT COUNT(*) as cnt FROM assets")->fetch_assoc()['cnt'] ?? 0;
                        if ($status_report && $status_report->num_rows > 0): 
                            while ($row = $status_report->fetch_assoc()): 
                                $percent = $total_assets > 0 ? round(($row['count'] / $total_assets) * 100, 1) : 0;
                        ?>
                                <tr class="border-b border-border-slate hover:bg-slate-800/50">
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            <?php 
                                            if ($row['status'] == 'active')      echo 'bg-emerald-500/20 text-emerald-400';
                                            elseif ($row['status'] == 'maintenance') echo 'bg-amber-500/20 text-amber-400';
                                            else                                 echo 'bg-red-500/20 text-red-400';
                                            ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium"><?= number_format($row['count']) ?></td>
                                    <td class="px-6 py-4"><?= $percent ?>%</td>
                                </tr>
                        <?php 
                            endwhile; 
                        else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                    No status data available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>