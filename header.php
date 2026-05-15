<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo $page_title ?? 'University of Vavuniya IMS'; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="icon" href="assets/uov-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Manrope"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <!-- Add common styles here from your original HTML -->
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-white overflow-hidden flex flex-col h-screen">
    <!-- Top Navigation -->
    <header class="border-b border-slate-700/50 bg-gradient-to-r from-slate-900/50 to-slate-800/50 backdrop-blur-sm px-8 py-4 flex-shrink-0 sticky top-0 z-40">
        <div class="flex items-center justify-between h-16">
            <!-- Logo and Title -->
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-full border-2 border-primary/30 bg-slate-900/50">
                    <img src="assets/uov-logo.png" alt="University of Vavuniya" class="w-12 h-12 object-contain" onerror="this.style.display='none'">
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">University of Vavuniya</h1>
                    <p class="text-xs text-slate-400">Inventory Management System</p>
                </div>
            </div>

            <!-- Center - Search Bar -->
            <div class="flex-1 max-w-md mx-8">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                    <input type="text" placeholder="Search assets, users..." 
                        class="w-full pl-10 pr-4 py-2 rounded-lg bg-slate-800/50 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/30 transition-all text-sm">
                </div>
            </div>

            <!-- Right Side - User Menu -->
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <button class="relative p-2 rounded-lg hover:bg-slate-700/50 transition-colors group">
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-white transition-colors">notifications</span>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Settings -->
                <button class="p-2 rounded-lg hover:bg-slate-700/50 transition-colors group">
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-white transition-colors">settings</span>
                </button>

                <!-- Divider -->
                <div class="w-px h-6 bg-slate-700"></div>

                <!-- User Profile Dropdown -->
                <div class="relative group">
                    <button class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-700/50 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center">
                            <span class="text-xs font-bold">
                                <?php 
                                // Get user initial
                                if (isset($_SESSION['user_id'])) {
                                    global $conn;
                                    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $user = $result->fetch_assoc();
                                    echo isset($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : 'U';
                                } else {
                                    echo 'G';
                                }
                                ?>
                            </span>
                        </div>
                        <span class="text-sm font-medium hidden sm:inline">
                            <?php 
                            if (isset($_SESSION['user_id'])) {
                                echo isset($user['username']) ? htmlspecialchars($user['username']) : 'User';
                            } else {
                                echo 'Guest';
                            }
                            ?>
                        </span>
                        <span class="material-symbols-outlined text-sm text-slate-400 group-hover:text-white transition-colors">expand_more</span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-2 z-50">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="px-4 py-2 border-b border-slate-700">
                                <p class="text-xs text-slate-400">Logged in as</p>
                                <p class="text-sm font-semibold text-white">
                                    <?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'User'; ?>
                                </p>
                                <p class="text-xs text-slate-400">
                                    <?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User'; ?>
                                </p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors">
                                <span class="material-symbols-outlined text-xs align-middle mr-2">person</span>
                                Profile
                            </a>
                            <a href="audit_logs.php" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors">
                                <span class="material-symbols-outlined text-xs align-middle mr-2">history</span>
                                Activity Log
                            </a>
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors">
                                <span class="material-symbols-outlined text-xs align-middle mr-2">logout</span>
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors">
                                <span class="material-symbols-outlined text-xs align-middle mr-2">login</span>
                                Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content Wrapper with flex grow to push footer down -->
    <div class="flex flex-1 overflow-hidden">