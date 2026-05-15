<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$page_title = 'Asset Registry';
include 'header.php';
include 'sidebar.php';

// Get messages from URL
$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Fetch assets with search/filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM assets";
if ($search) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE asset_id LIKE '%$search%' OR name LIKE '%$search%' OR category LIKE '%$search%'";
}
$query .= " ORDER BY created_at DESC";
$result = $conn->query($query);
$total_assets = $result->num_rows;
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Asset Registry</h1>
        <p class="text-slate-400">Manage and track all assets in the system</p>
    </div>

    <!-- Messages -->
    <?php if ($success_msg): ?>
        <div class="mb-6 p-4 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/50">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-500/20 text-red-400 border border-red-500/50">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <!-- Search and Actions -->
    <div class="flex gap-4 mb-6">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" placeholder="Search assets..." value="<?php echo htmlspecialchars($search); ?>" 
                class="flex-1 px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition">Search</button>
        </form>
        <a href="add_asset.php" class="px-6 py-2 bg-emerald-500 text-white rounded-lg hover:bg-opacity-90 transition flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span>
            Add Asset
        </a>
    </div>

    <!-- Assets Table -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
        <div class="p-6 border-b border-border-slate flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">All Assets (<?php echo $total_assets; ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-800 text-slate-400 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Asset ID</th>
                        <th class="px-6 py-3 font-semibold">Name</th>
                        <th class="px-6 py-3 font-semibold">Category</th>
                        <th class="px-6 py-3 font-semibold">Location</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Condition</th>
                        <th class="px-6 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-white">
                    <?php if ($total_assets > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b border-border-slate hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-slate-300"><?php echo htmlspecialchars($row['asset_id']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs bg-blue-500/20 text-blue-400">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['location']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    <?php 
                                    if ($row['status'] == 'active') echo 'bg-emerald-500/20 text-emerald-400';
                                    elseif ($row['status'] == 'maintenance') echo 'bg-amber-500/20 text-amber-400';
                                    else echo 'bg-red-500/20 text-red-400';
                                    ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs 
                                    <?php 
                                    if ($row['condition'] == 'excellent') echo 'bg-green-500/20 text-green-400';
                                    elseif ($row['condition'] == 'good') echo 'bg-blue-500/20 text-blue-400';
                                    else echo 'bg-orange-500/20 text-orange-400';
                                    ?>">
                                        <?php echo ucfirst($row['condition']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <a href="edit_asset.php?id=<?php echo $row['id']; ?>" class="text-blue-400 hover:text-blue-300 text-sm">Edit</a>
                                    <a href="delete_asset.php?id=<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-300 text-sm">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                No assets found. <a href="add_asset.php" class="text-primary hover:underline">Add one now</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>