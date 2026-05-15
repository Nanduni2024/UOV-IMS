<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
$page_title = 'Audit Logs';
include 'header.php';
include 'sidebar.php';

// Fetch logs with filters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

$query = "SELECT al.*, u.username FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id";
$conditions = [];
if ($action) {
    $action = $conn->real_escape_string($action);
    $conditions[] = "al.action = '$action'";
}
if ($user_id) {
    $user_id = $conn->real_escape_string($user_id);
    $conditions[] = "al.user_id = '$user_id'";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY al.timestamp DESC";
$result = $conn->query($query);
$total_logs = $result->num_rows;

// Get unique actions
$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Audit Logs</h1>
        <p class="text-slate-400">Track all system activities and user actions</p>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
        <form method="GET" class="flex gap-2">
            <select name="action" 
                class="px-4 py-2 rounded-lg bg-slate-800 text-white border border-slate-700 focus:outline-none focus:border-primary">
                <option value="">All Actions</option>
                <?php 
                $actions->data_seek(0);
                while ($row = $actions->fetch_assoc()): 
                ?>
                    <option value="<?php echo htmlspecialchars($row['action']); ?>" 
                        <?php echo $action == $row['action'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['action']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition">Filter</button>
            <a href="audit_logs.php" class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition">Clear</a>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
        <div class="p-6 border-b border-border-slate">
            <h2 class="text-xl font-bold text-white">Activity Log (<?php echo $total_logs; ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-800 text-slate-400 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Timestamp</th>
                        <th class="px-6 py-3 font-semibold">User</th>
                        <th class="px-6 py-3 font-semibold">Action</th>
                        <th class="px-6 py-3 font-semibold">Details</th>
                    </tr>
                </thead>
                <tbody class="text-white">
                    <?php if ($total_logs > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b border-border-slate hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 text-slate-400">
                                    <div class="font-semibold"><?php echo date('M d, Y', strtotime($row['timestamp'])); ?></div>
                                    <div class="text-xs"><?php echo date('H:i:s', strtotime($row['timestamp'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs bg-blue-500/20 text-blue-400">
                                        <?php echo htmlspecialchars($row['username'] ?? 'System'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                    <?php 
                                    $action_name = $row['action'];
                                    if (strpos($action_name, 'add') !== false) echo 'bg-emerald-500/20 text-emerald-400';
                                    elseif (strpos($action_name, 'delete') !== false) echo 'bg-red-500/20 text-red-400';
                                    elseif (strpos($action_name, 'edit') !== false) echo 'bg-amber-500/20 text-amber-400';
                                    else echo 'bg-slate-500/20 text-slate-400';
                                    ?>">
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-300 max-w-md truncate">
                                    <?php echo htmlspecialchars($row['details']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                No audit logs found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>