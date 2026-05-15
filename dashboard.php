<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Dashboard';
include 'header.php';
include 'sidebar.php';

/* ======================
   STATS
====================== */
$asset_count = $conn->query("SELECT COUNT(*) FROM assets")->fetch_row()[0] ?? 0;
$maintenance_count = $conn->query("SELECT COUNT(*) FROM maintenance_logs WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$overdue_count = $conn->query("SELECT COUNT(*) FROM maintenance_logs WHERE status = 'overdue'")->fetch_row()[0] ?? 0;

/* ======================
   TOTAL ASSET VALUE
====================== */
$asset_value_result = $conn->query("SELECT COALESCE(SUM(unit_price), 0) AS total_value FROM assets");

if ($asset_value_result) {
    $asset_value_row = $asset_value_result->fetch_assoc();
    $total_asset_value = $asset_value_row['total_value'] ?? 0;
    $asset_value_result->free();
} else {
    $total_asset_value = 0;
}

/* ======================
   RECENT ASSETS
====================== */
$recent_assets = $conn->query("
    SELECT asset_id, name, category, location, status 
    FROM assets 
    ORDER BY created_at DESC 
    LIMIT 5
");

/* ======================
   INVENTORY LEVELS
====================== */
$inventory_levels = $conn->query("
    SELECT category, 
           COUNT(*) as count, 
           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM assets 
    GROUP BY category
");

/* ======================
   LAST 2 DAYS MAINTENANCE CHART
====================== */
$last_2_days_result = $conn->query("
    SELECT DATE(scheduled_date) AS date,
           COUNT(*) AS count
    FROM maintenance_logs
    WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
      AND scheduled_date IS NOT NULL
    GROUP BY DATE(scheduled_date)
");

// Prepare chart data for today + yesterday
$last_2_days_data = [];
$max_count = 0;

// Default 0 values
for ($i = 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last_2_days_data[$date] = 0;
}

// Fill real DB data
if ($last_2_days_result) {
    while ($row = $last_2_days_result->fetch_assoc()) {
        $last_2_days_data[$row['date']] = (int)$row['count'];
        $max_count = max($max_count, $row['count']);
    }
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark w-full">
    <!-- Hero / Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">inventory_2</span>
                <h4 class="font-bold text-white">Total Assets</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= number_format($asset_count) ?></h2>
            <p class="text-xs text-slate-400 mt-2">+12% from last month</p>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-amber-500 text-2xl">build</span>
                <h4 class="font-bold text-white">Pending Maintenance</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= number_format($maintenance_count) ?></h2>
            <p class="text-xs text-slate-400 mt-2">3 urgent</p>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-red-500 text-2xl">warning</span>
                <h4 class="font-bold text-white">Overdue Tasks</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?= number_format($overdue_count) ?></h2>
            <p class="text-xs text-slate-400 mt-2">-5% from last week</p>
        </div>

        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-emerald-500 text-2xl">trending_up</span>
                <h4 class="font-bold text-white">Asset Value</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white">
                Rs. <?= number_format($total_asset_value, 0) ?>
            </h2>
            <p class="text-xs text-slate-400 mt-2">
                Estimated total (LKR)
                <?php if ($total_asset_value >= 1000000): ?>
                    <span class="text-emerald-400"> • <?= number_format($total_asset_value / 1000000, 1) ?>M</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Inventory Levels -->
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">inventory_2</span>
                Inventory Levels by Category
            </h4>
            <div class="space-y-3">
                <?php if ($inventory_levels && $inventory_levels->num_rows > 0): ?>
                    <?php while ($row = $inventory_levels->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                            <div>
                                <p class="text-white font-medium"><?= htmlspecialchars($row['category'] ?: 'Uncategorized') ?></p>
                                <p class="text-xs text-slate-400">
                                    <?= number_format($row['active_count']) ?> active of <?= number_format($row['count']) ?> total
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-primary"><?= number_format($row['count']) ?></span>
                                <p class="text-xs text-emerald-400">
                                    <?= $row['count'] > 0 ? round(($row['active_count'] / $row['count']) * 100) : 0 ?>% active
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-slate-400 text-sm">No inventory data available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usage Trends – Last 2 Days -->
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">trending_up</span>
                Scheduled Maintenance – Last Days
            </h4>
            <div class="space-y-3">
                <?php if ($last_2_days_data): ?>
                    <?php foreach ($last_2_days_data as $date => $count): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-400 w-20">
                                <?= date('M d', strtotime($date)) ?>
                            </span>
                            <div class="flex-1">
                                <div class="w-full bg-slate-800 rounded h-6 relative overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-6 rounded transition-all"
                                         style="width: <?= $max_count > 0 ? ($count / $max_count) * 100 : 0 ?>%"></div>
                                </div>
                            </div>
                            <span class="text-white font-semibold w-8 text-right"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-400 text-sm">No scheduled maintenance in the last days</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Assets Table -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden mb-8">
        <div class="p-6 border-b border-border-slate">
            <h4 class="font-bold text-white">Recent Assets</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[600px]">
                <thead class="bg-slate-800 text-slate-400">
                    <tr>
                        <th class="px-6 py-3">Asset ID</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-white">
                    <?php if ($recent_assets && $recent_assets->num_rows > 0): ?>
                        <?php while ($row = $recent_assets->fetch_assoc()): ?>
                            <tr class="border-b border-border-slate hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['asset_id']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($row['category'] ?: '-') ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($row['location'] ?: '-') ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php
                                        if ($row['status'] == 'active')      echo 'bg-emerald-500/20 text-emerald-400';
                                        elseif ($row['status'] == 'maintenance') echo 'bg-amber-500/20 text-amber-400';
                                        else                                 echo 'bg-red-500/20 text-red-400';
                                        ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                No recent assets found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
