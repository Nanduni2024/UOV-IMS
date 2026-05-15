<?php
include 'config.php';
$page_title = 'Reset Password';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email']; // From query param in production
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_pass, $email);
    $stmt->execute();
    header("Location: login.php");
}
?>

<main class="flex min-h-screen items-center justify-center">
    <form method="POST">
        <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Update Password</button>
    </form>
    <!-- Add original HTML -->
</main>
<?php include 'footer.php'; ?>