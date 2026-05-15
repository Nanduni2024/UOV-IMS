<?php
// profile.php

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username   = trim($_POST['username'] ?? '');
    $new_email      = trim($_POST['email'] ?? '');
    $new_department = trim($_POST['department'] ?? '');

    // Basic validation
    if (empty($new_username)) {
        $error = "Username is required.";
    } elseif (empty($new_email)) {
        $error = "Email is required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email is already used by another user
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $new_email, $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "This email is already in use by another account.";
        }
        $check->close();

        if (!$error) {
            // Update user
            $stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, department = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $new_username, $new_email, $new_department, $user_id);

            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Update session if you store these values there (optional)
                $_SESSION['username']   = $new_username;
                $_SESSION['email']      = $new_email;
                $_SESSION['department'] = $new_department;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch fresh/current user data
$stmt = $conn->prepare("
    SELECT username, email, role, department, status, created_at
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

// Fallback values
$username   = $user['username']   ?? $_SESSION['username']   ?? 'User';
$email      = $user['email']      ?? $_SESSION['email']      ?? '—';
$role       = $user['role']       ?? $_SESSION['role']       ?? 'user';
$department = $user['department'] ?? $_SESSION['department'] ?? null;
$status     = $user['status']     ?? 'active';
$joined     = $user['created_at'] ?? date('Y-m-d');

// Activity count
$activity_count = 0;
if ($user_id > 0) {
    $act_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM audit_logs WHERE user_id = ?");
    $act_stmt->bind_param("i", $user_id);
    $act_stmt->execute();
    $activity_count = $act_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $act_stmt->close();
}
?>

<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile – <?= htmlspecialchars($username) ?> | UOV IMS</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="icon" href="assets/uov-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#137fec",
                        "background-dark": "#101922",
                        surface: "#1a2332",
                        "border-slate": "#233648",
                    },
                    fontFamily: { display: ["Manrope"] }
                }
            }
        }
    </script>

    <style>
        body { background: linear-gradient(135deg, #0f1419, #1a2332 50%, #0f1419); }
        .card { background: linear-gradient(135deg, rgba(30,41,59,0.8), rgba(15,23,42,0.6)); backdrop-filter: blur(12px); }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="min-h-screen font-display text-white flex flex-col">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-8 overflow-y-auto">
    <div class="max-w-5xl mx-auto space-y-8">

        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold">My Profile</h1>
            <p class="text-slate-400 mt-1">Manage your personal information</p>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-900/30 border border-emerald-700 text-emerald-300 px-6 py-4 rounded-xl mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-900/30 border border-red-700 text-red-300 px-6 py-4 rounded-xl mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Profile summary card -->
            <div class="lg:col-span-1 space-y-6">

                <div class="card rounded-2xl border border-border-slate shadow-2xl overflow-hidden">
                    <div class="p-8 text-center">
                        <div class="w-28 h-28 mx-auto rounded-full bg-gradient-to-br from-primary to-cyan-600 flex items-center justify-center text-5xl font-bold shadow-xl mb-5 ring-4 ring-primary/20">
                            <?= strtoupper($username[0] ?? 'U') ?>
                        </div>
                        <h2 class="text-2xl font-bold mb-1"><?= htmlspecialchars($username) ?></h2>
                        <p class="text-slate-300 mb-5"><?= htmlspecialchars($email) ?></p>

                        <span class="inline-block px-5 py-1.5 rounded-full text-sm font-medium
                            <?php
                            if ($role === 'admin')     echo 'bg-violet-600/30 text-violet-300';
                            elseif ($role === 'clerk') echo 'bg-amber-600/30 text-amber-300';
                            else                       echo 'bg-blue-600/30 text-blue-300';
                            ?>">
                            <?= ucfirst($role) ?>
                        </span>
                    </div>

                    <div class="border-t border-slate-700/70 px-6 py-5 text-sm divide-y divide-slate-700/50">
                        <div class="flex justify-between py-2.5">
                            <span class="text-slate-400">Department</span>
                            <span><?= htmlspecialchars($department ?: '—') ?></span>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <span class="text-slate-400">Status</span>
                            <span class="<?= $status === 'active' ? 'text-emerald-400' : 'text-red-400' ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <span class="text-slate-400">Member since</span>
                            <span><?= date('M Y', strtotime($joined)) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick stats -->
                <div class="card rounded-2xl border border-border-slate p-6">
                    <h3 class="text-lg font-semibold mb-5 flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-primary">insights</span>
                        Activity
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-300">Actions logged</span>
                            <span class="font-semibold"><?= number_format($activity_count) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Personal information -->
                <div class="card rounded-2xl border border-border-slate p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7">
                        <h3 class="text-xl font-bold flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">person</span>
                            Personal Information
                        </h3>
                        <button id="editProfileBtn" class="px-5 py-2 bg-primary/20 hover:bg-primary/30 text-primary rounded-lg text-sm font-medium transition">
                            Edit Profile
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5 uppercase tracking-wide">Full Name</label>
                            <div class="bg-slate-800/40 border border-slate-700 rounded-lg px-4 py-3">
                                <?= htmlspecialchars($username) ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5 uppercase tracking-wide">Email</label>
                            <div class="bg-slate-800/40 border border-slate-700 rounded-lg px-4 py-3">
                                <?= htmlspecialchars($email) ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5 uppercase tracking-wide">Department</label>
                            <div class="bg-slate-800/40 border border-slate-700 rounded-lg px-4 py-3">
                                <?= htmlspecialchars($department ?: 'Not set') ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5 uppercase tracking-wide">Role</label>
                            <div class="bg-slate-800/40 border border-slate-700 rounded-lg px-4 py-3">
                                <?= ucfirst($role) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security section -->
                <div class="card rounded-2xl border border-border-slate p-6 md:p-8">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">shield</span>
                        Security
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <a href="change_password.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm transition">
                                <span class="material-symbols-outlined">lock_reset</span>
                                Change Password
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Logout -->
                <div class="card rounded-2xl border border-red-900/30 bg-gradient-to-r from-red-950/10 p-6 md:p-8">
                    <a href="logout.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600/20 hover:bg-red-600/30 text-red-300 rounded-lg transition text-sm">
                        <span class="material-symbols-outlined">logout</span>
                        Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Profile Modal -->
<div id="editModal" class="modal fixed inset-0 bg-black/70 items-center justify-center z-50">
    <div class="card w-full max-w-md mx-4 rounded-2xl border border-border-slate shadow-2xl overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary">edit</span>
                    Edit Profile
                </h3>
                <button id="closeModal" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="update_profile" value="1">

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($department ?? '') ?>"
                           class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                           placeholder="e.g. ICT, Administration">
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" id="cancelModal" class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-blue-600 rounded-lg transition font-medium">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const editBtn = document.getElementById('editProfileBtn');
    const modal = document.getElementById('editModal');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelModal');

    editBtn.addEventListener('click', () => {
        modal.classList.add('active');
    });

    const closeModal = () => {
        modal.classList.remove('active');
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Close when clicking outside modal content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
</script>

<?php include 'footer.php'; ?>

</body>
</html>