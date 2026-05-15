<?php
include 'config.php';
$page_title = 'Password Recovery';

$error_msg = '';
$success_msg = '';
$step = 'email'; // email, verification, reset

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step'])) {
        $step = $_POST['step'];

        if ($step === 'email') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';

            if (empty($email)) {
                $error_msg = "Please enter your email address.";
                $step = 'email';
            } else {
                // Check if email exists
                $check_stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
                if ($check_stmt) {
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    $check_stmt->store_result();

                    if ($check_stmt->num_rows > 0) {
                        // Generate reset token
                        $reset_token = bin2hex(random_bytes(32));
                        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                        // Store token in database (you may need to add columns to users table)
                        // For now, we'll simulate the process
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_token'] = $reset_token;
                        $_SESSION['reset_expiry'] = $token_expiry;

                        $success_msg = "Verification code sent to your email address. Check your inbox.";
                        $step = 'verification';
                        log_audit(null, 'password_recovery', "Recovery request for email: $email");
                    } else {
                        $error_msg = "Email address not found in our system.";
                        $step = 'email';
                    }
                    $check_stmt->close();
                }
            }
        } elseif ($step === 'verification') {
            $verification_code = isset($_POST['verification_code']) ? trim($_POST['verification_code']) : '';

            if (empty($verification_code)) {
                $error_msg = "Please enter the verification code.";
                $step = 'verification';
            } else {
                // Verify code (simplified - in production use proper token matching)
                $success_msg = "Email verified! You can now reset your password.";
                $step = 'reset';
            }
        } elseif ($step === 'reset') {
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

            if (empty($new_password) || empty($confirm_password)) {
                $error_msg = "Please enter both password fields.";
                $step = 'reset';
            } elseif (strlen($new_password) < 6) {
                $error_msg = "Password must be at least 6 characters long.";
                $step = 'reset';
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "Passwords do not match.";
                $step = 'reset';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ss", $hashed_password, $email);
                    if ($update_stmt->execute()) {
                        $success_msg = "Password reset successfully! Redirecting to login...";
                        log_audit(null, 'password_recovery', "Password reset for email: $email");
                        // Clear session
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_token']);
                        unset($_SESSION['reset_expiry']);
                        header("refresh:2;url=login.php");
                    } else {
                        $error_msg = "Error updating password. Please try again.";
                        $step = 'reset';
                    }
                    $update_stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo $page_title ?? 'Password Recovery - University of Vavuniya IMS'; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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

        /* Animated background elements */
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

        .recovery-wrapper {
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-box {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.2) 0%, rgba(15, 118, 236, 0.1) 100%);
            border: 2px solid rgba(19, 127, 236, 0.3);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .logo-box:hover {
            border-color: rgba(19, 127, 236, 0.6);
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.3) 0%, rgba(15, 118, 236, 0.2) 100%);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(19, 127, 236, 0.1);
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

        .recovery-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.6) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.4),
                0 0 1px rgba(19, 127, 236, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.98);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .recovery-card h2 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .recovery-card > p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 28px;
            font-weight: 500;
        }

        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
            gap: 12px;
        }

        .step {
            flex: 1;
            text-align: center;
        }

        .step-number {
            width: 40px;
            height: 40px;
            margin: 0 auto 8px;
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.2) 0%, rgba(15, 118, 236, 0.1) 100%);
            border: 2px solid rgba(19, 127, 236, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #137fec;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #137fec 0%, #0ea5e9 100%);
            border-color: #137fec;
            color: white;
            box-shadow: 0 8px 20px rgba(19, 127, 236, 0.3);
        }

        .step.completed .step-number {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-color: #22c55e;
            color: white;
        }

        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .step.active .step-label,
        .step.completed .step-label {
            color: #22d3ee;
        }

        /* Alert Styles */
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
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
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

        /* Form Group */
        .form-group {
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .input-icon {
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
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.5) 0%, rgba(30, 41, 59, 0.3) 100%);
            border: 1.5px solid rgba(148, 163, 184, 0.15);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 14px;
            font-family: 'Manrope', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="text"]::placeholder,
        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: #64748b;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.05) 0%, rgba(15, 118, 236, 0.03) 100%);
            border-color: rgba(19, 127, 236, 0.4);
            box-shadow: 
                0 0 0 3px rgba(19, 127, 236, 0.1),
                0 0 15px rgba(19, 127, 236, 0.15);
        }

        input[type="text"]:focus ~ .input-icon,
        input[type="email"]:focus ~ .input-icon,
        input[type="password"]:focus ~ .input-icon {
            color: #137fec;
        }

        /* Auth Buttons */
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
            font-family: 'Manrope', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            text-decoration: none;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            min-height: 48px;
            white-space: nowrap;
        }

        .auth-btn span {
            font-size: 18px !important;
        }

        .auth-btn-primary {
            background: linear-gradient(135deg, #137fec 0%, #0ea5e9 100%);
            color: white;
            border: none;
            box-shadow: 0 12px 28px rgba(19, 127, 236, 0.25);
        }

        .auth-btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s ease;
            z-index: 1;
        }

        .auth-btn-primary:hover {
            box-shadow: 0 18px 40px rgba(19, 127, 236, 0.35);
            transform: translateY(-3px);
        }

        .auth-btn-primary:hover::before {
            left: 100%;
        }

        .auth-btn-primary:active {
            transform: translateY(-1px);
        }

        .auth-btn-secondary {
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.12) 0%, rgba(14, 165, 233, 0.08) 100%);
            color: #22d3ee;
            border: 1.5px solid rgba(34, 211, 238, 0.35);
            box-shadow: 0 8px 20px rgba(34, 211, 238, 0.12);
            position: relative;
        }

        .auth-btn-secondary:hover {
            background: linear-gradient(135deg, rgba(19, 127, 236, 0.2) 0%, rgba(14, 165, 233, 0.15) 100%);
            border-color: rgba(34, 211, 238, 0.5);
            color: #06b6d4;
            box-shadow: 0 14px 32px rgba(34, 211, 238, 0.2);
            transform: translateY(-3px);
        }

        .auth-btn:active {
            transform: translateY(-1px);
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
            transition: all 0.2s ease;
        }

        .auth-link a:hover {
            color: #06b6d4;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: #64748b;
            animation: fadeIn 0.8s ease-out 0.5s both;
        }

        .footer p {
            margin: 0;
        }

        .footer p:last-child {
            margin-top: 4px;
            font-weight: 500;
            color: #475569;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .recovery-card {
                padding: 32px 24px;
            }

            .logo-section h1 {
                font-size: 24px;
            }

            .recovery-card h2 {
                font-size: 20px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"] {
                font-size: 16px;
            }

            .auth-buttons {
                grid-template-columns: 1fr;
                gap: 10px;
                margin: 24px 0;
            }

            .auth-btn {
                padding: 13px 14px;
                font-size: 12px;
                min-height: 46px;
            }

            .auth-btn span {
                font-size: 16px !important;
            }

            .step-indicator {
                margin-bottom: 24px;
            }

            .step-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-orb orb-1"></div>
        <div class="bg-orb orb-2"></div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <div class="recovery-wrapper">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo-box">
                    <span class="material-symbols-outlined logo-icon">account_balance</span>
                </div>
                <h1>University of Vavuniya</h1>
                <p>INVENTORY MANAGEMENT SYSTEM</p>
            </div>

            <!-- Recovery Card -->
            <div class="recovery-card">
                <h2><?php 
                    if ($step === 'email') echo 'Recover Password';
                    elseif ($step === 'verification') echo 'Verify Email';
                    else echo 'Reset Password';
                ?></h2>
                <p><?php 
                    if ($step === 'email') echo 'Enter your email to receive a verification code';
                    elseif ($step === 'verification') echo 'Enter the verification code sent to your email';
                    else echo 'Create your new password';
                ?></p>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?php echo ($step === 'email') ? 'active' : ($step !== 'email' ? 'completed' : ''); ?>">
                        <div class="step-number"><?php echo ($step !== 'email' ? '✓' : '1'); ?></div>
                        <div class="step-label">Email</div>
                    </div>
                    <div class="step <?php echo ($step === 'verification') ? 'active' : ($step === 'reset' ? 'completed' : ''); ?>">
                        <div class="step-number"><?php echo ($step === 'reset' ? '✓' : '2'); ?></div>
                        <div class="step-label">Verify</div>
                    </div>
                    <div class="step <?php echo ($step === 'reset') ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">Reset</div>
                    </div>
                </div>

                <!-- Error Alert -->
                <?php if ($error_msg): ?>
                    <div class="alert alert-error">
                        <span class="material-symbols-outlined alert-icon">error</span>
                        <div class="alert-text"><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Success Alert -->
                <?php if ($success_msg): ?>
                    <div class="alert alert-success">
                        <span class="material-symbols-outlined alert-icon">check_circle</span>
                        <div class="alert-text"><?php echo htmlspecialchars($success_msg); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Recovery Form -->
                <form method="POST">
                    <input type="hidden" name="step" value="<?php echo htmlspecialchars($step); ?>">

                    <?php if ($step === 'email'): ?>
                        <!-- Email Input -->
                        <div class="form-group">
                            <label for="email">University Email</label>
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    id="email"
                                    name="email" 
                                    placeholder="your.email@university.edu" 
                                    required 
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <span class="material-symbols-outlined input-icon">mail</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="auth-buttons">
                            <button type="submit" class="auth-btn auth-btn-primary">
                                <span class="material-symbols-outlined">send</span>
                                Send Code
                            </button>
                            <a href="login.php" class="auth-btn auth-btn-secondary">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back to Login
                            </a>
                        </div>

                    <?php elseif ($step === 'verification'): ?>
                        <!-- Verification Code Input -->
                        <div class="form-group">
                            <label for="verification_code">Verification Code</label>
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="verification_code"
                                    name="verification_code" 
                                    placeholder="Enter 6-digit code" 
                                    required 
                                    maxlength="6">
                                <span class="material-symbols-outlined input-icon">verified_user</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="auth-buttons">
                            <button type="submit" class="auth-btn auth-btn-primary">
                                <span class="material-symbols-outlined">check</span>
                                Verify Code
                            </button>
                            <a href="recovery_password.php" class="auth-btn auth-btn-secondary">
                                <span class="material-symbols-outlined">restart_alt</span>
                                Start Over
                            </a>
                        </div>

                    <?php elseif ($step === 'reset'): ?>
                        <!-- New Password Input -->
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="new_password"
                                    name="new_password" 
                                    placeholder="••••••••" 
                                    required 
                                    minlength="6">
                                <span class="material-symbols-outlined input-icon">lock</span>
                            </div>
                        </div>

                        <!-- Confirm Password Input -->
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="confirm_password"
                                    name="confirm_password" 
                                    placeholder="••••••••" 
                                    required 
                                    minlength="6">
                                <span class="material-symbols-outlined input-icon">lock_check</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="auth-buttons">
                            <button type="submit" class="auth-btn auth-btn-primary">
                                <span class="material-symbols-outlined">save</span>
                                Reset Password
                            </button>
                            <a href="login.php" class="auth-btn auth-btn-secondary">
                                <span class="material-symbols-outlined">login</span>
                                Sign In
                            </a>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Auth Link -->
                <div class="auth-link">
                    Remember your password? <a href="login.php">Sign In Here</a>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>© 2024 University of Vavuniya. All Rights Reserved.</p>
                <p>Inventory Management System v2.5.0</p>
            </div>
        </div>
    </div>
</body>
</html>