<?php
include 'config.php';
$page_title = 'Sign Up';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_msg = "All required fields must be filled.";
    } elseif (!in_array($role, ['user', 'admin', 'clerk'])) {
        $error_msg = "Invalid role selected.";
    } elseif (strlen($username) < 3) {
        $error_msg = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error_msg = "Email address already registered.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $department);
                    if ($stmt->execute()) {
                        $success_msg = "Account created successfully! Redirecting to login...";
                        // log_audit(null, 'signup', "New user registered: $email");  // uncomment if function exists
                        header("refresh:2;url=login.php");
                        exit;
                    } else {
                        $error_msg = "Error creating account: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Database error: " . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $error_msg = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title ?? 'Sign Up - University of Vavuniya IMS') ?></title>
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
                        "surface": "#1a2332",
                        "border-slate": "#233648",
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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 50%, #0f1419 100%);
            min-height: 100vh;
            font-family: 'Manrope', sans-serif;
        }

        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #137fec 0%, #0ea5e9 100%);
            top: -50px;
            right: -50px;
            animation: float 6s ease-in-out infinite;
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            bottom: -50px;
            left: -50px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 30px); }
        }

        .container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .signup-wrapper {
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-box {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(19,127,236,0.2) 0%, rgba(15,118,236,0.1) 100%);
            border: 2px solid rgba(19,127,236,0.3);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .logo-box:hover {
            border-color: rgba(19,127,236,0.6);
            background: linear-gradient(135deg, rgba(19,127,236,0.3) 0%, rgba(15,118,236,0.2) 100%);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(19,127,236,0.1);
        }

        .logo-icon {
            font-size: 40px !important;
            color: #137fec;
        }

        .logo-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .logo-section p {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .signup-card {
            background: linear-gradient(135deg, rgba(30,41,59,0.8) 0%, rgba(15,23,42,0.6) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 
                0 20px 60px rgba(0,0,0,0.4),
                0 0 1px rgba(19,127,236,0.1),
                inset 0 1px 0 rgba(255,255,255,0.05);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to   { opacity: 1; transform: scale(1); }
        }

        .signup-card h2 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .signup-card > p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 28px;
            font-weight: 500;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            animation: slideInDown 0.4s ease-out;
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239,68,68,0.1) 0%, rgba(220,38,38,0.05) 100%);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34,197,94,0.1) 0%, rgba(22,163,74,0.05) 100%);
            border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
        }

        .alert-icon {
            flex-shrink: 0;
            font-size: 18px !important;
            margin-top: 2px;
        }

        .alert-text {
            font-size: 13px;
            line-height: 1.5;
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.5s; }
        .form-group:nth-child(4) { animation-delay: 0.6s; }
        .form-group:nth-child(5) { animation-delay: 0.7s; }
        .form-group:nth-child(6) { animation-delay: 0.8s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon, .select-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 18px !important;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: linear-gradient(135deg, rgba(15,23,42,0.5) 0%, rgba(30,41,59,0.3) 100%);
            border: 1.5px solid rgba(148,163,184,0.15);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        input::placeholder {
            color: #64748b;
        }

        input:focus {
            background: linear-gradient(135deg, rgba(19,127,236,0.05) 0%, rgba(15,118,236,0.03) 100%);
            border-color: rgba(19,127,236,0.4);
            box-shadow: 0 0 0 3px rgba(19,127,236,0.1), 0 0 15px rgba(19,127,236,0.15);
        }

        input:focus ~ .input-icon {
            color: #137fec;
        }

        .select-wrapper {
            position: relative;
        }

        select {
            width: 100%;
            padding: 12px 14px 12px 42px;
            padding-right: 38px;
            background: linear-gradient(135deg, rgba(15,23,42,0.5) 0%, rgba(30,41,59,0.3) 100%);
            border: 1.5px solid rgba(148,163,184,0.15);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        select:focus {
            background: linear-gradient(135deg, rgba(19,127,236,0.05) 0%, rgba(15,118,236,0.03) 100%);
            border-color: rgba(19,127,236,0.4);
            box-shadow: 0 0 0 3px rgba(19,127,236,0.1), 0 0 15px rgba(19,127,236,0.15);
        }

        select:focus + .select-icon {
            color: #137fec;
        }

        select option {
            background: #1a2332;
            color: #f1f5f9;
            padding: 10px;
        }

        select option:hover {
            background: #137fec;
        }

        .auth-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin: 32px 0;
        }

        .auth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            min-height: 48px;
        }

        .auth-btn span {
            font-size: 18px !important;
        }

        .auth-btn-primary {
            background: linear-gradient(135deg, #137fec 0%, #0ea5e9 100%);
            color: white;
            box-shadow: 0 12px 28px rgba(19,127,236,0.25);
        }

        .auth-btn-primary:hover {
            box-shadow: 0 18px 40px rgba(19,127,236,0.35);
            transform: translateY(-3px);
        }

        .auth-btn-secondary {
            background: linear-gradient(135deg, rgba(19,127,236,0.12) 0%, rgba(14,165,233,0.08) 100%);
            color: #22d3ee;
            border: 1.5px solid rgba(34,211,238,0.35);
        }

        .auth-btn-secondary:hover {
            background: linear-gradient(135deg, rgba(19,127,236,0.2) 0%, rgba(14,165,233,0.15) 100%);
            border-color: rgba(34,211,238,0.5);
            color: #06b6d4;
            transform: translateY(-3px);
        }

        .auth-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #cbd5e1;
        }

        .auth-link a {
            color: #22d3ee;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-link a:hover {
            color: #06b6d4;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: #64748b;
        }

        .footer p:last-child {
            margin-top: 4px;
            font-weight: 500;
            color: #475569;
        }

        @media (max-width: 480px) {
            .signup-card { padding: 32px 24px; }
            .auth-buttons { grid-template-columns: 1fr; gap: 10px; }
        }
    </style>
</head>
<body>

    <div class="animated-bg">
        <div class="bg-orb orb-1"></div>
        <div class="bg-orb orb-2"></div>
    </div>

    <div class="container">
        <div class="signup-wrapper">

            <div class="logo-section">
                <div class="logo-box">
                    <span class="material-symbols-outlined logo-icon">account_balance</span>
                </div>
                <h1>University of Vavuniya</h1>
                <p>INVENTORY MANAGEMENT SYSTEM</p>
            </div>

            <div class="signup-card">
                <h2>Create Account</h2>
                <p>Join us to manage your inventory</p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-error">
                        <span class="material-symbols-outlined alert-icon">error</span>
                        <div class="alert-text"><?= htmlspecialchars($error_msg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success">
                        <span class="material-symbols-outlined alert-icon">check_circle</span>
                        <div class="alert-text"><?= htmlspecialchars($success_msg) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">

                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" placeholder="John Doe" required
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            <span class="material-symbols-outlined input-icon">person</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="your.email@example.com" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <span class="material-symbols-outlined input-icon">mail</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
                            <span class="material-symbols-outlined input-icon">lock</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required minlength="6">
                            <span class="material-symbols-outlined input-icon">lock_check</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department">Department (Optional)</label>
                        <div class="input-wrapper">
                            <input type="text" id="department" name="department" placeholder="Your Department"
                                   value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                            <span class="material-symbols-outlined input-icon">domain</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Select Role</label>
                        <div class="select-wrapper">
                            <select id="role" name="role" required>
                                <option value="" disabled selected>Choose your role...</option>
                                <option value="user"  <?= (($_POST['role'] ?? '') === 'user')  ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <span class="material-symbols-outlined select-icon">how_to_reg</span>
                        </div>
                    </div>

                    <div class="auth-buttons">
                        <button type="submit" class="auth-btn auth-btn-primary">
                            <span class="material-symbols-outlined">person_add</span>
                            Create Account
                        </button>
                        <a href="login.php" class="auth-btn auth-btn-secondary">
                            <span class="material-symbols-outlined">login</span>
                            Sign In
                        </a>
                    </div>

                </form>

                <div class="auth-link">
                    Already have an account? <a href="login.php">Sign In Here</a>
                </div>
            </div>

            <div class="footer">
                <p>© 2024 University of Vavuniya. All Rights Reserved.</p>
                <p>Inventory Management System v2.5.0</p>
            </div>

        </div>
    </div>

</body>
</html>