<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
// Check if user is admin - if not, they can only view
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
$page_title = 'User Management';
include 'header.php';
include 'sidebar.php';

// Handle user addition (only for admins)
$success_msg = '';
$error_msg = '';

// Check for redirect messages
if (isset($_GET['success'])) {
    $success_msg = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_msg = htmlspecialchars($_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    if (!$is_admin) {
        $error_msg = "Only admins can add users!";
    } else {
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $conn->real_escape_string($_POST['role']);

        // Check if email already exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $error_msg = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            
            if ($stmt->execute()) {
                $success_msg = "User added successfully!";
                log_audit($_SESSION['user_id'], 'add_user', "Added user: $username");
            } else {
                $error_msg = "Error adding user: " . $stmt->error;
            }
        }
    }
}

// Fetch all users
$result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$total_users = $result->num_rows;

// Get user statistics
$stats = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stat_data = [];
while ($row = $stats->fetch_assoc()) {
    $stat_data[$row['role']] = $row['count'];
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">User Management</h1>
        <p class="text-slate-400">Manage system users and assign roles</p>
        <?php if ($is_admin): ?>
            <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs bg-red-500/20 text-red-400">Admin Access Enabled</span>
        <?php else: ?>
            <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs bg-slate-500/20 text-slate-400">View Only</span>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">people</span>
                <h4 class="font-bold text-white">Total Users</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?php echo $total_users; ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-red-500 text-2xl">admin_panel_settings</span>
                <h4 class="font-bold text-white">Admins</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?php echo $stat_data['admin'] ?? 0; ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-blue-500 text-2xl">badge</span>
                <h4 class="font-bold text-white">Clerks</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?php echo $stat_data['clerk'] ?? 0; ?></h2>
        </div>
        <div class="bg-surface p-6 rounded-xl border border-border-slate shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-slate-500 text-2xl">person</span>
                <h4 class="font-bold text-white">Users</h4>
            </div>
            <h2 class="text-4xl font-extrabold text-white"><?php echo $stat_data['user'] ?? 0; ?></h2>
        </div>
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

    <!-- Add User Form (Admin Only) -->
    <?php if ($is_admin): ?>
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden mb-8">
        <div class="p-6 border-b border-border-slate">
            <h2 class="text-xl font-bold text-white">Add New User</h2>
        </div>
        <form method="POST" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Username</label>
                    <input type="text" name="username" required 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Email</label>
                    <input type="email" name="email" required 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Password</label>
                    <input type="password" name="password" required 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Role</label>
                    <select name="role" required 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white border border-slate-700 focus:outline-none focus:border-primary">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="add_user" value="1">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span>
                Add User
            </button>
        </form>
    </div>    <?php else: ?>
    <div class="bg-blue-500/20 text-blue-400 border border-blue-500/50 rounded-lg p-4 mb-8">
        <span class="material-symbols-outlined">info</span>
        You must be an admin to add users.
    </div>
    <?php endif; ?>
    <!-- Users Table -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden">
        <div class="p-6 border-b border-border-slate">
            <h2 class="text-xl font-bold text-white">All Users (<?php echo $total_users; ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-800 text-slate-400 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Username</th>
                        <th class="px-6 py-3 font-semibold">Email</th>
                        <th class="px-6 py-3 font-semibold">Role</th>
                        <th class="px-6 py-3 font-semibold">Joined</th>
                        <th class="px-6 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-white">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b border-border-slate hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                <?php 
                                if ($row['role'] == 'admin') echo 'bg-red-500/20 text-red-400';
                                elseif ($row['role'] == 'clerk') echo 'bg-blue-500/20 text-blue-400';
                                else echo 'bg-slate-500/20 text-slate-400';
                                ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-6 py-4 flex gap-2">
                                <?php if ($is_admin): ?>
                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="text-blue-400 hover:text-blue-300 text-sm">Edit</a>
                                    <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-300 text-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                <?php else: ?>
                                    <span class="text-slate-500 text-sm">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>