<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");
// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: user_management.php?error=Admin access required");
    exit;
}
$page_title = 'Edit User';
include 'header.php';
include 'sidebar.php';

$success_msg = '';
$error_msg = '';
$user = null;

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user details
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $error_msg = "User not found!";
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password'];

    if (!empty($password)) {
        // Update with new password
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $username, $email, $password, $role, $user_id);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    }

    if ($stmt->execute()) {
        $success_msg = "User updated successfully!";
        log_audit($_SESSION['user_id'], 'edit_user', "Updated user: $username");
        // Refresh user data
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error_msg = "Error updating user: " . $stmt->error;
    }
}
?>

<main class="flex-1 p-8 overflow-y-auto bg-background-dark">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Edit User</h1>
        <p class="text-slate-400">Modify user information and permissions</p>
    </div>

    <!-- Messages -->
    <?php if ($success_msg): ?>
        <div class="mb-6 p-4 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/50 flex justify-between items-center">
            <span><?php echo $success_msg; ?></span>
            <a href="user_management.php" class="text-sm underline">Back to Users</a>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-500/20 text-red-400 border border-red-500/50">
            <?php echo $error_msg; ?>
            <br><a href="user_management.php" class="text-sm underline">Back to Users</a>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
    <!-- Edit User Form -->
    <div class="bg-surface rounded-xl border border-border-slate shadow-lg overflow-hidden max-w-2xl">
        <div class="p-6 border-b border-border-slate">
            <h2 class="text-xl font-bold text-white">User Information</h2>
        </div>
        <form method="POST" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Username</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>"
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Email</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>"
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">New Password (leave empty to keep current)</label>
                    <input type="password" name="password" 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white placeholder-slate-500 border border-slate-700 focus:outline-none focus:border-primary"
                        placeholder="Enter new password or leave blank">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-white mb-2">Role</label>
                    <select name="role" required 
                        class="w-full px-4 py-2 rounded-lg bg-slate-800 text-white border border-slate-700 focus:outline-none focus:border-primary">
                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="clerk" <?php echo $user['role'] == 'clerk' ? 'selected' : ''; ?>>Clerk</option>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="update_user" value="1">
            
            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Save Changes
                </button>
                <a href="user_management.php" class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>