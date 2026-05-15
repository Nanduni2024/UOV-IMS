<?php
// change_password.php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error   = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password     = $_POST['old_password']     ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // Fetch current hashed password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($old_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $user_id);

            if ($update_stmt->execute()) {
                // Log the action
                log_audit(
                    $user_id,
                    'password_change',
                    'User changed their password successfully'
                );

                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password. Please try again.";
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Change Password – <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?> | UOV IMS</title>

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
    </style>
</head>
<body class="min-h-screen font-display text-white flex flex-col">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-8 overflow-y-auto">
    <div class="max-w-lg mx-auto">

        <!-- Page Title -->
        <div class="mb-8 text-center md:text-left">
            <h1 class="text-3xl md:text-4xl font-bold">Change Password</h1>
            <p class="text-slate-400 mt-2">Update your account password</p>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-900/30 border border-emerald-700/50 rounded-xl text-emerald-300 flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl">check_circle</span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-900/30 border border-red-700/50 rounded-xl text-red-300 flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card rounded-2xl border border-border-slate shadow-2xl p-6 md:p-10">
            <form method="POST" class="space-y-6">

                <!-- Current Password -->
                <div>
                    <label for="old_password" class="block text-sm font-medium text-slate-300 mb-2">
                        Current Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="old_password" 
                            name="old_password" 
                            required 
                            class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition"
                            placeholder="••••••••"
                        >
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePassword('old_password')">
                            visibility
                        </span>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label for="new_password" class="block text-sm font-medium text-slate-300 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required 
                            minlength="8"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition"
                            placeholder="••••••••"
                        >
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePassword('new_password')">
                            visibility
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Minimum 8 characters</p>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-300 mb-2">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            minlength="8"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition"
                            placeholder="••••••••"
                        >
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePassword('confirm_password')">
                            visibility
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full py-3 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg transition shadow-lg flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Back Link -->
        <div class="mt-6 text-center">
            <a href="profile.php" class="text-slate-400 hover:text-primary transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Profile
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}
</script>

</body>
</html>